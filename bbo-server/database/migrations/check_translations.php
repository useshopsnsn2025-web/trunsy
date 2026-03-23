<?php
/**
 * 检查翻译是否已存在于数据库中
 */

$host = '127.0.0.1';
$port = 3306;
$dbname = 'bbo';
$username = 'bbo';
$password = '123456';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "检查翻译命名空间...\n\n";

    // 检查各个命名空间的翻译数量
    $namespaces = ['ocbc', 'bri'];

    foreach ($namespaces as $ns) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, locale FROM ui_translations WHERE namespace = ? GROUP BY locale");
        $stmt->execute([$ns]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "命名空间: {$ns}\n";
        if (empty($results)) {
            echo "  ❌ 未找到任何翻译\n";
        } else {
            foreach ($results as $row) {
                echo "  ✅ {$row['locale']}: {$row['count']} 条翻译\n";
            }
        }
        echo "\n";
    }

} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
