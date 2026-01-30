<?php
require_once '../config/auto.php';

/**
 * AES-256-CBC 解密函数（与加密函数配套，相同密钥才能解密）
 * @param string $encryptedKey 加密后的key（Base64编码）
 * @param string $key 与加密时相同的自定义密钥
 * @return string|false 解密后的原始字符串，失败返回false
 */
function aesDecrypt($encryptedKey, $key)
{
    $cipher = 'aes-256-cbc';
    $key = hash('sha256', $key, true);
    // Base64解码，分离IV和密文
    $decoded = base64_decode($encryptedKey);
    if ($decoded === false) return false;
    $ivLen = openssl_cipher_iv_length($cipher);
    $iv = substr($decoded, 0, $ivLen); // 前16位为IV
    $cipherText = substr($decoded, $ivLen); // 剩余为密文
    // 解密还原原始字符串
    return openssl_decrypt($cipherText, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * 解密key，还原出md5密码和用户名（核心业务函数）
 * @param string $encryptedKey 加密后的key（generateKeyFromMd5PwdAndUser的返回值）
 * @param string $key 与加密时相同的自定义密钥
 * @return array|false 成功返回['md5_pwd' => 'md5密码', 'username' => '用户名']，失败返回false
 */
function parseMd5PwdAndUserFromKey($encryptedKey, $key)
{
    $separator = '|'; // 必须与加密时的分隔符一致
    // 第一步：解密key，得到拼接的原始字符串
    $decryptedData = aesDecrypt($encryptedKey, $key);
    if ($decryptedData === false) return false;
    
    // 第二步：按分隔符拆分（限制拆分1次，确保只拆分为md5密码和用户名两部分）
    $parts = explode($separator, $decryptedData, 2);
    if (count($parts) !== 2) return false; // 拆分失败（数据损坏/密钥错误）
    
    // 第三步：还原转义的用户名（将\|转回|）
    $md5Pwd = $parts[0];
    $username = str_replace('\\' . $separator, $separator, $parts[1]);
    
    // 第四步：校验md5密码格式（可选，确保数据有效性，md5固定32位）
    if (strlen($md5Pwd) !== 32 || !preg_match('/^[0-9a-f]{32}$/i', $md5Pwd)) {
        return false;
    }
    
    return [
        'md5_pwd' => $md5Pwd,
        'username' => $username
    ];
}