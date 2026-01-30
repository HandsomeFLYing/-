<?php
session_start();

// 加载用户配置
$userConfig = require_once __DIR__ . '/../config/config-user.php';
$nameuser = $_SESSION['user_info']['username'] ?? '';
$password = $userConfig[$nameuser]['password'] ?? '';
$currentDate = date('Y-m-d');
$identityKey = md5($password . $currentDate);
$userrole = $_SESSION['user_info']['role'] ?? '';
// 验证用户是否存在
if (!isset($userConfig[$nameuser])) {
    return null;
}
//检测身份密钥
if (!isset($_SESSION['user_info']['identity_key']) || $_SESSION['user_info']['identity_key'] !== $identityKey) {
    return null;
}
// 检测用户权限
if ($userConfig[$nameuser]['role'] !== $userrole) {
    return null;
}
return $userConfig[$nameuser]['role'];