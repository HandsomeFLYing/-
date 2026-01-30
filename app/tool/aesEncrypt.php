<?php

session_start();
$loginUser = $_SESSION['user_info']['username'];
$identity_key = $_SESSION['user_info']['identity_key'];
require_once '../config/auto.php';


/**
 * AES-256-CBC 加密函数（PHP官方推荐，可逆）
 * @param string $data 待加密的原始字符串（拼接后的md5密码+用户名）
 * @param string $key 自定义密钥（任意长度，内部自动转为32位标准密钥）
 * @return string 加密后的key（Base64编码，可直接存储/传输）
 */
function aesEncrypt($data, $key)
{
    $cipher = 'aes-256-cbc'; // 核心算法，AES-256-CBC（安全、高效）
    // 生成32位标准密钥（AES-256要求固定32位，true返回二进制确保长度）
    $key = hash('sha256', $key, true);
    // 生成随机IV（初始化向量，必须唯一，AES-CBC要求16位，无需保密）
    $ivLen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLen);
    // 加密：OPENSSL_RAW_DATA返回二进制密文，加密效率更高
    $cipherText = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    // IV+密文拼接后Base64编码，方便存储/传输（避免二进制乱码）
    return base64_encode($iv . $cipherText);
}

/**
 * 将md5密码+用户名加密成唯一key（核心业务函数）
 * @param string $md5Pwd 密码的md5值（如md5('123456')）
 * @param string $username 用户名（可包含任意字符，含分隔符也兼容）
 * @param string $key 自定义加密密钥（自己保管，解密时必须相同）
 * @return string 加密后的key（可存储到数据库/传给前端）
 */
function generateKeyFromMd5PwdAndUser($md5Pwd, $username, $key)
{
    $separator = '|'; // 核心分隔符，可根据需要修改（如#、$）
    // 转义用户名中的分隔符（避免拆分失败，如将|转为\|）
    $escapedUsername = str_replace($separator, '\\' . $separator, $username);
    // 拼接：md5密码 + 分隔符 + 转义后的用户名
    $originalData = $md5Pwd . $separator . $escapedUsername;
    // 调用通用加密函数生成key
    return aesEncrypt($originalData, $key);
}
//验证数据是否存在
if($loginUser !==null){
    //加密：md5密码+用户名 → 生成唯一key
    return $encryptedKey = generateKeyFromMd5PwdAndUser($identity_key, $loginUser, $customKey);
}else{
    return;
}

