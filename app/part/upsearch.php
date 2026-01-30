<?php
//防止url直接访问
if (basename($_SERVER['PHP_SELF']) === 'upsearch.php') {
    header('Location: 404.php');
    exit;
}
?>
    <script>
        // 基础功能
        function logout() {
            if (confirm('确定退出登录吗？')) {
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
            }
        }
        
        // 图片预览变量
        let scale = 1;
        let rotation = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let imgX = 0;
        let imgY = 0;
        // 移动端触控变量
        let initialDistance = 0;
        let initialScale = 1;
        let modalImage = null;
        let imageContainer = null;
        let modalInfo = null;
        let toggleInfoBtn = null;
        let isInfoVisible = true;
        
        // 缩略图生成配置
        let generatingCount = 0; // 当前正在生成的缩略图数量
        const MAX_CONCURRENT = 3; // 最多同时生成3个缩略图
        
        // 生成单张缩略图并替换默认图 - 优化：限制并发数
        function generateSingleThumb(imagePath, element) {
            // 检查并发数
            if (generatingCount >= MAX_CONCURRENT) {
                // 延迟执行
                setTimeout(() => generateSingleThumb(imagePath, element), 100);
                return;
            }

            generatingCount++;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../app/generate_thumb.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                generatingCount--;
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        // 替换为生成的缩略图
                        const img = element.querySelector('img');
                        img.src = "/" + res.thumb_url;
                        img.alt = element.dataset.name;
                        // 标记为已生成（避免重复处理）
                        element.dataset.thumbNeedGenerate = '0';
                        //console.log(`生成成功：${imagePath}`);
                    } else {
                        // 生成失败，显示错误图片
                        const img = element.querySelector('img');
                        img.src = '../error.png';
                        img.alt = '缩略图生成失败 - ' + element.dataset.name;
                        // 标记为已尝试生成，避免重复尝试
                        element.dataset.thumbNeedGenerate = '0';
                        console.warn(`生成失败：${imagePath}，原因：${res.msg}`);
                    }
                } catch (e) {
                    // 解析失败也显示错误图片
                    const img = element.querySelector('img');
                    img.src = '../error.png';
                    img.alt = '缩略图生成失败 - ' + element.dataset.name;
                    element.dataset.thumbNeedGenerate = '0';
                    console.error(`解析失败：${imagePath}`, e);
                }
            };
            xhr.onerror = function() {
                generatingCount--;
                console.error(`请求失败：${imagePath}`);
            };
            // 发送请求
            xhr.send(`image_path=${encodeURIComponent(imagePath)}`);
        }
        
        // 初始化：使用 Intersection Observer 监听需要生成缩略图的图片
        function initThumbGenerateList() {
            // 创建 Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const item = entry.target;
                        const needGenerate = item.dataset.thumbNeedGenerate === '1';
                        if (needGenerate) {
                            // 图片进入视口，开始生成缩略图
                            generateSingleThumb(item.dataset.path, item);
                            // 停止观察这个元素
                            observer.unobserve(item);
                        }
                    }
                });
            }, {
                rootMargin: '50px' // 提前50px开始生成
            });

            // 观察所有需要生成缩略图的图片
            document.querySelectorAll('.image-item[data-thumb-need-generate="1"]').forEach(item => {
                observer.observe(item);
            });

            //console.log('缩略图生成已启用懒加载模式');
        }
        
        // 绑定图片点击预览事件
        function bindImageClickEvents() {
            document.querySelectorAll('.image-item').forEach(item => {
                item.addEventListener('click', function() {
                    const modal = document.getElementById('imageModal');
                    const modalTitle = document.getElementById('modalTitle');
                    const modalName = document.getElementById('modalName');
                    const modalPath = document.getElementById('modalPath');
                    const modalSize = document.getElementById('modalSize');
                    const modalModified = document.getElementById('modalModified');
                    
                    // 重置图片状态
                    resetImage();
                    
                    // 提取原图信息
                    const originalUrl = this.dataset.url; 
                    const imageName = this.dataset.name;
                    const imagePath = this.dataset.path;
                    const imageSize = this.dataset.size;
                    const imageModified = this.dataset.modified;
                    
                    // 填充弹窗信息
                    modalTitle.textContent = imageName;
                    modalName.textContent = imageName;
                    modalPath.textContent = imagePath;
                    modalSize.textContent = imageSize;
                    modalModified.textContent = imageModified;
                    
                    // 预加载优化：先显示缩略图，再加载原图
                    modalImage.src = this.querySelector('img').src; // 先显示缩略图
                    
                    // 立即应用统一大小缩放
                    setTimeout(() => resetImage(), 50);
                    
                    const img = new Image();
                    img.src = originalUrl;
                    img.onload = function() {
                        modalImage.src = originalUrl; // 原图加载完成后替换
                        // 重新计算缩放比例适应新图片
                        setTimeout(() => resetImage(), 100);
                        console.log('原图真实尺寸：', this.naturalWidth + '×' + this.naturalHeight);
                    };
                    
                    // 显示弹窗
                    modal.style.display = 'block';
                    
                    // 原图加载失败降级处理
                    modalImage.onerror = function() {
                        alert('原图加载失败，将显示缩略图');
                        this.src = item.querySelector('img').src;
                        setTimeout(() => resetImage(), 50);
                    };
                });
            });
        }
        
        /**
         * 重置图片缩放/旋转/位置 - 优化：统一初始显示大小
         */
        function resetImage() {
            scale = 1;
            rotation = 0;
            imgX = 0;
            imgY = 0;
            const imageControls = document.querySelector('.image-controls');
            
            // 统一初始显示大小：适应容器宽度，最大不超过90%视口
            const containerRect = imageContainer.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            // 计算适合的初始缩放比例
            const maxWidth = Math.min(containerRect.width * 0.9, viewportWidth * 0.9);
            const maxHeight = Math.min(containerRect.height * 0.9, viewportHeight * 0.8);
            
            if (modalImage.naturalWidth && modalImage.naturalHeight) {
                const scaleX = maxWidth / modalImage.naturalWidth;
                const scaleY = maxHeight / modalImage.naturalHeight;
                scale = Math.min(scaleX, scaleY, 1); // 不超过原始大小
            }
            
            updateImageTransform();
            if (!isInfoVisible) toggleInfoPanel();
            
            // 确保初始状态按键在属性面板上方
            imageControls.classList.add('above-info');
        }
        
        /**
         * 更新图片变换样式（硬件加速优化，避免缩放闪烁）
         */
        function updateImageTransform() {
            modalImage.style.transform = `translate(-50%, -50%) translate(${imgX}px, ${imgY}px) scale(${scale}) rotate(${rotation}deg) translateZ(0)`;
        }
        
        /**
         * 缩放图片（限制最大缩放为5倍，避免像素块）
         */
        function zoomImage(delta) {
            if (isInfoVisible) toggleInfoPanel();
            scale = Math.max(0.1, Math.min(5, scale + delta));
            updateImageTransform();
        }
        
        /**
         * 旋转图片
         */
        function rotateImage() {
            rotation += 90;
            if (rotation >= 360) rotation = 0;
            updateImageTransform();
        }
        
        /**
         * 切换信息面板显示/隐藏
         */
        function toggleInfoPanel() {
            isInfoVisible = !isInfoVisible;
            const imageControls = document.querySelector('.image-controls');
            
            if (isInfoVisible) {
                modalInfo.classList.remove('hidden');
                // 属性面板显示时，按键移到面板上方
                imageControls.classList.add('above-info');
                toggleInfoBtn.textContent = 'ℹ️';
            } else {
                modalInfo.classList.add('hidden');
                // 属性面板隐藏时，按键回到底部
                imageControls.classList.remove('above-info');
                toggleInfoBtn.textContent = '👁️';
            }
        }
        
        // 关闭预览弹窗
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
            resetImage();
            isInfoVisible = true;
            modalInfo.classList.remove('hidden');
            toggleInfoBtn.textContent = 'ℹ️';
            // 重置按键位置
            const imageControls = document.querySelector('.image-controls');
            imageControls.classList.add('above-info');
        }
        
        // 页面加载完成初始化
        window.addEventListener('load', function() {
            // 初始化图片预览变量
            modalImage = document.getElementById('modalImage');
            imageContainer = document.getElementById('imageContainer');
            modalInfo = document.getElementById('modalInfo');
            toggleInfoBtn = document.getElementById('toggleInfoBtn');
            
            // 绑定图片点击预览事件
            bindImageClickEvents();
            
            // 初始化缩略图生成
            initThumbGenerateList();
            
            // 绑定预览控件事件
            document.getElementById('zoomInBtn').addEventListener('click', () => zoomImage(0.2));
            document.getElementById('zoomOutBtn').addEventListener('click', () => zoomImage(-0.2));
            document.getElementById('resetBtn').addEventListener('click', resetImage);
            document.getElementById('rotateBtn').addEventListener('click', rotateImage);
            toggleInfoBtn.addEventListener('click', toggleInfoPanel);
            
            // 鼠标滚轮缩放（保留PC端）
            imageContainer.addEventListener('wheel', function(e) {
                e.preventDefault();
                if (isInfoVisible) toggleInfoPanel();
                const mouseX = e.clientX;
                const mouseY = e.clientY;
                const rect = modalImage.getBoundingClientRect();
                const imgCenterX = rect.left + rect.width / 2;
                const imgCenterY = rect.top + rect.height / 2;
                const offsetX = mouseX - imgCenterX;
                const offsetY = mouseY - imgCenterY;
                const delta = e.deltaY > 0 ? -0.1 : 0.1;
                const oldScale = scale;
                scale = Math.max(0.1, Math.min(5, scale + delta));
                const scaleRatio = scale / oldScale;
                imgX = (imgX + offsetX) * scaleRatio - offsetX;
                imgY = (imgY + offsetY) * scaleRatio - offsetY;
                updateImageTransform();
            });
            
            // 鼠标拖拽（保留PC端）
            imageContainer.addEventListener('mousedown', (e) => {
                if (e.target === modalImage) {
                    isDragging = true;
                    imageContainer.classList.add('grabbing');
                    startX = e.clientX - imgX;
                    startY = e.clientY - imgY;
                    e.preventDefault();
                }
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                imgX = e.clientX - startX;
                imgY = e.clientY - startY;
                updateImageTransform();
            });
            
            document.addEventListener('mouseup', () => {
                isDragging = false;
                imageContainer.classList.remove('grabbing');
            });
            
            // 移动端触控事件适配
            let lastTouchTime = 0;
            const THROTTLE_DELAY = 50;
            
            /**
             * 计算两点之间的距离（用于捏合缩放）
             */
            function getDistance(touch1, touch2) {
                const x = touch2.clientX - touch1.clientX;
                const y = touch2.clientY - touch1.clientY;
                return Math.sqrt(x * x + y * y);
            }
            
            /**
             * 处理触控开始事件
             */
            function handleTouchStart(e) {
                if (e.target !== modalImage) return;
                
                // 双指触摸：记录初始距离和缩放比例
                if (e.touches.length === 2) {
                    initialDistance = getDistance(e.touches[0], e.touches[1]);
                    initialScale = scale;
                    isDragging = false;
                } 
                // 单指触摸：准备拖拽
                else if (e.touches.length === 1) {
                    isDragging = true;
                    startX = e.touches[0].clientX - imgX;
                    startY = e.touches[0].clientY - imgY;
                    imageContainer.classList.add('grabbing');
                }
                e.preventDefault();
            }
            
            /**
             * 处理触控移动事件（带节流）
             */
            function handleTouchMove(e) {
                if (e.target !== modalImage) return;
                const imageControls = document.querySelector('.image-controls');
                const now = Date.now();
                // 节流：避免高频触发
                if (now - lastTouchTime < THROTTLE_DELAY) {
                    return;
                }
                lastTouchTime = now;

                // 双指捏合缩放
                if (e.touches.length === 2) {
                    const currentDistance = getDistance(e.touches[0], e.touches[1]);
                    const scaleRatio = currentDistance / initialDistance;
                    scale = Math.max(0.1, Math.min(5, initialScale * scaleRatio));
                    updateImageTransform();
                    isInfoVisible = false;
                    modalInfo.classList.add('hidden');
                    // 属性面板隐藏时，按键回到底部
                    imageControls.classList.remove('above-info');
                    toggleInfoBtn.textContent = '👁️';
                } 
                // 单指拖拽
                else if (e.touches.length === 1 && isDragging) {
                    imgX = e.touches[0].clientX - startX;
                    imgY = e.touches[0].clientY - startY;
                    updateImageTransform();
                }
                
                e.preventDefault();
            }
            
            /**
             * 处理触控结束事件
             */
            function handleTouchEnd() {
                isDragging = false;
                imageContainer.classList.remove('grabbing');
            }
            
            // 绑定触控事件
            imageContainer.addEventListener('touchstart', handleTouchStart);
            imageContainer.addEventListener('touchmove', handleTouchMove);
            imageContainer.addEventListener('touchend', handleTouchEnd);
            imageContainer.addEventListener('touchcancel', handleTouchEnd);
        });
        
        // 点击弹窗外区域关闭
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) closeModal();
        });
        
        // ESC键关闭弹窗
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeFunctionModal();
            }
        });
    </script>