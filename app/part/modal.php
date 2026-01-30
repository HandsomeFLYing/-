<?php
//防止url直接访问
if (basename($_SERVER['PHP_SELF']) === 'modal.php') {
    header('Location: 404.php');
    exit;
}
?>
    <!-- 移动弹窗 -->
    <div class="modal" id="moveModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeFunctionModal()">&times;</span>
            <h3>移动图片</h3>
            <input type="hidden" id="moveSourcePath">
            <input type="hidden" id="moveTargetPath">
            <div class="form-item">
                <label>目标路径</label>
                <div id="dirTreeContainer" style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; max-height: 300px; overflow-y: auto;">
                    <!-- 目录树将通过JavaScript动态生成 -->
                    <div style="text-align: center; color: #666; padding: 20px;">加载目录树中...</div>
                </div>
            </div>
            <div class="form-item">
                <label>新建路径</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="newPathInput" placeholder="例如：新文件夹/子文件夹" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <button class="action-btn" onclick="createNewPath()" style="padding: 8px 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">创建</button>
                </div>
            </div>
            <div class="form-item">
                <label>当前选择：</label>
                <span id="currentSelectedPath" style="font-family: monospace;">请选择目标目录</span>
            </div>
            <button class="upload-btn" onclick="doMove()">确认移动</button>
        </div>
    </div>

    <!-- 删除确认弹窗 -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeFunctionModal()">&times;</span>
            <h3>确定要删除图片吗？将无法恢复</h3>
            <input type="hidden" id="deletePath">
            <button class="upload-btn" onclick="doDelete()">确认</button>
        </div>
    </div>

    <!-- 消息弹窗 -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeFunctionModal()">&times;</span>
            <h3 id="messageTitle"></h3>
            <p id="messageContent" align="center"></p>
            <button class="upload-btn" id="messageBtn" onclick="closeFunctionModal()">确认</button>
        </div>
    </div>

    <!-- 重命名弹窗 -->
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeFunctionModal()">&times;</span>
            <h3>重命名图片</h3>
            <div class="form-item">
                <label for="renameInput">新名称（含扩展名）：</label>
                <input type="text" id="renameInput" style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <input type="hidden" id="renamePath">
            <button class="upload-btn" onclick="doRename()">确认重命名</button>
        </div>
    </div>

    <!-- 图片预览弹窗 -->
    <div class="modal" id="imageModal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <div class="modal-image" id="imageContainer">
                <img id="modalImage" src="" alt="预览图片">
                <div class="image-controls">
                    <button class="control-btn" id="zoomOutBtn">-</button>
                    <button class="control-btn" id="resetBtn">⌂</button>
                    <button class="control-btn" id="zoomInBtn">+</button>
                    <button class="control-btn" id="rotateBtn">↻</button>
                    <button class="toggle-info-btn" id="toggleInfoBtn">ℹ️</button>
                </div>
            </div>
            <div class="modal-info" id="modalInfo">
                <h3 id="modalTitle">图片信息</h3>
                <div class="info-item">
                    <span class="info-label">名称：</span>
                    <span id="modalName"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">时间：</span>
                    <span id="modalModified"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">大小：</span>
                    <span id="modalSize"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">尺寸：</span>
                    <span id="modalDimensions"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">路径：</span>
                    <span id="modalPath"></span>
                </div>
            </div>
        </div>
    </div>