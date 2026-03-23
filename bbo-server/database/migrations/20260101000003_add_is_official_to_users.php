<?php
/**
 * 添加 is_official 字段到 users 表
 * 用于标识官方小号账户
 *
 * 执行方式：
 * 1. 直接访问 /api/migrate?file=20260101000003_add_is_official_to_users
 * 2. 或手动执行以下 SQL
 */

// 直接执行 SQL
$sql = <<<SQL
-- 添加 is_official 字段
ALTER TABLE `users` ADD COLUMN `is_official` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否官方小号(0=否,1=是)' AFTER `is_verified`;

-- 添加索引
ALTER TABLE `users` ADD INDEX `idx_is_official` (`is_official`);
SQL;

echo "请执行以下 SQL:\n\n";
echo $sql;
echo "\n\n";

// 如果是通过 ThinkPHP 调用
if (class_exists('think\facade\Db')) {
    try {
        \think\facade\Db::execute("ALTER TABLE `users` ADD COLUMN `is_official` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否官方小号(0=否,1=是)' AFTER `is_verified`");
        \think\facade\Db::execute("ALTER TABLE `users` ADD INDEX `idx_is_official` (`is_official`)");
        echo "Migration executed successfully!\n";
    } catch (\Exception $e) {
        echo "Migration error: " . $e->getMessage() . "\n";
    }
}
