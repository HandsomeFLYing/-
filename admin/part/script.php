<?php
//防止url直接访问
if (basename($_SERVER['PHP_SELF']) === 'script.php') {
    header('Location: 404.php');
    exit;
}
?>
<script>
    // 基础变量
    let currentPage = <?php echo $pageResult['currentPage']; ?>;
    let hasMore = <?php echo $pageResult['hasMore'] ? 'true' : 'false'; ?>;
    let isLoading = false;
    const path = "<?php echo urlencode($currentPath); ?>";
    const viewMode = "<?php echo urlencode($viewMode); ?>";
    let sortBy = "<?php echo urlencode($sortBy); ?>";
    let groupBy = "<?php echo urlencode($groupBy); ?>";
    let pageSize = calculateOptimalPageSize();

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
    const modalImage = document.getElementById('modalImage');
    const imageContainer = document.getElementById('imageContainer');
    const modalInfo = document.getElementById('modalInfo');
    const toggleInfoBtn = document.getElementById('toggleInfoBtn');
    let isInfoVisible = true;

    // 缩略图生成配置 - 简化：使用懒加载
    let generatingCount = 0; // 当前正在生成的缩略图数量
    const MAX_CONCURRENT = 3; // 最多同时生成3个缩略图

    // 移动端菜单控制
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const categoryNav = document.getElementById('categoryNav');
    mobileMenuBtn.addEventListener('click', function() {
        categoryNav.classList.toggle('open');
    });

    // 确认弹窗代码
    <?php require_once 'ifwin.php' ?>
    
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

    /**
     * 计算最优的每页加载数量（自动适配屏幕）
     */
    function calculateOptimalPageSize() {
        const viewportWidth = window.innerWidth - (window.innerWidth <= 768 ? 30 : 330);
        const viewportHeight = window.innerHeight - 150;
        const columnWidth = (window.innerWidth <= 768 ? 140 : 220) + (window.innerWidth <= 768 ? 10 : 20);
        const rowHeight = (window.innerWidth <= 768 ? 140 : 220) + (window.innerWidth <= 768 ? 10 : 20);

        const columnCount = Math.max(1, Math.floor(viewportWidth / columnWidth));
        const rowCount = Math.max(2, Math.floor(viewportHeight / rowHeight));
        let optimalSize = Math.floor(columnCount * rowCount * 1.5);
        optimalSize = Math.max(8, Math.min(40, optimalSize));
        
        //console.log(`自动适配加载数量：${optimalSize}张`);
        return optimalSize;
    }

    /**
     * 切换回到顶部按钮的显示/隐藏
     */
    function toggleBackToTopButton() {
        const backToTopBtn = document.getElementById('backToTopBtn');
        const imageGridContainer = document.getElementById('imageGridContainer');
        const scrollTop = imageGridContainer.scrollTop;
        
        // 滚动超过300px时显示按钮
        if (scrollTop > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    }

    /**
     * 平滑滚动到顶部
     */
    function scrollToTop() {
        const imageGridContainer = document.getElementById('imageGridContainer');
        
        // 平滑滚动到顶部
        imageGridContainer.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    /**
     * 检查是否应该加载更多内容
     */
    function shouldLoadMore() {
        const imageGridContainer = document.getElementById('imageGridContainer');
        
        if (!imageGridContainer) return false;
        
        const scrollTop = imageGridContainer.scrollTop;
        const scrollHeight = imageGridContainer.scrollHeight;
        const clientHeight = imageGridContainer.clientHeight;
        
        // 滚动到距离底部200px内时加载
        const isNearBottom = (scrollTop + clientHeight) >= (scrollHeight - 200);
        
        //console.log(`滚动检查: scrollTop=${scrollTop}, clientHeight=${clientHeight}, scrollHeight=${scrollHeight}, 接近底部=${isNearBottom}`);
        
        return isNearBottom;
    }

    /**
     * 自动填充页面（仅首次加载时填充，且不阻塞下滑加载）
     */
    async function autoFillPage() {
        if (!hasMore || isLoading) return;

        // 限制自动填充最多加载3页，避免一次性加载过多
        let loadCount = 0;
        const maxAutoLoad = 3;

        while (loadCount < maxAutoLoad && hasMore && !isLoading) {
            await loadNextPage(true);
            loadCount++;

            // 短暂延迟，让图片渲染完成后再检查
            await new Promise(resolve => setTimeout(resolve, 100));

            // 检查是否已铺满，铺满则停止自动填充
            if (isContentFilled()) break;
        }
    }

    /**
     * 判断图片是否铺满右侧可视区域（修正计算逻辑）
     */
    function isContentFilled() {
        const galleryContainer = document.getElementById('galleryContainer');
        const imageGridContainer = document.getElementById('imageGridContainer');
        
        if (!galleryContainer || !imageGridContainer) return true;
        
        const visibleHeight = galleryContainer.clientHeight || window.innerHeight;
        const contentHeight = imageGridContainer.offsetHeight || 0;
        
        // 预留20%的余量，避免过度填充
        return contentHeight >= visibleHeight * 1.2;
    }

    /**
     * 排序/分组切换事件处理
     */
    function onSortOrGroupChange() {
        sortBy = document.getElementById('sortBySelect').value;
        groupBy = document.getElementById('groupBySelect').value;
        
        currentPage = 1;
        hasMore = true;
        isLoading = false; // 切换排序时重置加载状态
        
        document.getElementById('imageGridContainer').innerHTML = '';
        document.getElementById('noMore').classList.remove('show');
        
        loadNextPage(false, true).then(() => {
            autoFillPage().then(initThumbGenerateList); // 重新收集需要生成的缩略图
        });
    }

    // 窗口大小变化时重新计算分页大小
    window.addEventListener('resize', () => {
        pageSize = calculateOptimalPageSize();
    });

    // 滚动加载配置
    let lastScrollTop = 0; // 上次滚动位置
    const SCROLL_THRESHOLD = 50; // 滚动阈值，避免微小滚动触发
    let lastLoadTime = 0; // 上次加载时间
    const LOAD_INTERVAL = 1000; // 加载间隔1秒，避免过于频繁

    /**
     * 滚动加载下一页（检测内容底部是否进入视口）
     */
    function handleScroll() {
        const currentScrollTop = galleryContainer.scrollTop || window.scrollY || 0;
        const scrollDelta = currentScrollTop - lastScrollTop;
        const now = Date.now();

        // 检查是否需要加载更多内容
        if (shouldLoadMore() && 
            (now - lastLoadTime) > LOAD_INTERVAL && 
            hasMore && !isLoading) {
            //console.log('满足加载条件，开始显示加载动画');
            lastLoadTime = now;
            
            // 显示2秒加载动画
            const loadingElement = document.getElementById('loading');
            loadingElement.classList.add('show');
            
            setTimeout(() => {
                if (!isLoading) { // 再次检查是否仍在加载中
                    loadNextPage().then(() => {
                        initThumbGenerateList(); // 加载新页后收集需要生成的缩略图
                    });
                }
            }, 1000); // 等待N秒后开始实际加载
        }

        lastScrollTop = currentScrollTop;
    }

    // 添加防抖的滚动监听，监听图片网格容器的滚动
    let scrollTimer = null;
    const imageGridContainer = document.getElementById('imageGridContainer');

    // 监听图片网格容器的滚动事件
    imageGridContainer.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(handleScroll, 50); // 50ms防抖，避免高频触发
        toggleBackToTopButton(); // 检查是否显示回到顶部按钮
    });

    /**
     * 加载下一页图片（完善错误处理，确保isLoading重置）
     */
    function loadNextPage(isAutoFill = false, isFirstPage = false) {
        return new Promise((resolve, reject) => {
            if (isLoading || (!hasMore && !isFirstPage)) {
                resolve();
                return;
            }
            
            isLoading = true;
            if (!isAutoFill) {
                document.getElementById('loading').classList.add('show');
            }
            
            const targetPage = isFirstPage ? 1 : currentPage + 1;
            const url = `?path=${path}&view_mode=${viewMode}&sort_by=${sortBy}&group_by=${groupBy}&page=${targetPage}&page_size=${pageSize}&ajax=1`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`请求失败：${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (isFirstPage) {
                        document.getElementById('imageGridContainer').innerHTML = data.html;
                    } else {
                        document.getElementById('imageGridContainer').insertAdjacentHTML('beforeend', data.html);
                    }
                    
                    currentPage = data.currentPage || targetPage; // 兼容数据异常
                    hasMore = data.hasMore || false;
                    isLoading = false; // 强制重置加载状态
                    
                    if (!isAutoFill) {
                        document.getElementById('loading').classList.remove('show');
                    }
                    
                    if (!hasMore) {
                        document.getElementById('noMore').classList.add('show');
                    }
                    
                    bindImageClickEvents();
                    resolve();
                })
                .catch(error => {
                    console.error('加载失败：', error);
                    isLoading = false; // 错误时也重置加载状态
                    if (!isAutoFill) {
                        document.getElementById('loading').classList.remove('show');
                        alert('加载失败，请稍后重试');
                    }
                    reject(error);
                });
        });
    }

    /**
     * 绑定图片点击预览事件
     */
    function bindImageClickEvents() {
        document.querySelectorAll('.image-item').forEach(item => {
            item.addEventListener('click', function() {
                const modal = document.getElementById('imageModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalName = document.getElementById('modalName');
                const modalPath = document.getElementById('modalPath');
                const modalSize = document.getElementById('modalSize');
                const modalDimensions = document.getElementById('modalDimensions');
                const modalModified = document.getElementById('modalModified');
                
                // 重置图片状态
                resetImage();
                
                // 提取原图信息
                const originalUrl = this.dataset.url; 
                const imageName = this.dataset.name;
                const imagePath = this.dataset.path;
                const imageSize = this.dataset.size;
                const imageDimensions = this.dataset.dimensions;
                const imageModified = this.dataset.modified;
                
                // 填充弹窗信息
                modalTitle.textContent = imageName;
                modalName.textContent = imageName;
                modalPath.textContent = imagePath;
                modalSize.textContent = imageSize;
                modalDimensions.textContent = imageDimensions;
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
                
                // // 控制台验证
                // console.log('=== 图片预览验证 ===');
                // console.log('缩略图URL：', this.querySelector('img').src);
                // console.log('原图URL：', originalUrl);
                
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
     * 初始化：使用 Intersection Observer 监听需要生成缩略图的图片
     */
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



    /**
     * 生成单张缩略图并替换默认图 - 优化：限制并发数
     */
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
     * 关闭预览弹窗
     */
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

    // ========== 移动端触控事件适配（节流优化，避免缩放闪烁） ==========
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

    // 绑定预览控件事件
    document.getElementById('zoomInBtn').addEventListener('click', () => zoomImage(0.2));
    document.getElementById('zoomOutBtn').addEventListener('click', () => zoomImage(-0.2));
    document.getElementById('resetBtn').addEventListener('click', resetImage);
    document.getElementById('rotateBtn').addEventListener('click', rotateImage);
    toggleInfoBtn.addEventListener('click', toggleInfoPanel);

    // 点击弹窗外区域关闭
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('imageModal');
        if (event.target === modal) closeModal();
    });

    // ESC键关闭弹窗
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') closeModal();
    });

    // 搜索图片功能
    function onSearchInput() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
        const imageItems = document.querySelectorAll('.image-item');
        
        imageItems.forEach(item => {
            const imageName = item.dataset.name.toLowerCase();
            if (searchTerm === '' || imageName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // 检查是否有搜索结果
        const visibleItems = Array.from(imageItems).filter(item => item.style.display !== 'none');
        const noMoreElement = document.getElementById('noMore');
        
        if (searchTerm !== '' && visibleItems.length === 0) {
            // 没有搜索结果，显示提示
            if (!document.getElementById('noSearchResults')) {
                const noResultsElement = document.createElement('div');
                noResultsElement.id = 'noSearchResults';
                noResultsElement.className = 'no-more';
                noResultsElement.style.display = 'block';
                noResultsElement.innerHTML = '<span>没有找到匹配的图片</span>';
                document.getElementById('galleryContainer').appendChild(noResultsElement);
            }
            document.getElementById('noSearchResults').style.display = 'block';
            noMoreElement.style.display = 'none';
        } else {
            // 有搜索结果或搜索框为空，隐藏提示
            const noResultsElement = document.getElementById('noSearchResults');
            if (noResultsElement) {
                noResultsElement.style.display = 'none';
            }
            noMoreElement.style.display = noMoreElement.classList.contains('show') ? 'block' : 'none';
        }
    }

    // 处理搜索框回车键事件
    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm) {
                // 跳转到搜索结果页面
                window.location.href = `search.php?keyword=${encodeURIComponent(searchTerm)}&page=1`;
            }
        }
    }

    onSortOrGroupChange(); // 初始加载第一页

    // 页面加载完成初始化（确保滚动监听先绑定）
    window.addEventListener('load', function() {
        // 绑定图片点击预览事件
        bindImageClickEvents();
        
        // 绑定回到顶部按钮事件
        document.getElementById('backToTopBtn').addEventListener('click', scrollToTop);
        
        // 延迟执行自动填充，确保滚动监听已生效
        setTimeout(() => {
            autoFillPage().then(() => {
                // 页面加载完成后初始化缩略图懒加载
                initThumbGenerateList();
            });
        }, 300);
    });
</script>