<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsTranslation;
use app\common\model\Currency;
use app\common\model\SystemConfig;
use app\common\helper\UrlHelper;

/**
 * 商品数据导出命令（后台异步执行）
 */
class GoodsExport extends Command
{
    protected function configure()
    {
        $this->setName('goods:export')
            ->addArgument('params_file', Argument::REQUIRED, 'Path to params JSON file')
            ->setDescription('Export goods data to ZIP file');
    }

    protected function execute(Input $input, Output $output)
    {
        $paramsFile = $input->getArgument('params_file');

        if (!file_exists($paramsFile)) {
            $output->writeln('<error>Params file not found: ' . $paramsFile . '</error>');
            return;
        }

        $params = json_decode(file_get_contents($paramsFile), true);
        @unlink($paramsFile); // Clean up params file

        if (!$params || empty($params['task_id'])) {
            $output->writeln('<error>Invalid params</error>');
            return;
        }

        $taskId = $params['task_id'];
        $ids = $params['ids'];
        $locale = $params['locale'];
        $currencyCode = $params['currency_code'];
        $rate = (float) $params['rate'];

        $output->writeln("Starting export task: {$taskId}");
        $output->writeln("Goods count: " . count($ids) . ", Locale: {$locale}, Currency: {$currencyCode}");

        $this->processExport($taskId, $ids, $locale, $currencyCode, $rate, $output);

        $output->writeln('<info>Export completed</info>');
    }

    protected function processExport(string $taskId, array $ids, string $locale, string $currencyCode, float $rate, Output $output): void
    {
        $total = count($ids);
        $progressFile = runtime_path() . 'export' . DIRECTORY_SEPARATOR . $taskId . '_progress.json';

        // Create temp directory
        $tempDir = runtime_path() . 'export' . DIRECTORY_SEPARATOR . $taskId;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $siteUrl = SystemConfig::getConfig('site_url', 'https://www.turnsysg.com');

        // Get currency decimals
        $currencyModel = Currency::where('code', $currencyCode)->find();
        $decimals = $currencyModel ? (int) $currencyModel->decimals : 2;

        foreach ($ids as $index => $goodsId) {
            $goodsId = (int) $goodsId;

            // Update progress
            file_put_contents($progressFile, json_encode([
                'status' => 'processing',
                'total' => $total,
                'current' => $index,
                'message' => "Processing goods {$goodsId} (" . ($index + 1) . "/{$total})",
                'file' => '',
            ]));


            $output->writeln("  [{$index}/{$total}] Processing goods #{$goodsId}");

            // Get goods
            $goods = GoodsModel::find($goodsId);
            if (!$goods) {
                $output->writeln("    Skipped: goods not found");
                continue;
            }

            // Get translation
            $translation = GoodsTranslation::where('goods_id', $goodsId)
                ->where('locale', $locale)
                ->find();

            // Fallback to en-us if not found
            if (!$translation) {
                $translation = GoodsTranslation::where('goods_id', $goodsId)
                    ->where('locale', 'en-us')
                    ->find();
            }

            if (!$translation) {
                $output->writeln("    Skipped: no translation found");
                continue;
            }

            // Create goods folder
            $goodsDir = $tempDir . DIRECTORY_SEPARATOR . $goodsId;
            if (!is_dir($goodsDir)) {
                mkdir($goodsDir, 0755, true);
            }

            // 标题.txt
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '标题.txt', $translation->title ?: '');

            // 描述.txt（去除 HTML 标签）
            $desc = $translation->description ?: '';
            $desc = preg_replace('/<br\s*\/?>/i', "\n", $desc);
            $desc = preg_replace('/<\/p>/i', "\n", $desc);
            $desc = strip_tags($desc);
            $desc = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');
            $desc = preg_replace("/\n{3,}/", "\n\n", trim($desc));
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '描述.txt', $desc);

            // 价格.txt（原始美元价格）
            $price = (float) $goods->price;
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '价格.txt', number_format($price, 2, '.', ''));

            // 链接.txt
            $productUrl = rtrim($siteUrl, '/') . '/product.html?id=' . $goodsId;
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '链接.txt', $productUrl);

            // Images
            $images = $goods->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?: [];
            }

            $imageUrls = [];
            foreach ($images as $img) {
                $fullUrl = UrlHelper::getFullUrl($img);
                $imageUrls[] = $fullUrl;
            }

            // 图片.txt
            file_put_contents($goodsDir . DIRECTORY_SEPARATOR . '图片.txt', implode("\n", $imageUrls));

            // 下载前5张图片文件
            foreach (array_slice($imageUrls, 0, 5) as $imgIndex => $imageUrl) {
                $num = $imgIndex + 1;
                try {
                    $ctx = stream_context_create([
                        'http' => ['timeout' => 15],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                    ]);
                    $imageContent = @file_get_contents($imageUrl, false, $ctx);
                    if ($imageContent === false) {
                        $output->writeln("    Image #{$num} download failed");
                        continue;
                    }

                    $ext = 'jpg';
                    $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '');
                    if (isset($pathInfo['extension'])) {
                        $rawExt = strtolower($pathInfo['extension']);
                        if (in_array($rawExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $ext = $rawExt === 'jpeg' ? 'jpg' : $rawExt;
                        }
                    }

                    file_put_contents($goodsDir . DIRECTORY_SEPARATOR . $num . '.' . $ext, $imageContent);
                } catch (\Throwable $e) {
                    $output->writeln("    Image #{$num} error: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Create ZIP
        $output->writeln("Creating ZIP file...");
        $zipPath = runtime_path() . 'export' . DIRECTORY_SEPARATOR . $taskId . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            file_put_contents($progressFile, json_encode([
                'status' => 'failed',
                'total' => $total,
                'current' => $total,
                'message' => 'Failed to create ZIP file',
                'file' => '',
            ]));

            return;
        }

        $this->addDirToZip($zip, $tempDir, '');
        $zip->close();

        // Clean up temp directory
        $this->removeDir($tempDir);

        // Update progress to completed
        file_put_contents($progressFile, json_encode([
            'status' => 'completed',
            'total' => $total,
            'current' => $total,
            'message' => 'Export completed',
            'file' => $zipPath,
        ]));

    }

    protected function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $zipPath = $prefix ? $prefix . '/' . $file : $file;
            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirToZip($zip, $filePath, $zipPath);
            } else {
                $zip->addFile($filePath, $zipPath);
            }
        }
    }

    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->removeDir($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
}
