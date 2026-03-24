<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\service\EbayCrawlerService;
use app\common\service\GeminiService;
use app\common\service\DeepSeekService;
use app\common\service\TranslateService;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsTranslation;
use app\common\model\GoodsConditionValue;
use app\common\model\User;

/**
 * 商品采集控制器
 */
class GoodsCrawl extends Base
{
    /**
     * 任务文件目录
     */
    protected function getTaskDir(): string
    {
        // 使用应用根目录下的 runtime/crawl_tasks/，确保控制器和 worker 路径一致
        $dir = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'crawl_tasks' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * 采集预览 - 从 eBay 列表页获取商品数据
     */
    public function preview(): Response
    {
        $url = input('post.url', '');
        $pages = (int) input('post.pages', 1);

        if (empty($url)) {
            return $this->error('请输入 eBay 列表页 URL');
        }

        // 验证是 eBay URL
        if (strpos($url, 'ebay.com') === false) {
            return $this->error('请输入有效的 eBay URL');
        }

        $pages = max(1, min(5, $pages));

        try {
            \think\facade\Log::info('Crawl preview: url=' . $url . ', pages=' . $pages);
            $crawler = new EbayCrawlerService();
            $result = $crawler->crawlList($url, $pages);
            \think\facade\Log::info('Crawl result: total=' . $result['total'] . ', errors=' . json_encode($result['errors']));

            return $this->success($result);
        } catch (\Exception $e) {
            \think\facade\Log::error('Crawl exception: ' . $e->getMessage());
            return $this->error('采集失败: ' . $e->getMessage());
        }
    }

    /**
     * 深度采集 - 获取商品详情页数据
     */
    public function detail(): Response
    {
        $items = input('post.items', []);

        if (empty($items) || !is_array($items)) {
            return $this->error('请选择要深度采集的商品');
        }

        if (count($items) > 20) {
            return $this->error('单次深度采集最多 20 个商品');
        }

        try {
            $crawler = new EbayCrawlerService();
            $result = $crawler->crawlDetail($items);

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('深度采集失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量导入 - 创建异步任务，立即返回任务ID
     */
    public function import(): Response
    {
        $items = input('post.items', []);
        $config = input('post.config', []);

        if (empty($items) || !is_array($items)) {
            return $this->error('请选择要导入的商品');
        }

        $categoryId = (int) ($config['category_id'] ?? 0);
        if (empty($categoryId)) {
            return $this->error('请选择商品分类');
        }

        // 验证用户可用
        $userIds = User::where('status', 1)->column('id');
        if (empty($userIds)) {
            return $this->error('没有可用的活跃用户');
        }

        // 生成任务ID
        $taskId = uniqid('crawl_', true);
        $taskDir = $this->getTaskDir();

        // 保存任务数据
        $taskData = [
            'id'         => $taskId,
            'items'      => $items,
            'config'     => $config,
            'user_ids'   => $userIds,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents($taskDir . $taskId . '.json', json_encode($taskData, JSON_UNESCAPED_UNICODE));

        // 初始化进度文件
        $progress = [
            'status'        => 'running',
            'total'         => count($items),
            'processed'     => 0,
            'success_count' => 0,
            'fail_count'    => 0,
            'current_title' => '',
            'goods_ids'     => [],
            'errors'        => [],
            'started_at'    => date('Y-m-d H:i:s'),
        ];
        file_put_contents($taskDir . $taskId . '_progress.json', json_encode($progress, JSON_UNESCAPED_UNICODE));

        // 启动后台 PHP 进程处理任务
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $phpBin = $isWindows ? str_replace('/', '\\', PHP_BINARY ?: 'php') : (PHP_BINARY ?: '/www/server/php/80/bin/php');
        $script = $isWindows ? str_replace('/', '\\', app()->getRootPath() . 'crawl_worker.php') : app()->getRootPath() . 'crawl_worker.php';
        $logFile = $isWindows ? str_replace('/', '\\', $taskDir . $taskId . '_log.txt') : $taskDir . $taskId . '_log.txt';

        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows: 使用 WScript.Shell Run 方法实现真正的非阻塞后台执行
            $batFile = str_replace('/', '\\', $taskDir . $taskId . '_run.bat');
            $batContent = '@echo off' . "\r\n"
                . '"' . $phpBin . '" "' . $script . '" "' . $taskId . '" > "' . $logFile . '" 2>&1' . "\r\n"
                . 'del "%~f0"' . "\r\n";
            file_put_contents($batFile, $batContent);

            // 使用 VBScript 的 WScript.Shell.Run 实现非阻塞
            $vbsFile = str_replace('/', '\\', $taskDir . $taskId . '_run.vbs');
            $vbsContent = 'Set WshShell = CreateObject("WScript.Shell")' . "\r\n"
                . 'WshShell.Run """' . $batFile . '""", 0, False' . "\r\n"
                . 'Set WshShell = Nothing' . "\r\n"
                . 'Set fso = CreateObject("Scripting.FileSystemObject")' . "\r\n"
                . 'fso.DeleteFile WScript.ScriptFullName' . "\r\n";
            file_put_contents($vbsFile, $vbsContent);

            // cscript 执行 vbs，立即返回
            pclose(popen('cscript //Nologo "' . $vbsFile . '"', 'r'));
        } else {
            $cmd = sprintf('"%s" "%s" "%s" > "%s" 2>&1 &', $phpBin, $script, $taskId, $logFile);
            pclose(popen($cmd, 'r'));
        }

        return $this->success([
            'task_id' => $taskId,
            'total'   => count($items),
            'message' => '导入任务已创建，正在后台处理',
        ]);
    }

    /**
     * 查询导入任务进度
     */
    public function importStatus(): Response
    {
        $taskId = input('task_id', '');
        if (empty($taskId)) {
            return $this->error('缺少任务ID');
        }

        // 安全校验：只允许字母数字下划线点
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $taskId)) {
            return $this->error('无效的任务ID');
        }

        $progressFile = $this->getTaskDir() . $taskId . '_progress.json';
        if (!file_exists($progressFile)) {
            return $this->error('任务不存在');
        }

        $progress = json_decode(file_get_contents($progressFile), true);
        if (!$progress) {
            return $this->error('任务数据异常');
        }

        return $this->success($progress);
    }
}
