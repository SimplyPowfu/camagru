<?php

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

<div class="editing-container">
    <div class="main-edit-area">
        <h2>Editor Avanzato</h2>
        <p>Trascina per spostare o Rotellina per ridimensionare</p>

        <div id="preview-container" class="preview-box">
            <video id="video" autoplay playsinline></video>
            <img id="file-preview" src="#" alt="Anteprima File" style="display: none;">
            <div id="sticker-overlay"></div>
            <canvas id="canvas" width="640" height="480" style="display: none;"></canvas>
        </div>

        <div class="sticker-selector">
            <h3>Seleziona gli sticker</h3>
            <div class="sticker-list">
                <?php foreach ($stickers as $index => $src): ?>
                    <img src="<?= htmlspecialchars($src) ?>" class="sticker-opt" data-id="<?= $index ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="controls">
            <div id="live-controls">
                <button id="btn-snap" class="btn" disabled>Scatta / Crea</button>
                <label for="file-input" class="btn-secondary">Scegli file dal PC</label>
                <input type="file" id="file-input" accept="image/*" style="display: none;">
                <button id="btn-webcam" class="btn-secondary" style="display: none;">Usa Webcam</button>
            </div>
            <div id="review-controls" style="display: none;">
                <button id="btn-save" class="btn btn-success">Salva Immagine</button>
                <button id="btn-discard" class="btn btn-danger">Scarta e Riprova</button>
            </div>
        </div>
    </div>

    <aside class="sidebar">
        <h3>Le tue creazioni</h3>
        <div id="side-gallery"></div>
    </aside>
</div>

<style>
    .editing-container { display: flex; gap: 20px; padding: 20px; font-family: sans-serif; }
    .main-edit-area { flex: 1; }
    .preview-box { position: relative; width: 640px; height: 480px; background: #000; border: 2px solid #333; overflow: hidden; user-select: none; }
    #video, #file-preview, #canvas { width: 100%; height: 100%; object-fit: contain; }
    #video { transform: scaleX(-1); object-fit: cover; }
    #sticker-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; }
    .dynamic-sticker { position: absolute; cursor: move; width: 150px; }
    .sticker-list { display: flex; gap: 10px; margin: 15px 0; padding: 10px; background: #eee; border-radius: 5px; }
    .sticker-opt { width: 70px; height: 70px; cursor: pointer; border: 3px solid transparent; padding: 5px; object-fit: contain; }
    .sticker-opt.selected { border-color: #007bff; background: white; border-radius: 10px; }
    .btn { background: #007bff; color: white; border: none; padding: 12px 24px; cursor: pointer; font-weight: bold; border-radius: 5px; }
    .btn:disabled { background: #ccc; cursor: not-allowed; }
    .btn-secondary { background: #6c757d; color: white; padding: 10px 20px; cursor: pointer; border-radius: 5px; display: inline-block; margin-right: 10px; }
    .sidebar { width: 250px; background: #f8f9fa; padding: 15px; border: 1px solid #ddd; }
</style>

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
        btnDiscard: document.getElementById('btn-discard')
    };

    function renderUI() {
        if (AppState.phase === 'live') {
            DOM.canvas.style.display = 'none';
            DOM.stickerOverlay.style.display = 'block';
            DOM.liveControls.style.display = 'block';
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
            DOM.reviewControls.style.display = 'block';
        }

        DOM.stickerOpts.forEach(opt => {
            opt.classList.toggle('selected', AppState.activeStickers.has(opt.dataset.id));
        });

        const isReady = (AppState.source === 'webcam' && AppState.hasWebcamAccess) || (AppState.source === 'file' && AppState.isFileLoaded);
        DOM.btnSnap.disabled = !(AppState.activeStickers.size > 0 && isReady);
    }

    // --- LOGICA DRAG & RESIZE ---
    function setupInteractions(imgElement, id) {
        // Drag
        imgElement.addEventListener('mousedown', (e) => {
            e.preventDefault();
            AppState.draggingSticker = id;
            const rect = imgElement.getBoundingClientRect();
            AppState.dragOffset.x = e.clientX - rect.left;
            AppState.dragOffset.y = e.clientY - rect.top;
        });

        // Resize con rotellina
        imgElement.addEventListener('wheel', (e) => {
            e.preventDefault();
            const data = AppState.activeStickers.get(id);
            const scaleAmount = 10;
            
            if (e.deltaY < 0) { // Scroll su = Ingrandisci
                data.w = Math.min(data.w + scaleAmount, 500); // Max 500px
            } else { // Scroll giù = Rimpicciolisci
                data.w = Math.max(data.w - scaleAmount, 30);  // Min 30px
            }
            
            imgElement.style.width = data.w + 'px';
        });
    }

    window.addEventListener('mousemove', (e) => {
        if (!AppState.draggingSticker) return;
        const containerRect = DOM.previewBox.getBoundingClientRect();
        const data = AppState.activeStickers.get(AppState.draggingSticker);
        const el = document.querySelector(`.dynamic-sticker[data-id="${AppState.draggingSticker}"]`);

        data.x = e.clientX - containerRect.left - AppState.dragOffset.x;
        data.y = e.clientY - containerRect.top - AppState.dragOffset.y;
        el.style.left = data.x + 'px';
        el.style.top = data.y + 'px';
    });

    window.addEventListener('mouseup', () => { AppState.draggingSticker = null; });

    // --- CLICK STICKER LIST ---
    DOM.stickerOpts.forEach(opt => {
        opt.addEventListener('click', function() {
            const id = this.dataset.id;
            if (AppState.activeStickers.has(id)) {
                AppState.activeStickers.delete(id);
                document.querySelector(`.dynamic-sticker[data-id="${id}"]`)?.remove();
            } else {
                const data = { src: this.src, x: 245, y: 165, w: 150 }; 
                AppState.activeStickers.set(id, data);

                const img = document.createElement('img');
                img.src = data.src;
                img.classList.add('dynamic-sticker');
                img.dataset.id = id;
                img.style.left = data.x + 'px';
                img.style.top = data.y + 'px';
                img.style.width = data.w + 'px';
                
                setupInteractions(img, id);
                DOM.stickerOverlay.appendChild(img);
            }
            renderUI();
        });
    });

    // --- WEBCAM & FILE ---
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
                DOM.filePreview.src = ev.target.result;
                AppState.source = 'file';
                AppState.isFileLoaded = true;
                renderUI();
            };
            reader.readAsDataURL(file);
        }
        e.target.value = null;
    });

    // --- SNAPSHOT ---
    DOM.btnSnap.addEventListener('click', async () => {
        DOM.ctx.clearRect(0, 0, DOM.canvas.width, DOM.canvas.height);
        if (AppState.source === 'webcam') {
            DOM.ctx.save();
            DOM.ctx.scale(-1, 1);
            DOM.ctx.drawImage(DOM.video, -DOM.canvas.width, 0, DOM.canvas.width, DOM.canvas.height);
            DOM.ctx.restore();
        } else {
            DOM.ctx.drawImage(DOM.filePreview, 0, 0, DOM.canvas.width, DOM.canvas.height);
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

    DOM.btnSave.addEventListener('click', () => { console.log(DOM.canvas.toDataURL('image/png')); })
    // DOM.btnSave.addEventListener('click', async () => {
    //     const dataUrl = DOM.canvas.toDataURL('image/png');

    //     try {
    //         const response = await fetch('/api/save', { // Assicurati che il tuo router punti a PhotoController
    //             method: 'POST',
    //             headers: { 'Content-Type': 'application/json' },
    //             body: JSON.stringify({ image: dataUrl })
    //         });

    //         const result = await response.json();
    //         if (result.success) {
    //             alert("Immagine salvata con successo!");
    //             AppState.phase = 'live'; // Torna alla modalità scatto
    //             renderUI();
    //         } else {
    //             alert("Errore: " + result.message);
    //         }
    //     } catch (error) {
    //         console.error("Errore nell'invio:", error);
    //     }
    // });
    DOM.btnDiscard.addEventListener('click', () => { AppState.phase = 'live'; renderUI(); });
    initWebcam();
})();
</script>