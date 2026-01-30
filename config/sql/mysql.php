<?php
//防止url直接访问
if (basename($_SERVER['PHP_SELF']) === 'mysql.php') {
    header('Location: /index.php');
    exit;
}
//require_once '../config/yml.php';
//数据库连接表
require_once 'config.php';
//连接数据库
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    return require_once "user.php";
}

// 检查 users 表是否存在，如果不存在则创建
$stmt = $pdo->query("SHOW TABLES LIKE '{$db_config['table']}'");
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    // 创建 users 表
    $createTableSQL = "CREATE TABLE {$db_config['table']} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(32) NOT NULL,
        role VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTableSQL);
    
    // 从 user.php 中读取用户数据并插入
    $userData = include __DIR__ . '/user.php';
    foreach ($userData as $username => $userInfo) {
        $insertSQL = "INSERT INTO {$db_config['table']} (username, password, role) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($insertSQL);
        $stmt->execute([$username, $userInfo['password'], $userInfo['role']]);
    }
}

// 返回用户配置数组
$stmt = $pdo->prepare("SELECT username, password, role FROM {$db_config['table']}");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$userConfig = [];
foreach ($users as $user) {
    $username = $user['username'];
    if($user['role'] !== 'admin'){//区别用户类型
        $userImageRoot = $imageRoot . DIRECTORY_SEPARATOR . $username;
    } else {
        $userImageRoot = $imageRoot;
    }
    $userConfig[$username] = [
        'password' => $user['password'],
        'role' => $user['role'],
        'root_path' => $userImageRoot,
        'thumb_root' => $thumbRoot . DIRECTORY_SEPARATOR . $username,
        'relative_root' => $imgDir .'/' . $username
    ];
}
return $userConfig;


