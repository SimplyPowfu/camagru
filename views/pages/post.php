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

    <div id="post" style="border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); background: #f0f0f0;">
        </div>

    <div id="post-icon" style="display: flex; align-items: center; gap: 20px; padding: 15px 0 10px 0;">
        </div>

    <div id="comments-section" style="display: none; border-top: 1px solid var(--border); padding-top: 20px;">
        
        <div id="comments-list" style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px; display: flex; flex-direction: column; gap: 12px;">
            <p style="color: var(--text-muted); text-align: center; font-style: italic;">Nessun commento presente. Sii il primo!</p>
        </div>

        <div class="comment-input-area" style="display: flex; gap: 10px; align-items: center; background: #f7f7f7; padding: 10px; border-radius: var(--radius);">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" id="new-comment" placeholder="Scrivi un commento..." style="flex: 1; padding: 12px 15px; border-radius: 20px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); background: var(--surface); margin: 0; outline: none;">
            <button id="btn-send-comment" class="btn btn-primary" style="border-radius: 20px; padding: 10px 20px;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>

</div>

<script>
    (function() {
        async function loadPost() {
            const sideGallery = document.getElementById('post');
            const usernameDisplay = document.getElementById('username');
            const userInitial = document.getElementById('user-initial');
            const postIcon = document.getElementById('post-icon');

            const postName = "<?= htmlspecialchars($_GET['post'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
            if (!postName) {
                sideGallery.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--danger);">Post non trovato.</p>';
                return;
            }

            try {
                const response = await fetch(`/api/post/picture?file_path=${encodeURIComponent(postName)}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    sideGallery.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = '/uploads/' + result.data.file_path;
                    img.style.width = "100%";
                    img.style.display = "block";
                    sideGallery.appendChild(img);
                    
                    usernameDisplay.textContent = result.data.username;
                    if (result.data.username) {
                        userInitial.textContent = result.data.username.charAt(0).toUpperCase();
                    }

                    // --- CHECK LIKES ---
                    try {
                        const responseLike = await fetch(`/api/post/checkLike?image_id=${result.data.id}`);
                        const resultLike = await responseLike.json();
                        
                        if (resultLike.success) {
                            postIcon.innerHTML = '';
                            
                            // Container Like
                            const likeContainer = document.createElement('div');
                            likeContainer.style.display = 'flex';
                            likeContainer.style.alignItems = 'center';
                            likeContainer.style.gap = '8px';
                            likeContainer.style.cursor = 'pointer';
                            
                            // Icona Cuore
                            const like = document.createElement('i');
                            like.style.fontSize = '24px';
                            like.style.transition = 'transform 0.2s';
                            like.className = resultLike.action === 'like' ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                            if (resultLike.action === 'like') like.style.color = 'var(--danger)';
                            
                            // Contatore Like
                            const countSpan = document.createElement('span');
                            countSpan.textContent = resultLike.count ? Number(resultLike.count) : '0';
                            countSpan.style.fontWeight = '700';
                            countSpan.style.fontSize = '1.1rem';

                            // Effetto hover sul cuore
                            likeContainer.onmouseover = () => like.style.transform = 'scale(1.1)';
                            likeContainer.onmouseout = () => like.style.transform = 'scale(1)';
                            
                            likeContainer.onclick = () => toggleLike(result.data.id, like, countSpan);
                            
                            likeContainer.appendChild(like);
                            likeContainer.appendChild(countSpan);

                            // Container Commenti
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

                            // Evento per aprire/chiudere la sezione commenti
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

                            // Invio Messaggio
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

        // Funzione che aggiorna il blocco dei commenti
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
                        avatar.style.flexShrink = '0'; // Evita che l'avatar si deformi
                        avatar.textContent = comment.username.charAt(0).toUpperCase();

                        const p = document.createElement('div');
                        p.style.background = 'var(--background)';
                        p.style.padding = '10px 15px';
                        p.style.borderRadius = '0 15px 15px 15px'; 
                        p.style.flex = '1';
                        p.style.minWidth = '0'; // Permette al flex-child di scendere sotto la sua larghezza minima

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

        // Toggle Like
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

        // Add Comment
        async function addComment(imageId, comment) {
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            try {
                const response = await fetch(`/api/post/addComment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_id: imageId, comment: comment, csrf_token: csrfToken })
                });
                const result = await response.json();
                if (result.success)
                    loadComment(imageId);
            } catch (error) {
                console.error("Errore nel salvataggio del commento:", error);
            }
        }

        loadPost();
    })();
</script>