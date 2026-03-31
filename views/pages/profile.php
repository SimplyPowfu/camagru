<?php
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<div style="display: flex; flex-direction: column; gap: 30px;">

    <div class="card">
        <h2 style="color: var(--primary); font-weight: 800; margin-bottom: 20px;">
            <i class="fa-solid fa-images"></i> I Miei Post
        </h2>
        <div id="home-gallery" class="profile-grid">
            </div>
    </div>

    <div class="card">
        <h2 style="color: var(--primary); font-weight: 800; margin-bottom: 20px;">
            <i class="fa-solid fa-user-gear"></i> Impostazioni Profilo
        </h2>
        
        <form id="editForm" autocomplete="off" style="max-width: 500px;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" autocomplete="off" placeholder="Cambia Username">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" autocomplete="off" placeholder="Cambia Email">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="new-password" placeholder="Cambia Password">
            </div>
            
            <div style="margin-top: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500; color: var(--text-main);">
                    <input type="checkbox" name="notify_comments" id="notify_comments" style="width: 18px; height: 18px; cursor: pointer;"
                        <?= (isset($_SESSION['user']['notify_comments']) && (int)$_SESSION['user']['notify_comments'] === 1) ? 'checked' : '' ?>>
                    Inviami un'email quando ricevo un commento
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
                <i class="fa-solid fa-floppy-disk"></i> Salva Modifiche
            </button>
        </form>
        <p id="message" style="margin-top: 15px; font-weight: 600;"></p>
    </div>
</div>

<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content card" style="border: none;">
        <i class="fa-solid fa-triangle-exclamation fa-3x" style="color: var(--danger); margin-bottom: 15px;"></i>
        <h3 style="margin-bottom: 10px; color: var(--text-main);">Elimina Post</h3>
        <p style="color: var(--text-muted); margin-bottom: 25px;">Sei sicuro di voler eliminare questa foto? L'azione è irreversibile.</p>
        
        <div class="modal-buttons" style="display: flex; gap: 15px; justify-content: center;">
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
                    profileGallery.innerHTML = '<p style="color: var(--text-muted); font-style: italic;">Non hai ancora scattato nessuna foto.</p>';
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
                        e.stopPropagation(); // Evita di aprire il post
                        fileToDelete = photo.file_path;
                        wrapperToRemove = wrapper;
                        document.getElementById('delete-modal').style.display = 'flex';
                    };

                    wrapper.appendChild(img);
                    wrapper.appendChild(trash);
                    profileGallery.appendChild(wrapper);
                });
            }
        } catch (error) {
            console.error("Errore nel caricamento della gallery:", error);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.loadUserGallery();

        // --- Logica Modale Eliminazione ---
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
            
            btnConfirm.disabled = true; // Previeni doppi click
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
                    
                    // Se la galleria rimane vuota, ricarichiamo per mostrare il messaggio "vuoto"
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

        // --- Logica Modifica Dati Utente ---
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
                    messageElement.style.color = 'var(--success)';
                    messageElement.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + result.message;
                } else {
                    messageElement.style.color = 'var(--danger)';
                    messageElement.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + result.message;
                }
                
                setTimeout(() => { messageElement.textContent = ''; }, 3000);
            } catch (error) {
                messageElement.style.color = 'var(--danger)';
                messageElement.textContent = 'Errore di connessione al server.';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva Modifiche';
            }
        });
    });
</script>

<style>
    /* Griglia per le foto nel profilo */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    /* Contenitore Foto nel Profilo */
    .post-wrapper {
        position: relative;
        width: 100%;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        transition: var(--transition);
        cursor: pointer;
    }

    .post-wrapper img {
        width: 100%;
        aspect-ratio: 1/1; /* Miniature quadrate per il profilo */
        object-fit: cover;
        display: block;
        transition: transform 0.3s;
    }

    .post-wrapper:hover {
        border-color: var(--primary);
    }
    
    .post-wrapper:hover img {
        transform: scale(1.05);
    }

    /* Il Cestino: Più elegante e visibile solo all'hover */
    .trash-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 35px;
        height: 35px;
        background: rgba(255, 255, 255, 0.9); /* Sfondo bianco per staccare dalla foto */
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }

    .post-wrapper:hover .trash-icon {
        opacity: 1;
        transform: translateY(0);
    }

    .trash-icon:hover {
        background: var(--danger);
        color: white;
    }
</style>