<?php
// 用户数据库操作文件
// 启动会话
session_start();

// 检查是否有权限访问
$loginUser = require_once  '../app/auto-login.php';
if (!isset($_SESSION['user_info']) || $loginUser !== 'admin') {
    header('Location: login.php');
    exit;
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否是 JSON 请求
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        // 读取 JSON 数据
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        // 加载数据库配置
        require_once '../config/sql/mysql.php';
        
        // 处理删除用户
        if (isset($data['action']) && $data['action'] === 'delete_user') {
            $username = $data['username'];
            
            // 连接数据库
            try {
                $db_config = [
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'tuk_db',
                    'username' => 'tuk_user',
                    'password' => 'your_password',
                    'table' => 'users'
                ];
                
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 删除用户
                $stmt = $pdo->prepare("DELETE FROM {$db_config['table']} WHERE username = ?");
                $result = $stmt->execute([$username]);
                
                if ($result) {
                    // 返回 JSON 响应
                    echo json_encode([
                        'success' => true,
                        'message' => '用户删除成功！'
                    ]);
                } else {
                    // 删除失败
                    echo json_encode([
                        'success' => false,
                        'message' => '用户删除失败！'
                    ]);
                }
            } catch (PDOException $e) {
                // 数据库错误
                echo json_encode([
                    'success' => false,
                    'message' => '数据库错误：' . $e->getMessage()
                ]);
            }
            exit;
        } else if (isset($data['action']) && $data['action'] === 'edit_user') {
            // 处理编辑用户
            $username = $data['username'];
            $password = $data['password'];
            $role = $data['role'];
            
            // 连接数据库
            try {
                $db_config = [
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'tuk_db',
                    'username' => 'tuk_user',
                    'password' => 'your_password',
                    'table' => 'users'
                ];
                
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 更新用户信息
                if (!empty($password)) {
                    // 更新密码和角色
                    $stmt = $pdo->prepare("UPDATE {$db_config['table']} SET password = ?, role = ? WHERE username = ?");
                    $result = $stmt->execute([md5($password), $role, $username]);
                } else {
                    // 只更新角色
                    $stmt = $pdo->prepare("UPDATE {$db_config['table']} SET role = ? WHERE username = ?");
                    $result = $stmt->execute([$role, $username]);
                }
                
                if ($result) {
                    // 返回 JSON 响应
                    echo json_encode([
                        'success' => true,
                        'message' => '用户编辑成功！'
                    ]);
                } else {
                    // 更新失败
                    echo json_encode([
                        'success' => false,
                        'message' => '用户编辑失败！'
                    ]);
                }
            } catch (PDOException $e) {
                // 数据库错误
                echo json_encode([
                    'success' => false,
                    'message' => '数据库错误：' . $e->getMessage()
                ]);
            }
            exit;
        } else if (isset($data['action']) && $data['action'] === 'add_user') {
            // 处理添加用户
            $username = $data['username'];
            $password = $data['password'];
            $role = $data['role'];
            
            // 连接数据库
            try {
                $db_config = [
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'tuk_db',
                    'username' => 'tuk_user',
                    'password' => 'your_password',
                    'table' => 'users'
                ];
                
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 检查用户是否存在
                $stmt = $pdo->prepare("SELECT * FROM {$db_config['table']} WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    // 用户已存在
                    echo json_encode([
                        'success' => false,
                        'message' => '用户已存在！'
                    ]);
                    exit;
                }
                
                // 添加用户
                $stmt = $pdo->prepare("INSERT INTO {$db_config['table']} (username, password, role) VALUES (?, ?, ?)");
                $result = $stmt->execute([$username, md5($password), $role]);
                
                if ($result) {
                    // 返回 JSON 响应
                    echo json_encode([
                        'success' => true,
                        'message' => '用户添加成功！'
                    ]);
                } else {
                    // 添加失败
                    echo json_encode([
                        'success' => false,
                        'message' => '用户添加失败！'
                    ]);
                }
            } catch (PDOException $e) {
                // 数据库错误
                echo json_encode([
                    'success' => false,
                    'message' => '数据库错误：' . $e->getMessage()
                ]);
            }
            exit;
        }
    } else {
        // 处理传统表单提交（添加用户）
        if (isset($_POST['add_user'])) {
            $newUsername = $_POST['new_username'];
            $newPassword = $_POST['new_password'];
            $newRole = $_POST['new_role'];
            
            // 连接数据库
            try {
                $db_config = [
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'tuk_db',
                    'username' => 'tuk_user',
                    'password' => 'your_password',
                    'table' => 'users'
                ];
                
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 检查用户是否存在
                $stmt = $pdo->prepare("SELECT * FROM {$db_config['table']} WHERE username = ?");
                $stmt->execute([$newUsername]);
                if ($stmt->rowCount() > 0) {
                    // 用户已存在
                    $successMessage = '用户已存在！';
                } else {
                    // 添加用户
                    $stmt = $pdo->prepare("INSERT INTO {$db_config['table']} (username, password, role) VALUES (?, ?, ?)");
                    $result = $stmt->execute([$newUsername, md5($newPassword), $newRole]);
                    
                    if ($result) {
                        // 添加成功
                        $successMessage = '用户添加成功！';
                    } else {
                        // 添加失败
                        $successMessage = '用户添加失败！';
                    }
                }
            } catch (PDOException $e) {
                // 数据库错误
                $successMessage = '数据库错误：' . $e->getMessage();
            }
            
            // 重定向回设置页面
            header('Location: settings.php?success=1&message=' . urlencode($successMessage));
            exit;
        }
    }
}
?>