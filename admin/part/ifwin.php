    // 显示自定义确认弹窗
    function showConfirmDialog(title, message, onConfirm, onCancel) {
        // 创建弹窗容器
        const dialogContainer = document.createElement('div');
        dialogContainer.id = 'customConfirmDialog';
        dialogContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        
        // 创建弹窗内容
        const dialogContent = document.createElement('div');
        dialogContent.style.cssText = `
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        
        // 创建标题
        const dialogTitle = document.createElement('h3');
        dialogTitle.textContent = title;
        dialogTitle.style.cssText = `
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        `;
        
        // 创建消息
        const dialogMessage = document.createElement('p');
        dialogMessage.textContent = message;
        dialogMessage.style.cssText = `
            margin-bottom: 20px;
            color: #666;
        `;
        
        // 创建按钮容器
        const dialogButtons = document.createElement('div');
        dialogButtons.style.cssText = `
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        `;
        
        // 创建取消按钮
        const cancelButton = document.createElement('button');
        cancelButton.textContent = '取消';
        cancelButton.style.cssText = `
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: #333;
            cursor: pointer;
            font-size: 14px;
        `;
        cancelButton.addEventListener('click', function() {
            document.body.removeChild(dialogContainer);
            if (onCancel) onCancel();
        });
        
        // 创建确认按钮
        const confirmButton = document.createElement('button');
        confirmButton.textContent = '确认';
        confirmButton.style.cssText = `
            padding: 8px 16px;
            border: 1px solid #007bff;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-size: 14px;
        `;
        confirmButton.addEventListener('click', function() {
            document.body.removeChild(dialogContainer);
            if (onConfirm) onConfirm();
        });
        
        // 组装弹窗
        dialogButtons.appendChild(cancelButton);
        dialogButtons.appendChild(confirmButton);
        dialogContent.appendChild(dialogTitle);
        dialogContent.appendChild(dialogMessage);
        dialogContent.appendChild(dialogButtons);
        dialogContainer.appendChild(dialogContent);
        
        // 添加到页面
        document.body.appendChild(dialogContainer);
        
        // 点击弹窗外区域关闭
        dialogContainer.addEventListener('click', function(event) {
            if (event.target === dialogContainer) {
                document.body.removeChild(dialogContainer);
                if (onCancel) onCancel();
            }
        });
    }