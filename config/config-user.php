<?php
// 适配generate_thumb.php的路径配置
$rootDir = __DIR__ . DIRECTORY_SEPARATOR . '..'; // 网站根目录（上一级）
$imageRoot = $rootDir . DIRECTORY_SEPARATOR . 'images';
$thumbRoot = $rootDir . DIRECTORY_SEPARATOR . 'thumbnails';

// 确保基础目录存在
if (!is_dir($imageRoot)) {
    mkdir($imageRoot, 0755, true);
}
if (!is_dir($thumbRoot)) {
    mkdir($thumbRoot, 0755, true);
}

//s'change to sql data storage
if (false) {
    return require_once "sql/mysql.php"; // 引入sql数据
}else{
    return require_once "sql/user.php"; // 引入本地数据
}