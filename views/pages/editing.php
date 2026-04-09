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

    $modelsDir = __DIR__ . '/../../public/filter/3Dmodels/';
    $models3D = [];
    if (is_dir($modelsDir)) {
        $files = scandir($modelsDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'glb') {
                $models3D[] = $file;
            }
        }
    }
?>

<div class="card">
    <div class="main-edit-area">
        <h2 class="page-title">Editor Avanzato</h2>
        <p class="page-subtitle">
            <i class="fa-solid fa-circle-info"></i> Trascina per spostare o usa la rotellina per ridimensionare gli sticker.
        </p>

        <div id="preview-container" class="preview-box">
            <video id="video" autoplay playsinline class="preview-layer preview-video"></video>
            <img id="file-preview" src="#" alt="Anteprima" style="display: none;" class="preview-layer preview-img">
            <canvas id="canvas" width="640" height="480" style="display: none;" class="preview-layer"></canvas>

            <div id="sticker-overlay" class="preview-layer layer-overlay"></div>
            <div id="webgl-container" class="preview-layer layer-webgl"></div>

            <div id="save-loader" style="display: none;" class="save-loader">
                <i class="fa-solid fa-circle-notch fa-spin fa-3x text-primary"></i>
                <p class="save-loader-text">Salvataggio in corso...</p>
            </div>
        </div>

        <div class="sticker-selector">
            <h3 class="filter3d-title">1. Scegli i tuoi sticker</h3>
            <div class="sticker-list">
                <?php foreach ($stickers as $index => $src): ?>
                    <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" class="sticker-opt" data-id="<?= $index ?>" title="Clicca per aggiungere">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filter3d-container">
            <h3 class="filter3d-title"><i class="fa-solid fa-cube"></i> Modelli 3D</h3>
            <div class="filter3d-list">
                <?php foreach ($models3D as $glb): 
                    $iconName = str_replace('.glb', '.png', $glb);
                    $iconPath = '/filter/3Dicon/' . $iconName;
                ?>
                    <img src="<?= htmlspecialchars($iconPath, ENT_QUOTES, 'UTF-8') ?>" 
                        class="model-opt" 
                        data-filename="<?= htmlspecialchars($glb, ENT_QUOTES, 'UTF-8') ?>" 
                        title="<?= htmlspecialchars(pathinfo($glb, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8') ?>"
                        alt="3D Filter">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="controls">
            <div id="live-controls" style="display: flex; gap: 12px; flex-wrap: wrap;">
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

            <div id="review-controls" style="display: none; gap: 12px;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button id="btn-save" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Conferma e Salva
                </button>
                <button id="btn-discard" class="btn btn-danger">
                    <i class="fa-solid fa-trash"></i> Scarta e Riprova
                </button>
            </div>
        </div>
        <p id="message" class="form-message"></p>
    </div>
</div>

<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

    document.addEventListener('DOMContentLoaded', () => {

        const webglContainer = document.getElementById('webgl-container');
        
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(50, 640 / 480, 0.1, 1000);
        camera.position.z = 5;

        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(640, 480);
        renderer.domElement.style.width = '100%';
        renderer.domElement.style.height = '100%';
        webglContainer.appendChild(renderer.domElement);

        const ambientLight = new THREE.AmbientLight(0xffffff, 1.5);
        scene.add(ambientLight);
        const dirLight = new THREE.DirectionalLight(0xffffff, 2);
        dirLight.position.set(0, 5, 5);
        scene.add(dirLight);

        const loader = new GLTFLoader();
        let currentObject = null; 
        let mixer = null; 
        const clock = new THREE.Clock();
        
        function load3DModel(modelFilename) {
            if (currentObject) {
                scene.remove(currentObject);
                currentObject = null;
                mixer = null;
            }
            
            if (!modelFilename) return;

            loader.load(`/filter/3Dmodels/${modelFilename}`, (gltf) => {
                currentObject = gltf.scene;
                
                if (modelFilename === 'blooming_hibiscus.glb')
                    currentObject.scale.set(3, 3, 3);
                else if (modelFilename === 'person_arms.glb')
                    currentObject.scale.set(1.8, 1.8, 1.8);
                else if (modelFilename === 'ophanim_angel.glb')
                    currentObject.scale.set(0.2, 0.2, 0.2);
                else
                    currentObject.scale.set(1, 1, 1);
                
                const box = new THREE.Box3().setFromObject(currentObject);
                const center = box.getCenter(new THREE.Vector3());
                
                if (modelFilename === 'ophanim_angel.glb'){
                    currentObject.position.x += (currentObject.position.x - center.x + 1.5);
                    currentObject.position.y += (currentObject.position.y - center.y - 0.8);
                    currentObject.rotation.y += THREE.MathUtils.degToRad(270);
                }
                else if (modelFilename === 'person_arms.glb'){
                    currentObject.position.x += (currentObject.position.x - center.x);
                    currentObject.position.y += (currentObject.position.y - center.y - 4.5);
                }
                else {
                    currentObject.position.x += (currentObject.position.x - center.x + 2);
                    currentObject.position.y += (currentObject.position.y - center.y + 0.2);
                    currentObject.rotation.y += THREE.MathUtils.degToRad(240);
                }
                
                scene.add(currentObject);
                
                if (gltf.animations && gltf.animations.length > 0) {
                    mixer = new THREE.AnimationMixer(currentObject);
                    const clip = gltf.animations[0];
                    const action = mixer.clipAction(clip);
                    
                    if (modelFilename === 'blooming_hibiscus.glb') {
                        action.setLoop(THREE.LoopOnce);
                        action.clampWhenFinished = true;
                    }
                    action.play();
                } else {
                    mixer = null;
                }
            }, undefined, (error) => {
                console.error("Errore caricamento GLB:", error);
            });
        }
        
        window.addEventListener('3dModelChanged', (e) => {
            load3DModel(e.detail);
        });

        function animate() {
            requestAnimationFrame(animate);
            const delta = clock.getDelta();
            if (mixer) {
                mixer.update(delta);
            }
            renderer.render(scene, camera);
        }
        animate();

        const AppState = {
            phase: 'live',
            source: 'webcam',
            hasWebcamAccess: false,
            isFileLoaded: false,
            activeStickers: new Map(),
            draggingSticker: null,
            dragOffset: { x: 0, y: 0 },
            active3DModel: null,
            pinchInitialDistance: 0, // parametri per il touchscreen
            pinchInitialWidth: 0
        };

        const DOM = {
            video: document.getElementById('video'),
            filePreview: document.getElementById('file-preview'),
            fileInput: document.getElementById('file-input'),
            canvas: document.getElementById('canvas'),
            ctx: document.getElementById('canvas').getContext('2d'),
            stickerOverlay: document.getElementById('sticker-overlay'),
            previewBox: document.getElementById('preview-container'),
            webglContainer: webglContainer, 
            modelOpts: document.querySelectorAll('.model-opt'),
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
                DOM.webglContainer.style.display = 'block';
                
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
                DOM.webglContainer.style.display = 'block'; 
                
                DOM.canvas.style.display = 'block'; 
                DOM.liveControls.style.display = 'none';
                DOM.reviewControls.style.display = 'flex';
            }

            DOM.stickerOpts.forEach(opt => {
                if(AppState.activeStickers.has(opt.dataset.id)) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });

            // Aggiornamento classi per i modelli 3D
            DOM.modelOpts.forEach(opt => {
                if (opt.dataset.filename === AppState.active3DModel) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
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
                if (e.deltaY < 0) data.w = Math.min(data.w + 25, 1000);
                else data.w = Math.max(data.w - 25, 30);
                updateStickerDOM(imgElement, data);
            });

            imgElement.addEventListener('touchstart', (e) => {
                e.preventDefault();
                AppState.draggingSticker = id;
                const rect = imgElement.getBoundingClientRect();
                
                if (e.touches.length === 1) {
                    AppState.dragOffset.x = e.touches[0].clientX - rect.left;
                    AppState.dragOffset.y = e.touches[0].clientY - rect.top;
                } else if (e.touches.length === 2) {
                    AppState.pinchInitialDistance = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    AppState.pinchInitialWidth = AppState.activeStickers.get(id).w;
                }
            }, { passive: false });
        }

        window.addEventListener('mousemove', (e) => {
            if (!AppState.draggingSticker) return;
            const containerRect = DOM.previewBox.getBoundingClientRect();
            const el = document.querySelector(`.dynamic-sticker[data-id="${AppState.draggingSticker}"]`);
            const data = AppState.activeStickers.get(AppState.draggingSticker);
            const scaleX = 640 / containerRect.width;
            const scaleY = 480 / containerRect.height;
            data.x = (e.clientX - containerRect.left - AppState.dragOffset.x) * scaleX;
            data.y = (e.clientY - containerRect.top - AppState.dragOffset.y) * scaleY;
            updateStickerDOM(el, data);
        });

        window.addEventListener('mouseup', () => { AppState.draggingSticker = null; });

        window.addEventListener('touchmove', (e) => {
            if (!AppState.draggingSticker) return;
            if (e.cancelable) e.preventDefault(); 
            
            const containerRect = DOM.previewBox.getBoundingClientRect();
            const el = document.querySelector(`.dynamic-sticker[data-id="${AppState.draggingSticker}"]`);
            if (!el) return;
            
            const data = AppState.activeStickers.get(AppState.draggingSticker);
            
            if (e.touches.length === 1) {
                const scaleX = 640 / containerRect.width;
                const scaleY = 480 / containerRect.height;
                data.x = (e.touches[0].clientX - containerRect.left - AppState.dragOffset.x) * scaleX;
                data.y = (e.touches[0].clientY - containerRect.top - AppState.dragOffset.y) * scaleY;
                updateStickerDOM(el, data);
                
            } else if (e.touches.length === 2 && AppState.pinchInitialDistance > 0) {
                const currentDistance = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                
                const scale = currentDistance / AppState.pinchInitialDistance;
                data.w = Math.max(30, Math.min(1000, AppState.pinchInitialWidth * scale));
                updateStickerDOM(el, data);
            }
        }, { passive: false });

        window.addEventListener('touchend', (e) => {
            if (e.touches.length === 0) {
                AppState.draggingSticker = null;
            } else if (e.touches.length === 1 && AppState.draggingSticker) {
                const el = document.querySelector(`.dynamic-sticker[data-id="${AppState.draggingSticker}"]`);
                if (el) {
                    const rect = el.getBoundingClientRect();
                    AppState.dragOffset.x = e.touches[0].clientX - rect.left;
                    AppState.dragOffset.y = e.touches[0].clientY - rect.top;
                }
            }
        });

        DOM.stickerOpts.forEach(opt => {
            opt.addEventListener('click', function() {
                const id = this.dataset.id;
                if (AppState.activeStickers.has(id)) {
                    AppState.activeStickers.delete(id);
                    document.querySelector(`.dynamic-sticker[data-id="${id}"]`)?.remove();
                } else {
                    const data = { src: this.src, x: 220, y: 140, w: 200 };
                    AppState.activeStickers.set(id, data);
                    const img = document.createElement('img');
                    img.src = data.src;
                    img.classList.add('dynamic-sticker');
                    img.dataset.id = id;
                    setupInteractions(img, id);
                    updateStickerDOM(img, data);
                    DOM.stickerOverlay.appendChild(img);
                }
                renderUI();
            });
        });

        // Logica Toggle per i Modelli 3D
        DOM.modelOpts.forEach(opt => {
            opt.addEventListener('click', function() {
                const filename = this.dataset.filename;
                // Se clicco quello già attivo, lo spengo. Altrimenti lo attivo.
                if (AppState.active3DModel === filename) {
                    AppState.active3DModel = null;
                } else {
                    AppState.active3DModel = filename;
                }
                window.dispatchEvent(new CustomEvent('3dModelChanged', { detail: AppState.active3DModel }));
                renderUI();
            });
        });

        async function initWebcam() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user' } 
                });
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
                        tCanv.width = 640; tCanv.height = 480;
                        const tCtx = tCanv.getContext('2d');
                        const ratio = Math.max(640 / img.width, 480 / img.height);
                        const drawW = img.width * ratio;
                        const drawH = img.height * ratio;
                        tCtx.drawImage(img, (640 - drawW) / 2, (480 - drawH) / 2, drawW, drawH);
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
            DOM.ctx.clearRect(0, 0, 640, 480);
            
            if (AppState.source === 'webcam') {
                const videoW = DOM.video.videoWidth;
                const videoH = DOM.video.videoHeight;
                const targetW = 640;
                const targetH = 480;

                const scale = Math.max(targetW / videoW, targetH / videoH);
                const drawW = videoW * scale;
                const drawH = videoH * scale;

                const offsetX = (targetW - drawW) / 2;
                const offsetY = (targetH - drawH) / 2;

                DOM.ctx.save();
                DOM.ctx.translate(targetW, 0);
                DOM.ctx.scale(-1, 1);
                DOM.ctx.drawImage(DOM.video, offsetX, offsetY, drawW, drawH);
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
                const videoW = DOM.video.videoWidth;
                const videoH = DOM.video.videoHeight;
                const targetW = 640;
                const targetH = 480;
                
                const scale = Math.max(targetW / videoW, targetH / videoH);
                const drawW = videoW * scale;
                const drawH = videoH * scale;
                const offsetX = (targetW - drawW) / 2;
                const offsetY = (targetH - drawH) / 2;

                bCtx.save(); 
                bCtx.translate(targetW, 0);
                bCtx.scale(-1, 1);
                bCtx.drawImage(DOM.video, offsetX, offsetY, drawW, drawH);
                bCtx.restore();
            } else {
                bCtx.drawImage(DOM.filePreview, 0, 0, 640, 480);
            }

            const stickersData = Array.from(AppState.activeStickers.values()).map(s => ({
                filename: s.src.split('/').pop(),
                x: s.x, y: s.y, w: s.w
            }));

            try {
                const response = await fetch('/api/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        image: baseCanvas.toDataURL('image/png'), 
                        stickers: stickersData,
                        filter_3d: AppState.active3DModel, 
                        csrf_token: document.querySelector('input[name="csrf_token"]').value
                    })
                });

                const result = await response.json();
                if (result.success) {
                    DOM.message.className = 'form-message text-success';
                    DOM.message.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + result.message;
                    
                    AppState.phase = 'live';
                    AppState.activeStickers.clear();
                    document.querySelectorAll('.dynamic-sticker').forEach(el => el.remove());
                    
                    AppState.active3DModel = null;
                    window.dispatchEvent(new CustomEvent('3dModelChanged', { detail: null }));
                    
                    renderUI();
                    if (typeof window.loadUserGallery === 'function') window.loadUserGallery();
                } else {
                    DOM.message.className = 'form-message text-danger';
                    DOM.message.textContent = result.message;
                }
            } catch (error) {
                DOM.message.className = 'form-message text-danger';
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
    });
</script>