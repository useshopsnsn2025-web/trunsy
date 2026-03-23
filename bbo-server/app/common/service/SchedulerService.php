<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\ScheduledTask;
use app\common\model\ScheduledTaskLog;
use think\facade\Console;
use think\facade\Log;

/**
 * 计划任务调度服务
 */
class SchedulerService
{
    /**
     * 运行所有到期的任务
     * @return array
     */
    public function runDueTasks(): array
    {
        $results = [];
        $tasks = ScheduledTask::getDueTasks();

        foreach ($tasks as $task) {
            $results[] = $this->runTask($task);
        }

        return $results;
    }

    /**
     * 执行单个任务
     * @param ScheduledTask $task
     * @return array
     */
    public function runTask(ScheduledTask $task): array
    {
        $startTime = microtime(true);
        $log = ScheduledTaskLog::start($task->id);

        try {
            // 执行命令
            $output = $this->executeCommand($task->command, $task->timeout);

            // 更新任务状态
            $task->last_run_at = date('Y-m-d H:i:s');
            $task->next_run_at = $this->getNextRunTime($task->cron_expression);
            $task->last_result = ScheduledTask::RESULT_SUCCESS;
            $task->last_output = $output;
            $task->run_count = $task->run_count + 1;
            $task->save();

            // 完成日志
            $log->finish(true, $output);

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'task_id' => $task->id,
                'name' => $task->name,
                'success' => true,
                'output' => $output,
                'duration' => $duration,
            ];

        } catch (\Throwable $e) {
            // 更新任务状态
            $task->last_run_at = date('Y-m-d H:i:s');
            $task->next_run_at = $this->getNextRunTime($task->cron_expression);
            $task->last_result = ScheduledTask::RESULT_FAIL;
            $task->last_output = $e->getMessage();
            $task->run_count = $task->run_count + 1;
            $task->fail_count = $task->fail_count + 1;
            $task->save();

            // 完成日志
            $log->finish(false, null, $e->getMessage());

            Log::error("Scheduled task [{$task->name}] failed: " . $e->getMessage());

            return [
                'task_id' => $task->id,
                'name' => $task->name,
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
            ];
        }
    }

    /**
     * 执行命令
     * @param string $command
     * @param int $timeout
     * @return string
     */
    protected function executeCommand(string $command, int $timeout = 300): string
    {
        // 使用 ThinkPHP Console 执行命令
        ob_start();
        try {
            Console::call($command);
            $output = ob_get_clean();
            return $output ?: 'Command executed successfully';
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        }
    }

    /**
     * 计算下次执行时间
     * @param string $cronExpression
     * @return string
     */
    public function getNextRunTime(string $cronExpression): string
    {
        $cron = new CronExpression($cronExpression);
        return $cron->getNextRunDate()->format('Y-m-d H:i:s');
    }

    /**
     * 验证 Cron 表达式
     * @param string $expression
     * @return bool
     */
    public function isValidCronExpression(string $expression): bool
    {
        try {
            new CronExpression($expression);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取 Cron 表达式的人类可读描述
     * @param string $expression
     * @return string
     */
    public function describeCronExpression(string $expression): string
    {
        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) {
            return '无效的表达式';
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        // 常见模式
        if ($expression === '* * * * *') {
            return '每分钟';
        }
        if ($expression === '0 * * * *') {
            return '每小时';
        }
        if (preg_match('/^0 \*\/(\d+) \* \* \*$/', $expression, $m)) {
            return "每 {$m[1]} 小时";
        }
        if (preg_match('/^(\d+) (\d+) \* \* \*$/', $expression, $m)) {
            return "每天 {$m[2]}:{$m[1]}";
        }
        if (preg_match('/^0 (\d+) \* \* \*$/', $expression, $m)) {
            return "每天 {$m[1]}:00";
        }

        return $expression;
    }
}

/**
 * Cron 表达式解析器
 */
class CronExpression
{
    protected $minute;
    protected $hour;
    protected $day;
    protected $month;
    protected $weekday;

    public function __construct(string $expression)
    {
        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) {
            throw new \InvalidArgumentException('Invalid cron expression: must have 5 parts');
        }

        [$this->minute, $this->hour, $this->day, $this->month, $this->weekday] = $parts;
    }

    /**
     * 获取下次执行时间
     * @param \DateTime|null $from
     * @return \DateTime
     */
    public function getNextRunDate(?\DateTime $from = null): \DateTime
    {
        $from = $from ?: new \DateTime();
        $next = clone $from;
        $next->setTime((int)$next->format('H'), (int)$next->format('i'), 0);
        $next->modify('+1 minute');

        // 最多尝试一年
        $maxAttempts = 525600; // 一年的分钟数
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            if ($this->matches($next)) {
                return $next;
            }
            $next->modify('+1 minute');
            $attempts++;
        }

        throw new \RuntimeException('Could not find next run date within one year');
    }

    /**
     * 检查时间是否匹配 Cron 表达式
     * @param \DateTime $date
     * @return bool
     */
    public function matches(\DateTime $date): bool
    {
        return $this->matchesPart($this->minute, (int)$date->format('i'), 0, 59)
            && $this->matchesPart($this->hour, (int)$date->format('G'), 0, 23)
            && $this->matchesPart($this->day, (int)$date->format('j'), 1, 31)
            && $this->matchesPart($this->month, (int)$date->format('n'), 1, 12)
            && $this->matchesPart($this->weekday, (int)$date->format('w'), 0, 6);
    }

    /**
     * 检查值是否匹配 Cron 表达式的一部分
     * @param string $part
     * @param int $value
     * @param int $min
     * @param int $max
     * @return bool
     */
    protected function matchesPart(string $part, int $value, int $min, int $max): bool
    {
        // 通配符
        if ($part === '*') {
            return true;
        }

        // 处理列表 (1,2,3)
        if (strpos($part, ',') !== false) {
            $values = explode(',', $part);
            foreach ($values as $v) {
                if ($this->matchesPart($v, $value, $min, $max)) {
                    return true;
                }
            }
            return false;
        }

        // 处理范围 (1-5)
        if (strpos($part, '-') !== false) {
            [$start, $end] = explode('-', $part);
            return $value >= (int)$start && $value <= (int)$end;
        }

        // 处理步长 (*/5 或 1-10/2)
        if (strpos($part, '/') !== false) {
            [$range, $step] = explode('/', $part);
            $step = (int)$step;

            if ($range === '*') {
                return ($value - $min) % $step === 0;
            }

            if (strpos($range, '-') !== false) {
                [$start, $end] = explode('-', $range);
                if ($value < (int)$start || $value > (int)$end) {
                    return false;
                }
                return ($value - (int)$start) % $step === 0;
            }
        }

        // 精确匹配
        return (int)$part === $value;
    }
}
