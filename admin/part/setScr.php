    <script>

        // 移动端菜单控制
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const categoryNav = document.getElementById('categoryNav');
        mobileMenuBtn.addEventListener('click', function() {
            categoryNav.classList.toggle('open');
        });
        // 标签页切换
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // 移除所有标签页和面板的活动状态
                document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
                
                // 添加当前标签页和面板的活动状态
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-panel').classList.add('active');
            });
        });
        
        // 保存基础设置
        document.getElementById('save_basic').addEventListener('click', function() {
            // 收集表单数据
            const formData = {
                action: 'save_basic',
                image_dir: document.getElementById('image_dir').value,
                thumb_dir: document.getElementById('thumb_dir').value,
                allowed_exts: document.getElementById('allowed_exts').value,
                thumb_width: document.getElementById('thumb_width').value,
                thumb_height: document.getElementById('thumb_height').value,
                page_size: document.getElementById('page_size').value,
                use_database: document.getElementById('use_database').checked ? 'true' : 'false'
            };
            
            // 显示加载状态
            this.textContent = '保存中...';
            this.disabled = true;
            
            // 发送 AJAX 请求
            fetch('../app/config-save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                // 恢复按钮状态
                this.textContent = '保存设置';
                this.disabled = false;
                
                // 显示结果
                if (data.success) {
                    // 创建成功消息
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = data.message;
                    
                    // 添加到页面
                    const settingsContainer = document.querySelector('.settings-container');
                    const firstChild = settingsContainer.firstElementChild;
                    settingsContainer.insertBefore(successMessage, firstChild);
                    
                    // 3秒后移除消息
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                } else {
                    alert('保存失败: ' + data.message);
                }
            })
            .catch(error => {
                // 恢复按钮状态
                this.textContent = '保存设置';
                this.disabled = false;
                
                console.error('保存设置时出错:', error);
                alert('保存设置时出错，请稍后重试');
            });
        });
        
        // // 退出登录
        // function logout() {
        //     if (confirm('确定要退出登录吗？')) {
        //         window.location.href = 'login.php?action=logout';
        //     }
        // }
        // 确认弹窗代码
        <?php require_once 'part/ifwin.php' ?>
        // 退出登录
        function logout() {
            showConfirmDialog('退出登录', '确定退出登录吗？', function() {
                localStorage.removeItem('tk_user');
                fetch('../app/auto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                })
                .then(() => {
                    window.location.href = '../';
                });
            });
        }
        
        // 全局变量，用于存储当前操作的用户名
        let currentUsername = '';
        
        // 显示删除弹窗
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                currentUsername = this.getAttribute('data-username');
                document.getElementById('deleteUsername').textContent = currentUsername;
                document.getElementById('deleteModal').style.display = 'flex';
            });
        });
        
        // 隐藏删除弹窗
        document.getElementById('cancelDelete').addEventListener('click', function() {
            document.getElementById('deleteModal').style.display = 'none';
        });
        
        // 确认删除用户
        document.getElementById('confirmDelete').addEventListener('click', function() {
            // 获取存储模式
            const storageMode = document.getElementById('storageMode').value;
            const url = storageMode === 'database' ? '../app/user-db.php' : 'settings.php';
            
            // 发送 AJAX 请求删除用户
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_user',
                    username: currentUsername
                })
            })
            .then(response => response.json())
            .then(data => {
                // 隐藏弹窗
                document.getElementById('deleteModal').style.display = 'none';
                
                // 显示结果
                if (data.success) {
                    // 创建成功消息
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = data.message;
                    
                    // 添加到页面
                    const settingsContainer = document.querySelector('.settings-container');
                    const firstChild = settingsContainer.firstElementChild;
                    settingsContainer.insertBefore(successMessage, firstChild);
                    
                    // 3秒后移除消息
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                    // 刷新页面以更新用户列表
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('删除失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('删除用户时出错:', error);
                alert('删除用户时出错，请稍后重试');
            });
        });
        
        // 显示编辑弹窗
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                currentUsername = this.getAttribute('data-username');
                const currentRole = this.getAttribute('data-role');
                
                document.getElementById('editUsername').value = currentUsername;
                document.getElementById('editPassword').value = '';
                document.getElementById('editRole').value = currentRole;
                
                document.getElementById('editModal').style.display = 'flex';
            });
        });
        
        // 隐藏编辑弹窗
        document.getElementById('cancelEdit').addEventListener('click', function() {
            document.getElementById('editModal').style.display = 'none';
        });
        
        // 保存编辑用户
        document.getElementById('saveEdit').addEventListener('click', function() {
            const editUsername = document.getElementById('editUsername').value;
            const editPassword = document.getElementById('editPassword').value;
            const editRole = document.getElementById('editRole').value;
            
            // 获取存储模式
            const storageMode = document.getElementById('storageMode').value;
            const url = storageMode === 'database' ? '../app/user-db.php' : 'settings.php';
            
            // 发送 AJAX 请求编辑用户
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'edit_user',
                    username: editUsername,
                    password: editPassword,
                    role: editRole
                })
            })
            .then(response => response.json())
            .then(data => {
                // 隐藏弹窗
                document.getElementById('editModal').style.display = 'none';
                
                // 显示结果
                if (data.success) {
                    // 创建成功消息
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = data.message;
                    
                    // 添加到页面
                    const settingsContainer = document.querySelector('.settings-container');
                    const firstChild = settingsContainer.firstElementChild;
                    settingsContainer.insertBefore(successMessage, firstChild);
                    
                    // 3秒后移除消息
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                    // 刷新页面以更新用户列表
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('编辑失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('编辑用户时出错:', error);
                alert('编辑用户时出错，请稍后重试');
            });
        });
        
        // 添加用户
        document.getElementById('add_user').addEventListener('click', function() {
            const newUsername = document.getElementById('new_username').value;
            const newPassword = document.getElementById('new_password').value;
            const newRole = document.getElementById('new_role').value;
            
            // 验证输入
            if (!newUsername || !newPassword) {
                alert('用户名和密码不能为空！');
                return;
            }
            
            // 获取存储模式
            const storageMode = document.getElementById('storageMode').value;
            const url = storageMode === 'database' ? '../app/user-db.php' : 'settings.php';
            
            // 显示加载状态
            this.textContent = '添加中...';
            this.disabled = true;
            
            // 发送 AJAX 请求添加用户
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add_user',
                    username: newUsername,
                    password: newPassword,
                    role: newRole
                })
            })
            .then(response => response.json())
            .then(data => {
                // 恢复按钮状态
                this.textContent = '添加用户';
                this.disabled = false;
                
                // 显示结果
                if (data.success) {
                    // 创建成功消息
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = data.message;
                    
                    // 添加到页面
                    const settingsContainer = document.querySelector('.settings-container');
                    const firstChild = settingsContainer.firstElementChild;
                    settingsContainer.insertBefore(successMessage, firstChild);
                    
                    // 3秒后移除消息
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                    
                    // 清空表单
                    document.getElementById('new_username').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('new_role').value = 'user';
                    
                    // 刷新页面以更新用户列表
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('添加失败: ' + data.message);
                }
            })
            .catch(error => {
                // 恢复按钮状态
                this.textContent = '添加用户';
                this.disabled = false;
                
                console.error('添加用户时出错:', error);
                alert('添加用户时出错，请稍后重试');
            });
        });
    </script>