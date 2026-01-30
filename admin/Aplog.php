<?php
session_start();
$loginUser = require_once  '../app/auto-login.php';
// 登录验证+权限验证
if (!isset($_SESSION['user_info']) || $loginUser === null) {
    header('Location: login.php');
    exit;
}
if (isset($_SESSION['user_info']) && $loginUser === 'admin') {
    $userRole = $loginUser;
}
if (isset($_SESSION['user_info']) && $loginUser === 'user') {
    $userRole = '用户>' . $loginUser;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API操作日志</title>
    <link rel="stylesheet" href="/style/apmodal.css">
    <link rel="stylesheet" href="/style/<?php echo $loginUser ?>.css">
</head>
<body>
    <!-- 移动端汉堡按钮 -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    
    <!-- 左侧分类导航 -->
    <div class="category-nav" id="categoryNav">
        <div class="category-header">
            <h2>图库管理</h2>
            <a href="javascript:logout()" class="view-mode-btn">退出登录</a>
            <a href="" class="view-mode-btn">刷新页面</a>
        </div>
        
        <div class="category-breadcrumb">
            <span class="breadcrumb-item"><a href="">首页</a></span>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item"><a href="">API工具</a></span>
            
        </div>
        
        <ul class="category-list">
            <li class="category-item all-items">
                <a href="Aplog.php" class="category-link active">
                    <span class="category-icon">🖇️</span>
                    API工具
                </a>
                <?php if ($_SESSION['user_info']['role'] === 'admin'): ?>
                <a href="settings.php" class="category-link">
                    <span class="category-icon">⚙️</span>
                    系统设置
                </a>
                <?php endif; ?>
                <a href="index.php" class="category-link">
                    <span class="category-icon">🖼️</span>
                    全部图片
                </a>
            </li>
        </ul>
    </div>
    <div class="gallery-container">
        <div class="container">
            <h1>API工具 - <?php echo htmlspecialchars($userRole); ?></h1>
            <a href="index.php" class="btn">⬅返回管理首页</a><a href="Aplog-key.php" class="btn btn-secondary">Key操作</a>
            <small class="form-text text-muted">本页面可直接下载学习调用API方法,JS脚本就是使用方法</small>
            <div class="log-section">
                <h2>API工具</h2>
                <form id="apiForm">
                    <div class="form-group">
                        <label for="action">操作类型</label>
                        <select id="action" name="action">
                            <option value="get_files">获取文件列表</option>
                            <option value="get_dirs">获取目录结构</option>
                            <option value="create_dir">创建目录</option>
                            <option value="delete_dir">删除目录</option>
                            <option value="upload">上传文件</option>
                            <option value="delete">删除文件</option>
                            <option value="move">移动文件</option>
                            <option value="rename">重命名文件</option>
                            <option value="generate_thumb">生成缩略图</option>
                            <option value="search_files">搜索文件</option>
                            <option value="get_dir_size">计算目录大小</option>
                        </select>
                    </div>
                    <div id="dynamicFields" class="dynamic-fields">
                        <!-- 动态生成的输入框将显示在这里 -->
                    </div>
                    <button type="submit" class="btn">执行操作</button>
                    <button type="button" class="btn btn-secondary" onclick="exportLogs()">导出日志</button>
                    <button type="button" class="btn btn-secondary" onclick="clearLogs()">清空日志</button>
                </form>
            </div>
            
            <div class="log-section">
                <h2>操作响应</h2>
                <div id="response" class="response">
                    <h3>响应结果</h3>
                    <pre id="responseContent">请执行操作查看响应结果</pre>
                </div>
            </div>
            
            <div class="log-section">
                <h2>操作日志</h2>
                <div id="logContent" class="log-content"></div>
            </div>
        </div>
    </div>
    
    <script>
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
        // HTML转义函数
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        // JS 调用方法
        // 操作类型对应的参数配置
        const actionParams = {
            get_files: [
                { name: 'current_path', label: '当前路径', type: 'text', defaultValue: '' },
                { name: 'page', label: '页码', type: 'number', defaultValue: 1 },
                { name: 'page_size', label: '每页数量', type: 'number', defaultValue: 12 }
            ],
            get_dirs: [],
            create_dir: [
                { name: 'dir_path', label: '目录路径', type: 'text', defaultValue: '' }
            ],
            upload: [
                { name: 'upload_path', label: '上传路径', type: 'text', defaultValue: '' },
                { name: 'file', label: '上传文件', type: 'file' }
            ],
            delete: [
                { name: 'file_path', label: '文件路径', type: 'text', defaultValue: '' },
                { name: 'thumb_path', label: '缩略图路径', type: 'text', defaultValue: '' }
            ],
            move: [
                { name: 'source_path', label: '源路径', type: 'text', defaultValue: '' },
                { name: 'target_path', label: '目标路径', type: 'text', defaultValue: '' }
            ],
            rename: [
                { name: 'source_path', label: '源路径', type: 'text', defaultValue: '' },
                { name: 'target_path', label: '目标路径', type: 'text', defaultValue: '' }
            ],
            generate_thumb: [
                { name: 'file_path', label: '文件路径', type: 'text', defaultValue: '' }
            ],
            delete_dir: [
                { name: 'dir_path', label: '目录路径', type: 'text', defaultValue: '' }
            ],
            search_files: [
                { name: 'keyword', label: '搜索关键词', type: 'text', defaultValue: '' },
                { name: 'search_path', label: '搜索路径', type: 'text', defaultValue: '' },
                { name: 'page', label: '页码', type: 'number', defaultValue: 1 },
                { name: 'page_size', label: '每页数量', type: 'number', defaultValue: 12 }
            ],
            get_dir_size: [
                { name: 'dir_path', label: '目录路径', type: 'text', defaultValue: '' }
            ]
        };
        
        // 生成动态输入框
        function generateDynamicFields() {
            const action = document.getElementById('action').value;
            const dynamicFieldsContainer = document.getElementById('dynamicFields');
            
            // 清空现有字段
            dynamicFieldsContainer.innerHTML = '';
            
            // 获取当前操作需要的参数
            const params = actionParams[action] || [];
            
            // 生成输入框
            params.forEach(param => {
                const formGroup = document.createElement('div');
                formGroup.className = 'form-group';
                
                const label = document.createElement('label');
                label.setAttribute('for', param.name);
                label.textContent = param.label;
                formGroup.appendChild(label);
                
                if (param.type === 'file') {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.id = param.name;
                    input.name = param.name;
                    formGroup.appendChild(input);
                } else if (param.type === 'number') {
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.id = param.name;
                    input.name = param.name;
                    input.value = param.defaultValue;
                    formGroup.appendChild(input);
                } else {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.id = param.name;
                    input.name = param.name;
                    input.value = param.defaultValue;
                    formGroup.appendChild(input);
                }
                
                dynamicFieldsContainer.appendChild(formGroup);
            });
        }
        
        // 为操作类型选择框添加change事件
        document.getElementById('action').addEventListener('change', generateDynamicFields);
        
        // 执行API操作
        document.getElementById('apiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const action = document.getElementById('action').value;
            
            // 构建请求体
            const formData = new FormData();
            formData.append('action', action);
            
            // 收集动态生成的输入框的值
            const params = actionParams[action] || [];
            const collectedParams = {};
            
            params.forEach(param => {
                const input = document.getElementById(param.name);
                if (input) {
                    if (param.type === 'file') {
                        if (input.files && input.files[0]) {
                            formData.append(param.name, input.files[0]);
                            collectedParams[param.name] = input.files[0].name;
                        }
                    } else {
                        const value = input.value;
                        if (value) {
                            formData.append(param.name, value);
                            collectedParams[param.name] = value;
                        }
                    }
                }
            });
            
            // 发送请求
            fetch('../app/file_operate.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                // 检查响应是否为JSON
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return res.json().then(data => ({ type: 'json', data }));
                } else {
                    return res.text().then(text => ({ type: 'html', data: text }));
                }
            })
            .then(result => {
                if (result.type === 'json') {
                    // 显示JSON响应结果
                    document.getElementById('responseContent').textContent = beautifyResponse(result.data);
                    
                    // 记录操作日志
                    const logEntry = {
                        timestamp: new Date().toISOString(),
                        action: action,
                        params: collectedParams,
                        response: result.data
                    };
                    
                    // 添加到日志内容
                    const logContent = document.getElementById('logContent');
                    logContent.textContent += JSON.stringify(logEntry, null, 2) + '\n\n';
                } else {
                    // 显示HTML响应结果
                    document.getElementById('responseContent').textContent = 'HTML响应: ' + result.data.substring(0, 500) + (result.data.length > 500 ? '...' : '');
                    
                    // 打开小窗口显示完整HTML
                    const popup = window.open('', 'HTML响应', 'width=800,height=600');
                    if (popup) {
                        popup.document.write(`
                            <html>
                            <head>
                                <title>HTML响应</title>
                                <style>
                                    body { font-family: Arial, sans-serif; margin: 20px; }
                                    h1 { color: #333; }
                                    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
                                    .close-btn { margin-top: 20px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
                                </style>
                            </head>
                            <body>
                                <h1>HTML响应内容</h1>
                                <pre>${escapeHtml(result.data)}</pre>
                                <button class="close-btn" onclick="window.close()">关闭窗口</button>
                            </body>
                            </html>
                        `);
                        popup.document.close();
                    }
                    
                    // 记录操作日志
                    const logEntry = {
                        timestamp: new Date().toISOString(),
                        action: action,
                        params: collectedParams,
                        error: 'HTML响应',
                        html_content: result.data.substring(0, 1000) + (result.data.length > 1000 ? '...' : '')
                    };
                    
                    // 添加到日志内容
                    const logContent = document.getElementById('logContent');
                    logContent.textContent += JSON.stringify(logEntry, null, 2) + '\n\n';
                }
                
                // 滚动到底部
                const logContent = document.getElementById('logContent');
                logContent.scrollTop = logContent.scrollHeight;
            })
            .catch(error => {
                const errorMsg = '请求失败: ' + error.message;
                document.getElementById('responseContent').textContent = errorMsg;
                
                // 检查是否是JSON解析错误（可能是HTML响应）
                if (error.message.includes('Unexpected token') && error.message.includes('<')) {
                    // 重新发送请求，以文本形式获取完整的HTML内容
                    fetch('../app/file_operate.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(htmlContent => {
                        // 显示HTML响应结果
                        document.getElementById('responseContent').textContent = 'HTML响应: ' + htmlContent.substring(0, 500) + (htmlContent.length > 500 ? '...' : '');
                        
                        // 打开小窗口显示完整HTML
                        const popup = window.open('', 'HTML响应', 'width=800,height=600');
                        if (popup) {
                            popup.document.write(`
                                <html>
                                <head>
                                    <title>报错log</title>
                                    <style>
                                        body { font-family: Arial, sans-serif; margin: 20px; }
                                        h1 { color: #333; }
                                        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
                                        .close-btn { margin-top: 20px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
                                    </style>
                                </head>
                                <body>
                                    <h1>响应内容</h1>
                                    ${htmlContent}
                                    <br />
                                    <h2>报错信息</h2>
                                    <pre>${escapeHtml(htmlContent)}</pre>
                                    <pre>${escapeHtml(errorMsg)}</pre>
                                    <button class="close-btn" onclick="window.close()">关闭窗口</button>
                                </body>
                                </html>
                            `);
                            popup.document.close();
                        }
                        
                        // 记录操作日志
                        const logEntry = {
                            timestamp: new Date().toISOString(),
                            action: action,
                            params: collectedParams,
                            error: errorMsg,
                            html_content: htmlContent.substring(0, 1000) + (htmlContent.length > 1000 ? '...' : '')
                        };
                        
                        // 添加到日志内容
                        const logContent = document.getElementById('logContent');
                        logContent.textContent += JSON.stringify(logEntry, null, 2) + '\n\n';
                        logContent.scrollTop = logContent.scrollHeight;
                    })
                    .catch(fetchError => {
                        // 如果获取HTML内容也失败了，显示错误信息
                        const fetchErrorMsg = '获取HTML内容失败: ' + fetchError.message;
                        document.getElementById('responseContent').textContent = fetchErrorMsg;
                        
                        // 记录错误日志
                        const logEntry = {
                            timestamp: new Date().toISOString(),
                            action: action,
                            params: collectedParams,
                            error: fetchErrorMsg
                        };
                        
                        const logContent = document.getElementById('logContent');
                        logContent.textContent += JSON.stringify(logEntry, null, 2) + '\n\n';
                        logContent.scrollTop = logContent.scrollHeight;
                    });
                } else {
                    // 记录错误日志
                    const logEntry = {
                        timestamp: new Date().toISOString(),
                        action: action,
                        params: collectedParams,
                        error: errorMsg
                    };
                    
                    const logContent = document.getElementById('logContent');
                    logContent.textContent += JSON.stringify(logEntry, null, 2) + '\n\n';
                    logContent.scrollTop = logContent.scrollHeight;
                }
            });
        });
        
        // 导出日志函数
        function exportLogs() {
            const logContent = document.getElementById('logContent').textContent;
            if (!logContent || logContent === '操作日志将显示在这里') {
                alert('没有可导出的日志内容');
                return;
            }
            
            const blob = new Blob([logContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'api_operations_log_' + new Date().toISOString().replace(/[:.]/g, '-') + '.txt';
            a.click();
            URL.revokeObjectURL(url);
        }
        
        // 清空日志函数
        function clearLogs() {
            if (confirm('确定要清空日志内容吗？')) {
                document.getElementById('logContent').textContent = '操作日志将显示在这里';
            }
        }
        
        // 美化显示JSON响应
        function beautifyResponse(data) {
            try {
                return JSON.stringify(data, null, 2);
            } catch (e) {
                return String(data);
            }
        }
        
        // 初始化页面时生成默认输入框
        window.onload = function() {
            generateDynamicFields();
            
            fetch('../app/file_operate.php', {
                method: 'POST',
                body: 'action=get_dirs'
            })
            .then(res => res.json())
            .then(data => {
                console.log('目录结构:', data);
            })
            .catch(error => {
                console.error('获取目录结构失败:', error);
            });
        };
    </script>
</body>
</html>