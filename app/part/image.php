<?php
//防止url为image.php直接访问
if (basename($_SERVER['PHP_SELF']) === 'image.php') {
    header('Location: 404.php');
    exit;
}



// 安全检查：防止目录遍历漏洞
if ($safeCurrentPath === false || strpos($safeCurrentPath, realpath($rootDir)) !== 0) {
    $currentPath = '';
    $safeCurrentPath = realpath($rootDir);
}

// 检查 GD 库是否启用
if (!extension_loaded('gd') && !extension_loaded('gd2')) {
    die('错误：请启用 PHP GD 扩展以生成缩略图');
}

/**
 * 创建目录（递归）- 修复 "mkdir(): File exists" 警告
 * @param string $dir 目标目录
 */
function createDir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

/**
 * 构建文件夹树形结构 - 修复：定义$fullCurrentDir变量，解决分类不显示问题
 */
function buildFolderTree($baseDir, $currentDir = '') {
    $tree = [];
    // 修复点：定义缺失的$fullCurrentDir变量
    $fullCurrentDir = rtrim($baseDir . '/' . $currentDir, '/');
    
    if (!is_dir($fullCurrentDir)) return $tree;
    
    $items = scandir($fullCurrentDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullItemPath = $fullCurrentDir . '/' . $item;
        $relativeItemPath = $currentDir === '' ? $item : $currentDir . '/' . $item;
        
        if (is_dir($fullItemPath)) {
            $folderNode = [
                'name' => $item,
                'path' => $relativeItemPath,
                'full_path' => $fullItemPath,
                'level' => substr_count($relativeItemPath, '/'),
                'parent' => $currentDir,
                'children' => []
            ];
            $folderNode['children'] = buildFolderTree($baseDir, $relativeItemPath);
            $tree[] = $folderNode;
        }
    }
    return $tree;
}

/**
 * 获取指定目录下的图片（仅判断缩略图是否存在，不生成，避免超时）
 */
function getImagesInDir($dir, $relativePath = '', $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight) {
    $images = [];
    if (!is_dir($dir)) return $images;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $dir . '/' . $item;
        $relativeFullPath = $relativePath === '' ? $item : $relativePath . '/' . $item;
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        
        if (is_file($fullPath) && in_array($ext, $allowedExts)) {
            $fileMtime = filemtime($fullPath);
            $imageInfo = getimagesize($fullPath);
            
            // 1. 构建路径（仅判断缩略图是否存在，不生成）
            $thumbRelativePath = $relativeFullPath;
            $thumbFullPath = $thumbDir . '/' . $relativeFullPath;
            $defaultThumbUrl = 'loading.png'; // 默认缩略图URL
            // 缩略图存在则用缩略图，否则用默认图
            $thumbUrl = file_exists($thumbFullPath) ? 'thumbnails/' . $thumbRelativePath : $defaultThumbUrl;
            $originalUrl = $GLOBALS['imgDir'] . '/' . $relativeFullPath . '#图片'; // 原图URL

            $images[] = [
                'name' => $item,
                'path' => $relativeFullPath,
                'full_path' => $fullPath,
                'url' => $originalUrl, // 原图URL（预览用）
                'thumb_url' => $thumbUrl, // 缩略图URL（小图显示用）
                'thumb_need_generate' => !file_exists($thumbFullPath), // 标记是否需要生成缩略图
                'size' => filesize($fullPath),
                'dimensions' => $imageInfo ? $imageInfo[0] . '×' . $imageInfo[1] : '未知',
                'modified' => date('Y-m-d H:i:s', $fileMtime),
                'modified_time' => $fileMtime,
                'modified_year' => date('Y', $fileMtime),
                'modified_month' => date('Y-m', $fileMtime),
                'modified_day' => date('Y-m-d', $fileMtime)
            ];
        }
    }
    return $images;
}

/**
 * 递归获取所有目录下的图片
 */
function getAllImagesRecursive($baseDir, $currentDir = '', $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight) {
    $allImages = [];
    $fullCurrentDir = $baseDir . '/' . $currentDir;
    
    if (!is_dir($fullCurrentDir)) return $allImages;
    
    $currentImages = getImagesInDir($fullCurrentDir, $currentDir, $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight);
    $allImages = array_merge($allImages, $currentImages);
    
    $items = scandir($fullCurrentDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullItemPath = $fullCurrentDir . '/' . $item;
        $relativeItemPath = $currentDir === '' ? $item : $currentDir . '/' . $item;
        
        if (is_dir($fullItemPath)) {
            $subDirImages = getAllImagesRecursive($baseDir, $relativeItemPath, $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight);
            $allImages = array_merge($allImages, $subDirImages);
        }
    }
    return $allImages;
}

/**
 * 按时间排序
 */
function sortImagesByTime($images, $sortBy) {
    if ($sortBy === 'time_asc') {
        usort($images, function($a, $b) {
            return $a['modified_time'] - $b['modified_time'];
        });
    } else {
        usort($images, function($a, $b) {
            return $b['modified_time'] - $a['modified_time'];
        });
    }
    return $images;
}

/**
 * 分组图片
 */
function groupImages($images, $groupBy) {
    $groupedImages = [];
    
    if ($groupBy === 'none') {
        $groupedImages['all'] = $images;
    } elseif ($groupBy === 'year') {
        foreach ($images as $image) {
            $year = $image['modified_year'];
            $groupedImages[$year][] = $image;
        }
        krsort($groupedImages);
    } elseif ($groupBy === 'month') {
        foreach ($images as $image) {
            $month = $image['modified_month'];
            $groupedImages[$month][] = $image;
        }
        krsort($groupedImages);
    } elseif ($groupBy === 'day') {
        foreach ($images as $image) {
            $day = $image['modified_day'];
            $groupedImages[$day][] = $image;
        }
        krsort($groupedImages);
    }
    
    return $groupedImages;
}

/**
 * 分页获取图片
 */
function getImagesByPage($allImages, $page, $pageSize, $groupBy) {
    $groupedImages = groupImages($allImages, $groupBy);
    $flattenedImages = [];
    
    foreach ($groupedImages as $groupName => $images) {
        foreach ($images as $image) {
            $image['group_name'] = $groupName;
            $flattenedImages[] = $image;
        }
    }
    
    $total = count($flattenedImages);
    $totalPages = ceil($total / $pageSize);
    $offset = ($page - 1) * $pageSize;
    $currentPageFlattened = array_slice($flattenedImages, $offset, $pageSize);
    
    $currentPageGrouped = [];
    foreach ($currentPageFlattened as $image) {
        $groupName = $image['group_name'];
        unset($image['group_name']);
        $currentPageGrouped[$groupName][] = $image;
    }
    
    return [
        'grouped_images' => $currentPageGrouped,
        'flattened_images' => $currentPageFlattened,
        'hasMore' => $page < $totalPages,
        'currentPage' => $page,
        'totalPages' => $totalPages
    ];
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

// 1. 初始化缩略图目录（仅创建目录，不生成缩略图）
createDir($thumbDir);

// 2. 构建文件夹树
$folderTree = buildFolderTree($rootDir);

// 3. 获取图片列表（仅判断缩略图是否存在，不生成）
$allImages = [];
if ($currentPath === '') {
    $allImages = getAllImagesRecursive($rootDir, '', $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight);
} else {
    $allImages = getImagesInDir($safeCurrentPath, $currentPath, $allowedExts, $rootDir, $thumbDir, $thumbWidth, $thumbHeight);
}

// 4. 排序 + 分页
$allImages = sortImagesByTime($allImages, $sortBy);
$pageResult = getImagesByPage($allImages, $page, $pageSize, $groupBy);

// AJAX 请求处理
if ($isAjax) {
    header('Content-Type: application/json');
    $html = '';
    
    foreach ($pageResult['grouped_images'] as $groupName => $images) {
        if ($groupBy !== 'none') {
            $groupTitle = '';
            if ($groupBy === 'year') {
                $groupTitle = $groupName . '年（' . count($images) . '张）';
            } elseif ($groupBy === 'month') {
                $groupTitle = $groupName . '（' . count($images) . '张）';
            } elseif ($groupBy === 'day') {
                $groupTitle = $groupName . '（' . count($images) . '张）';
            }
            $html .= '<div class="image-group">';
            $html .= '<div class="group-title">' . $groupTitle . '</div>';
            $html .= '<div class="image-grid group-grid">';
        } else {
            if (empty($html)) $html .= '<div class="image-grid">';
        }
        
        foreach ($images as $image) {
            // 渲染图片项，添加需要生成缩略图的标记
            $html .= '<div class="image-item" 
                data-name="' . htmlspecialchars($image['name']) . '"
                data-path="' . htmlspecialchars($image['path']) . '"
                data-size="' . formatFileSize($image['size']) . '"
                data-dimensions="' . htmlspecialchars($image['dimensions']) . '"
                data-modified="' . htmlspecialchars($image['modified']) . '"
                data-url="' . htmlspecialchars($image['url']) . '"
                data-thumb-need-generate="' . ($image['thumb_need_generate'] ? '1' : '0') . '">
                <img src="' . htmlspecialchars($image['thumb_url']) . '" alt="' . htmlspecialchars($image['name']) . '">
                <div class="caption">' . htmlspecialchars($image['name']) . '</div>
            </div>';
        }
        
        if ($groupBy !== 'none') $html .= '</div></div>';
    }
    
    if ($groupBy === 'none' && !empty($html)) $html .= '</div>';
    
    echo json_encode([
        'html' => $html,
        'hasMore' => $pageResult['hasMore'],
        'currentPage' => $pageResult['currentPage']
    ]);
    exit;
}

// 面包屑导航
function getCategoryBreadcrumb($currentPath) {
    $breadcrumb = [];
    if ($currentPath === '') return $breadcrumb;
    $pathParts = explode('/', $currentPath);
    $tempPath = '';
    foreach ($pathParts as $part) {
        $tempPath = $tempPath === '' ? $part : $tempPath . '/' . $part;
        $breadcrumb[] = ['name' => $part, 'path' => $tempPath];
    }
    return $breadcrumb;
}

$breadcrumb = getCategoryBreadcrumb($currentPath);

// 获取当前级分类
function getCurrentLevelChildren($folderTree, $currentPath) {
    $result = [];
    $findChildren = function($tree, $targetPath) use (&$findChildren, &$result) {
        foreach ($tree as $node) {
            if ($node['path'] === $targetPath) {
                $result = $node['children'];
                return true;
            }
            if (!empty($node['children']) && $findChildren($node['children'], $targetPath)) {
                return true;
            }
        }
        return false;
    };
    if ($currentPath === '') {
        $result = $folderTree;
    } else {
        $findChildren($folderTree, $currentPath);
    }
    return $result;
}

$currentLevelCategories = getCurrentLevelChildren($folderTree, $currentPath);
?>
