<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<h1>Post</h1>
<h3>Post</h3>
<p id ="username"></p>
<div id="post"></div>
<div id="post-icon"></div>
<div id="comments-section" style="display: none; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
    <h4>Commenti</h4>
    
    <div id="comments-list" style="max-height: 150px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px;">
        <p style="color: #888;">Nessun commento presente.</p>
    </div>

    <div class="comment-input-area" style="display: flex; gap: 10px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="text" id="new-comment" placeholder="Scrivi un commento..." style="flex: 1; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        <button id="btn-send-comment" class="btn" style="padding: 8px 15px;">Invia</button>
    </div>
</div>

<script>
    (function() {
        async function loadPost() {
            const sideGallery = document.getElementById('post');
            const usernameDisplay = document.getElementById('username');
            const postIcon = document.getElementById('post-icon');


			const postName = "<?= $_GET['post'] ?? '' ?>";
			if (!postName) return ;

            try {
                const response = await fetch(`/api/post/picture?file_path=${postName}`);
                const result = await response.json();
                if (result.success && result.data) {
                    sideGallery.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = '/uploads/' + result.data.file_path;
                    img.style.maxWidth = "100%";
                    sideGallery.appendChild(img);
                    usernameDisplay.textContent = result.data.username;
                    // Check Like
                    try {
                        const responseLike = await fetch(`/api/post/checkLike?image_id=${result.data.id}`);
                        const resultLike = await responseLike.json();
                        if (resultLike.success) {
                            postIcon.innerHTML = '';
                            
                            // Icona Cuore
                            const like = document.createElement('i');
                            like.style.fontSize = '20px';
                            like.className = resultLike.action == 'like' ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                            if (resultLike.action == 'like') like.style.color = 'red';
                            
                            // Contatore Like
                            const countSpan = document.createElement('span');
                            countSpan.textContent = resultLike.count ? Number(resultLike.count) : '';
                            countSpan.style.marginRight = '3px';
                            countSpan.style.fontWeight = 'bold';

                            // Eventi click Like
                            like.onclick = () => toggleLike(result.data.id, like, countSpan);

                            // Commenti
                            const comment = document.createElement('i');
                            comment.className = 'fa-regular fa-comment';
                            comment.style.cursor = 'pointer';
                            comment.style.fontSize = '20px';
                            comment.style.marginLeft = '10px';

                            // Evento per aprire/chiudere la sezione commenti
                            comment.onclick = () => {
                                const section = document.getElementById('comments-section');
                                // Toggle: se è nascosto lo mostra, se è mostrato lo nasconde
                                section.style.display = (section.style.display === 'none') ? 'block' : 'none';
                                
                                if (section.style.display === 'block') {
                                    comment.className = 'fa-solid fa-comment';
                                    document.getElementById('new-comment').focus();
                                    loadComment(result.data.id);
                                }
                                else comment.className = 'fa-regular fa-comment';
                            };

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

                            postIcon.appendChild(countSpan);
                            postIcon.appendChild(like);
                            postIcon.appendChild(comment);
                        }
                    } catch (error) {
                        console.error("Errore nel caricamento delle icon:", error);
                    }
                }
            } catch (error) {
                console.error("Errore nel caricamento del post:", error);
            }
        }

        // Funzione che aggiorna il blocco dei commenti di un post
        async function loadComment(imageId) {
            const commentsList = document.getElementById('comments-list');
            if (!commentsList) return;
            try {
                const response = await fetch(`/api/post/loadComments?image_id=${imageId}`);
                const result = await response.json();
                if (result.success && result.data) {
                    commentsList.innerHTML = '';
                    
                    if (result.data.length === 0) return;
                    result.data.forEach(comment => {
                        const p = document.createElement('p');
                        p.className = 'comment-item';
                        const strongUser = document.createElement('strong');
                        strongUser.textContent = `${comment.username}: `;
                        const textSpan = document.createElement('span');
                        textSpan.textContent = comment.content;
                        p.appendChild(strongUser);
                        p.appendChild(textSpan);
                        commentsList.appendChild(p);
                    });
                    commentsList.scrollTop = commentsList.scrollHeight;
                }
            } catch (error) {
                console.error("Errore nel caricamento del post:", error);
            }
        }

        // Funzione per mettere/togliere il like al click
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
                    like.className = result.action == 'liked' ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                    like.style.color = result.action == 'liked' ? 'red' : '';
                }
                count.textContent = result.count ? Number(result.count) : '';
            }  catch (error) {
                console.error("Errore nell' aggiornamento dei like:", error);
            }
        }

        // Funzione che aggiunge un commento ad un post
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
                    loadComment(imageId);// funzione aggiornamento sezione commenti
            } catch (error) {
                console.error("Errore nel salvataggio del commento:", error);
            }
        }

        loadPost();
    })();
</script>