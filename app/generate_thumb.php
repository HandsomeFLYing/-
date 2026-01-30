<?php
// 临时开启错误显示（0关1开）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
// generate_thumb.php - 独立缩略图生成接口
header('Content-Type: application/json');
set_time_limit(10); // 单张图片生成最多10秒，避免超时

// 配置项
require_once '../config/yml.php';

// 安全检查：仅允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => '仅支持POST请求']);
    exit;
}

// 获取参数
$imagePath = isset($_POST['image_path']) ? $_POST['image_path'] : '';
$fullImagePath = $rootDir . '/' . $imagePath;
$thumbFullPath = $thumbDir . '/' . $imagePath;

// 验证参数
if (empty($imagePath) || !file_exists($fullImagePath)) {
    echo json_encode(['success' => false, 'msg' => '图片不存在']);
    exit;
}

// 检查文件格式
$ext = strtolower(pathinfo($fullImagePath, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts)) {
    echo json_encode(['success' => false, 'msg' => '不支持的图片格式']);
    exit;
}

// 避免重复生成（加简单锁）
$lockFile = $thumbFullPath . '.lock';
if (file_exists($lockFile)) {
    echo json_encode(['success' => false, 'msg' => '正在生成中']);
    exit;
}
file_put_contents($lockFile, '1');

/**
 * 创建目录（递归）
 */
function createDir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

/**
 * 生成缩略图（等比例缩放+留白填充，避免变形）
 */
function generateThumbnail($srcPath, $destPath, $width, $height) {
    $imageInfo = getimagesize($srcPath);
    if (!$imageInfo) return false;
    
    $srcWidth = $imageInfo[0];
    $srcHeight = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // 创建原图资源
    switch ($mime) {
        case 'image/jpeg':
            $srcImage = imagecreatefromjpeg($srcPath);
            $isTransparent = false;
            break;
        case 'image/png':
            $srcImage = imagecreatefrompng($srcPath);
            $isTransparent = true;
            break;
        case 'image/gif':
            $srcImage = imagecreatefromgif($srcPath);
            $isTransparent = true;
            break;
        case 'image/webp':
            $srcImage = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($srcPath) : false;
            $isTransparent = true;
            break;
        default:
            return false;
    }
    
    if (!$srcImage) return false;

    // 等比例缩放（避免变形）
    $scale = min($width / $srcWidth, $height / $srcHeight);
    $newWidth = (int)($srcWidth * $scale);
    $newHeight = (int)($srcHeight * $scale);
    
    // 创建目标画布
    $destImage = imagecreatetruecolor($width, $height);
    if ($isTransparent) {
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefill($destImage, 0, 0, $transparent);
    } else {
        $white = imagecolorallocate($destImage, 255, 255, 255);
        imagefill($destImage, 0, 0, $white);
    }

    // 居中放置
    $x = (int)(($width - $newWidth) / 2);
    $y = (int)(($height - $newHeight) / 2);
    imagecopyresampled($destImage, $srcImage, $x, $y, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // 保存缩略图
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($destImage, $destPath, 80);
            break;
        case 'image/png':
            $result = imagepng($destImage, $destPath, 6);
            break;
        case 'image/gif':
            $result = imagegif($destImage, $destPath);
            break;
        case 'image/webp':
            $result = function_exists('imagewebp') ? imagewebp($destImage, $destPath, 80) : false;
            break;
    }

    // 释放资源
    imagedestroy($srcImage);
    imagedestroy($destImage);
    return $result;
}

// 执行生成
createDir(dirname($thumbFullPath));
$success = generateThumbnail($fullImagePath, $thumbFullPath, $thumbWidth, $thumbHeight);

// 移除锁文件
@unlink($lockFile);

// 返回结果
if ($success) {
    $thumbUrl = 'thumbnails/' . $imagePath;
    echo json_encode(['success' => true, 'thumb_url' => $thumbUrl]);
} else {
    echo json_encode(['success' => false, 'msg' => '生成失败']);
}
exit;
?>