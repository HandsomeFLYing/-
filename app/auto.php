<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 加载用户配置
$userConfig = require_once __DIR__ . '/../config/config-user.php';

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';
$password = md5($_POST['password'] ?? '');

$result = ['code' => 0, 'msg' => ''];

// 退出登录
if ($action === 'logout') {
    unset($_SESSION['user_info']);
    $result['code'] = 1;
    $result['msg'] = '退出成功';
    echo json_encode($result);
    exit;
}

if ($action !== 'login') {
    $result['msg'] = '非法请求';
    echo json_encode($result);
    exit;
}

// 验证账号是否存在
if (!isset($userConfig[$username])) {
    $result['msg'] = '账号不存在';
    echo json_encode($result);
    exit;
}

$user = $userConfig[$username];
// 验证密码（MD5方式，生产环境建议替换为password_verify）
if ($password !== $user['password']) {
    $result['msg'] = '密码错误';
    echo json_encode($result);
    exit;
}

// 生成身份密钥（密码与日期组合）
$currentDate = date('Y-m-d');
$identityKey = md5($password . $currentDate);

// 登录成功，设置Session
$_SESSION['user_info'] = [
    'username' => $username,
    'role' => $user['role'],
    'root_path' => $user['root_path'],
    'thumb_root' => $user['thumb_root'],
    'relative_root' => $user['relative_root'],
    'identity_key' => $identityKey,
    'login_date' => $currentDate
];

// 返回成功（前端写入localStorage）
$result['code'] = 1;
$result['msg'] = '登录成功';
$result['data'] = [
    'username' => $username,
    'role' => $user['role'],
    'encrypted_password' => md5($password),
    'identity_key' => $identityKey,
    'login_date' => $currentDate
];
echo json_encode($result);
exit;