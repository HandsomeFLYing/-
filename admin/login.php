<?php
session_start();
// 如果已登录，直接跳转
$loginUser = require_once  '../app/auto-login.php';
if ($loginUser !== null) {
    header('Location: ../admin');
    exit;
}
// if ($loginUser === 'user') {
//     header('Location: ../user');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 简约图库</title>
    <style>
        /* 复用主页风格，简化登录样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft Yahei", sans-serif;
        }
        body {
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 24px;
        }
        .form-item {
            margin-bottom: 20px;
        }
        .form-item label {
            display: block;
            margin-bottom: 8px;
            color: #666;
        }
        .form-item input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #4285f4;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #3367d6;
        }
        .error-msg {
            color: #ff4444;
            text-align: center;
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">账号登录</h2>
        <div class="error-msg" id="errorMsg"></div>
        <form id="loginForm">
            <div class="form-item">
                <label for="username">账号</label>
                <input type="text" id="username" name="username" required placeholder="请输入账号名">
            </div>
            <div class="form-item">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="请输入密码">
            </div>
            <button type="submit" class="login-btn">登录</button>
        </form>
        <a href="../">返回首页</a>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');

            fetch('../app/auto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.code === 1) {
                    // 登录成功，安全写入localStorage
                    const userData = {
                        username: data.data.username,
                        role: data.data.role,
                        encrypted_password: data.data.encrypted_password,
                        identity_key: data.data.identity_key,
                        login_date: data.data.login_date
                    };
                    localStorage.setItem('tk_user', JSON.stringify(userData));
                    window.location.href = 'index.php';
                } else {
                    errorMsg.style.display = 'block';
                    errorMsg.innerText = data.msg;
                }
            })
            .catch(err => {
                errorMsg.style.display = 'block';
                errorMsg.innerText = '网络错误，请重试';
            });
        });
    </script>
</body>
</html>