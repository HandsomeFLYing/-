<?php
//防止url为image.php直接访问
if (basename($_SERVER['PHP_SELF']) === 'file.php') {
    header('Location: 404.php');
    exit;
}
session_start();
if (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_SESSION['user_info']) && $_SESSION['user_info']['role'] === 'user') {
    $dataPathAdd = $_SESSION['user_info']['username'] . '/';
}else{
    $dataPathAdd = '';
}
?>
<script>
    // 打开删除弹窗
    function deleteImage(filePath, thumbPath) {
        document.getElementById('deletePath').value = filePath;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    // 执行删除
    function doDelete() {
        const filePath = document.getElementById('deletePath').value;
        const deleteModal = document.getElementById('deleteModal');
        const deleteBtn = deleteModal.querySelector('button.upload-btn');
        const deleteTitle = deleteModal.querySelector('h3');
        
        // 保存原始状态
        const originalTitle = deleteTitle.textContent;
        const originalBtnText = deleteBtn.textContent;
        
        // 显示处理中状态
        deleteTitle.textContent = '正在删除...';
        deleteBtn.textContent = '处理中...';
        deleteBtn.disabled = true;

        fetch('../app/file_operate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&file_path=${encodeURIComponent(filePath)}`
        })
        .then(res => res.json())
        .then(data => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示结果
            if (data.code === 1) {
                showMessageModal('删除成功！', data.msg, function() {
                    //删除对应容器
                    const fileContainer = document.querySelector(`[data-path="<?php echo $dataPathAdd; ?>${filePath}"]`);
                    if (fileContainer) {
                        fileContainer.remove();
                    }
                } ,false);
                //恢复按钮状态
                deleteBtn.disabled = false;
                deleteTitle.textContent = '确定要删除图片吗？将无法恢复.';
                deleteBtn.textContent = '确认删除';
            } else {
                showMessageModal('删除失败！', data.msg);
            }
        })
        .catch(err => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示错误
            showMessageModal('删除异常！', '网络错误，无反馈消息！');
            
            //输出错误
            console.error('删除图片异常：', err);
        });
        
    }
    // 打开移动弹窗
    function openMoveModal(sourcePath) {
        document.getElementById('moveSourcePath').value = sourcePath;
        document.getElementById('moveTargetPath').value = sourcePath;
        document.getElementById('currentSelectedPath').textContent = sourcePath;
        document.getElementById('moveModal').style.display = 'flex';
        
        // 生成目录树
        generateDirTree();
    }
    // 关闭功能弹窗
    function closeFunctionModal() {
        document.getElementById('moveModal').style.display = 'none';
        document.getElementById('deleteModal').style.display = 'none';
        document.getElementById('renameModal').style.display = 'none';
        document.getElementById('messageModal').style.display = 'none';
    }
    
    // 显示消息弹窗
    function showMessageModal(title, content, callback = null ,loc = true) {
        const messageModal = document.getElementById('messageModal');
        const messageTitle = document.getElementById('messageTitle');
        const messageContent = document.getElementById('messageContent');
        const messageBtn = document.getElementById('messageBtn');
        
        messageTitle.textContent = title;
        messageContent.textContent = content;
        
        // 设置按钮点击事件
        messageBtn.onclick = function() {
            // 关闭消息弹窗
            closeFunctionModal();
            // 执行回调函数（如果有）
            if (callback) {
                
                callback();
            }
            //刷新页面
            if(loc){
                location.reload();
            }else{
                return true;
            }
        };
        
        messageModal.style.display = 'flex';
    }

    // 打开重命名弹窗
    function openRenameModal(filePath, fileName) {
        document.getElementById('renamePath').value = filePath;
        document.getElementById('renameInput').value = fileName;
        document.getElementById('renameModal').style.display = 'flex';
    }

    // 执行重命名
    function doRename() {
        const filePath = document.getElementById('renamePath').value;
        const newFileName = document.getElementById('renameInput').value;

        if (!newFileName) {
            alert('请输入新文件名');
            return;
        }

        // 确保目标路径是目录，如果是文件路径则使用其目录
        const directoryPath = filePath.substring(0, filePath.lastIndexOf('/'));
        const targetFilePath = directoryPath + '/' + newFileName;

        const renameModal = document.getElementById('renameModal');
        const renameBtn = renameModal.querySelector('button.upload-btn');
        const renameTitle = renameModal.querySelector('h3');
        
        // 保存原始状态
        const originalTitle = renameTitle.textContent;
        const originalBtnText = renameBtn.textContent;
        
        // 显示处理中状态
        renameTitle.textContent = '正在重命名...';
        renameBtn.textContent = '处理中...';
        renameBtn.disabled = true;

        fetch('../app/file_operate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=rename&source_path=${encodeURIComponent(filePath)}&target_path=${encodeURIComponent(targetFilePath)}`
        })
        .then(res => res.json())
        .then(data => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示结果
            if (data.code === 1) {
                showMessageModal('重命名成功！', data.msg, function() {
                    location.reload();
                });
            } else {
                showMessageModal('重命名失败！', data.msg);
            }
        })
        .catch(err => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示错误
            showMessageModal('重命名异常！', '网络错误，无反馈消息！');
            
            //输出错误
            console.error('重命名图片失败：', err);
        });
    }

    // 生成目录树
    function generateDirTree() {
        const dirTreeContainer = document.getElementById('dirTreeContainer');
        dirTreeContainer.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">加载目录树中...</div>';
        
        // 获取目录结构
        fetch('../app/file_operate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_dirs'
        })
        .then(res => res.json())
        .then(data => {
            if (data.code === 1) {
                dirTreeContainer.innerHTML = '';
                const dirTree = document.createElement('ul');
                dirTree.className = 'dir-tree';
                
                // 递归生成目录树
                function buildTree(dirs, parentPath = '') {
                    dirs.forEach(dir => {
                        const li = document.createElement('li');
                        const dirPath = parentPath ? parentPath + '/' + dir.name : dir.name;
                        
                        li.innerHTML = `
                            <span class="dir-item" data-path="${dir.path}">
                                <span class="dir-icon">📁</span>
                                <span class="dir-name">${dir.name}</span>
                            </span>
                        `;
                        
                        // 添加点击事件
                        li.querySelector('.dir-item').addEventListener('click', function() {
                            const targetPath = this.dataset.path;
                            document.getElementById('moveTargetPath').value = targetPath;
                            document.getElementById('currentSelectedPath').textContent = targetPath;
                            
                            // 高亮选中项
                            document.querySelectorAll('.dir-item').forEach(item => {
                                item.style.backgroundColor = '';
                                item.style.fontWeight = 'normal';
                            });
                            this.style.backgroundColor = '#e8f0fe';
                            this.style.fontWeight = 'bold';
                        });
                        
                        if (dir.children && dir.children.length > 0) {
                            const ul = document.createElement('ul');
                            ul.className = 'dir-subtree';
                            buildTree(dir.children, dirPath);
                            li.appendChild(ul);
                        }
                        
                        dirTree.appendChild(li);
                    });
                }
                
                buildTree(data.data);
                dirTreeContainer.appendChild(dirTree);
            } else {
                dirTreeContainer.innerHTML = '<div style="text-align: center; color: #ff4444; padding: 20px;">加载目录树失败</div>';
            }
        })
        .catch(err => {
            dirTreeContainer.innerHTML = '<div style="text-align: center; color: #ff4444; padding: 20px;">网络错误</div>';
        });
    }
    // 执行移动
    function doMove() {
        const sourcePath = document.getElementById('moveSourcePath').value;
        const targetPath = document.getElementById('moveTargetPath').value;

        if (sourcePath === targetPath) {
            alert('目标路径与原路径相同！');
            return;
        }

        // 确保目标路径是目录，如果是文件路径则使用其目录
        const sourceFileName = sourcePath.split('/').pop();
        const targetFilePath = targetPath.endsWith('/') ? targetPath + sourceFileName : targetPath + '/' + sourceFileName;

        const moveModal = document.getElementById('moveModal');
        const moveBtn = moveModal.querySelector('button.upload-btn');
        const moveTitle = moveModal.querySelector('h3');
        
        // 保存原始状态
        const originalTitle = moveTitle.textContent;
        const originalBtnText = moveBtn.textContent;
        
        // 显示处理中状态
        moveTitle.textContent = '正在移动...';
        moveBtn.textContent = '处理中...';
        moveBtn.disabled = true;

        fetch('../app/file_operate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=move&source_path=${encodeURIComponent(sourcePath)}&target_path=${encodeURIComponent(targetFilePath)}`
        })
        .then(res => res.json())
        .then(data => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示结果
            if (data.code === 1) {
                showMessageModal('移动成功！', data.msg, function() {
                    location.reload();
                });
            } else {
                showMessageModal('移动失败！', data.msg);
            }
        })
        .catch(err => {
            // 关闭当前弹窗
            closeFunctionModal();
            
            // 使用消息弹窗显示错误
            showMessageModal('移动异常！', '网络错误，无反馈消息！');
            
            //输出错误
            console.error('移动图片失败：', err);
        });
    }

    // 创建新路径
    function createNewPath() {
        // 获取输入的新路径
        const newPath = document.getElementById('newPathInput').value.trim();
        
        if (!newPath) {
            alert('请输入要创建的路径！');
            return;
        }

        // 解析路径，创建目录结构
        const pathParts = newPath.split('/');
        let currentPath = '';
        let parentNode = document.querySelector('.dir-tree');
        
        // 递归创建目录节点
        pathParts.forEach((part, index) => {
            currentPath = index === 0 ? part : currentPath + '/' + part;
            
            // 检查目录是否已存在
            let existingDir = parentNode.querySelector(`.dir-item[data-path="${currentPath}"]`);
            if (!existingDir) {
                // 创建新目录节点
                const li = document.createElement('li');
                li.innerHTML = `
                    <span class="dir-item" data-path="${currentPath}">
                        <span class="dir-icon">📁</span>
                        <span class="dir-name">${part}</span>
                    </span>
                `;
                
                // 添加点击事件
                li.querySelector('.dir-item').addEventListener('click', function() {
                    const targetPath = this.dataset.path;
                    document.getElementById('moveTargetPath').value = targetPath;
                    document.getElementById('currentSelectedPath').textContent = targetPath;
                    
                    // 高亮选中项
                    document.querySelectorAll('.dir-item').forEach(item => {
                        item.style.backgroundColor = '';
                        item.style.fontWeight = 'normal';
                    });
                    this.style.backgroundColor = '#e8f0fe';
                    this.style.fontWeight = 'bold';
                });
                
                // 添加到父节点
                parentNode.appendChild(li);
                
                // 创建子目录容器
                const ul = document.createElement('ul');
                ul.className = 'dir-subtree';
                li.appendChild(ul);
                
                // 更新父节点为当前目录的子目录容器
                parentNode = ul;
            } else {
                // 目录已存在，更新父节点为其对应的子目录容器
                const existingLi = existingDir.closest('li');
                const existingUl = existingLi.querySelector('.dir-subtree');
                if (existingUl) {
                    parentNode = existingUl;
                }
            }
        });
        
        // 清空输入框
        document.getElementById('newPathInput').value = '';
    }
</script>