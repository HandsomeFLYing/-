<?php 
require_once 'part/setTor.php';
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>系统设置 - 简约图库</title>
    <!-- 样式表 -->
    <link rel="stylesheet" type="text/css" href="/Style/admin.css">
    <link rel="stylesheet" type="text/css" href="/Style/settings.css">

</head>
<body>
    <!-- 移动端汉堡按钮 -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    
    <!-- 左侧分类导航 -->
    <div class="category-nav" id="categoryNav">
        <div class="category-header">
            <h2>图库管理</h2>
            <a href="javascript:logout()" class="view-mode-btn">退出登录</a>
            <a class="view-mode-btn">系统设置</a>
        </div>
        <div class="category-breadcrumb">
            <span class="breadcrumb-item"><a href="?path=&amp;view_mode=current_level&amp;sort_by=time_desc&amp;group_by=none">首页</a></span>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item"><a href="#">系统设置</a></span>
        </div>
        <ul class="category-list">
            <li class="category-item all-items">
                <a href="Aplog.php" class="category-link mobile-all-items-link">
                    <span class="category-icon">🖇️</span>
                    API工具
                </a>
                <a href="settings.php" class="category-link active">
                    <span class="category-icon">⚙️</span>
                    系统设置
                </a>
                <a href="index.php" class="category-link">
                    <span class="category-icon">🖼️</span>
                    全部图片
                </a>
            </li>
        </ul>
    </div>

    <!-- 右侧设置区域 -->
    <div class="gallery-container" id="galleryContainer">
        <div class="settings-container">
            <h1>系统设置</h1>
            
            <?php if (isset($successMessage)): ?>
                <div class="success-message">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <!-- 删除用户弹窗 -->
            <div class="modal" id="deleteModal" style="display: none;">
                <div class="modal-content">
                    <h3>确认删除用户</h3>
                    <p>确定要删除用户 <span id="deleteUsername"></span> 吗？此操作不可撤销。</p>
                    <div class="modal-buttons">
                        <button type="button" id="cancelDelete" class="btn btn-secondary">取消</button>
                        <button type="button" id="confirmDelete" class="btn btn-danger">确认删除</button>
                    </div>
                </div>
            </div>
            
            <!-- 编辑用户弹窗 -->
            <div class="modal" id="editModal" style="display: none;">
                <div class="modal-content">
                    <h3>编辑用户信息</h3>
                    <form id="editUserForm">
                        <input type="hidden" id="editUsername" name="edit_username">
                        <div class="form-group">
                            <label for="editPassword">新密码（留空则不修改）</label>
                            <input type="text" id="editPassword" name="edit_password">
                        </div>
                        <div class="form-group">
                            <label for="editRole">角色</label>
                            <select id="editRole" name="edit_role" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="user">普通用户</option>
                                <option value="admin">管理员</option>
                            </select>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" id="cancelEdit" class="btn btn-secondary">取消</button>
                            <button type="button" id="saveEdit" class="btn btn-primary">保存修改</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 设置标签页 -->
            <div class="settings-tabs">
                <div class="settings-tab active" data-tab="basic">基本设置</div>
                <div class="settings-tab" data-tab="data">用户管理</div>
            </div>
            
            <!-- 基础设置面板 -->
            <div class="settings-panel active" id="basic-panel">
                <form method="POST">
                    <h2>基本配置</h2>
                    
                    <div class="form-group">
                        <label for="image_dir">图片目录</label>
                        <input type="text" id="image_dir" name="image_dir" value="<?php echo htmlspecialchars($currentImageDir); ?>">
                        <small class="form-text text-muted">不建议设置修改，目前部分没适配</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="thumb_dir">缩略图目录</label>
                        <input type="text" id="thumb_dir" name="thumb_dir" value="<?php echo htmlspecialchars($currentThumbDir); ?>">
                        <small class="form-text text-muted">不建议设置修改，目前部分没适配</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_exts">支持的图片格式（逗号分隔）</label>
                        <input type="text" id="allowed_exts" name="allowed_exts" value="<?php echo htmlspecialchars($currentAllowedExts); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="thumb_width">缩略图宽度</label>
                        <input type="number" id="thumb_width" name="thumb_width" value="<?php echo htmlspecialchars($currentThumbWidth); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="thumb_height">缩略图高度</label>
                        <input type="number" id="thumb_height" name="thumb_height" value="<?php echo htmlspecialchars($currentThumbHeight); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="page_size">每次图片数量</label>
                        <input type="number" id="page_size" name="page_size" value="<?php echo htmlspecialchars($currentPageSize); ?>">
                        <small class="form-text text-muted">按照服务器配置设置，数字越高占用资源越多</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="use_database" name="use_database" value="true" <?php echo $currentUseDatabase ? 'checked' : ''; ?>>
                            <span class="checkbox-label">使用数据库存储（默认使用本地文件存储）</span>
                        </label>
                    </div>
                    
                    <button type="button" id="save_basic" class="btn btn-primary">保存设置</button>
                </form>
            </div>
            
            <!-- 管理数据面板 -->
            <div class="settings-panel" id="data-panel">
                <h2>用户管理</h2>
                
                <!-- 存储模式隐藏字段 -->
                <input type="hidden" id="storageMode" value="<?php echo $currentUseDatabase ? 'database' : 'file'; ?>">
                
                <!-- 用户列表 -->
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 读取用户数据
                        $users = [];
                        if ($currentUseDatabase) {
                            // 从数据库读取用户数据
                            try {
                                require_once '../config/sql/config.php';
                                
                                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
                                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                $stmt = $pdo->prepare("SELECT username, role FROM {$db_config['table']}");
                                $stmt->execute();
                                $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($usersData as $user) {
                                    $users[$user['username']] = [
                                        'role' => $user['role']
                                    ];
                                }
                            } catch (PDOException $e) {
                                // 数据库错误，从本地文件读取
                                if (file_exists('../config/sql/user.php')) {
                                    $users = require '../config/sql/user.php';
                                }
                            }
                        } else {
                            // 从本地文件读取用户数据
                            if (file_exists('../config/sql/user.php')) {
                                $users = require '../config/sql/user.php';
                            }
                        }
                        
                        // 显示用户列表
                        if (!empty($users)) {
                            foreach ($users as $username => $userInfo) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($username) . '</td>';
                                echo '<td>' . htmlspecialchars($userInfo['role']) . '</td>';
                                echo '<td>';
                                echo '<div class="action-buttons">';
                                echo '<button class="btn btn-sm btn-secondary edit-user" data-username="' . htmlspecialchars($username) . '" data-role="' . htmlspecialchars($userInfo['role']) . '">编辑</button>';
                                echo '<button class="btn btn-sm btn-danger delete-user" data-username="' . htmlspecialchars($username) . '">删除</button>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr>';
                            echo '<td colspan="3" style="text-align: center;">暂无用户数据</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                
                <!-- 添加用户表单 -->
                <h3 style="margin-top: 30px;">添加用户</h3>
                <form id="addUserForm" method="POST">
                    <div class="form-group">
                        <label for="new_username">用户名</label>
                        <input type="text" id="new_username" name="new_username">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">密码</label>
                        <input type="text" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_role">角色</label>
                        <select id="new_role" name="new_role" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    
                    <button type="button" id="add_user" class="btn btn-primary">添加用户</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 脚本文件 -->
    <?php require_once 'part/setScr.php'; ?>
</body>
</html>