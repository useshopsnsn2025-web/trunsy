<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\SystemConfig;
use app\common\model\UiTranslation;
use Mpdf\Mpdf;
use think\facade\Log;

/**
 * 收据生成服务
 */
class ReceiptService
{
    /**
     * 收据存储目录
     */
    const RECEIPT_DIR = 'storage/receipts';

    /**
     * 生成订单付款收据（PDF格式）
     * @param array $orderData 订单数据
     * @param string $locale 语言
     * @return string|null 收据文件路径
     */
    public function generatePaymentReceipt(array $orderData, string $locale = 'en-us'): ?string
    {
        try {
            $receiptNo = 'R' . date('Ymd') . '-' . ($orderData['order_no'] ?? $orderData['id']);
            $html = $this->buildReceiptHtml($orderData, $receiptNo, $locale);

            // 确保目录存在
            $dir = root_path() . self::RECEIPT_DIR . '/' . date('Ym');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // 确保临时目录存在
            $tempDir = root_path() . 'runtime/mpdf';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // 根据语言选择字体配置
            // mPDF 内置支持 CJK 字体
            $fontConfig = $this->getFontConfig($locale);

            // 创建 mPDF 实例
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => $fontConfig['default_font'],
                'tempDir' => $tempDir,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            // 写入 HTML 内容
            $mpdf->WriteHTML($html);

            // 保存 PDF 文件
            $filename = $receiptNo . '.pdf';
            $filepath = $dir . '/' . $filename;
            $mpdf->Output($filepath, \Mpdf\Output\Destination::FILE);

            Log::info('Receipt PDF generated', [
                'receipt_no' => $receiptNo,
                'order_no' => $orderData['order_no'] ?? '',
                'filepath' => $filepath,
                'locale' => $locale,
            ]);

            return $filepath;
        } catch (\Exception $e) {
            Log::error('Receipt PDF generation failed', [
                'order_no' => $orderData['order_no'] ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * 根据语言获取字体配置
     */
    protected function getFontConfig(string $locale): array
    {
        // mPDF 会自动处理 CJK 字体
        // 使用 autoLangToFont 功能自动选择合适的字体
        switch ($locale) {
            case 'zh-tw':
            case 'zh-cn':
                return ['default_font' => 'sun-exta']; // 中文
            case 'ja-jp':
                return ['default_font' => 'sun-exta']; // 日文
            default:
                return ['default_font' => 'dejavusans']; // 默认
        }
    }

    /**
     * 构建收据HTML内容
     */
    protected function buildReceiptHtml(array $orderData, string $receiptNo, string $locale): string
    {
        // 获取平台信息
        $platformName = SystemConfig::getConfig('site_name', 'TURNSY');
        $platformUrl = SystemConfig::getConfig('site_url', 'https://www.turnsysg.com');
        $platformLogo = SystemConfig::getConfig('site_logo', '');

        // 订单信息
        $orderNo = $orderData['order_no'] ?? '';
        $orderDate = isset($orderData['created_at']) ? date('F j, Y', strtotime($orderData['created_at'])) : date('F j, Y');
        $currency = $orderData['currency'] ?? 'USD';

        // 格式化金额
        $formatAmount = function($amount) use ($currency) {
            $symbol = $this->getCurrencySymbol($currency);
            if ($currency === 'JPY') {
                return $symbol . number_format((float)$amount, 0);
            }
            return $symbol . number_format((float)$amount, 2);
        };

        // 商品信息
        $goodsTitle = $orderData['goods_title'] ?? $orderData['goods']['title'] ?? 'Product';
        $goodsImage = $orderData['goods_image'] ?? $orderData['goods']['image'] ?? '';
        $quantity = $orderData['quantity'] ?? 1;
        $price = $formatAmount($orderData['price'] ?? $orderData['goods_amount'] ?? 0);
        $shippingFee = $formatAmount($orderData['shipping_fee'] ?? 0);
        $totalAmount = $formatAmount($orderData['total_amount'] ?? 0);

        // 银行卡信息
        $cardSnapshot = $orderData['card_snapshot'] ?? [];
        if (is_string($cardSnapshot)) {
            $cardSnapshot = json_decode($cardSnapshot, true) ?? [];
        }
        $cardLastFour = $cardSnapshot['last_four'] ?? $cardSnapshot['card_last_four'] ?? '****';
        $cardBrand = $cardSnapshot['card_brand'] ?? $cardSnapshot['brand'] ?? '';
        $cardDisplay = trim(($cardBrand ? $cardBrand . ' ' : '') . '**** ' . $cardLastFour);

        // 交易号
        $transactionNo = $orderData['payment_no'] ?? '';

        // 买家信息
        $buyerName = $orderData['buyer_name'] ?? $orderData['buyer']['nickname'] ?? '';
        $buyerEmail = $orderData['buyer_email'] ?? $orderData['buyer']['email'] ?? '';

        // 收货地址
        $address = $orderData['address'] ?? [];
        if (is_string($address)) {
            $address = json_decode($address, true) ?? [];
        }
        $recipientName = $address['name'] ?? $address['recipient_name'] ?? '';
        $addressLine = $address['address'] ?? $address['address_line'] ?? '';
        $city = $address['city'] ?? '';
        $state = $address['state'] ?? $address['province'] ?? '';
        $postalCode = $address['postal_code'] ?? $address['zip'] ?? '';
        $country = $address['country'] ?? '';

        // 多语言标签
        $labels = $this->getLabels($locale);

        return <<<HTML
<!DOCTYPE html>
<html lang="{$locale}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$labels['receipt']} - {$receiptNo}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .logo {
            max-height: 50px;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .receipt-no {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-block {
            flex: 1;
        }
        .info-block h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .info-block p {
            margin: 3px 0;
            font-size: 14px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            text-align: left;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 1px;
        }
        .items-table td {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .items-table .text-right {
            text-align: right;
        }
        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background: #f5f5f5;
        }
        .product-title {
            font-weight: 500;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .total-row.final {
            border-bottom: none;
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
        }
        .payment-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .payment-info h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .payment-row .label {
            color: #666;
        }
        .payment-row .value {
            font-weight: 500;
        }
        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
        .status-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .receipt {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="company-info">
                <img src="{$platformLogo}" alt="{$platformName}" class="logo" onerror="this.style.display='none'">
                <h2 style="margin-top: 10px;">{$platformName}</h2>
            </div>
            <div class="receipt-title">
                <h1>{$labels['receipt']}</h1>
                <p class="receipt-no">{$receiptNo}</p>
                <p style="margin-top: 10px;"><span class="status-badge">{$labels['paid']}</span></p>
            </div>
        </div>

        <div class="info-section">
            <div class="info-block">
                <h3>{$labels['bill_to']}</h3>
                <p><strong>{$buyerName}</strong></p>
                <p>{$buyerEmail}</p>
            </div>
            <div class="info-block">
                <h3>{$labels['ship_to']}</h3>
                <p><strong>{$recipientName}</strong></p>
                <p>{$addressLine}</p>
                <p>{$city}, {$state} {$postalCode}</p>
                <p>{$country}</p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>{$labels['order_info']}</h3>
                <p><strong>{$labels['order_no']}:</strong> {$orderNo}</p>
                <p><strong>{$labels['date']}:</strong> {$orderDate}</p>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>{$labels['item']}</th>
                    <th class="text-right">{$labels['quantity']}</th>
                    <th class="text-right">{$labels['price']}</th>
                    <th class="text-right">{$labels['amount']}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="{$goodsImage}" alt="" class="product-image" onerror="this.style.display='none'">
                            <span class="product-title">{$goodsTitle}</span>
                        </div>
                    </td>
                    <td class="text-right">{$quantity}</td>
                    <td class="text-right">{$price}</td>
                    <td class="text-right">{$price}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>{$labels['subtotal']}</span>
                <span>{$price}</span>
            </div>
            <div class="total-row">
                <span>{$labels['shipping']}</span>
                <span>{$shippingFee}</span>
            </div>
            <div class="total-row final">
                <span>{$labels['total']}</span>
                <span>{$totalAmount}</span>
            </div>
        </div>

        <div class="payment-info">
            <h3>{$labels['payment_info']}</h3>
            <div class="payment-row">
                <span class="label">{$labels['payment_method']}</span>
                <span class="value">{$cardDisplay}</span>
            </div>
            <div class="payment-row">
                <span class="label">{$labels['transaction_id']}</span>
                <span class="value">{$transactionNo}</span>
            </div>
            <div class="payment-row">
                <span class="label">{$labels['payment_date']}</span>
                <span class="value">{$orderDate}</span>
            </div>
            <div class="payment-row">
                <span class="label">{$labels['amount_charged']}</span>
                <span class="value">{$totalAmount}</span>
            </div>
        </div>

        <div class="footer">
            <p>{$labels['thank_you']}</p>
            <p>{$platformName} | {$platformUrl}</p>
            <p style="margin-top: 15px;">{$labels['auto_generated']}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 获取货币符号
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'SGD' => 'S$',
            'TWD' => 'NT$',
            'JPY' => '¥',
            'HKD' => 'HK$',
            'CNY' => '¥',
            'EUR' => '€',
            'GBP' => '£',
        ];
        return $symbols[strtoupper($currency)] ?? '$';
    }

    /**
     * 获取多语言标签（从数据库读取）
     */
    protected function getLabels(string $locale): array
    {
        // 从数据库获取 receipt 命名空间的翻译
        $dbTranslations = UiTranslation::getTranslationsByNamespace($locale, 'receipt');

        // 如果数据库中没有翻译，尝试获取英文翻译
        if (empty($dbTranslations)) {
            $dbTranslations = UiTranslation::getTranslationsByNamespace('en-us', 'receipt');
        }

        // 默认值（fallback）
        $defaults = [
            'receipt' => 'RECEIPT',
            'paid' => 'PAID',
            'bill_to' => 'Bill To',
            'ship_to' => 'Ship To',
            'order_info' => 'Order Information',
            'order_no' => 'Order No',
            'date' => 'Date',
            'item' => 'Item',
            'quantity' => 'Qty',
            'price' => 'Price',
            'amount' => 'Amount',
            'subtotal' => 'Subtotal',
            'shipping' => 'Shipping',
            'total' => 'Total',
            'payment_info' => 'Payment Information',
            'payment_method' => 'Payment Method',
            'transaction_id' => 'Transaction ID',
            'payment_date' => 'Payment Date',
            'amount_charged' => 'Amount Charged',
            'thank_you' => 'Thank you for your purchase!',
            'auto_generated' => 'This is an automatically generated receipt.',
        ];

        // 将数据库键名映射到模板使用的键名
        $keyMap = [
            'title' => 'receipt',
            'billTo' => 'bill_to',
            'shipTo' => 'ship_to',
            'orderInfo' => 'order_info',
            'orderNo' => 'order_no',
            'paymentInfo' => 'payment_info',
            'paymentMethod' => 'payment_method',
            'transactionId' => 'transaction_id',
            'paymentDate' => 'payment_date',
            'amountCharged' => 'amount_charged',
            'thankYou' => 'thank_you',
            'autoGenerated' => 'auto_generated',
        ];

        // 合并数据库翻译到默认值
        foreach ($dbTranslations as $key => $value) {
            // 检查是否需要映射键名
            $targetKey = $keyMap[$key] ?? $key;
            $defaults[$targetKey] = $value;
        }

        return $defaults;
    }

    /**
     * 删除收据文件
     */
    public function deleteReceipt(string $filepath): bool
    {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
