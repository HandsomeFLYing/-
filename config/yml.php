<?php
// 配置项

//wwwroot目录
$wwwfile = $_SERVER['DOCUMENT_ROOT'];

// 图片存放目录
$imgDir = 'images'; 

// 原图根目录
$rootDir =  $wwwfile . '/' . $imgDir; 

// 缩略图根目录
$thumbDir =  $wwwfile . '/thumbnails'; 

// 支持的图片格式
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']; 
$thumbWidth = 220; // 缩略图宽度
$thumbHeight = 220; // 缩略图高度


$currentPath = isset($_GET['path']) ? $_GET['path'] : ''; // 当前选中的分类路径
$viewMode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'current_level'; // 显示模式
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'time_desc'; // 排序方式
$groupBy = isset($_GET['group_by']) ? $_GET['group_by'] : 'none'; // 分组方式
$safeCurrentPath = realpath($rootDir . '/' . $currentPath); // 解析后的当前路径
$pageSize = isset($_GET['page_size']) ? intval($_GET['page_size']) : 11; // 每页图片数量
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; 
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1'; // 是否为AJAX请求
