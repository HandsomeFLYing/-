<?php
// 生成指定明文的MD5值
$password = $_GET['password'] ?? '123456'; // 替换成你想要的明文密码
$md5_password = md5($password);
echo "明文：{$password}<br>";
echo "正确MD5值：{$md5_password}<br>";

?>