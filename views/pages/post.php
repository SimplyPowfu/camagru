<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<div class="card post-container">
    
    <div class="post-header">
        <div class="avatar-lg" id="user-initial"><i class="fa-solid fa-user"></i></div>
        <div>
            <h3 class="post-author-name" id="username">Caricamento...</h3>
            <p class="post-meta-text">Camagru Post</p>
        </div>
    </div>

    <div id="post" class="post-media-box">
        <div id="webgl-container" class="preview-layer layer-webgl"></div>
    </div>

    <div id="post-icon" class="post-actions"></div>

    <div id="comments-section" class="comments-section" style="display: none;">
        <div id="comments-list" class="comments-list">
            <p class="comment-empty">Nessun commento presente. Sii il primo!</p>
        </div>
        
        <div class="comment-input-area">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" id="new-comment" class="comment-input" placeholder="Scrivi un commento...">
            <button id="btn-send-comment" class="btn btn-primary" style="border-radius: 20px;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

    document.addEventListener('DOMContentLoaded', () => {

        // Funzione di Hashing per generare un colore univoco basato sull'username
        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++)
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            const hue = Math.abs(hash % 360);
            return `hsl(${hue}, 65%, 55%)`;
        }

        function init3DModel(modelFilename) {
            const webglContainer = document.getElementById('webgl-container');
            if (!webglContainer) return;

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

            function animate() {
                requestAnimationFrame(animate);
                const delta = clock.getDelta();
                if (mixer) {
                    mixer.update(delta);
                }
                renderer.render(scene, camera);
            }
            animate();
        }

        async function loadPost() {
            const postContainer = document.querySelector('.post-container');
            const sideGallery = document.getElementById('post');
            const usernameDisplay = document.getElementById('username');
            const userInitial = document.getElementById('user-initial');
            const postIcon = document.getElementById('post-icon');

            const urlParams = new URLSearchParams(window.location.search);
            const postName = urlParams.get('post');

            if (!postName) {
                postContainer.innerHTML = `
                        <div style="text-align: center; padding: 60px 20px;">
                            <i class="fa-solid fa-image-slash" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                            <h2 style="color: var(--text-main); margin-bottom: 10px; font-weight: 800;">Oops!</h2>
                            <p style="color: var(--danger); font-weight: 500; margin-bottom: 30px;">Nessuna foto trovata</p>
                            <a href="/" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fa-solid fa-house"></i> Torna alla Home
                            </a>
                        </div>
                    `;
                return;
            }

            try {
                const response = await fetch(`/api/post/picture?file_path=${encodeURIComponent(postName)}`);
                const result = await response.json();
                if (result.success && result.data) {
                    const img = document.createElement('img');
                    img.src = result.data.file_path;
                    img.className = "preview-layer preview-img";
                    
                    sideGallery.appendChild(img);
                    
                    usernameDisplay.textContent = result.data.username;
                    if (result.data.username) {
                        userInitial.innerHTML = result.data.username.charAt(0).toUpperCase();
                        // Applica il colore calcolato all'avatar dell'autore del post
                        userInitial.style.backgroundColor = stringToColor(result.data.username);
                    }

                    if (result.data.filter_3d && result.data.filter_3d !== "")
                        init3DModel(result.data.filter_3d);// TODO, rendere la funzione export per usarla anche su editing

                    try {
                        const responseLike = await fetch(`/api/post/checkLike?image_id=${result.data.id}`);
                        const resultLike = await responseLike.json();
                        
                        if (resultLike.success) {
                            postIcon.innerHTML = '';
                            
                            const likeContainer = document.createElement('div');
                            likeContainer.className = 'action-btn action-like';
                            
                            const like = document.createElement('i');
                            like.className = resultLike.action === 'like' ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart';
                            
                            const countSpan = document.createElement('span');
                            countSpan.textContent = resultLike.count ? Number(resultLike.count) : '0';

                            likeContainer.onclick = () => toggleLike(result.data.id, like, countSpan);
                            
                            likeContainer.appendChild(like);
                            likeContainer.appendChild(countSpan);

                            const commentContainer = document.createElement('div');
                            commentContainer.className = 'action-btn action-comment';

                            const comment = document.createElement('i');
                            comment.className = 'fa-regular fa-comment';

                            commentContainer.onclick = () => {
                                const section = document.getElementById('comments-section');
                                section.style.display = (section.style.display === 'none') ? 'block' : 'none';
                                
                                if (section.style.display === 'block') {
                                    comment.className = 'fa-solid fa-comment';
                                    comment.style.color ='#FF7F66';
                                    document.getElementById('new-comment').focus();
                                    loadComment(result.data.id);
                                } else {
                                    comment.className = 'fa-regular fa-comment';
                                    comment.style.color = '';
                                }
                            };

                            commentContainer.appendChild(comment);

                            postIcon.appendChild(likeContainer);
                            postIcon.appendChild(commentContainer);

                            const shareContainer = document.createElement('div');
                            shareContainer.className = 'action-btn action-share'; // Assicurati di stilizzare questa classe nel CSS
                            shareContainer.style.display = 'flex';
                            shareContainer.style.alignItems = 'center';
                            shareContainer.style.gap = '8px';
                            shareContainer.style.cursor = 'pointer';

                            const shareIcon = document.createElement('i');
                            shareIcon.className = 'fa-solid fa-share-nodes text-primary';
                            shareIcon.style.fontSize = '24px';
                            shareIcon.style.transition = 'transform 0.2s';

                            const shareText = document.createElement('span');
                            shareText.textContent = "Condividi";
                            shareText.className = 'text-primary'
                            shareText.style.fontWeight = '600';

                            const shareMess = document.createElement('p');
                            

                            shareContainer.onmouseover = () => shareIcon.style.transform = 'scale(1.1)';
                            shareContainer.onmouseout = () => shareIcon.style.transform = 'scale(1)';

                            shareContainer.onclick = async () => {
                                const postTitle = `Guarda la creazione di ${result.data.username} su Camagru!`;
                                const postUrl = window.location.href;

                                if (navigator.share) {
                                    try {
                                        await navigator.share({
                                            title: 'Camagru Post',
                                            text: postTitle,
                                            url: postUrl
                                        });
                                        shareMess.textContent = 'Condivisione riuscita';
                                        shareMess.style = 'color: green;'
                                    } catch (err) {
                                        shareMess.textContent = 'Condivisione annullata';
                                        shareMess.style = 'color: red;'
                                    }
                                } else {
                                    try {
                                        await navigator.clipboard.writeText(postUrl);
                                        shareMess.textContent = 'Link del post copiato sugli appunti!';
                                        shareMess.style = 'color: green;'
                                    } catch (e) {
                                        shareMess.textContent = 'Copia negli appunti fallita: ' + e;
                                        shareMess.style = 'color: red;'
                                    }
                                }
                            };

                            shareContainer.appendChild(shareIcon);
                            shareContainer.appendChild(shareText);
                            shareContainer.appendChild(shareMess);
                            postIcon.appendChild(shareContainer);

                            const btnSend = document.getElementById('btn-send-comment');
                            const inputComment = document.getElementById('new-comment');

                            btnSend.onclick = () => {
                                const text = inputComment.value.trim();
                                if (text !== "") {
                                    addComment(result.data.id, text);
                                    inputComment.value = '';
                                }
                            };
                            
                            inputComment.onkeypress = (e) => {
                                if (e.key === 'Enter') btnSend.click();
                            };
                        }
                    } catch (error) {
                        console.error("Errore nel caricamento delle icon:", error);
                    }
                } else {
                    postContainer.innerHTML = `
                        <div style="text-align: center; padding: 60px 20px;">
                            <i class="fa-solid fa-image-slash" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                            <h2 style="color: var(--text-main); margin-bottom: 10px; font-weight: 800;">Oops!</h2>
                            <p style="color: var(--danger); font-weight: 500; margin-bottom: 30px;">${result.message}</p>
                            <a href="/" class="btn btn-primary" style="text-decoration: none;">
                                <i class="fa-solid fa-house"></i> Torna alla Home
                            </a>
                        </div>
                    `;
                }
            } catch (error) {
                console.error("Errore nel caricamento del post:", error);
            }
        }

        async function loadComment(imageId) {
            const commentsList = document.getElementById('comments-list');
            if (!commentsList) return;
            
            try {
                const response = await fetch(`/api/post/loadComments?image_id=${imageId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    commentsList.innerHTML = '';
                    
                    if (result.data.length === 0) {
                        commentsList.innerHTML = '<p class="comment-empty">Nessun commento. Rompi il ghiaccio!</p>';
                        return;
                    }
                    
                    result.data.forEach(comment => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'comment-wrapper';
                        
                        const avatar = document.createElement('div');
                        avatar.className = 'avatar-sm';
                        avatar.textContent = comment.username.charAt(0).toUpperCase();
                        // Applica il colore calcolato all'avatar del commento
                        avatar.style.backgroundColor = stringToColor(comment.username);

                        const bubble = document.createElement('div');
                        bubble.className = 'comment-bubble';

                        const strongUser = document.createElement('strong');
                        strongUser.className = 'comment-author';
                        strongUser.textContent = comment.username;
                        
                        const textSpan = document.createElement('span');
                        textSpan.className = 'comment-text';
                        textSpan.textContent = comment.content;

                        bubble.appendChild(strongUser);
                        bubble.appendChild(textSpan);
                        wrapper.appendChild(avatar);
                        wrapper.appendChild(bubble);
                        commentsList.appendChild(wrapper);
                    });
                    commentsList.scrollTop = commentsList.scrollHeight;
                }
            } catch (error) {
                console.error("Errore nel caricamento dei commenti:", error);
            }
        }

        async function toggleLike(imageId, like, count){
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            try {
                const response = await fetch(`/api/post/toggleLike`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_id: imageId, csrf_token: csrfToken })
                });
                const result = await response.json();
                
                if (result.success && result.action){
                    like.className = result.action === 'liked' ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart';
                    
                    if(result.action === 'liked') {
                        like.style.transform = 'scale(1.3)';
                        setTimeout(() => like.style.transform = 'scale(1)', 200);
                    }
                }
                count.textContent = result.count ? Number(result.count) : '0';
            } catch (error) {
                console.error("Errore nell' aggiornamento dei like:", error);
            }
        }

        async function addComment(imageId, comment) {
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            try {
                const response = await fetch(`/api/post/addComment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_id: imageId, comment: comment, csrf_token: csrfToken })
                });
                const result = await response.json();
                if (result.success) loadComment(imageId);
            } catch (error) {
                console.error("Errore nel salvataggio del commento:", error);
            }
        }

        loadPost();
    });
</script>