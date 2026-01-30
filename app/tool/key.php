<?php
require_once 'tool/aesDecrypt.php';
require_once 'tool/aesEncrypt.php';
require_once '../config/auto.php';

// 1. 原始数据：密码 + 用户名
$originalPwd = '123456abc'; // 原始密码
$username = 'zhangsan|123'; // 用户名（包含分隔符|，测试转义/还原逻辑）
$md5Pwd = md5($originalPwd); // 生成密码的md5值
echo "原始md5密码：{$md5Pwd}<br>";
echo "原始用户名：{$username}<br>";

// 2. 加密：md5密码+用户名 → 生成唯一key
$encryptedKey = generateKeyFromMd5PwdAndUser($md5Pwd, $username, $customKey);
echo "加密后的key：{$encryptedKey}<br>";

// 3. 解密：key → 还原md5密码+用户名
$parsedData = parseMd5PwdAndUserFromKey($encryptedKey, $customKey);
if ($parsedData === false) {
    echo "解密失败（密钥错误/key损坏）<br>";
} else {
    echo "解密还原结果：<br>";
    echo "md5密码：{$parsedData['md5_pwd']}<br>";
    echo "用户名：{$parsedData['username']}<br>";
}