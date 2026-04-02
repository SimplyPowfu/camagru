<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
        <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold;">
            <span id="user-initial"><i class="fa-solid fa-user"></i></span>
        </div>
        <div>
            <h3 style="margin: 0; color: var(--text-main); font-weight: 700;">
                <span id="username">Caricamento...</span>
            </h3>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Camagru Post</p>
        </div>
    </div>

    <div id="post" style="position: relative; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); background: #f0f0f0; aspect-ratio: 4/3;">
        <div id="webgl-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;"></div>
    </div>

    <div id="post-icon" style="display: flex; align-items: center; gap: 20px; padding: 15px 0 10px 0;"></div>

    <div id="comments-section" style="display: none; border-top: 1px solid var(--border); padding-top: 20px;">
        <div id="comments-list" style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px; display: flex; flex-direction: column; gap: 12px;">
            <p style="color: var(--text-muted); text-align: center; font-style: italic;">Nessun commento presente. Sii il primo!</p>
        </div>
        <div class="comment-input-area" style="display: flex; gap: 10px; align-items: center; background: var(--background); padding: 10px; border-radius: var(--radius);">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" id="new-comment" placeholder="Scrivi un commento..." style="flex: 1; padding: 12px 15px; border-radius: 20px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); background: var(--surface); margin: 0; outline: none;">
            <button id="btn-send-comment" class="btn btn-primary" style="border-radius: 20px; padding: 10px 20px;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>

</div>

<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

    document.addEventListener('DOMContentLoaded', () => {

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
            const sideGallery = document.getElementById('post');
            const usernameDisplay = document.getElementById('username');
            const userInitial = document.getElementById('user-initial');
            const postIcon = document.getElementById('post-icon');

            const urlParams = new URLSearchParams(window.location.search);
            const postName = urlParams.get('post');

            if (!postName) {
                sideGallery.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--danger);">Post non trovato.</p>';
                return;
            }

            try {
                const response = await fetch(`/api/post/picture?file_path=${encodeURIComponent(postName)}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    
                    const img = document.createElement('img');
                    img.src = '/uploads/' + result.data.file_path;
                    img.style.width = "100%";
                    img.style.height = "100%";
                    img.style.objectFit = "cover";
                    img.style.position = "absolute";
                    img.style.top = "0";
                    img.style.left = "0";
                    img.style.zIndex = "1";
                    
                    sideGallery.appendChild(img);
                    
                    usernameDisplay.textContent = result.data.username;
                    if (result.data.username) {
                        userInitial.textContent = result.data.username.charAt(0).toUpperCase();
                    }

                    if (result.data.filter_3d && result.data.filter_3d !== "") {
                        init3DModel(result.data.filter_3d);
                    }

                    try {
                        const responseLike = await fetch(`/api/post/checkLike?image_id=${result.data.id}`);
                        const resultLike = await responseLike.json();
                        
                        if (resultLike.success) {
                            postIcon.innerHTML = '';
                            
                            const likeContainer = document.createElement('div');
                            likeContainer.style.display = 'flex';
                            likeContainer.style.alignItems = 'center';
                            likeContainer.style.gap = '8px';
                            likeContainer.style.cursor = 'pointer';
                            
                            const like = document.createElement('i');
                            like.style.fontSize = '24px';
                            like.style.transition = 'transform 0.2s';
                            like.className = resultLike.action === 'like' ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                            if (resultLike.action === 'like') like.style.color = 'var(--danger)';
                            
                            const countSpan = document.createElement('span');
                            countSpan.textContent = resultLike.count ? Number(resultLike.count) : '0';
                            countSpan.style.fontWeight = '700';
                            countSpan.style.fontSize = '1.1rem';

                            likeContainer.onmouseover = () => like.style.transform = 'scale(1.1)';
                            likeContainer.onmouseout = () => like.style.transform = 'scale(1)';
                            likeContainer.onclick = () => toggleLike(result.data.id, like, countSpan);
                            
                            likeContainer.appendChild(like);
                            likeContainer.appendChild(countSpan);

                            const commentContainer = document.createElement('div');
                            commentContainer.style.display = 'flex';
                            commentContainer.style.alignItems = 'center';
                            commentContainer.style.gap = '8px';
                            commentContainer.style.cursor = 'pointer';

                            const comment = document.createElement('i');
                            comment.className = 'fa-regular fa-comment';
                            comment.style.fontSize = '24px';
                            comment.style.transition = 'transform 0.2s';
                            
                            const commentText = document.createElement('span');
                            commentText.textContent = "Commenta";
                            commentText.style.fontWeight = '600';

                            commentContainer.onmouseover = () => comment.style.transform = 'scale(1.1)';
                            commentContainer.onmouseout = () => comment.style.transform = 'scale(1)';

                            commentContainer.onclick = () => {
                                const section = document.getElementById('comments-section');
                                section.style.display = (section.style.display === 'none') ? 'block' : 'none';
                                
                                if (section.style.display === 'block') {
                                    comment.className = 'fa-solid fa-comment';
                                    comment.style.color = 'var(--primary)';
                                    document.getElementById('new-comment').focus();
                                    loadComment(result.data.id);
                                } else {
                                    comment.className = 'fa-regular fa-comment';
                                    comment.style.color = '';
                                }
                            };

                            commentContainer.appendChild(comment);
                            commentContainer.appendChild(commentText);

                            postIcon.appendChild(likeContainer);
                            postIcon.appendChild(commentContainer);

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
                        commentsList.innerHTML = '<p style="color: var(--text-muted); text-align: center; font-style: italic;">Nessun commento. Rompi il ghiaccio!</p>';
                        return;
                    }
                    
                    result.data.forEach(comment => {
                        const wrapper = document.createElement('div');
                        wrapper.style.display = 'flex';
                        wrapper.style.gap = '10px';
                        
                        const avatar = document.createElement('div');
                        avatar.style.width = '30px';
                        avatar.style.height = '30px';
                        avatar.style.borderRadius = '50%';
                        avatar.style.background = 'var(--text-muted)';
                        avatar.style.color = 'white';
                        avatar.style.display = 'flex';
                        avatar.style.alignItems = 'center';
                        avatar.style.justifyContent = 'center';
                        avatar.style.fontSize = '0.8rem';
                        avatar.style.fontWeight = 'bold';
                        avatar.style.flexShrink = '0'; 
                        avatar.textContent = comment.username.charAt(0).toUpperCase();

                        const p = document.createElement('div');
                        p.style.background = 'var(--background)';
                        p.style.padding = '10px 15px';
                        p.style.borderRadius = '0 15px 15px 15px'; 
                        p.style.flex = '1';
                        p.style.minWidth = '0'; 

                        const strongUser = document.createElement('strong');
                        strongUser.textContent = comment.username;
                        strongUser.style.display = 'block';
                        strongUser.style.fontSize = '0.9rem';
                        strongUser.style.color = 'var(--primary)';
                        strongUser.style.marginBottom = '3px';
                        
                        const textSpan = document.createElement('span');
                        textSpan.textContent = comment.content;
                        textSpan.style.color = 'var(--text-main)';
                        textSpan.style.wordBreak = 'break-word';
                        textSpan.style.whiteSpace = 'pre-wrap';
                        textSpan.style.display = 'block';

                        p.appendChild(strongUser);
                        p.appendChild(textSpan);
                        wrapper.appendChild(avatar);
                        wrapper.appendChild(p);
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
                    like.className = result.action === 'liked' ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                    like.style.color = result.action === 'liked' ? 'var(--danger)' : '';
                    
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