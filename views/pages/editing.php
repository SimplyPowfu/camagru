<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $filterDir = __DIR__ . '/../../public/filter/';
    $stickers = [];
    if (is_dir($filterDir)) {
        $files = scandir($filterDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                $stickers[] = '/filter/' . $file;
            }
        }
    }
?>

<div class="card">
    <div class="main-edit-area">
        <h2 style="color: var(--primary); font-weight: 800; margin-bottom: 5px;">Editor Avanzato</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-info"></i> Trascina per spostare o usa la rotellina per ridimensionare gli sticker.
        </p>

        <div id="preview-container" class="preview-box" style="position: relative; width: 100%; max-width: 640px; aspect-ratio: 4/3; background: #000; margin: 0 auto 20px auto; overflow: hidden; border-radius: var(--radius); border: 2px solid var(--border);">
            <video id="video" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
            <img id="file-preview" src="#" alt="Anteprima" style="display: none; width: 100%; height: 100%; object-fit: contain;">
            <div id="sticker-overlay" style="position: absolute; inset: 0; pointer-events: none;"></div>
            <canvas id="canvas" width="640" height="480" style="display: none; width: 100%; height: 100%;"></canvas>
            
            <div id="save-loader" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.85); z-index: 100; flex-direction: column; align-items: center; justify-content: center;">
                <i class="fa-solid fa-circle-notch fa-spin fa-3x" style="color: var(--primary);"></i>
                <p style="margin-top: 15px; font-weight: 700; color: var(--text-main);">Salvataggio in corso...</p>
            </div>
        </div>

        <div class="sticker-selector">
            <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: var(--text-main);">1. Scegli i tuoi sticker</h3>
            <div class="sticker-list">
                <?php foreach ($stickers as $index => $src): ?>
                    <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" class="sticker-opt" data-id="<?= $index ?>" title="Clicca per aggiungere">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="controls">
            <div id="live-controls" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button id="btn-snap" class="btn btn-primary" disabled>
                    <i class="fa-solid fa-camera"></i> Scatta / Crea
                </button>
                <label for="file-input" class="btn btn-secondary">
                    <i class="fa-solid fa-upload"></i> Carica Foto
                </label>
                <input type="file" id="file-input" accept="image/*" style="display: none;">
                <button id="btn-webcam" class="btn btn-secondary" style="display: none;">
                    <i class="fa-solid fa-video"></i> Usa Webcam
                </button>
            </div>

            <div id="review-controls" style="display: none; gap: 10px;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button id="btn-save" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Conferma e Salva
                </button>
                <button id="btn-discard" class="btn btn-danger">
                    <i class="fa-solid fa-trash"></i> Scarta
                </button>
            </div>
        </div>
        <p id="message" style="margin-top: 15px; font-weight: 600;"></p>
    </div>
</div>

<script>
(function() {
    const AppState = {
        phase: 'live',
        source: 'webcam',
        hasWebcamAccess: false,
        isFileLoaded: false,
        activeStickers: new Map(),
        draggingSticker: null,
        dragOffset: { x: 0, y: 0 }
    };

    const DOM = {
        video: document.getElementById('video'),
        filePreview: document.getElementById('file-preview'),
        fileInput: document.getElementById('file-input'),
        canvas: document.getElementById('canvas'),
        ctx: document.getElementById('canvas').getContext('2d'),
        stickerOverlay: document.getElementById('sticker-overlay'),
        previewBox: document.getElementById('preview-container'),
        btnSnap: document.getElementById('btn-snap'),
        btnWebcam: document.getElementById('btn-webcam'),
        stickerOpts: document.querySelectorAll('.sticker-opt'),
        liveControls: document.getElementById('live-controls'),
        reviewControls: document.getElementById('review-controls'),
        btnSave: document.getElementById('btn-save'),
        btnDiscard: document.getElementById('btn-discard'),
        saveLoader: document.getElementById('save-loader'),
        message: document.getElementById('message')
    };

    function renderUI() {
        if (AppState.phase === 'live') {
            DOM.canvas.style.display = 'none';
            DOM.stickerOverlay.style.display = 'block';
            DOM.liveControls.style.display = 'flex';
            DOM.reviewControls.style.display = 'none';
            if (AppState.source === 'webcam') {
                DOM.video.style.display = 'block';
                DOM.filePreview.style.display = 'none';
                DOM.btnWebcam.style.display = 'none';
            } else {
                DOM.video.style.display = 'none';
                DOM.filePreview.style.display = 'block';
                DOM.btnWebcam.style.display = AppState.hasWebcamAccess ? 'inline-block' : 'none';
            }
        } else {
            DOM.video.style.display = 'none';
            DOM.filePreview.style.display = 'none';
            DOM.stickerOverlay.style.display = 'none';
            DOM.canvas.style.display = 'block';
            DOM.liveControls.style.display = 'none';
            DOM.reviewControls.style.display = 'flex';
        }

        DOM.stickerOpts.forEach(opt => {
            opt.classList.toggle('selected', AppState.activeStickers.has(opt.dataset.id));
        });

        const isReady = (AppState.source === 'webcam' && AppState.hasWebcamAccess) || (AppState.source === 'file' && AppState.isFileLoaded);
        DOM.btnSnap.disabled = !(AppState.activeStickers.size > 0 && isReady);
    }

    function updateStickerDOM(el, data) {
        el.style.left = (data.x / 640 * 100) + '%';
        el.style.top = (data.y / 480 * 100) + '%';
        el.style.width = (data.w / 640 * 100) + '%';
    }

    function setupInteractions(imgElement, id) {
        imgElement.addEventListener('mousedown', (e) => {
            e.preventDefault();
            AppState.draggingSticker = id;
            const rect = imgElement.getBoundingClientRect();
            AppState.dragOffset.x = e.clientX - rect.left;
            AppState.dragOffset.y = e.clientY - rect.top;
        });

        imgElement.addEventListener('wheel', (e) => {
            e.preventDefault();
            const data = AppState.activeStickers.get(id);
            const scaleAmount = 25;
            
            if (e.deltaY < 0) data.w = Math.min(data.w + scaleAmount, 1000); // Permetti resize molto grandi
            else data.w = Math.max(data.w - scaleAmount, 30);
            updateStickerDOM(imgElement, data);
        });
    }

    window.addEventListener('mousemove', (e) => {
        if (!AppState.draggingSticker) return;
        
        const containerRect = DOM.previewBox.getBoundingClientRect();
        const el = document.querySelector(`.dynamic-sticker[data-id="${AppState.draggingSticker}"]`);
        const data = AppState.activeStickers.get(AppState.draggingSticker);
        
        const scaleX = 640 / containerRect.width;
        const scaleY = 480 / containerRect.height;
        
        // Posizione in pixel rispetto allo schermo
        let newX_px = e.clientX - containerRect.left - AppState.dragOffset.x;
        let newY_px = e.clientY - containerRect.top - AppState.dragOffset.y;
        
        // Convertiamo le coordinate dello schermo in coordinate interne 640x480
        data.x = newX_px * scaleX;
        data.y = newY_px * scaleY;
        
        updateStickerDOM(el, data);
    });

    window.addEventListener('mouseup', () => { AppState.draggingSticker = null; });

    DOM.stickerOpts.forEach(opt => {
        opt.addEventListener('click', function() {
            const id = this.dataset.id;
            if (AppState.activeStickers.has(id)) {
                AppState.activeStickers.delete(id);
                document.querySelector(`.dynamic-sticker[data-id="${id}"]`)?.remove();
            } else {
                // Genera quasi al centro
                const data = { src: this.src, x: 220, y: 140, w: 200 };
                AppState.activeStickers.set(id, data);
                
                const img = document.createElement('img');
                img.src = data.src;
                img.classList.add('dynamic-sticker');
                img.dataset.id = id;
                img.style.position = 'absolute';
                img.style.pointerEvents = 'all'; 
                img.style.cursor = 'grab';
                img.style.zIndex = '20';
                
                setupInteractions(img, id);
                updateStickerDOM(img, data);
                DOM.stickerOverlay.appendChild(img);
            }
            renderUI();
        });
    });

    async function initWebcam() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            DOM.video.srcObject = stream;
            AppState.hasWebcamAccess = true;
            AppState.source = 'webcam';
        } catch (err) {
            AppState.hasWebcamAccess = false;
            AppState.source = 'file';
        }
        renderUI();
    }
    DOM.btnWebcam.addEventListener('click', () => { AppState.source = 'webcam'; renderUI(); });
    DOM.fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = new Image();
                img.onload = () => {
                    const tCanv = document.createElement('canvas');
                    tCanv.width = 640; 
                    tCanv.height = 480;
                    const tCtx = tCanv.getContext('2d');
                    
                    const ratio = Math.max(640 / img.width, 480 / img.height);
                    const drawW = img.width * ratio;
                    const drawH = img.height * ratio;
                    const offsetX = (640 - drawW) / 2;
                    const offsetY = (480 - drawH) / 2;
                    
                    tCtx.drawImage(img, offsetX, offsetY, drawW, drawH);
                    
                    DOM.filePreview.src = tCanv.toDataURL('image/jpeg', 0.9);
                    AppState.source = 'file';
                    AppState.isFileLoaded = true;
                    renderUI();
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        }
        e.target.value = null;
    });

    DOM.btnSnap.addEventListener('click', async () => {
        DOM.message.textContent = '';
        DOM.ctx.clearRect(0, 0, DOM.canvas.width, DOM.canvas.height);
        
        if (AppState.source === 'webcam') {
            DOM.ctx.save();
            DOM.ctx.scale(-1, 1);
            DOM.ctx.drawImage(DOM.video, -640, 0, 640, 480);
            DOM.ctx.restore();
        } else {
            DOM.ctx.drawImage(DOM.filePreview, 0, 0, 640, 480);
        }

        const promises = Array.from(AppState.activeStickers.values()).map(data => {
            return new Promise(res => {
                const img = new Image();
                img.onload = () => {
                    const ratio = img.height / img.width;
                    DOM.ctx.drawImage(img, data.x, data.y, data.w, data.w * ratio);
                    res();
                };
                img.src = data.src;
            });
        });

        await Promise.all(promises);
        AppState.phase = 'review';
        renderUI();
    });

    DOM.btnSave.addEventListener('click', async () => {
        DOM.saveLoader.style.display = 'flex';
        DOM.message.textContent = '';
        DOM.btnSave.disabled = true;
        
        const baseCanvas = document.createElement('canvas');
        baseCanvas.width = 640; baseCanvas.height = 480;
        const bCtx = baseCanvas.getContext('2d');
        
        if (AppState.source === 'webcam') {
            bCtx.save(); bCtx.scale(-1, 1);
            bCtx.drawImage(DOM.video, -640, 0, 640, 480);
            bCtx.restore();
        } else {
            bCtx.drawImage(DOM.filePreview, 0, 0, 640, 480);
        }

        const stickersData = Array.from(AppState.activeStickers.values()).map(s => ({
            filename: s.src.split('/').pop(),
            x: s.x,
            y: s.y,
            w: s.w
        }));

        try {
            const response = await fetch('/api/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    image: baseCanvas.toDataURL('image/png'), 
                    stickers: stickersData,
                    csrf_token: document.querySelector('input[name="csrf_token"]').value
                })
            });

            const result = await response.json();
            if (result.success) {
                DOM.message.style.color = 'var(--success)';
                DOM.message.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + result.message;
                AppState.phase = 'live';
                AppState.activeStickers.clear();
                document.querySelectorAll('.dynamic-sticker').forEach(el => el.remove());
                renderUI();
                if (typeof window.loadUserGallery === 'function') window.loadUserGallery();
            } else {
                DOM.message.style.color = 'var(--danger)';
                DOM.message.textContent = result.message;
            }
        } catch (error) {
            DOM.message.style.color = 'var(--danger)';
            DOM.message.textContent = 'Errore di connessione.';
        } finally {
            DOM.saveLoader.style.display = 'none';
            DOM.btnSave.disabled = false;
        }
    });

    DOM.btnDiscard.addEventListener('click', () => { 
        DOM.message.textContent = '';
        AppState.phase = 'live'; 
        renderUI(); 
    });

    initWebcam();
    renderUI();
})();
</script>