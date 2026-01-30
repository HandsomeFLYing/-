<?php
session_start();
// 登录验证+权限验证
$loginUser = require_once  '../app/auto-login.php';
if (!isset($_SESSION['user_info']) || $loginUser !== 'admin') {
    header('Location: login.php');
    exit;
}

// 配置项
// 校验配置文件
if (!file_exists('../config/yml.php')) {
    die('<div style="text-align:center;margin:50px;color:#ff4444;">错误：配置文件缺失！</div>');
}
require_once '../config/yml.php';
require_once '../config/config-user.php';

// 处理成功提示
if (isset($_GET['success'])) {
    $successMessage = '基础设置保存成功！';
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否是 JSON 请求
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        // 读取 JSON 数据
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        // 处理删除用户
        if (isset($data['action']) && $data['action'] === 'delete_user') {
            $username = $data['username'];
            
            // 读取用户数据文件
            $userFile = '../config/sql/user.php';
            $userContent = file_get_contents($userFile);
            
            // 使用更可靠的方法读取用户数据：直接解析文件内容，保持原始的变量形式
            $usersArray = [];
            
            // 提取 return 语句中的数组部分
            preg_match('/return \[(.*?)\];/s', $userContent, $matches);
            if (isset($matches[1])) {
                $userArrayStr = $matches[1];
                
                // 使用正则表达式匹配每个用户条目
                $userPattern = "/'([^']+)'\s*=>\s*\[(.*?)\](?:,|$)/s";
                preg_match_all($userPattern, $userArrayStr, $userMatches, PREG_SET_ORDER);
                
                foreach ($userMatches as $userMatch) {
                    $userName = $userMatch[1];
                    $userData = $userMatch[2];
                    
                    // 提取用户的各个字段，保持原始形式
                    $userInfo = [];
                    $fieldPattern = "/'([^']+)'\s*=>\s*(.*?)(?:,|$)/s";
                    preg_match_all($fieldPattern, $userData, $fieldMatches, PREG_SET_ORDER);
                    
                    foreach ($fieldMatches as $fieldMatch) {
                        $fieldName = trim($fieldMatch[1]);
                        $fieldValue = trim($fieldMatch[2]);
                        // 对于 root_path 和 thumb_root 字段，保持原始形式，不要移除引号
                        if (!in_array($fieldName, ['root_path', 'thumb_root'])) {
                            // 移除字段值两端的引号（如果有）
                            if ((substr($fieldValue, 0, 1) === '\'' && substr($fieldValue, -1, 1) === '\'') || 
                                (substr($fieldValue, 0, 1) === '"' && substr($fieldValue, -1, 1) === '"')) {
                                $fieldValue = substr($fieldValue, 1, -1);
                            }
                        }
                        $userInfo[$fieldName] = $fieldValue;
                    }
                    
                    $usersArray[$userName] = $userInfo;
                }
            }
            
            // 确保 $usersArray 是一个数组
            if (!is_array($usersArray)) {
                $usersArray = [];
            }
            
            // 检查用户是否存在
            if (isset($usersArray[$username])) {
                // 删除用户
                unset($usersArray[$username]);
                
                // 生成新的用户数组字符串
                $newUsersArray = '';
                foreach ($usersArray as $userName => $userInfo) {
                    $newUsersArray .= "'$userName' => [\n";
                    $newUsersArray .= "    'password' => '$userInfo[password]',\n";
                    $newUsersArray .= "    'role' => '$userInfo[role]',\n";
                    // 对于 root_path 和 thumb_path 字段，直接使用原始值，保持其格式
                    $newUsersArray .= "    'root_path' => $userInfo[root_path],\n";
                    $newUsersArray .= "    'thumb_root' => $userInfo[thumb_root],\n";
                    $newUsersArray .= "    'relative_root' => '$userInfo[relative_root]'\n";
                    $newUsersArray .= "],\n";
                }
                $newUsersArray = rtrim($newUsersArray, ",\n");
                
                // 更新用户数据文件
                $userContent = preg_replace('/return \[(.*?)\];/s', 'return [' . $newUsersArray . '];', $userContent);
                file_put_contents($userFile, $userContent);
                
                // 返回 JSON 响应
                echo json_encode([
                    'success' => true,
                    'message' => '用户删除成功！'
                ]);
            } else {
                // 用户不存在
                echo json_encode([
                    'success' => false,
                    'message' => '用户不存在！'
                ]);
            }
            exit;
        } else if (isset($data['action']) && $data['action'] === 'edit_user') {
            // 处理编辑用户
            $username = $data['username'];
            $password = $data['password'];
            $role = $data['role'];
            
            // 读取用户数据文件
            $userFile = '../config/sql/user.php';
            $userContent = file_get_contents($userFile);
            
            // 使用更可靠的方法读取用户数据：直接解析文件内容，保持原始的变量形式
            $usersArray = [];
            
            // 提取 return 语句中的数组部分
            preg_match('/return \[(.*?)\];/s', $userContent, $matches);
            if (isset($matches[1])) {
                $userArrayStr = $matches[1];
                
                // 使用正则表达式匹配每个用户条目
                $userPattern = "/'([^']+)'\s*=>\s*\[(.*?)\](?:,|$)/s";
                preg_match_all($userPattern, $userArrayStr, $userMatches, PREG_SET_ORDER);
                
                foreach ($userMatches as $userMatch) {
                    $userName = $userMatch[1];
                    $userData = $userMatch[2];
                    
                    // 提取用户的各个字段，保持原始形式
                    $userInfo = [];
                    $fieldPattern = "/'([^']+)'\s*=>\s*(.*?)(?:,|$)/s";
                    preg_match_all($fieldPattern, $userData, $fieldMatches, PREG_SET_ORDER);
                    
                    foreach ($fieldMatches as $fieldMatch) {
                        $fieldName = trim($fieldMatch[1]);
                        $fieldValue = trim($fieldMatch[2]);
                        // 对于 root_path 和 thumb_root 字段，保持原始形式，不要移除引号
                        if (!in_array($fieldName, ['root_path', 'thumb_root'])) {
                            // 移除字段值两端的引号（如果有）
                            if ((substr($fieldValue, 0, 1) === '\'' && substr($fieldValue, -1, 1) === '\'') || 
                                (substr($fieldValue, 0, 1) === '"' && substr($fieldValue, -1, 1) === '"')) {
                                $fieldValue = substr($fieldValue, 1, -1);
                            }
                        }
                        $userInfo[$fieldName] = $fieldValue;
                    }
                    
                    $usersArray[$userName] = $userInfo;
                }
            }
            
            // 确保 $usersArray 是一个数组
            if (!is_array($usersArray)) {
                $usersArray = [];
            }
            
            // 检查用户是否存在
            if (isset($usersArray[$username])) {
                // 更新用户信息
                if (!empty($password)) {
                    $usersArray[$username]['password'] = md5($password); // 加密密码
                }
                $usersArray[$username]['role'] = $role;
                
                // 生成新的用户数组字符串
                $newUsersArray = '';
                foreach ($usersArray as $userName => $userInfo) {
                    $newUsersArray .= "'$userName' => [\n";
                    $newUsersArray .= "    'password' => '$userInfo[password]',\n";
                    $newUsersArray .= "    'role' => '$userInfo[role]',\n";
                    // 对于 root_path 和 thumb_path 字段，直接使用原始值，保持其格式
                    $newUsersArray .= "    'root_path' => $userInfo[root_path],\n";
                    $newUsersArray .= "    'thumb_root' => $userInfo[thumb_root],\n";
                    $newUsersArray .= "    'relative_root' => '$userInfo[relative_root]'\n";
                    $newUsersArray .= "],\n";
                }
                $newUsersArray = rtrim($newUsersArray, ",\n");
                
                // 更新用户数据文件
                $userContent = preg_replace('/return \[(.*?)\];/s', 'return [' . $newUsersArray . '];', $userContent);
                file_put_contents($userFile, $userContent);
                
                // 返回 JSON 响应
                echo json_encode([
                    'success' => true,
                    'message' => '用户编辑成功！'
                ]);
            } else {
                // 用户不存在
                echo json_encode([
                    'success' => false,
                    'message' => '用户不存在！'
                ]);
            }
            exit;
        } else if (isset($data['action']) && $data['action'] === 'add_user') {
            // 处理添加用户
            $username = $data['username'];
            $password = $data['password'];
            $role = $data['role'];
            
            // 读取用户数据文件
            $userFile = '../config/sql/user.php';
            $userContent = file_get_contents($userFile);
            
            // 使用更可靠的方法读取用户数据：直接解析文件内容，保持原始的变量形式
            $usersArray = [];
            
            // 提取 return 语句中的数组部分
            preg_match('/return \[(.*?)\];/s', $userContent, $matches);
            if (isset($matches[1])) {
                $userArrayStr = $matches[1];
                
                // 使用正则表达式匹配每个用户条目
                $userPattern = "/'([^']+)'\s*=>\s*\[(.*?)\](?:,|$)/s";
                preg_match_all($userPattern, $userArrayStr, $userMatches, PREG_SET_ORDER);
                
                foreach ($userMatches as $userMatch) {
                    $userName = $userMatch[1];
                    $userData = $userMatch[2];
                    
                    // 提取用户的各个字段，保持原始形式
                    $userInfo = [];
                    $fieldPattern = "/'([^']+)'\s*=>\s*(.*?)(?:,|$)/s";
                    preg_match_all($fieldPattern, $userData, $fieldMatches, PREG_SET_ORDER);
                    
                    foreach ($fieldMatches as $fieldMatch) {
                        $fieldName = trim($fieldMatch[1]);
                        $fieldValue = trim($fieldMatch[2]);
                        // 对于 root_path 和 thumb_root 字段，保持原始形式，不要移除引号
                        if (!in_array($fieldName, ['root_path', 'thumb_root'])) {
                            // 移除字段值两端的引号（如果有）
                            if ((substr($fieldValue, 0, 1) === '\'' && substr($fieldValue, -1, 1) === '\'') || 
                                (substr($fieldValue, 0, 1) === '"' && substr($fieldValue, -1, 1) === '"')) {
                                $fieldValue = substr($fieldValue, 1, -1);
                            }
                        }
                        $userInfo[$fieldName] = $fieldValue;
                    }
                    
                    $usersArray[$userName] = $userInfo;
                }
            }
            
            // 确保 $usersArray 是一个数组
            if (!is_array($usersArray)) {
                $usersArray = [];
            }
            
            // 检查用户是否存在
            if (isset($usersArray[$username])) {
                // 用户已存在
                echo json_encode([
                    'success' => false,
                    'message' => '用户已存在！'
                ]);
                exit;
            }
            
            // 添加新用户
            if ($role === 'user') {
                $rootPath = '$imageRoot . DIRECTORY_SEPARATOR . "' . $username . '"';
                $thumbPath = '$thumbRoot . DIRECTORY_SEPARATOR . "' . $username . '"';
                $relativeRoot = $imgDir . '/' . $username;
            } else {
                $rootPath = '$imageRoot';
                $thumbPath = '$thumbRoot';
                $relativeRoot = $imgDir . '';
            }
            
            $usersArray[$username] = [
                'password' => md5($password),
                'role' => $role,
                'root_path' => $rootPath,
                'thumb_root' => $thumbPath,
                'relative_root' => $relativeRoot
            ];
            
            // 生成新的用户数组字符串
            $newUsersArray = '';
            foreach ($usersArray as $userName => $userInfo) {
                $newUsersArray .= "'$userName' => [\n";
                $newUsersArray .= "    'password' => '$userInfo[password]',\n";
                $newUsersArray .= "    'role' => '$userInfo[role]',\n";
                // 对于 root_path 和 thumb_path 字段，直接使用原始值，保持其格式
                $newUsersArray .= "    'root_path' => $userInfo[root_path],\n";
                $newUsersArray .= "    'thumb_root' => $userInfo[thumb_root],\n";
                $newUsersArray .= "    'relative_root' => '$userInfo[relative_root]'\n";
                $newUsersArray .= "],\n";
            }
            $newUsersArray = rtrim($newUsersArray, ",\n");
            
            // 更新用户数据文件
            $userContent = preg_replace('/return \[(.*?)\];/s', 'return [' . $newUsersArray . '];', $userContent);
            file_put_contents($userFile, $userContent);
            
            // 返回 JSON 响应
            echo json_encode([
                'success' => true,
                'message' => '用户添加成功！'
            ]);
            exit;
        }
    } else {
        // 处理传统表单提交（添加用户）
        // 添加用户
        if (isset($_POST['add_user'])) {
            $newUsername = $_POST['new_username'];
            $newPassword = $_POST['new_password'];
            $newRole = $_POST['new_role'];
            
            // 读取用户数据文件
            $userFile = '../config/sql/user.php';
            $userContent = file_get_contents($userFile);
            
            // 使用更可靠的方法读取用户数据：直接解析文件内容，保持原始的变量形式
            $usersArray = [];
            
            // 提取 return 语句中的数组部分
            preg_match('/return \[(.*?)\];/s', $userContent, $matches);
            if (isset($matches[1])) {
                $userArrayStr = $matches[1];
                
                // 使用正则表达式匹配每个用户条目
                $userPattern = "/'([^']+)'\s*=>\s*\[(.*?)\](?:,|$)/s";
                preg_match_all($userPattern, $userArrayStr, $userMatches, PREG_SET_ORDER);
                
                foreach ($userMatches as $userMatch) {
                    $username = $userMatch[1];
                    $userData = $userMatch[2];
                    
                    // 提取用户的各个字段，保持原始形式
                    $userInfo = [];
                    $fieldPattern = "/'([^']+)'\s*=>\s*(.*?)(?:,|$)/s";
                    preg_match_all($fieldPattern, $userData, $fieldMatches, PREG_SET_ORDER);
                    
                    foreach ($fieldMatches as $fieldMatch) {
                        $fieldName = trim($fieldMatch[1]);
                        $fieldValue = trim($fieldMatch[2]);
                        // 对于 root_path 和 thumb_root 字段，保持原始形式，不要移除引号
                        if (!in_array($fieldName, ['root_path', 'thumb_root'])) {
                            // 移除字段值两端的引号（如果有）
                            if ((substr($fieldValue, 0, 1) === '\'' && substr($fieldValue, -1, 1) === '\'') || 
                                (substr($fieldValue, 0, 1) === '"' && substr($fieldValue, -1, 1) === '"')) {
                                $fieldValue = substr($fieldValue, 1, -1);
                            }
                        }
                        $userInfo[$fieldName] = $fieldValue;
                    }
                    
                    $usersArray[$username] = $userInfo;
                }
            }
            
            // 确保 $usersArray 是一个数组
            if (!is_array($usersArray)) {
                $usersArray = [];
            }
            
            // 添加新用户
            if ($newRole === 'user') {
                $rootPath = '$imageRoot . DIRECTORY_SEPARATOR . "' . $newUsername . '"';
                $thumbPath = '$thumbRoot . DIRECTORY_SEPARATOR . "' . $newUsername . '"';
                $relativeRoot = $imgDir . '/' . $newUsername;
            } else {
                $rootPath = '$imageRoot';
                $thumbPath = '$thumbRoot';
                $relativeRoot = $imgDir . '';
            }
            
            $usersArray[$newUsername] = [
                'password' => md5($newPassword),
                'role' => $newRole,
                'root_path' => $rootPath,
                'thumb_root' => $thumbPath,
                'relative_root' => $relativeRoot
            ];
            
            // 生成新的用户数组字符串
            $newUsersArray = '';
            foreach ($usersArray as $username => $userInfo) {
                $newUsersArray .= "'$username' => [\n";
                $newUsersArray .= "    'password' => '$userInfo[password]',\n";
                $newUsersArray .= "    'role' => '$userInfo[role]',\n";
                // 对于 root_path 和 thumb_path 字段，直接使用原始值，保持其格式
                $newUsersArray .= "    'root_path' => $userInfo[root_path],\n";
                $newUsersArray .= "    'thumb_root' => $userInfo[thumb_root],\n";
                $newUsersArray .= "    'relative_root' => '$userInfo[relative_root]'\n";
                $newUsersArray .= "],\n";
            }
            $newUsersArray = rtrim($newUsersArray, ",\n");
            
            // 更新用户数据文件
            $userContent = preg_replace('/return \[(.*?)\];/s', 'return [' . $newUsersArray . '];', $userContent);
            file_put_contents($userFile, $userContent);
            
            // 提示添加成功
            $successMessage = '用户添加成功！';
        }
    }
}

// 获取当前配置值
$currentImageDir = basename($imageRoot);
$currentThumbDir = basename($thumbRoot);
$currentUseDatabase = false; // 默认值
$currentYmlImgDir = $imgDir;
$currentYmlThumbDir = str_replace($wwwfile . '/', '', $thumbDir);
$currentAllowedExts = implode(', ', $allowedExts);
$currentThumbWidth = $thumbWidth;
$currentThumbHeight = $thumbHeight;
$currentPageSize = $pageSize;

// 检查是否使用数据库
$configUserContent = file_get_contents('../config/config-user.php');
preg_match('/if \((true|false)\) {/', $configUserContent, $matches);
if (isset($matches[1])) {
    $currentUseDatabase = $matches[1] === 'true';
}
?>