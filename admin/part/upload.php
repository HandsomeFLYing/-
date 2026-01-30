<?php
//防止url为image.php直接访问
if (basename($_SERVER['PHP_SELF']) === 'upload.php') {
    header('Location: 404.php');
    exit;
}
?>
<script>
// 上传功能实现
window.addEventListener('load', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    
    // 文件列表容器
    let fileListContainer = document.createElement('div');
    fileListContainer.id = 'fileListContainer';
    fileListContainer.style.marginTop = '20px';
    fileListContainer.style.display = 'none';
    uploadArea.appendChild(fileListContainer);
    
    // 选中的文件列表
    let selectedFiles = [];
    
    // 点击选择文件按钮
    selectFileBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // 文件选择变化
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    // 拖放事件
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('active');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('active');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('active');
        
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });
    
    // 处理选中的文件
    function handleFiles(files) {
        // 添加新文件到列表
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            // 检查是否是图片文件
            if (file.type.startsWith('image/')) {
                selectedFiles.push(file);
            }
        }
        
        // 更新文件列表显示
        updateFileList();
        
        // 显示上传按钮
        if (selectedFiles.length > 0) {
            uploadBtn.style.display = 'inline-block';
        } else {
            uploadBtn.style.display = 'none';
        }
    }
    
    // 更新文件列表显示
    function updateFileList() {
        if (selectedFiles.length === 0) {
            fileListContainer.style.display = 'none';
            return;
        }
        
        fileListContainer.style.display = 'block';
        fileListContainer.innerHTML = '';
        
        // 创建文件列表标题
        const listTitle = document.createElement('div');
        listTitle.style.fontWeight = '600';
        listTitle.style.marginBottom = '10px';
        listTitle.textContent = `已选择 ${selectedFiles.length} 个文件：`;
        fileListContainer.appendChild(listTitle);
        
        // 创建文件列表
        const fileList = document.createElement('div');
        fileList.style.maxHeight = '300px';
        fileList.style.overflowY = 'auto';
        fileList.style.border = '1px solid #e9ecef';
        fileList.style.borderRadius = '4px';
        fileList.style.padding = '10px';
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.style.display = 'flex';
            fileItem.style.justifyContent = 'space-between';
            fileItem.style.alignItems = 'center';
            fileItem.style.padding = '8px';
            fileItem.style.borderBottom = '1px solid #f1f3f4';
            
            const fileInfo = document.createElement('div');
            fileInfo.style.flex = '1';
            
            const fileName = document.createElement('div');
            fileName.style.fontSize = '14px';
            fileName.style.marginBottom = '4px';
            fileName.textContent = file.name;
            
            const fileSize = document.createElement('div');
            fileSize.style.fontSize = '12px';
            fileSize.style.color = '#6c757d';
            fileSize.textContent = formatFileSize(file.size);
            
            fileInfo.appendChild(fileName);
            fileInfo.appendChild(fileSize);
            
            const removeBtn = document.createElement('button');
            removeBtn.style.background = 'none';
            removeBtn.style.border = 'none';
            removeBtn.style.color = '#dc3545';
            removeBtn.style.cursor = 'pointer';
            removeBtn.style.padding = '4px 8px';
            removeBtn.style.borderRadius = '4px';
            removeBtn.textContent = '移除';
            
            removeBtn.addEventListener('click', function() {
                selectedFiles.splice(index, 1);
                updateFileList();
                
                if (selectedFiles.length === 0) {
                    uploadBtn.style.display = 'none';
                }
            });
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            fileList.appendChild(fileItem);
        });
        
        fileListContainer.appendChild(fileList);
    }
    
    // 开始上传
    uploadBtn.addEventListener('click', function() {
        if (selectedFiles.length === 0) return;
        
        // 显示上传进度
        uploadBtn.disabled = true;
        uploadBtn.textContent = '上传中...';
        
        // 创建上传进度容器
        const progressContainer = document.createElement('div');
        progressContainer.id = 'uploadProgressContainer';
        progressContainer.style.marginTop = '20px';
        uploadArea.appendChild(progressContainer);
        
        let uploadedCount = 0;
        let totalCount = selectedFiles.length;
        
        // 逐个上传文件
        selectedFiles.forEach((file, index) => {
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('file', file);
            formData.append('upload_path', '<?php echo isset($_GET['path']) ? addslashes($_GET['path']) : ''; ?>');
            
            // 创建单个文件的进度条
            const fileProgress = document.createElement('div');
            fileProgress.style.marginBottom = '10px';
            
            const fileProgressInfo = document.createElement('div');
            fileProgressInfo.style.display = 'flex';
            fileProgressInfo.style.justifyContent = 'space-between';
            fileProgressInfo.style.marginBottom = '5px';
            
            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            
            const progressText = document.createElement('span');
            progressText.textContent = '0%';
            
            fileProgressInfo.appendChild(fileName);
            fileProgressInfo.appendChild(progressText);
            
            const progressBar = document.createElement('div');
            progressBar.style.width = '100%';
            progressBar.style.height = '8px';
            progressBar.style.backgroundColor = '#f1f3f4';
            progressBar.style.borderRadius = '4px';
            progressBar.style.overflow = 'hidden';
            
            const progressFill = document.createElement('div');
            progressFill.style.width = '0%';
            progressFill.style.height = '100%';
            progressFill.style.backgroundColor = '#28a745';
            progressFill.style.transition = 'width 0.3s ease';
            
            progressBar.appendChild(progressFill);
            fileProgress.appendChild(fileProgressInfo);
            fileProgress.appendChild(progressBar);
            progressContainer.appendChild(fileProgress);
            
            // 发送上传请求
            fetch('../app/file_operate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                uploadedCount++;
                
                // 更新进度
                progressFill.style.width = '100%';
                progressText.textContent = '100%';
                
                // 检查是否所有文件都上传完成
                if (uploadedCount === totalCount) {
                    // 显示上传结果
                    setTimeout(() => {
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = '开始上传';
                        
                        // 清空文件列表
                        selectedFiles = [];
                        updateFileList();
                        uploadBtn.style.display = 'none';
                        
                        // 移除进度容器
                        if (progressContainer) {
                            progressContainer.remove();
                        }
                        
                        // 显示上传成功消息
                        showMessageModal('上传完成', `成功上传 ${totalCount} 个文件`, function() {
                            // 刷新页面
                            location.reload();
                        });
                    }, 500);
                }
            })
            .catch(error => {
                uploadedCount++;
                
                // 显示错误信息
                progressFill.style.backgroundColor = '#dc3545';
                progressFill.style.width = '100%';
                progressText.textContent = '失败';
                
                // 检查是否所有文件都处理完成
                if (uploadedCount === totalCount) {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = '开始上传';
                    
                    // 显示上传结果
                    showMessageModal('上传完成', `部分文件上传失败，请重试`, function() {
                        // 刷新页面
                        location.reload();
                    });
                }
                
                console.error('上传失败:', error);
            });
        });
    });
    
    // 格式化文件大小
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>