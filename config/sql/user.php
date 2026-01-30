<?php
// 防止url直接访问
if (basename($_SERVER['PHP_SELF']) === 'user.php') {
    header('Location: /index.php');
    exit;
}

// 本地数据存储
// PHP7.3兼容，用户/管理员配置
return ['admin' => [
    'password' => 'e10adc3949ba59abbe56e057f20f883e',
    'role' => 'admin',
    'root_path' => $imageRoot,
    'thumb_root' => $thumbRoot,
    'relative_root' => 'images'
],
'user1' => [
    'password' => 'e10adc3949ba59abbe56e057f20f883e',
    'role' => 'user',
    'root_path' => $imageRoot . DIRECTORY_SEPARATOR . 'user1',
    'thumb_root' => $thumbRoot . DIRECTORY_SEPARATOR . 'user1',
    'relative_root' => 'images/user1'
]];