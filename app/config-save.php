<?php
// 临时开启错误显示（0关1开）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
// 配置保存处理文件

// 启动会话
session_start();

// 检查是否有权限访问
$loginUser = require_once  '../app/auto-login.php';
if (!isset($_SESSION['user_info']) || $loginUser !== 'admin') {
    // 对于 JSON 请求，返回 JSON 响应
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        echo json_encode([
            'success' => false,
            'message' => '没有权限访问此页面！'
        ]);
        exit;
    } else {
        // 对于传统请求，重定向到登录页面
        header('Location: login.php');
        exit;
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否是 JSON 请求
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        // 读取 JSON 数据
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        // 处理基础设置保存
        if (isset($data['action']) && $data['action'] === 'save_basic') {
            // 读取当前配置文件内容
            $ymlContent = file_get_contents('../config/yml.php');
            $configUserContent = file_get_contents('../config/config-user.php');
            
            // 更新配置
            // 使用更简单、更可靠的方法来更新配置文件
            
            // 更新 config-user.php 文件
            // 使用正则表达式来更新配置项，确保可以多次修改
            $configUserContent = preg_replace('/\$imageRoot = \$rootDir . DIRECTORY_SEPARATOR . \'[^\']*\';/', '$imageRoot = $rootDir . DIRECTORY_SEPARATOR . \'' . $data['image_dir'] . '\';', $configUserContent);
            $configUserContent = preg_replace('/\$thumbRoot = \$rootDir . DIRECTORY_SEPARATOR . \'[^\']*\';/', '$thumbRoot = $rootDir . DIRECTORY_SEPARATOR . \'' . $data['thumb_dir'] . '\';', $configUserContent);
            $configUserContent = preg_replace('/if \(true\) \{/', 'if (' . ($data['use_database'] === 'true' ? 'true' : 'false') . ') {', $configUserContent);
            $configUserContent = preg_replace('/if \(false\) \{/', 'if (' . ($data['use_database'] === 'true' ? 'true' : 'false') . ') {', $configUserContent);
            
            // 更新 yml.php 文件
            // 使用正则表达式来更新配置项，确保可以多次修改
            $ymlContent = preg_replace('/\$imgDir = \'[^\']*\';/', '$imgDir = \'' . $data['image_dir'] . '\';', $ymlContent);
            $ymlContent = preg_replace('/\$thumbDir =  \$wwwfile . \'\/[^\']*\';/', '$thumbDir =  $wwwfile . \'/' . $data['thumb_dir'] . '\';', $ymlContent);
            $ymlContent = preg_replace('/\$allowedExts = \[.*?\];/', '$allowedExts = [' . implode(', ', array_map(function($ext) { return '\'' . trim($ext) . '\''; }, explode(',', $data['allowed_exts']))) . '];', $ymlContent);
            $ymlContent = preg_replace('/\$thumbWidth = \d+;/', '$thumbWidth = ' . $data['thumb_width'] . ';', $ymlContent);
            $ymlContent = preg_replace('/\$thumbHeight = \d+;/', '$thumbHeight = ' . $data['thumb_height'] . ';', $ymlContent);
            $ymlContent = preg_replace('/\$pageSize = isset\(\$_GET\[\'page_size\'\]\) \? intval\(\$_GET\[\'page_size\'\]\) : \d+;/', '$pageSize = isset($_GET[\'page_size\']) ? intval($_GET[\'page_size\']) : ' . $data['page_size'] . ';', $ymlContent);
            
            // 写入配置文件
            file_put_contents('../config/yml.php', $ymlContent);
            file_put_contents('../config/config-user.php', $configUserContent);
            
            // 返回 JSON 响应
            echo json_encode([
                'success' => true,
                'message' => '基础设置保存成功！'
            ]);
            exit;
        } else {
            // 未知的操作类型
            echo json_encode([
                'success' => false,
                'message' => '未知的操作类型！'
            ]);
            exit;
        }
    } else {
        // 处理传统表单提交
        if (isset($_POST['save_basic'])) {
            // 读取当前配置文件内容
            $ymlContent = file_get_contents('../config/yml.php');
            $configUserContent = file_get_contents('../config/config-user.php');
            
            // 更新配置
            // 使用更简单、更可靠的方法来更新配置文件
            
            // 更新 config-user.php 文件
            // 使用正则表达式来更新配置项，确保可以多次修改
            $configUserContent = preg_replace('/\$imageRoot = \$rootDir . DIRECTORY_SEPARATOR . \'[^\']*\';/', '$imageRoot = $rootDir . DIRECTORY_SEPARATOR . \'' . $_POST['image_dir'] . '\';', $configUserContent);
            $configUserContent = preg_replace('/\$thumbRoot = \$rootDir . DIRECTORY_SEPARATOR . \'[^\']*\';/', '$thumbRoot = $rootDir . DIRECTORY_SEPARATOR . \'' . $_POST['thumb_dir'] . '\';', $configUserContent);
            $configUserContent = preg_replace('/if \(true\) \{/', 'if (' . ($_POST['use_database'] === 'true' ? 'true' : 'false') . ') {', $configUserContent);
            $configUserContent = preg_replace('/if \(false\) \{/', 'if (' . ($_POST['use_database'] === 'true' ? 'true' : 'false') . ') {', $configUserContent);
            
            // 更新 yml.php 文件
            // 使用正则表达式来更新配置项，确保可以多次修改
            $ymlContent = preg_replace('/\$imgDir = \'[^\']*\';/', '$imgDir = \'' . $_POST['image_dir'] . '\';', $ymlContent);
            $ymlContent = preg_replace('/\$thumbDir =  \$wwwfile . \'\/[^\']*\';/', '$thumbDir =  $wwwfile . \'/' . $_POST['thumb_dir'] . '\';', $ymlContent);
            $ymlContent = preg_replace('/\$allowedExts = \[.*?\];/', '$allowedExts = [' . implode(', ', array_map(function($ext) { return '\'' . trim($ext) . '\''; }, explode(',', $_POST['allowed_exts']))) . '];', $ymlContent);
            $ymlContent = preg_replace('/\$thumbWidth = \d+;/', '$thumbWidth = ' . $_POST['thumb_width'] . ';', $ymlContent);
            $ymlContent = preg_replace('/\$thumbHeight = \d+;/', '$thumbHeight = ' . $_POST['thumb_height'] . ';', $ymlContent);
            $ymlContent = preg_replace('/\$pageSize = isset\(\$_GET\[\'page_size\'\]\) \? intval\(\$_GET\[\'page_size\'\]\) : \d+;/', '$pageSize = isset($_GET[\'page_size\']) ? intval($_GET[\'page_size\']) : ' . $_POST['page_size'] . ';', $ymlContent);
            
            // 写入配置文件
            file_put_contents('../config/yml.php', $ymlContent);
            file_put_contents('../config/config-user.php', $configUserContent);
            
            // 重定向回设置页面
            header('Location: settings.php?success=1');
            exit;
        } else {
            // 未知的表单提交
            header('Location: settings.php');
            exit;
        }
    }
} else {
    // 非 POST 请求
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        // 对于 JSON 请求，返回 JSON 响应
        echo json_encode([
            'success' => false,
            'message' => '只支持 POST 请求！'
        ]);
        exit;
    } else {
        // 对于传统请求，重定向到设置页面
        header('Location: settings.php');
        exit;
    }
}
?>