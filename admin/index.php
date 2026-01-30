<?php
session_start();
// 登录验证+权限验证
$loginUser = require_once  '../app/auto-login.php';
if (!isset($_SESSION['user_info']) || $loginUser === null ) {
    header('Location: login.php');
    exit;
}

// 配置项
// 校验配置文件
if (!file_exists('../config/yml.php')) {
    die('<div style="text-align:center;margin:50px;color:#ff4444;">错误：配置文件缺失！</div>');
}
require_once '../config/yml.php';
if ($loginUser === 'user'){
//获取当前用户名
$username = $_SESSION['user_info']['username'];
// 图片存放目录
$imgDir = $imgDir. '/' . $username; 
// 图片存放目录
$rootDir =  $wwwfile . '/' . $imgDir; 
}
// 校验主程序文件
if (!file_exists('../app/part/admin.php')) {
    die('<div style="text-align:center;margin:50px;color:#ff4444;">错误：主程序文件缺失！</div>');
}
require_once '../app/part/admin.php';

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>简约图库</title>
    <!-- 样式表 -->
    <link rel="stylesheet" type="text/css" href="/Style/<?php echo $loginUser ?>.css">
</head>
<body>
    <!-- 移动端汉堡按钮 -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    
    <!-- 左侧分类导航 -->
    <div class="category-nav <?php echo $viewMode === 'tree_view' ? 'tree-view' : ''; ?>" id="categoryNav">
        <div class="category-header">
            <h2>图库管理</h2>
            <a href="javascript:logout()" class="view-mode-btn">退出登录</a>
            <a href="?path=<?php echo urlencode($currentPath); ?>&view_mode=<?php echo $viewMode === 'current_level' ? 'tree_view' : 'current_level'; ?>&sort_by=<?php echo urlencode($sortBy); ?>&group_by=<?php echo urlencode($groupBy); ?>" class="view-mode-btn">
                <?php echo $viewMode === 'current_level' ? '简单分类' : '树状分类'; ?>
            </a>
        </div>
        
        <div class="category-breadcrumb">
            <span class="breadcrumb-item"><a href="?path=&view_mode=<?php echo urlencode($viewMode); ?>&sort_by=<?php echo urlencode($sortBy); ?>&group_by=<?php echo urlencode($groupBy); ?>">首页</a></span>
            <?php foreach ($breadcrumb as $index => $item): ?>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item">
                    <a href="?path=<?php echo urlencode($item['path']); ?>&view_mode=<?php echo urlencode($viewMode); ?>&sort_by=<?php echo urlencode($sortBy); ?>&group_by=<?php echo urlencode($groupBy); ?>">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                </span>
            <?php endforeach; ?>
        </div>
        
        <ul class="category-list">
            <li class="category-item all-items">
                <?php if ($_SESSION['user_info']['role'] === 'admin'): ?>
                <a href="Aplog.php" class="category-link mobile-all-items-link">
                    <span class="category-icon">🖇️</span>
                    API工具
                </a>
                <a href="settings.php" class="category-link">
                    <span class="category-icon">⚙️</span>
                    系统设置
                </a>
                <?php endif; ?>
                <a href="?path=&view_mode=<?php echo urlencode($viewMode); ?>&sort_by=<?php echo urlencode($sortBy); ?>&group_by=<?php echo urlencode($groupBy); ?>" class="category-link <?php echo $currentPath === '' ? 'active' : ''; ?>">
                    <span class="category-icon">🖼️</span>
                    全部图片
                </a>
            </li>
            
            <?php
            if ($viewMode === 'current_level') {
                if (!empty($currentLevelCategories)) {
                    foreach ($currentLevelCategories as $category) {
                        $isActive = $category['path'] === $currentPath;
                        echo '<li class="category-item level-' . $category['level'] . '">';
                        echo '<a href="?path=' . urlencode($category['path']) . '&view_mode=' . urlencode($viewMode) . '&sort_by=' . urlencode($sortBy) . '&group_by=' . urlencode($groupBy) . '" class="category-link ' . ($isActive ? 'active' : '') . '">';
                        echo '<span class="category-icon">🟰</span>';
                        echo htmlspecialchars($category['name']);
                        echo '</a>';
                        echo '</li>';
                    }
                } else {
                    echo '<li class="category-item"><div class="category-link" style="color:#6c757d;cursor:default;">';
                    echo '<span class="category-icon">❌</span>';
                    echo '没有更多了';
                    echo '</div></li>';
                }
            } else {
                function renderFullTree($tree, $currentPath, $viewMode, $sortBy, $groupBy) {
                    foreach ($tree as $node) {
                        $isActive = $node['path'] === $currentPath;
                        echo '<li class="category-item level-' . $node['level'] . '">';
                        echo '<a href="?path=' . urlencode($node['path']) . '&view_mode=' . urlencode($viewMode) . '&sort_by=' . urlencode($sortBy) . '&group_by=' . urlencode($groupBy) . '" class="category-link ' . ($isActive ? 'active' : '') . '">';
                        echo '<span class="category-icon">↳📁</span>';
                        echo htmlspecialchars($node['name']);
                        echo '</a>';
                        if (!empty($node['children'])) {
                            echo '<ul class="category-list">';
                            renderFullTree($node['children'], $currentPath, $viewMode, $sortBy, $groupBy);
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }
                renderFullTree($folderTree, $currentPath, $viewMode, $sortBy, $groupBy);
            }
            ?>
        </ul>
    </div>

    <!-- 右侧图片展示区 -->
    <div class="gallery-container" id="galleryContainer">
        <!-- 上传区域 -->
        <div class="upload-area" id="uploadArea">
            <div style="font-size: 48px; margin-bottom: 15px;">📤</div>
            <div style="font-size: 16px; margin-bottom: 10px;">点击或拖拽文件到此处上传</div>
            <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
            <button class="upload-btn" id="selectFileBtn">选择文件</button>
            <button class="upload-btn" id="uploadBtn" style="display: none; background: #28a745;">开始上传</button>
        </div>
        <!-- 头部控制栏 -->
        <div class="gallery-header">
            <h1><?php echo $currentPath === '' ? '全部图片' : htmlspecialchars($currentPath); ?></h1>
            
            <div class="gallery-controls">
                <!-- 搜索 -->
                <input type="text" id="searchInput" class="control-select" placeholder="🔎搜索图片名称...可回车" oninput="onSearchInput()" onkeypress="handleSearchKeyPress(event)">
                <!-- 排序与分组 -->
                <select class="control-select" id="sortBySelect" onchange="onSortOrGroupChange()">
                    <option value="time_desc" <?php echo $sortBy === 'time_desc' ? 'selected' : ''; ?>>按时间降序（新→旧）</option>
                    <option value="time_asc" <?php echo $sortBy === 'time_asc' ? 'selected' : ''; ?>>按时间升序（旧→新）</option>
                </select>
                
                <select class="control-select" id="groupBySelect" onchange="onSortOrGroupChange()">
                    <option value="none" <?php echo $groupBy === 'none' ? 'selected' : ''; ?>>不分组</option>
                    <option value="year" <?php echo $groupBy === 'year' ? 'selected' : ''; ?>>按年分组</option>
                    <option value="month" <?php echo $groupBy === 'month' ? 'selected' : ''; ?>>按月分组</option>
                    <option value="day" <?php echo $groupBy === 'day' ? 'selected' : ''; ?>>按日分组</option>
                </select>
            </div>
        </div>
        <!-- 图片网格 -->
        <div id="imageGridContainer">
            <?php if (empty($pageResult['grouped_images']) || (count($pageResult['grouped_images']) === 1 && empty($pageResult['grouped_images']['all']))): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 80px 0; color: #6c757d;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🖼️</div>
                    <div style="font-size: 16px;">暂无图片</div>
                </div>
            <?php else: ?>
                <?php foreach ($pageResult['grouped_images'] as $groupName => $images): ?>
                    <?php if ($groupBy !== 'none'): ?>
                        <div class="image-group">
                            <?php 
                            $groupTitle = '';
                            if ($groupBy === 'year') {
                                $groupTitle = $groupName . '年（' . count($images) . '张）';
                            } elseif ($groupBy === 'month') {
                                $groupTitle = $groupName . '（' . count($images) . '张）';
                            } elseif ($groupBy === 'day') {
                                $groupTitle = $groupName . '（' . count($images) . '张）';
                            }
                            ?>
                            <div class="group-title"><?php echo $groupTitle; ?></div>
                            <div class="image-grid group-grid">
                                <?php foreach ($images as $image): ?>
                                    <div class="image-item" 
                                         data-name="<?php echo htmlspecialchars($image['name']); ?>"
                                         data-path="<?php echo htmlspecialchars($image['path']); ?>"
                                         data-size="<?php echo formatFileSize($image['size']); ?>"
                                         data-dimensions="<?php echo htmlspecialchars($image['dimensions']); ?>"
                                         data-modified="<?php echo htmlspecialchars($image['modified']); ?>"
                                         data-url="<?php echo htmlspecialchars($image['url']); ?>"
                                         data-thumb-need-generate="<?php echo $image['thumb_need_generate'] ? '1' : '0'; ?>">
                                        <img src="<?php echo htmlspecialchars($image['thumb_url']); ?>" alt="<?php echo htmlspecialchars($image['name']); ?>">
                                        <div class="caption"><?php echo htmlspecialchars($image['name']); ?></div>
                                        <div class="image-actions">
                                            <button class="action-btn delete-btn" onclick="event.stopPropagation(); deleteImage('<?php echo htmlspecialchars($image['file_url']); ?>', '<?php echo htmlspecialchars($image['thumb_url']); ?>')">删除</button>
                                            <button class="action-btn move-btn" onclick="event.stopPropagation(); openMoveModal('<?php echo htmlspecialchars($image['file_url']); ?>')">移动</button>
                                            <button class="action-btn rename-btn" onclick="event.stopPropagation(); openRenameModal('<?php echo htmlspecialchars($image['file_url']); ?>', '<?php echo htmlspecialchars($image['name']); ?>')">修改</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="image-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="image-item" 
                                     data-name="<?php echo htmlspecialchars($image['name']); ?>"
                                     data-path="<?php echo htmlspecialchars($image['path']); ?>"
                                     data-size="<?php echo formatFileSize($image['size']); ?>"
                                     data-dimensions="<?php echo htmlspecialchars($image['dimensions']); ?>"
                                     data-modified="<?php echo htmlspecialchars($image['modified']); ?>"
                                     data-url="<?php echo htmlspecialchars($image['url']); ?>"
                                     data-thumb-need-generate="<?php echo $image['thumb_need_generate'] ? '1' : '0'; ?>">
                                    <img src="<?php echo htmlspecialchars($image['thumb_url']); ?>" alt="<?php echo htmlspecialchars($image['name']); ?>">
                                    <div class="caption"><?php echo htmlspecialchars($image['name']); ?></div>
                                    <div class="image-actions">
                                        <button class="action-btn delete-btn" onclick="event.stopPropagation(); deleteImage('<?php echo htmlspecialchars($image['file_url']); ?>', '<?php echo htmlspecialchars($image['thumb_url']); ?>')">删除</button>
                                        <button class="action-btn move-btn" onclick="event.stopPropagation(); openMoveModal('<?php echo htmlspecialchars($image['file_url']); ?>')">移动</button>
                                        <button class="action-btn rename-btn" onclick="event.stopPropagation(); openRenameModal('<?php echo htmlspecialchars($image['file_url']); ?>', '<?php echo htmlspecialchars($image['name']); ?>')">修改</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="loading" id="loading">
            <span>加载中...</span>
        </div>
        
        <div class="no-more" id="noMore">
            <span>没有更多图片了</span>
        </div>
        
        <!-- 回到顶部按钮 -->
        <button class="back-to-top" id="backToTopBtn" title="回到顶部">
            ↑
        </button>
    </div>

    <!-- 弹窗 -->
    <?php include '../app/part/modal.php'; ?>

    <!-- 脚本文件 -->
    <?php include 'part/script.php'; ?>
    <?php include 'part/file.php'; ?>
    <?php include 'part/upload.php'; ?>

    
    
    
</body>
</html>