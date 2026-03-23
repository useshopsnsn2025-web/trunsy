<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * eBay 数据采集服务
 */
class EbayCrawlerService
{
    /**
     * User-Agent 列表
     */
    protected $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
    ];

    /**
     * 代理地址（支持 http/socks5）
     */
    protected $proxy = '';

    /**
     * ScraperAPI key 轮换器
     */
    protected $scraperApiRotator = null;

    public function __construct()
    {
        // 从系统配置读取代理设置
        try {
            $configs = \think\facade\Db::table('system_configs')
                ->whereIn('key', ['crawl_proxy'])
                ->column('value', 'key');
            $this->proxy = $configs['crawl_proxy'] ?? '';
        } catch (\Exception $e) {
            // 忽略
        }

        // 初始化 ScraperAPI key 轮换器（冷却1小时）
        $this->scraperApiRotator = new ApiKeyRotator('scraper_api_key', 3600);
    }

    /**
     * eBay Condition 映射到平台 condition
     */
    protected $conditionMap = [
        'new'               => 1,
        'brand new'         => 1,
        'new with tags'     => 1,
        'new with box'      => 1,
        'new without tags'  => 1,
        'new without box'   => 1,
        'new other'         => 1,
        'open box'          => 2,
        'like new'          => 2,
        'excellent'         => 2,
        'certified refurbished'   => 2,
        'excellent - refurbished' => 2,
        'very good - refurbished' => 2,
        'refurbished'       => 2,
        'seller refurbished'=> 2,
        'pre-owned'         => 3,
        'used'              => 3,
        'good'              => 3,
        'good - refurbished'=> 3,
        'acceptable'        => 4,
        'for parts or not working' => 5,
        'for parts'         => 5,
        'not working'       => 5,
    ];

    /**
     * 采集列表页
     */
    public function crawlList(string $url, int $pages = 1): array
    {
        $allItems = [];
        $errors = [];

        for ($page = 1; $page <= $pages; $page++) {
            $pageUrl = $this->buildPageUrl($url, $page);

            try {
                $this->log('Crawl fetching: ' . $pageUrl);
                $html = $this->fetchPage($pageUrl);
                $this->log('Crawl HTML length: ' . strlen($html));
                $items = $this->parseListHtml($html);
                $this->log('Crawl parsed items: ' . count($items));
                $allItems = array_merge($allItems, $items);
            } catch (\Exception $e) {
                $errors[] = "Page {$page}: " . $e->getMessage();
            }

            // 每页请求间隔 1-3 秒
            if ($page < $pages) {
                usleep(mt_rand(1000000, 3000000));
            }
        }

        return [
            'total' => count($allItems),
            'items' => $allItems,
            'errors' => $errors,
        ];
    }

    /**
     * 采集商品详情页
     */
    public function crawlDetail(array $items): array
    {
        $results = [];
        $errors = [];

        foreach ($items as $item) {
            try {
                $html = $this->fetchPage($item['listing_url']);
                $detail = $this->parseDetailHtml($html);
                $results[] = array_merge($item, $detail);
            } catch (\Exception $e) {
                $errors[] = [
                    'ebay_item_id' => $item['ebay_item_id'] ?? '',
                    'reason' => $e->getMessage(),
                ];
                $results[] = $item; // 保留原始数据
            }

            usleep(mt_rand(1000000, 3000000));
        }

        return [
            'items' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * 构建分页 URL
     */
    protected function buildPageUrl(string $url, int $page): string
    {
        $parsed = parse_url($url);
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $path = $parsed['path'] ?? '';
        $isBrowsePage = (strpos($path, '/b/') !== false);

        if ($isBrowsePage) {
            // Browse 页面使用 _pgn 分页参数，保持原始路径
            $query['_pgn'] = $page;
            $query['rt'] = 'nc';
        } else {
            // 搜索页面
            $query['_pgn'] = $page;
            if (!isset($query['_ipg'])) {
                $query['_ipg'] = 60;
            }
            // 强制美国优先
            $query['LH_PrefLoc'] = '1';
        }

        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'www.ebay.com') . $path;
        return $baseUrl . '?' . http_build_query($query);
    }

    /**
     * 获取页面 HTML
     */
    protected function fetchPage(string $url): string
    {
        // 如果是 browse 页面 /b/，转换为搜索页面
        $fetchUrl = $this->convertBrowseUrl($url);

        // 优先使用 ScraperAPI（能绕过 JS challenge），支持多 key 轮换
        if ($this->scraperApiRotator && $this->scraperApiRotator->hasKeys()) {
            $apiKey = $this->scraperApiRotator->getKey();
            if ($apiKey) {
                try {
                    return $this->fetchViaScraperApi($fetchUrl, $apiKey);
                } catch (\RuntimeException $e) {
                    // 配额耗尽或 key 无效，标记失败并尝试下一个
                    if (strpos($e->getMessage(), 'quota exceeded') !== false
                        || strpos($e->getMessage(), 'invalid API key') !== false
                        || strpos($e->getMessage(), 'HTTP 403') !== false
                        || strpos($e->getMessage(), 'HTTP 429') !== false) {
                        $this->scraperApiRotator->markFailed($apiKey);
                        $this->log('ScraperAPI key failed, trying next key...');
                        // 尝试获取下一个 key
                        $nextKey = $this->scraperApiRotator->getKey();
                        if ($nextKey && $nextKey !== $apiKey) {
                            return $this->fetchViaScraperApi($fetchUrl, $nextKey);
                        }
                    }
                    throw $e;
                }
            }
        }

        // 直接请求
        return $this->fetchDirect($fetchUrl);
    }

    /**
     * 转换 browse URL 为搜索 URL
     */
    protected function convertBrowseUrl(string $url): string
    {
        if (preg_match('/\/b\/([^\/]+)\/(\d+)\//', $url, $m)) {
            $parsed = parse_url($url);
            $query = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }
            $query['_sacat'] = $m[2];
            $productName = str_replace('-', ' ', $m[1]);
            $query['_nkw'] = $productName;
            $url = 'https://www.ebay.com/sch/i.html?' . http_build_query($query);
            $this->log('Browse URL converted to search: ' . $url);
        }
        return $url;
    }

    /**
     * 通过 ScraperAPI 获取页面（推荐，处理 JS challenge）
     */
    protected function fetchViaScraperApi(string $url, string $apiKey = ''): string
    {
        $apiUrl = 'https://api.scraperapi.com?api_key=' . $apiKey
            . '&url=' . urlencode($url)
            . '&country_code=us';

        $this->log('Fetching via ScraperAPI: ' . $url);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '',
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($html === false) {
            throw new \RuntimeException("ScraperAPI cURL error: {$error}");
        }

        $this->log("ScraperAPI result: HTTP {$httpCode}, HTML len: " . strlen($html));

        if ($httpCode === 403) {
            throw new \RuntimeException('ScraperAPI quota exceeded or invalid API key (HTTP 403)');
        }

        if ($httpCode === 429) {
            throw new \RuntimeException('ScraperAPI rate limit exceeded (HTTP 429)');
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("ScraperAPI HTTP {$httpCode}");
        }

        return $html;
    }

    /**
     * 直接请求获取页面
     */
    protected function fetchDirect(string $url): string
    {
        $userAgent = $this->getRandomUserAgent();
        $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ebay_crawl_' . md5($userAgent) . '.txt';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Cache-Control: max-age=0',
                'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'Sec-Ch-Ua-Mobile: ?0',
                'Sec-Ch-Ua-Platform: "Windows"',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_ENCODING       => '',
            CURLOPT_REFERER        => 'https://www.ebay.com/',
        ]);

        // 配置代理
        if (!empty($this->proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if (strpos($this->proxy, 'socks5') === 0) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
            $this->log('Using proxy: ' . $this->proxy);
        }

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);

        $this->log("Direct fetch: HTTP {$httpCode}, HTML len: " . strlen($html ?: ''));

        if ($html === false) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        // 检测 JS challenge
        if (strpos($finalUrl, 'splashui/challenge') !== false || strpos($html, 'splashui/challenge') !== false) {
            $this->log('eBay JS challenge detected');
            throw new \RuntimeException('eBay anti-bot verification triggered. Solution: configure scraper_api_key in System Settings (free at scraperapi.com, 1000 req/month).');
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP {$httpCode} response");
        }

        return $html;
    }

    /**
     * 检测页面实际使用的货币
     */
    protected function detectPageCurrency(string $html): string
    {
        // 通过页面中的货币过滤器或标记检测实际货币
        $currencyPatterns = [
            'DKK' => '/Under DKK|DKK\s[\d,]+/',
            'SEK' => '/Under SEK|SEK\s[\d,]+/',
            'NOK' => '/Under NOK|NOK\s[\d,]+/',
            'GBP' => '/Under £|£[\d,]+/',
            'EUR' => '/Under €|€[\d,]+/',
            'CAD' => '/Under C \$|C \$[\d,]+/',
            'AUD' => '/Under AU \$|AU \$[\d,]+/',
        ];

        foreach ($currencyPatterns as $code => $pattern) {
            if (preg_match($pattern, $html)) {
                return $code;
            }
        }

        return 'USD';
    }

    /**
     * 解析列表页 HTML
     */
    protected function parseListHtml(string $html): array
    {
        $items = [];

        // 检测页面实际货币（eBay 根据 IP 自动转换货币）
        $pageCurrency = $this->detectPageCurrency($html);

        // 抑制 HTML 解析警告
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // eBay 新版页面使用 s-card 类（旧版使用 s-item）
        $nodes = $xpath->query('//li[contains(@class, "s-card")]');

        if ($nodes === false || $nodes->length === 0) {
            // 降级尝试旧版 s-item
            $nodes = $xpath->query('//div[contains(@class, "s-item")]|//li[contains(@class, "s-item")]');
        }

        if ($nodes === false || $nodes->length === 0) {
            return $items;
        }

        foreach ($nodes as $node) {
            try {
                $item = $this->parseItemFromDom($xpath, $node);
                if ($item && !empty($item['title']) && $item['title'] !== 'Shop on eBay') {
                    // 覆盖货币为页面检测到的实际货币
                    $item['currency'] = $pageCurrency;
                    $items[] = $item;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $items;
    }

    /**
     * 从 DOM 节点解析单个商品
     */
    protected function parseItemFromDom(\DOMXPath $xpath, \DOMNode $node): ?array
    {
        // 获取 listing ID
        $ebayItemId = '';
        if ($node instanceof \DOMElement) {
            $ebayItemId = $node->getAttribute('data-listingid');
        }

        // 标题 - 新版 s-card__title 或旧版 s-item__title
        $titleNodes = $xpath->query('.//div[contains(@class, "s-card__title")]//span[contains(@class, "su-styled-text")]|.//div[contains(@class, "s-item__title")]//span[@role="heading"]', $node);
        $title = '';
        if ($titleNodes && $titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
        }

        if (empty($title) || $title === 'Shop on eBay') {
            return null;
        }

        // 价格 - 新版 s-card__price 或旧版 s-item__price
        $priceNodes = $xpath->query('.//span[contains(@class, "s-card__price")]|.//span[contains(@class, "s-item__price")]', $node);
        $priceText = '';
        if ($priceNodes && $priceNodes->length > 0) {
            $priceText = trim($priceNodes->item(0)->textContent);
        }
        $priceData = $this->normalizePrice($priceText);

        // 图片 - 新版 s-card__image 或旧版 s-item__image-img
        $imgNodes = $xpath->query('.//img[contains(@class, "s-card__image")]|.//img[contains(@class, "s-item__image-img")]', $node);
        $imageUrl = '';
        if ($imgNodes && $imgNodes->length > 0) {
            $img = $imgNodes->item(0);
            $imageUrl = $img->getAttribute('src');
            // 尝试 data-defer-load (eBay 延迟加载)
            $deferSrc = $img->getAttribute('data-defer-load');
            if ($deferSrc && strpos($deferSrc, 'ebayimg.com') !== false) {
                $imageUrl = $deferSrc;
            }
            // 替换缩略图为大图
            $imageUrl = preg_replace('/\/s-l\d+\./', '/s-l500.', $imageUrl);
        }

        // 商品链接
        $linkNodes = $xpath->query('.//a[contains(@class, "s-card__link")]|.//a[contains(@class, "s-item__link")]', $node);
        $listingUrl = '';
        if ($linkNodes && $linkNodes->length > 0) {
            $listingUrl = $linkNodes->item(0)->getAttribute('href');
            // 从 URL 提取 Item ID（如果 data-listingid 为空）
            if (empty($ebayItemId) && preg_match('/\/itm\/(\d+)/', $listingUrl, $m)) {
                $ebayItemId = $m[1];
            }
            // 清理 URL 保留到 item ID
            if (preg_match('/(https?:\/\/[^?]+)/', $listingUrl, $m)) {
                $listingUrl = $m[1];
            }
        }

        // 成色/Condition - 新版在 subtitle 中
        $conditionText = '';
        $subtitleNodes = $xpath->query('.//div[contains(@class, "s-card__subtitle")]//span[contains(@class, "su-styled-text")]|.//span[contains(@class, "SECONDARY_INFO")]', $node);
        if ($subtitleNodes && $subtitleNodes->length > 0) {
            // 遍历所有 subtitle 找成色信息
            foreach ($subtitleNodes as $subNode) {
                $text = trim($subNode->textContent);
                $lower = strtolower($text);
                // 检查是否包含成色关键词
                if (preg_match('/(new|used|pre-owned|refurbished|open box|like new|good|acceptable|for parts|brand new)/i', $lower)) {
                    $conditionText = $text;
                    break;
                }
            }
        }

        // 运费信息
        $shipping = '';
        $shipNodes = $xpath->query('.//span[contains(@class, "s-card__shipping")]|.//span[contains(@class, "s-item__shipping")]|.//span[contains(@class, "s-item__freeXDays")]', $node);
        if ($shipNodes && $shipNodes->length > 0) {
            $shipping = trim($shipNodes->item(0)->textContent);
        }
        // 也在 attribute-row 中查找 shipping
        if (empty($shipping)) {
            $attrNodes = $xpath->query('.//div[contains(@class, "s-card__attribute-row")]//span[contains(@class, "su-styled-text")]', $node);
            if ($attrNodes) {
                foreach ($attrNodes as $attrNode) {
                    $text = trim($attrNode->textContent);
                    if (stripos($text, 'shipping') !== false || stripos($text, 'Free') !== false) {
                        $shipping = $text;
                        break;
                    }
                }
            }
        }

        // 卖家
        $seller = '';
        $sellerNodes = $xpath->query('.//span[contains(@class, "s-card__seller")]|.//span[contains(@class, "s-item__seller-info")]', $node);
        if ($sellerNodes && $sellerNodes->length > 0) {
            $seller = trim($sellerNodes->item(0)->textContent);
        }

        // 位置
        $location = '';
        $locNodes = $xpath->query('.//span[contains(@class, "s-card__location")]|.//span[contains(@class, "s-item__location")]|.//span[contains(@class, "s-item__itemLocation")]', $node);
        if ($locNodes && $locNodes->length > 0) {
            $location = trim($locNodes->item(0)->textContent);
            $location = preg_replace('/^from\s+/i', '', $location);
        }

        // 已售数量
        $soldCount = 0;
        $soldNodes = $xpath->query('.//span[contains(@class, "s-card__sold")]|.//span[contains(@class, "s-item__quantitySold")]|.//span[contains(@class, "s-item__hotness")]', $node);
        if ($soldNodes && $soldNodes->length > 0) {
            $soldText = $soldNodes->item(0)->textContent;
            if (preg_match('/([\d,]+)\s*sold/i', $soldText, $m)) {
                $soldCount = (int)str_replace(',', '', $m[1]);
            }
        }

        return [
            'ebay_item_id'    => $ebayItemId,
            'title'           => $title,
            'price'           => $priceData['price'],
            'original_price'  => $priceData['original_price'],
            'price_range'     => $priceData['is_range'],
            'currency'        => $priceData['currency'],
            'condition_text'  => $conditionText,
            'condition_mapped'=> $this->mapCondition($conditionText),
            'image_url'       => $imageUrl,
            'image_urls'      => $imageUrl ? [$imageUrl] : [],
            'listing_url'     => $listingUrl,
            'seller'          => $seller,
            'location'        => $location,
            'shipping'        => $shipping,
            'sold_count'      => $soldCount,
            'parsed_attrs'    => $this->parseAttributes($title),
        ];
    }

    /**
     * 解析详情页 HTML
     */
    protected function parseDetailHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $result = [];

        // 提取更多图片
        $imageUrls = [];
        $imgNodes = $xpath->query('//div[contains(@class, "ux-image-carousel")]//img|//div[@id="mainImgHldr"]//img');
        if ($imgNodes) {
            foreach ($imgNodes as $img) {
                $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
                if ($src && strpos($src, 'ebayimg.com') !== false) {
                    $src = preg_replace('/\/s-l\d+\./', '/s-l500.', $src);
                    if (!in_array($src, $imageUrls)) {
                        $imageUrls[] = $src;
                    }
                }
            }
        }

        // 也尝试从 JSON-LD 提取图片
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        if ($scriptNodes) {
            foreach ($scriptNodes as $script) {
                $json = json_decode($script->textContent, true);
                if ($json && isset($json['image'])) {
                    $imgs = is_array($json['image']) ? $json['image'] : [$json['image']];
                    foreach ($imgs as $img) {
                        if (is_string($img) && !in_array($img, $imageUrls)) {
                            $imageUrls[] = $img;
                        }
                    }
                }
            }
        }

        if (!empty($imageUrls)) {
            $result['image_urls'] = $imageUrls;
            $result['image_url'] = $imageUrls[0];
        }

        // 提取商品规格
        $specs = [];
        $specRows = $xpath->query('//div[contains(@class, "ux-layout-section-evo")]//div[contains(@class, "ux-labels-values")]');
        if ($specRows) {
            foreach ($specRows as $row) {
                $labelNode = $xpath->query('.//div[contains(@class, "ux-labels-values__labels")]', $row);
                $valueNode = $xpath->query('.//div[contains(@class, "ux-labels-values__values")]', $row);
                if ($labelNode && $labelNode->length > 0 && $valueNode && $valueNode->length > 0) {
                    $label = trim($labelNode->item(0)->textContent);
                    $value = trim($valueNode->item(0)->textContent);
                    if ($label && $value) {
                        $specs[$label] = $value;
                    }
                }
            }
        }

        if (!empty($specs)) {
            $result['detail_specs'] = $specs;
        }

        return $result;
    }

    /**
     * 从标题解析属性
     */
    public function parseAttributes(string $title): array
    {
        $attrs = [
            'brand'   => '',
            'model'   => '',
            'storage' => '',
            'color'   => '',
        ];

        // 品牌识别
        $brands = ['Apple', 'Samsung', 'Google', 'Huawei', 'Xiaomi', 'OnePlus', 'OPPO', 'Vivo', 'Sony', 'LG', 'Motorola', 'Nokia', 'Nothing'];
        foreach ($brands as $brand) {
            if (stripos($title, $brand) !== false) {
                $attrs['brand'] = $brand;
                break;
            }
        }

        // 型号识别 - iPhone
        if (preg_match('/iPhone\s*(SE|\d+)\s*(Pro\s*Max|Pro|Plus|Mini)?/i', $title, $m)) {
            $attrs['model'] = 'iPhone ' . trim($m[1] . ' ' . ($m[2] ?? ''));
        }
        // 型号识别 - Samsung Galaxy
        elseif (preg_match('/Galaxy\s*(S|A|Z|Note|Fold|Flip)\s*(\d+)\s*(\+|Plus|Ultra|FE)?/i', $title, $m)) {
            $attrs['model'] = 'Galaxy ' . trim($m[1] . $m[2] . ' ' . ($m[3] ?? ''));
        }
        // 型号识别 - Google Pixel
        elseif (preg_match('/Pixel\s*(\d+)\s*(a|Pro|XL)?/i', $title, $m)) {
            $attrs['model'] = 'Pixel ' . trim($m[1] . ' ' . ($m[2] ?? ''));
        }

        // 存储容量
        if (preg_match('/(\d+)\s*(GB|TB)/i', $title, $m)) {
            $attrs['storage'] = $m[1] . strtoupper($m[2]);
        }

        // 颜色识别
        $colors = [
            'Black', 'White', 'Blue', 'Red', 'Green', 'Gold', 'Silver', 'Purple',
            'Pink', 'Yellow', 'Orange', 'Gray', 'Grey', 'Graphite', 'Midnight',
            'Starlight', 'Sierra Blue', 'Alpine Green', 'Deep Purple',
            'Space Black', 'Natural Titanium', 'Blue Titanium', 'White Titanium',
            'Black Titanium', 'Desert Titanium', 'Pacific Blue', 'Coral',
        ];
        foreach ($colors as $color) {
            if (stripos($title, $color) !== false) {
                $attrs['color'] = $color;
                break;
            }
        }

        return $attrs;
    }

    /**
     * 映射 eBay condition 到平台 condition 值
     */
    public function mapCondition(string $ebayCondition): int
    {
        $normalized = strtolower(trim($ebayCondition));

        if (isset($this->conditionMap[$normalized])) {
            return $this->conditionMap[$normalized];
        }

        // 模糊匹配
        foreach ($this->conditionMap as $key => $value) {
            if (strpos($normalized, $key) !== false) {
                return $value;
            }
        }

        return 1; // 默认全新
    }

    /**
     * 标准化价格
     */
    public function normalizePrice(string $priceText): array
    {
        $result = [
            'price'          => 0,
            'original_price' => 0,
            'currency'       => 'USD',
            'is_range'       => false,
        ];

        if (empty($priceText)) {
            return $result;
        }

        // 检测货币
        $currencyMap = [
            '$'   => 'USD',
            'US $'=> 'USD',
            '£'   => 'GBP',
            '€'   => 'EUR',
            'C $'  => 'CAD',
            'AU $' => 'AUD',
            'JPY'  => 'JPY',
            '¥'    => 'JPY',
            'DKK'  => 'DKK',
            'SEK'  => 'SEK',
            'NOK'  => 'NOK',
            'CHF'  => 'CHF',
        ];

        foreach ($currencyMap as $symbol => $code) {
            if (strpos($priceText, $symbol) !== false) {
                $result['currency'] = $code;
                break;
            }
        }

        // 提取数字
        preg_match_all('/([\d,]+\.?\d*)/', $priceText, $matches);
        $numbers = array_map(function ($n) {
            return (float) str_replace(',', '', $n);
        }, $matches[1] ?? []);

        if (count($numbers) >= 2) {
            // 价格范围
            $result['price'] = min($numbers[0], $numbers[1]);
            $result['original_price'] = max($numbers[0], $numbers[1]);
            $result['is_range'] = true;
        } elseif (count($numbers) === 1) {
            $result['price'] = $numbers[0];
        }

        return $result;
    }

    /**
     * 随机 User-Agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * 安全日志（兼容 CLI 和 Web 环境）
     */
    protected function log(string $message): void
    {
        try {
            \think\facade\Log::info($message);
        } catch (\Exception $e) {
            // CLI 环境或 Log 不可用时忽略
        }
    }
}
