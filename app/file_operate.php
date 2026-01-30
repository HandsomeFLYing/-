<?php
// 临时开启错误显示（0关1开）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
// // 开启错误捕获，确保返回JSON格式
// ini_set('display_errors', 0);
// error_reporting(E_ALL);

// // 注册错误处理函数
// set_error_handler(function($errno, $errstr, $errfile, $errline) {
//     header('Content-Type: application/json; charset=utf-8');
//     echo json_encode(['code' => 0, 'msg' => '服务器错误: ' . $errstr]);
//     exit;
// });

// // 注册异常处理函数
// set_exception_handler(function($exception) {
//     header('Content-Type: application/json; charset=utf-8');
//     echo json_encode(['code' => 0, 'msg' => '服务器异常: ' . $exception->getMessage()]);
//     exit;
// });
$key = $_POST['key'] ?? '';
header('Content-Type: application/json; charset=utf-8');
if($key === ''){
    session_start();
    $loginUser = require_once 'auto-login.php';
    

    // 根据登陆权限加载配置
    if ($loginUser === 'admin' && $_SESSION['user_info']['role'] === 'admin') {
        // 包含配置文件
        require_once '../config/yml.php';
    }
    if ($loginUser === 'user' && $_SESSION['user_info']['role'] === 'user') {
        // 包含用户配置文件
        require_once '../config/yml.php';
        //获取当前用户名
        $username = $_SESSION['user_info']['username'];
        // 原图根目录
        $rootDir =  $wwwfile . '/' . $imgDir . '/' . $username; 
    }



    // 登录验证
    if (!isset($_SESSION['user_info'])) {
        echo json_encode(['code' => 0, 'msg' => '未登录']);
        exit;
    }

    // 身份密钥验证
    $userInfo = $_SESSION['user_info'];
    $currentDate = date('Y-m-d');

    // 验证身份密钥是否有效
    if (!isset($userInfo['identity_key']) || !isset($userInfo['login_date'])) {
        echo json_encode(['code' => 0, 'msg' => '身份验证失败，请重新登录']);
        exit;
    }

    // 验证登录日期是否有效（最多允许2天的有效期）
    $loginDate = $userInfo['login_date'];
    $dateDiff = strtotime($currentDate) - strtotime($loginDate);
    $daysDiff = $dateDiff / (60 * 60 * 24);

    if ($daysDiff > 2) {
        echo json_encode(['code' => 0, 'msg' => '登录已过期，请重新登录']);
        exit;
    }
}else{
    require_once 'tool/aesDecrypt.php';
    $userConfig = require_once '../config/config-user.php';
    $parsedData = parseMd5PwdAndUserFromKey($key, $customKey);
    if ($parsedData === false) {
        echo json_encode(['code' => 0, 'msg' => '身份验证失败，请重新登录']);
        exit;
    } else {
        //获取用户信息
        $userInfo = $userConfig[$parsedData['username']];
        $password = $userInfo['password'];
        // 生成身份密钥（密码与日期组合）
        $currentDate = date('Y-m-d');
        $identityKey = md5($password . $currentDate);
        //验证是否匹配
        if($identityKey !== $parsedData['md5_pwd']){
            echo json_encode(['code' => 0, 'msg' => '登录已过期，请重新登录']);
            exit;
        }
    }
    require_once '../config/yml.php';
    if($userInfo['role'] === 'user'){
        //获取当前用户名
        $username = $userInfo['username'];
        // 原图根目录
        $rootDir =  $wwwfile . '/' . $imgDir . '/' . $username; 
    }
}


$action = $_POST['action'] ?? '';
$result = ['code' => 0, 'msg' => ''];

/**
 * 创建目录（递归）
 */
function createDir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = $bytes;
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units)-1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * 生成缩略图（等比例缩放+留白填充，避免变形）
 */
function generateThumbnail($srcPath, $destPath, $width, $height = null) {
    if ($height === null) {
        $height = $width;
    }
    
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

/**
 * 路径验证（防止路径穿越，允许子文件夹和文件）
 * @param string $basePath 基础目录
 * @param string $targetPath 目标路径
 * @return bool
 */
function validatePath($basePath, $targetPath) {
    // 先确保基础目录存在
    if (!is_dir($basePath)) {
        mkdir($basePath, 0755, true);
    }
    // 解析真实路径（处理../）
    $realBase = realpath($basePath);
    // 对于文件路径，先获取目录路径进行验证
    $targetDir = is_file($targetPath) ? dirname($targetPath) : $targetPath;
    
    // 处理目标目录不存在的情况
    $realTargetDir = realpath($targetDir);
    if ($realTargetDir) {
        // 目录存在，直接验证
        return strpos($realTargetDir, $realBase) === 0 || $realTargetDir === $realBase;
    } else {
        // 目录不存在，需要构建真实路径并验证
        // 处理相对路径
        if (strpos($targetDir, DIRECTORY_SEPARATOR) !== 0 && strpos($targetDir, ':') === false) {
            // 相对路径，基于basePath构建完整路径
            $fullPath = $basePath . DIRECTORY_SEPARATOR . $targetDir;
        } else {
            // 绝对路径，直接使用
            $fullPath = $targetDir;
        }
        
        // 解析完整路径，处理../等
        $parts = explode(DIRECTORY_SEPARATOR, $fullPath);
        $cleanParts = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($cleanParts);
            } elseif ($part !== '.' && $part !== '') {
                $cleanParts[] = $part;
            }
        }
        
        // 重建路径
        $cleanPath = implode(DIRECTORY_SEPARATOR, $cleanParts);
        // 确保路径格式正确（添加驱动器号或根目录）
        if (strpos($targetDir, ':') !== false) {
            // Windows绝对路径
            $drive = substr($targetDir, 0, strpos($targetDir, ':')) . ':';
            $cleanPath = $drive . DIRECTORY_SEPARATOR . $cleanPath;
        } elseif (strpos($targetDir, DIRECTORY_SEPARATOR) === 0) {
            // 根目录路径
            $cleanPath = DIRECTORY_SEPARATOR . $cleanPath;
        }
        
        // 验证清理后的路径是否在basePath下
        $realCleanPath = realpath($cleanPath) ?: $cleanPath;
        return strpos($realCleanPath, $realBase) === 0 || $realCleanPath === $realBase;
    }
}

/**
 * 判断是否为绝对路径
 * @param string $path 路径
 * @return bool
 */
function is_absolute_path($path) {
    // Windows系统
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return (preg_match('#^[A-Z]:/#i', $path) || substr($path, 0, 1) === '\\');
    }
    // Unix/Linux系统
    return (strpos($path, '/') === 0);
}

/**
 * 相对路径转绝对路径
 * @param string $relativePath 相对路径
 * @param string $baseAbsolutePath 基础绝对路径
 * @return string
 */
function relativeToAbsolute($relativePath, $baseAbsolutePath) {
    // 先替换URL的/为系统分隔符
    $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    // 拼接绝对路径（如果relativePath已经是绝对路径，直接返回）
    if (is_absolute_path($relativePath)) {
        $absolutePath = realpath($relativePath);
    } else {
        $absolutePath = $baseAbsolutePath . DIRECTORY_SEPARATOR . $relativePath;
    }
    // 解析真实路径，处理../等
    return realpath($absolutePath) ?: $absolutePath;
}

// 1. 上传文件
if ($action === 'upload') {
    // 支持单文件上传（file）和多文件上传（files）
    $isSingleFile = isset($_FILES['file']); //单文件上传
    $isMultipleFiles = isset($_FILES['files']); //多文件上传
    // 验证上传文件是否是图片
    if ($isSingleFile) {
        $fileType = mime_content_type($_FILES['file']['tmp_name']);
        if (strpos($fileType, 'image/') !== 0) {
            $result['msg'] = '仅支持上传图片文件';
            echo json_encode($result);
            exit;
        }
    } elseif ($isMultipleFiles) {
        foreach ($_FILES['files']['tmp_name'] as $tmpName) {
            $fileType = mime_content_type($tmpName);
            if (strpos($fileType, 'image/') !== 0) {
                $result['msg'] = '仅支持上传图片文件';
                echo json_encode($result);
                exit;
            }
        }
    }

    if (!$isSingleFile && !$isMultipleFiles) {
        $result['msg'] = '未选择文件';
        echo json_encode($result);
        exit;
    }
    // 获取上传路径（相对于用户根目录）
    $uploadPath = $rootDir . DIRECTORY_SEPARATOR . ($_POST['upload_path'] ?? $rootDir);
    //替换目标路径中'..'，防止越级
    // $uploadPath = str_replace('..', '', $uploadPath);
    // 验证上传路径
    if (!validatePath($rootDir, $uploadPath)) {
        $result['msg'] = '上传路径异常';
        echo json_encode($result);
        exit;
    }

    // 创建目录（如果不存在）
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $successFiles = [];
    $errorFiles = [];

    // 处理单文件上传
    if ($isSingleFile) {
        $name = $_FILES['file']['name'];
        $tmpName = $_FILES['file']['tmp_name'];
        $size = $_FILES['file']['size'];
        $error = $_FILES['file']['error'];

        if ($error !== UPLOAD_ERR_OK) {
            $errorFiles[] = $name . '：上传失败（错误码：' . $error . '）';
        } else {
            // 获取日期（YYYY-MM-DD）
            $datePath = date('Y-m-d');
            // 生成唯一文件名（防止覆盖）
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $ext;
            $targetFile = $uploadPath . DIRECTORY_SEPARATOR . $datePath . '-'. $fileName;
            if (move_uploaded_file($tmpName, $targetFile)) {
                // 生成缩略图
                $thumbFile = str_replace($rootDir, $userInfo['thumb_root'], $targetFile);
                // 确保缩略图目录存在
                createDir(dirname($thumbFile));
                generateThumbnail($targetFile, $thumbFile, 200); // 200px缩略图
                $successFiles[] = $name;
            } else {
                $errorFiles[] = $name . '：保存失败';
            }
        }
    }
    // 处理多文件上传
    else if ($isMultipleFiles) {
        foreach ($_FILES['files']['name'] as $index => $name) {
            $tmpName = $_FILES['files']['tmp_name'][$index];
            $size = $_FILES['files']['size'][$index];
            $error = $_FILES['files']['error'][$index];

            if ($error !== UPLOAD_ERR_OK) {
                $errorFiles[] = $name . '：上传失败（错误码：' . $error . '）';
                continue;
            }
            // 获取日期（YYYY-MM-DD）
            $datePath = date('Y-m-d');
            // 生成唯一文件名（防止覆盖）
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $ext;
            $targetFile = $uploadPath . DIRECTORY_SEPARATOR . $datePath . '-' . $fileName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                // 生成缩略图
                $thumbFile = str_replace($rootDir, $userInfo['thumb_root'], $targetFile);
                // 确保缩略图目录存在
                createDir(dirname($thumbFile));
                generateThumbnail($targetFile, $thumbFile, 200); // 200px缩略图
                $successFiles[] = $name;
            } else {
                $errorFiles[] = $name . '：保存失败';
            }
        }
    }

    if (!empty($successFiles)) {
        $result['code'] = 1;
        $result['msg'] = '上传成功：' . implode(',', $successFiles);
    } else {
        $result['msg'] = '全部上传失败：' . implode(',', $errorFiles);
    }
    echo json_encode($result);
    exit;
}

// 2. 删除文件
if ($action === 'delete') {
    $filePath = $_POST['file_path'] ?? '';
    $thumbPath = $_POST['thumb_path'] ?? '';

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($filePath)) {
        $filePath = $rootDir . DIRECTORY_SEPARATOR . ltrim($filePath, '/');
    }
    if (!is_absolute_path($thumbPath)) {
        $thumbPath = $userInfo['thumb_root'] . DIRECTORY_SEPARATOR . ltrim($thumbPath, '/');
    }

    if (!validatePath($rootDir, $filePath)) {
        $result['msg'] = '非法文件路径';
        echo json_encode($result);
        exit;
    }
    // 验证缩略图路径与原图路径对应
    if (str_replace($rootDir, $userInfo['thumb_root'], $filePath) !== $thumbPath) {
        // 尝试与原图路径对应
        $thumbPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($filePath, '/');
    }
    
    // 删除原图
    $deleteSuccess = false;
    if (file_exists($filePath) && unlink($filePath)) {
        // 删除缩略图
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        $deleteSuccess = true;
    }

    if ($deleteSuccess) {
        $result['code'] = 1;
        $result['msg'] = '删除成功';
    } else {
        $result['msg'] = '删除失败（文件不存在或权限不足）';
    }
    echo json_encode($result);
    exit;
}

// 3. 移动文件
if ($action === 'move') {
    $sourcePath = $_POST['source_path'] ?? ''; // 原始完整路径（包含文件名）
    $targetPath = $_POST['target_path'] ?? ''; // 新的完整路径（包含文件名）
    //替换目标路径中'..'，防止越级
    $targetPath = str_replace('..', '', $targetPath);

    // 验证参数
    if (empty($sourcePath) || empty($targetPath)) {
        $result['msg'] = '源路径和目标路径不能为空';
        echo json_encode($result);
        exit;
    }

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($sourcePath)) {
        $sourcePath = $rootDir . DIRECTORY_SEPARATOR . ltrim($sourcePath, '/');
    }
    if (!is_absolute_path($targetPath)) {
        $targetPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($targetPath, '/');
    }

    // 创建目标路径不超过194长度
    if (strlen($targetPath) > 194) {
        $result['msg'] = '目标路径过长，移动失败';
        echo json_encode($result);
        exit;
    }
    //创建目标路径并且不超过用户根目录
    else {
        // 创建目标目录 （该创建有一定安全隐患，待修复）
        $targetDir = dirname($targetPath);
        if (!file_exists($targetDir)) {
            $mkdirSuccess = mkdir($targetDir, 0755, true);
            if (!$mkdirSuccess) {
                $result['msg'] = '目标目录创建失败，移动失败';
                echo json_encode($result);
                exit;
            }
        }
    }

    // 验证路径合法性
    if (!validatePath($rootDir, $sourcePath) || !validatePath($rootDir, dirname($targetPath))) {
        $result['msg'] = '非法路径（禁止跨目录移动）';
        echo json_encode($result);
        exit;
    }

    // 二次验证并创建目标目录
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        $mkdirSuccess = mkdir($targetDir, 0755, true);
        if (!$mkdirSuccess) {
            $result['msg'] = '目标目录创建失败，移动失败';
            echo json_encode($result);
            exit;
        }
    }
    
    // 检测目标文件是否已存在
    if (file_exists($targetPath)) {
        $result['msg'] = '目标文件已存在！';
        echo json_encode($result);
        exit;
    }

    // 检测源文件是否存在
    if (!file_exists($sourcePath)) {
        $result['msg'] = '源文件丢失！';
        echo json_encode($result);
        exit;
    }

    // 移动原图
    $moveSuccess = false;
    if (rename($sourcePath, $targetPath)) {
        // 移动缩略图
        $sourceThumb = str_replace($rootDir, $userInfo['thumb_root'], $sourcePath);
        $targetThumb = str_replace($rootDir, $userInfo['thumb_root'], $targetPath);
        
        // 确保缩略图目录存在
        $thumbDir = dirname($targetThumb);
        if (!file_exists($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        if (file_exists($sourceThumb)) {
            rename($sourceThumb, $targetThumb);
        }
        $moveSuccess = true;
    }

    if ($moveSuccess) {
        $result['code'] = 1;
        $result['msg'] = '移动成功';
    } else {
        $result['msg'] = '移动失败（权限不足或其他错误）';
    }
    echo json_encode($result);
    exit;
}

// 4. 获取文件列表（核心修复：递归加载子文件夹图片，支持分页）
if ($action === 'get_files') {
    $currentPath = $_POST['current_path'] ?? $rootDir;
    $page = intval($_POST['page'] ?? 1);
    $pageSize = intval($_POST['page_size'] ?? 12);
    
    if (!validatePath($rootDir, $currentPath)) {
        $result['msg'] = '非法路径';
        echo json_encode($result);
        exit;
    }

    // 递归遍历目录获取所有图片文件
    function getImagesRecursive($dir, $userRootPath, $userThumbRoot) {
        $files = [];
        $dirItems = scandir($dir);
        foreach ($dirItems as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                // 递归遍历子文件夹
                $subFiles = getImagesRecursive($itemPath, $userRootPath, $userThumbRoot);
                $files = array_merge($files, $subFiles);
            } elseif (is_file($itemPath)) {
                // 仅处理图片文件
                $ext = strtolower(pathinfo($itemPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // 缩略图路径应该与原图存放路径一致
                    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($itemPath, strlen($userRootPath)));
                    // 确保$relativePath是相对路径，不包含完整路径
                    if (strpos($relativePath, '/') === 0) {
                        $relativePath = ltrim($relativePath, '/');
                    }
                    // 确保$relativePath不包含/admin/等前缀
                    if (strpos($relativePath, 'admin/') === 0) {
                        $relativePath = ltrim(str_replace('admin/', '', $relativePath), '/');
                    }
                    // 移除路径中的双斜杠
                    $relativePath = preg_replace('/\/+/', '/', $relativePath);
                    $thumbPath = $userThumbRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
                    
                    // 确保目标目录存在
                    createDir(dirname($thumbPath));
                    
                    // 检查缩略图是否存在，标记是否需要生成
                    $thumbExists = file_exists($thumbPath);
                    $thumbNeedGenerate = !$thumbExists;
                    
                    // 生成与主页一致的URL路径（确保路径正确，没有多余的斜杠）
                    $fileUrl = '/' . rtrim($GLOBALS['imgDir'], '/') . '/' . ltrim($relativePath, '/');
                    $thumbUrl = $thumbExists ? 'thumbnails/' . ltrim($relativePath, '/') : 'loading.png';
                    // 确保$relativeThumbPath正确设置
                    $relativeThumbPath = ltrim($relativePath, '/');

                    $files[] = [
                        'name' => $item,
                        'path' => $relativePath,
                        'thumb_path' => $relativeThumbPath,
                        'url' => $fileUrl,
                        'thumb_url' => $thumbUrl,
                        'thumb_need_generate' => $thumbNeedGenerate,
                        'size' => formatFileSize(filesize($itemPath)),
                        'modified' => date('Y-m-d H:i:s', filemtime($itemPath))
                    ];
                }
            }
        }
        return $files;
    }

    // 执行递归遍历
    $files = getImagesRecursive($currentPath, $rootDir, $userInfo['thumb_root']);
    
    // 按修改时间排序（最新的在前）
    usort($files, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    // 处理分页
    $total = count($files);
    $offset = ($page - 1) * $pageSize;
    $paginatedFiles = array_slice($files, $offset, $pageSize);

    $result['code'] = 1;
    $result['data'] = $paginatedFiles;
    echo json_encode($result);
    exit;
}

// 5. 获取目录结构（用于移动弹窗的目录树）
if ($action === 'get_dirs') {
    $rootPath = $rootDir;
    
    // 递归获取目录结构
    function getDirsRecursive($dir, $rootPath) {
        $dirs = [];
        $dirItems = scandir($dir);
        foreach ($dirItems as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                // 生成相对路径（相对于网站根目录）
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($itemPath, strlen($rootPath)));
                $dirInfo = [
                    'name' => $item,
                    'path' => $relativePath,
                    'children' => getDirsRecursive($itemPath, $rootPath)
                ];
                $dirs[] = $dirInfo;
            }
        }
        return $dirs;
    }
    
    // 获取目录结构
    $dirs = getDirsRecursive($rootPath, $rootPath);
    
    $result['code'] = 1;
    $result['data'] = $dirs;
    echo json_encode($result);
    exit;
}

// 6. 生成缩略图
if ($action === 'generate_thumb') {
    $filePath = $_POST['file_path'] ?? '';
    
    // 将相对路径转换为绝对路径
    if (!is_absolute_path($filePath)) {
        $filePath = $rootDir . DIRECTORY_SEPARATOR . ltrim($filePath, '/');
    }
    
    // 验证路径合法性
    if (!validatePath($rootDir, $filePath)) {
        $result['msg'] = '非法文件路径';
        echo json_encode($result);
        exit;
    }
    
    // 生成缩略图路径
    $thumbPath = str_replace($rootDir, $userInfo['thumb_root'], $filePath);
    
    // 确保目标目录存在
    createDir(dirname($thumbPath));
    
    // 生成缩略图
    $success = generateThumbnail($filePath, $thumbPath, 220, 220);
    
    if ($success) {
        $result['code'] = 1;
        $result['msg'] = '缩略图生成成功';
    } else {
        $result['msg'] = '缩略图生成失败';
    }
    
    echo json_encode($result);
    exit;
}

// 7. 重命名文件
if ($action === 'rename') {
    $sourcePath = $_POST['source_path'] ?? '';// 原始完整路径（包含文件名）
    $targetPath = $_POST['target_path'] ?? '';// 新的完整路径（包含文件名）
    //替换目标路径中'..'，防止越级
    $targetPath = str_replace('..', '', $targetPath);
    // 验证新文件名不为空
    if (empty($targetPath)) {
        $result['msg'] = '新文件名不能为空';
        echo json_encode($result);
        exit;
    }
    //验证新文件名是图片格式
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $result['msg'] = '新文件名必须是图片格式（jpg、jpeg、png、gif、webp）';
        echo json_encode($result);
        exit;
    }
    //替换目标路径中'..'，防止越级
    $targetPath = str_replace('..', '', $targetPath);

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($sourcePath)) {
        $sourcePath = $rootDir . DIRECTORY_SEPARATOR . ltrim($sourcePath, '/');
    }
    if (!is_absolute_path($targetPath)) {
        $targetPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($targetPath, '/');
    }

    // 验证路径合法性
    if (!validatePath($rootDir, $sourcePath) || !validatePath($rootDir, dirname($targetPath))) {
        $result['msg'] = '非法路径（禁止跨目录重命名）';
        echo json_encode($result);
        exit;
    }

    // 创建目标目录
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    // 检测目标文件是否已存在
    if (file_exists($targetPath)) {
        $result['msg'] = '已存在！命名重复';
        echo json_encode($result);
        exit;
    }

    // 重命名原图
    $renameSuccess = false;
    if (file_exists($sourcePath) && rename($sourcePath, $targetPath)) {
        // 重命名缩略图
        $sourceThumb = str_replace($rootDir, $userInfo['thumb_root'], $sourcePath);
        $targetThumb = str_replace($rootDir, $userInfo['thumb_root'], $targetPath);
        if (file_exists($sourceThumb)) {
            rename($sourceThumb, $targetThumb);
        }
        $renameSuccess = true;
    }

    if ($renameSuccess) {
        $result['code'] = 1;
        $result['msg'] = '重命名成功';
    } else {
        $result['msg'] = '重命名失败（文件不存在或权限不足）';
    }
    echo json_encode($result);
    exit;
}

// 8. 创建目录
if ($action === 'create_dir') {
    $dirPath = $_POST['dir_path'] ?? '';
    //删除路径中'..'，防止越级
    $dirPath = str_replace('..', '', $dirPath);
    //验证目标目录不能为空
    if (empty($dirPath)) { 
        $result['msg'] = '不能为空';
        echo json_encode($result);
        exit;
    }

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($dirPath)) {
        $dirPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($dirPath, '/');
    }

    // 验证路径合法性
    if (!validatePath($rootDir, $dirPath)) {
        $result['msg'] = '非法路径（禁止跨目录创建）';
        echo json_encode($result);
        exit;
    }

    // 创建目录
    $createSuccess = false;
    if (!file_exists($dirPath)) {
        $createSuccess = mkdir($dirPath, 0755, true);
    } else {
        $createSuccess = true; // 目录已存在，视为成功
    }

    if ($createSuccess) {
        // 同时创建对应的缩略图目录
        $thumbDir = str_replace($rootDir, $userInfo['thumb_root'], $dirPath);
        if (!file_exists($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        $result['code'] = 1;
        $result['msg'] = '目录创建成功';
    } else {
        $result['msg'] = '目录创建失败（权限不足）';
    }
    echo json_encode($result);
    exit;
}

//9. 删除目录
if ($action === 'delete_dir') {
    $dirPath = $_POST['dir_path'] ?? '';// 目标目录路径
    //删除路径中'..'，防止越级
    $dirPath = str_replace('..', '', $dirPath);
    //验证目标目录不能为空
    if (empty($dirPath)) {
        $result['msg'] = '目标目录不能为空';
        echo json_encode($result);
        exit;
    }
    

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($dirPath)) {
        $dirPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($dirPath, '/');
    }

    // 验证路径合法性
    if (!validatePath($rootDir, $dirPath)) {
        $result['msg'] = '非法路径（禁止跨目录删除）';
        echo json_encode($result);
        exit;
    }

    // 递归删除目录
    function deleteDirRecursive($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (!deleteDirRecursive($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    // 删除目录
    $deleteSuccess = deleteDirRecursive($dirPath);
    // 同时删除对应的缩略图目录
    $thumbDir = str_replace($rootDir, $userInfo['thumb_root'], $dirPath);
    deleteDirRecursive($thumbDir);

    if ($deleteSuccess) {
        $result['code'] = 1;
        $result['msg'] = '目录删除成功';
    } else {
        $result['msg'] = '目录删除失败（权限不足或目录不为空）';
    }
    echo json_encode($result);
    exit;
}

// 10. 文件搜索
if ($action === 'search_files') {
    $keyword = $_POST['keyword'] ?? ''; // 搜索关键词
    $searchPath =  ($rootDir . DIRECTORY_SEPARATOR . $_POST['search_path'] ?? $rootDir); // 搜索路径，默认用户根目录
    $page = intval($_POST['page'] ?? 1); // 页码
    $pageSize = intval($_POST['page_size'] ?? 12); // 每页数量
    // 验证搜索关键词
    if (empty($keyword)) {
        $result['msg'] = '搜索关键词不能为空';
        echo json_encode($result);
        exit;
    }
    // 验证关键词合法性
    if (preg_match('/[\\\\\/:\*\?"<>\|]/', $keyword)) {
        $result['msg'] = '搜索关键词包含非法字符';
        echo json_encode($result);
        exit;
    }

    // 验证搜索路径
    if (!validatePath($rootDir, $searchPath)) {
        $result['msg'] = '搜索路径‘不存在’或‘非法’';
        echo json_encode($result);
        exit;
    }

    // 递归搜索文件
    function searchFilesRecursive($dir, $keyword, $userRootPath, $userThumbRoot) {
        $files = [];
        $dirItems = scandir($dir);
        foreach ($dirItems as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                // 递归搜索子目录
                $subFiles = searchFilesRecursive($itemPath, $keyword, $userRootPath, $userThumbRoot);
                $files = array_merge($files, $subFiles);
            } elseif (is_file($itemPath)) {
                // 检查文件名是否包含关键词
                if (stripos($item, $keyword) !== false) {
                    $ext = strtolower(pathinfo($itemPath, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($itemPath, strlen($userRootPath)));
                        if (strpos($relativePath, '/') === 0) {
                            $relativePath = ltrim($relativePath, '/');
                        }
                        $relativePath = preg_replace('/\/+/', '/', $relativePath);
                        $thumbPath = $userThumbRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
                        $thumbExists = file_exists($thumbPath);
                        $fileUrl = '/' . rtrim($GLOBALS['imgDir'], '/') . '/' . ltrim($relativePath, '/');
                        $thumbUrl = $thumbExists ? 'thumbnails/' . ltrim($relativePath, '/') : 'loading.png';
                        $relativeThumbPath = ltrim($relativePath, '/');
                        // 验证搜索文件是否为图片格式，不是则跳过
                        $fileType = mime_content_type($itemPath);
                        if (strpos($fileType, 'image/') !== 0) {
                            continue;
                        }

                        $files[] = [
                            'name' => $item,
                            'path' => $relativePath,
                            'thumb_path' => $relativeThumbPath,
                            'url' => $fileUrl,
                            'thumb_url' => $thumbUrl,
                            'size' => formatFileSize(filesize($itemPath)),
                            'modified' => date('Y-m-d H:i:s', filemtime($itemPath))
                        ];
                    }
                }
            }
        }
        return $files;
    }

    // 执行搜索
    $files = searchFilesRecursive($searchPath, $keyword, $rootDir, $userInfo['thumb_root']);

    // 按修改时间排序
    usort($files, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });

    // 处理分页
    $total = count($files);
    $offset = ($page - 1) * $pageSize;
    $paginatedFiles = array_slice($files, $offset, $pageSize);

    $result['code'] = 1;
    $result['data'] = $paginatedFiles;
    $result['total'] = $total;
    $result['msg'] = '搜索完成，找到 ' . $total . ' 个文件';
    echo json_encode($result);
    exit;
}

// 11. 目录大小计算
if ($action === 'get_dir_size') {
    $dirPath = $_POST['dir_path'] ?? $rootDir;

    // 将相对路径转换为绝对路径
    if (!is_absolute_path($dirPath)) {
        $dirPath = $rootDir . DIRECTORY_SEPARATOR . ltrim($dirPath, '/');
    }

    // 验证路径合法性
    if (!validatePath($rootDir, $dirPath)) {
        $result['msg'] = '非法目录路径';
        echo json_encode($result);
        exit;
    }

    // 递归计算目录大小
    function calculateDirSize($dir) {
        $size = 0;
        $dirItems = scandir($dir);
        foreach ($dirItems as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $size += calculateDirSize($itemPath);
            } elseif (is_file($itemPath)) {
                $size += filesize($itemPath);
            }
        }
        return $size;
    }

    // 计算目录大小
    $dirSize = calculateDirSize($dirPath);

    $result['code'] = 1;
    $result['data'] = [
        'size' => $dirSize,
        'formatted_size' => formatFileSize($dirSize)
    ];
    $result['msg'] = '目录大小计算完成';
    echo json_encode($result);
    exit;
}

// 未知操作
$result['msg'] = '未知操作：' . $action;
echo json_encode($result);