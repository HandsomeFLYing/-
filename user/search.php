<?php
// 登录验证+权限验证
session_start();
$loginUser = require_once  '../app/auto-login.php';
// 登录验证
if (!isset($_SESSION['user_info']) || $loginUser !== 'user') {
    header('Location: ../admin/');
    exit;
}

// 配置项
// 校验配置文件
if (!file_exists('../config/yml.php')) {
    die('<div style="text-align:center;margin:50px;color:#ff4444;">错误：配置文件缺失！</div>');
}
require_once '../config/yml.php';
//获取当前用户名
$username = $_SESSION['user_info']['username'];

// 工具函数：格式化文件大小
function formatFileSize($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 2) . ' ' . $units[$unitIndex];
}

// 获取搜索参数
$keyword = $_GET['keyword'] ?? '';
$page = intval($_GET['page'] ?? 1);
$pageSize = 12;

// 搜索结果
$searchResults = [];
$total = 0;
$errorMsg = '';

if ($keyword) {
    // 直接实现搜索逻辑
    try {
        $userInfo = $_SESSION['user_info'];
        $rootPath = $userInfo['root_path'];
        $thumbRoot = $userInfo['thumb_root'];
        
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
                            $fileUrl = '/' . rtrim($GLOBALS['imgDir'], '/') . '/' . $GLOBALS['username'] . '/' . ltrim($relativePath, '/');
                            $thumbUrl = $thumbExists ? 'thumbnails/' . $GLOBALS['username'] . '/' . ltrim($relativePath, '/') : 'loading.png';
                            $relativeThumbPath = ltrim($relativePath, '/');

                            $files[] = [
                                'name' => $item,
                                'path' => $relativePath,
                                'thumb_path' => $relativeThumbPath,
                                'url' => $fileUrl,
                                'thumb_url' => $thumbUrl,
                                'size' => formatFileSize(filesize($itemPath)),
                                'modified' => date('Y-m-d H:i:s', filemtime($itemPath)),
                                'thumb_need_generate' => $thumbExists ? 0 : 1
                            ];
                        }
                    }
                }
            }
            return $files;
        }
        
        // 执行搜索
        $files = searchFilesRecursive($rootPath, $keyword, $rootPath, $thumbRoot);
        
        // 按修改时间排序
        usort($files, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        // 计算总数
        $total = count($files);
        
        // 处理分页
        $offset = ($page - 1) * $pageSize;
        $searchResults = array_slice($files, $offset, $pageSize);
        
    } catch (Exception $e) {
        $errorMsg = '搜索失败：' . $e->getMessage();
    }
}

// 计算分页
$totalPages = ceil($total / $pageSize);
$startPage = max(1, $page - 2);
$endPage = min($totalPages, $startPage + 4);
$startPage = max(1, $endPage - 4);

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>搜索结果 - 简约图库</title>
    <!-- 样式表 -->
    <link rel="stylesheet" type="text/css" href="/Style/user.css">
    <style>
        .search-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .search-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .search-info {
            color: #666;
            font-size: 14px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
            gap: 5px;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            background-color: #fff;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        .pagination .active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .pagination .disabled {
            color: #999;
            pointer-events: none;
            background-color: #f5f5f5;
        }
        .no-results {
            text-align: center;
            padding: 80px 0;
            color: #6c757d;
        }
        .no-results .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- 左侧分类导航 -->
    <div class="category-nav" id="categoryNav">
        <div class="category-header">
            <h2>图库管理</h2>
            <a href="javascript:logout()" class="view-mode-btn">退出登录</a>
            <a href="index.php" class="view-mode-btn">返回首页</a>
        </div>
        
        <div class="category-breadcrumb">
            <span class="breadcrumb-item"><a href="index.php">首页</a></span>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item">搜索结果</span>
        </div>
        <ul class="category-list">
            <li class="category-item all-items">
                <a href="/user" class="category-link">
                    <span class="category-icon">🖼️</span>
                    全部图片
                </a>
            </li>
        </ul>
    </div>

    <!-- 右侧搜索结果展示区 -->
    <div class="gallery-container" id="galleryContainer">
        <!-- 搜索头部 -->
        <div class="search-header">
            <a href="index.php" class="back-link">← 返回首页</a>
            <h1>搜索结果</h1>
            <div class="search-info">
                关键词：<strong><?php echo htmlspecialchars($keyword); ?></strong>
                <?php if ($total > 0): ?>
                    | 找到 <strong><?php echo $total; ?></strong> 个结果
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    | <span style="color: #dc3545;"><?php echo htmlspecialchars($errorMsg); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 搜索结果 -->
        <div id="imageGridContainer">
            <?php if (empty($searchResults)): ?>
                <div class="no-results">
                    <div class="icon">🔍</div>
                    <div><?php echo $errorMsg ?: '没有找到匹配的图片'; ?></div>
                </div>
            <?php else: ?>
                <div class="image-grid">
                    <?php foreach ($searchResults as $image): ?>
                        <div class="image-item" 
                             data-name="<?php echo htmlspecialchars($image['name']); ?>"
                             data-path="<?php echo htmlspecialchars($image['path']); ?>"
                             data-size="<?php echo htmlspecialchars($image['size']); ?>"
                             data-modified="<?php echo htmlspecialchars($image['modified']); ?>"
                             data-url="<?php echo htmlspecialchars($image['url']); ?>"
                             data-thumb-need-generate="<?php echo $image['thumb_need_generate'] ? '1' : '0'; ?>">
                            <img src="/<?php echo htmlspecialchars($image['thumb_url']); ?>" alt="<?php echo htmlspecialchars($image['name']); ?>">
                            <div class="caption"><?php echo htmlspecialchars($image['name']); ?></div>
                            <div class="image-actions">
                                <button class="action-btn delete-btn" onclick="event.stopPropagation(); deleteImage('<?php echo htmlspecialchars($image['path']); ?>', '<?php echo htmlspecialchars($image['thumb_path']); ?>')">删除</button>
                                <button class="action-btn move-btn" onclick="event.stopPropagation(); openMoveModal('<?php echo htmlspecialchars($image['path']); ?>')">移动</button>
                                <button class="action-btn rename-btn" onclick="event.stopPropagation(); openRenameModal('<?php echo htmlspecialchars($image['path']); ?>', '<?php echo htmlspecialchars($image['name']); ?>')">修改</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 分页 -->
        <?php if ($total > 0): ?>
            <div class="pagination">
                <a href="search.php?keyword=<?php echo urlencode($keyword); ?>&page=1" class="<?php echo $page == 1 ? 'disabled' : ''; ?>">首页</a>
                <a href="search.php?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo max(1, $page - 1); ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>">上一页</a>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="search.php?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <a href="search.php?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>">下一页</a>
                <a href="search.php?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $totalPages; ?>" class="<?php echo $page == $totalPages ? 'disabled' : ''; ?>">末页</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 弹窗 -->
    <?php include '../app/part/modal.php'; ?>

    <!-- 脚本文件 -->


    <?php 
    include '../app/part/upsearch.php';
    include '../admin/part/file.php'; 
    ?>

</body>
</html>