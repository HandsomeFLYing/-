<?php
session_start();
// 登录验证+权限验证
$loginUser = require_once  '../app/auto-login.php';
if (!isset($_SESSION['user_info']) || $loginUser !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!-- 操作信息打印脚本 -->
    <script>
    // 监听文件选择
    document.getElementById('selectFileBtn').addEventListener('click', function() {
        console.log('用户点击了选择文件按钮');
    });

    document.getElementById('fileInput').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        console.log('用户选择了文件:', files.map(file => file.name));
    });

    document.getElementById('uploadBtn').addEventListener('click', function() {
        console.log('用户点击了开始上传按钮');
    });

    // 重写删除函数以添加日志
    const originalDeleteImage = deleteImage;
    deleteImage = function(path, thumbUrl) {
        console.log('删除操作:', { path, thumbUrl });
        return originalDeleteImage(path, thumbUrl);
    };

    // 重写移动相关函数以添加日志
    const originalOpenMoveModal = openMoveModal;
    openMoveModal = function(path) {
        console.log('打开移动弹窗:', { path });
        return originalOpenMoveModal(path);
    };

    const originalDoMove = doMove;
    doMove = function() {
        const sourcePath = document.getElementById('moveSourcePath').value;
        const targetPath = document.getElementById('moveTargetPath').value;
        console.log('执行移动操作:', { sourcePath, targetPath });
        return originalDoMove();
    };

    // 重写修改相关函数以添加日志
    const originalOpenRenameModal = openRenameModal;
    openRenameModal = function(path, name) {
        console.log('打开重命名弹窗:', { path, name });
        return originalOpenRenameModal(path, name);
    };

    const originalDoRename = doRename;
    doRename = function() {
        const path = document.getElementById('renamePath').value;
        const newName = document.getElementById('renameInput').value;
        console.log('执行重命名操作:', { path, newName });
        return originalDoRename();
    };

    // 监听排序和分组变化
    window.onSortOrGroupChange = function() {
        const sortBy = document.getElementById('sortBySelect').value;
        const groupBy = document.getElementById('groupBySelect').value;
        console.log('排序或分组变化:', { sortBy, groupBy });
        // 执行原有的跳转逻辑
        const currentPath = new URLSearchParams(window.location.search).get('path') || '';
        const viewMode = new URLSearchParams(window.location.search).get('view_mode') || 'current_level';
        window.location.href = `?path=${encodeURIComponent(currentPath)}&view_mode=${encodeURIComponent(viewMode)}&sort_by=${encodeURIComponent(sortBy)}&group_by=${encodeURIComponent(groupBy)}`;
    };
    </script>