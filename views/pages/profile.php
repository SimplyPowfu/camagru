<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<div style="display: flex; flex-direction: column; gap: 24px;">

    <div class="card">
        <h2 class="profile-section-title">
            <i class="fa-solid fa-images"></i> I Miei Post
        </h2>
        <div id="home-gallery" class="profile-grid"></div>
    </div>

    <div class="card">
        <h2 class="profile-section-title">
            <i class="fa-solid fa-user-gear"></i> Impostazioni Profilo
        </h2>
        
        <form id="editForm" class="profile-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" autocomplete="off" placeholder="Cambia Username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" autocomplete="off" placeholder="Cambia Email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" autocomplete="new-password" placeholder="Cambia Password">
            </div>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="notify_comments" id="notify_comments" class="checkbox-input"
                        <?= (isset($_SESSION['user']['notify_comments']) && (int)$_SESSION['user']['notify_comments'] === 1) ? 'checked' : '' ?>>
                    Inviami un'email quando ricevo un commento
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-floppy-disk"></i> Salva Modifiche
            </button>
        </form>
        <p id="message" class="form-message"></p>
    </div>
</div>

<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content card">
        <i class="fa-solid fa-triangle-exclamation fa-3x modal-icon"></i>
        <h3 class="modal-title">Elimina Post</h3>
        <p class="modal-text">Sei sicuro di voler eliminare questa foto? L'azione è irreversibile.</p>
        
        <div class="modal-buttons">
            <button id="btn-cancel-delete" class="btn btn-secondary">Annulla</button>
            <button id="btn-confirm-delete" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Elimina</button>
        </div>
    </div>
</div>

<script>
    let fileToDelete = null;
    let wrapperToRemove = null;

    window.loadUserGallery = async function() {
        const profileGallery = document.getElementById('home-gallery');
        if (!profileGallery) return;

        const username = "<?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"; 
        if (!username) return;

        try {
            const response = await fetch(`/api/user/picture?username=${encodeURIComponent(username)}`);
            const result = await response.json();

            if (result.success && result.data) {
                profileGallery.innerHTML = '';
                
                if (result.data.length === 0) {
                    profileGallery.innerHTML = '<p class="comment-empty">Non hai ancora scattato nessuna foto.</p>';
                    return;
                }
                
                result.data.forEach(photo => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'post-wrapper';

                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.alt = "Mio Post";
                    img.loading = "lazy";
                    img.onclick = () => window.location.href = `/post?post=${encodeURIComponent(photo.file_path)}`;

                    const trash = document.createElement('div');
                    trash.className = 'trash-icon';
                    trash.innerHTML = '<i class="fa-solid fa-trash"></i>';
                    
                    trash.onclick = (e) => {
                        e.stopPropagation();
                        fileToDelete = photo.file_path;
                        wrapperToRemove = wrapper;
                        document.getElementById('delete-modal').style.display = 'flex';
                    };

                    wrapper.appendChild(img);
                    wrapper.appendChild(trash);

                    if (photo.filter_3d && photo.filter_3d.trim() !== '') {
                        const iconFileName = photo.filter_3d.replace('.glb', '.png');
                        const icon3D = document.createElement('div');
                        icon3D.className = 'icon-3d-badge badge-top-right';
                        
                        const iconImg = document.createElement('img');
                        iconImg.src = `/filter/3Dicon/${iconFileName}`;
                        iconImg.alt = "3D Interactive";
                        
                        icon3D.appendChild(iconImg);
                        wrapper.appendChild(icon3D);
                    }
                    profileGallery.appendChild(wrapper);
                });
            }
        } catch (error) {
            console.error("Errore nel caricamento della gallery:", error);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.loadUserGallery();

        const modal = document.getElementById('delete-modal');
        const btnCancel = document.getElementById('btn-cancel-delete');
        const btnConfirm = document.getElementById('btn-confirm-delete');

        btnCancel.onclick = () => {
            modal.style.display = 'none';
            fileToDelete = null;
            wrapperToRemove = null;
        };

        btnConfirm.onclick = async () => {
            if (!fileToDelete) return;
            const token = document.querySelector('input[name="csrf_token"]').value;
            
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('/api/remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({file_path: fileToDelete, csrf_token: token})
                });
                const result = await response.json();

                if (result.success) {
                    if (wrapperToRemove) wrapperToRemove.remove(); 
                    modal.style.display = 'none';
                    fileToDelete = null;
                    wrapperToRemove = null;
                    
                    if(document.querySelectorAll('.post-wrapper').length === 0) {
                        window.loadUserGallery();
                    }
                } else {
                    alert("Errore: " + result.message);
                }
            } catch (error) {
                console.error("Errore eliminazione:", error);
                alert("Errore di connessione.");
            } finally {
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fa-solid fa-trash"></i> Elimina';
            }
        };

        // Gestione Salvataggio Dati
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageElement = document.getElementById('message');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvataggio...';

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/edit/profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    messageElement.className = 'form-message text-success';
                    messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + result.message;
                } else {
                    messageElement.className = 'form-message text-danger';
                    messageElement.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + result.message;
                }
                
                setTimeout(() => { messageElement.textContent = ''; }, 3000);
            } catch (error) {
                messageElement.className = 'form-message text-danger';
                messageElement.textContent = 'Errore di connessione al server.';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva Modifiche';
            }
        });
    });
</script>