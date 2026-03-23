<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\model\UserEvent;
use app\common\model\PageViewDuration;

class Analytics extends Base
{
    /**
     * Batch report events
     * POST /api/analytics/report
     */
    public function report(): Response
    {
        $events = input('post.events', []);

        if (empty($events) || !is_array($events)) {
            return $this->success(['inserted' => 0]);
        }

        // Limit batch size
        $events = array_slice($events, 0, 50);

        $ip = request()->ip();
        $userId = $this->getUserId();

        $rows = [];
        foreach ($events as $event) {
            if (empty($event['event_type']) || empty($event['session_id'])) {
                continue;
            }
            $rows[] = [
                'user_id' => $userId > 0 ? $userId : 0,
                'session_id' => substr((string)($event['session_id'] ?? ''), 0, 64),
                'event_type' => substr((string)($event['event_type'] ?? ''), 0, 50),
                'event_category' => substr((string)($event['event_category'] ?? 'interaction'), 0, 30),
                'page' => substr((string)($event['page'] ?? ''), 0, 100),
                'target' => substr((string)($event['target'] ?? ''), 0, 100),
                'properties' => !empty($event['properties']) ? json_encode($event['properties']) : null,
                'device_type' => substr((string)($event['device_type'] ?? ''), 0, 20),
                'ip' => $ip,
                'created_at' => !empty($event['timestamp'])
                    ? date('Y-m-d H:i:s', (int)($event['timestamp'] / 1000))
                    : date('Y-m-d H:i:s'),
            ];
        }

        $inserted = 0;
        if (!empty($rows)) {
            try {
                $inserted = UserEvent::batchInsert($rows);
            } catch (\Throwable $e) {
                // Silently fail - analytics should not break user experience
            }
        }

        return $this->success(['inserted' => $inserted]);
    }

    /**
     * Report page duration
     * POST /api/analytics/page-duration
     */
    public function pageDuration(): Response
    {
        $records = input('post.records', []);

        if (empty($records) || !is_array($records)) {
            return $this->success(['inserted' => 0]);
        }

        $records = array_slice($records, 0, 20);
        $userId = $this->getUserId();

        $rows = [];
        foreach ($records as $record) {
            if (empty($record['page']) || empty($record['session_id']) || empty($record['duration_ms'])) {
                continue;
            }
            $durationMs = (int)$record['duration_ms'];
            if ($durationMs < 500 || $durationMs > 3600000) {
                continue; // Skip unrealistic durations
            }
            $rows[] = [
                'user_id' => $userId > 0 ? $userId : 0,
                'session_id' => substr((string)$record['session_id'], 0, 64),
                'page' => substr((string)$record['page'], 0, 100),
                'page_title' => substr((string)($record['page_title'] ?? ''), 0, 100),
                'referrer_page' => substr((string)($record['referrer_page'] ?? ''), 0, 100),
                'duration_ms' => $durationMs,
                'enter_at' => !empty($record['enter_at'])
                    ? date('Y-m-d H:i:s', (int)($record['enter_at'] / 1000))
                    : date('Y-m-d H:i:s', time() - (int)($durationMs / 1000)),
                'leave_at' => !empty($record['leave_at'])
                    ? date('Y-m-d H:i:s', (int)($record['leave_at'] / 1000))
                    : date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        $inserted = 0;
        if (!empty($rows)) {
            try {
                $inserted = PageViewDuration::batchInsert($rows);
            } catch (\Throwable $e) {
                // Silently fail
            }
        }

        return $this->success(['inserted' => $inserted]);
    }
}
