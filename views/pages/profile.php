<h1>Profile</h1>
<?php if (isset($_SESSION['user'])): ?>
    <p>Ciao, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong>!</p>
<?php endif; ?>
<!-- Edit user data -->
<form id="editForm">
    <div>
        <label>Username</label>
        <input type="text" name="username">
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email">
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password">
    </div>
    <div style="margin-top: 15px; margin-bottom: 15px;">
        <label style="cursor: pointer;">
            <input type="checkbox" name="notify_comments" id="notify_comments"
                <?= (isset($_SESSION['user']['notify_comments']) && $_SESSION['user']['notify_comments'] == 1) ? 'checked' : '' ?>>
            Inviami un'email quando ricevo un commento
        </label>
    </div>
    <button type="submit">Submit</button>
</form>
<p id="message" style="margin-top: 5px;"></p>

<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h4>Sei sicuro?</h4>
        <p>Vuoi davvero eliminare questa foto? L'azione è irreversibile.</p>
        <div class="modal-buttons">
            <button id="btn-cancel-delete" class="btn btn-secondary">No, annulla</button>
            <button id="btn-confirm-delete" class="btn btn-danger">Sì, elimina</button>
        </div>
    </div>
</div>

<aside class="gallery">
    <h3>I miei post</h3>
    <div id="home-gallery"></div>
</aside>

<script>
    let fileToDelete = null;
    let wrapperToRemove = null;

    window.loadUserGallery = async function() {
        const sideGallery = document.getElementById('home-gallery');
        if (!sideGallery) return;

        const username = "<?= $_SESSION['user']['username'] ?? '' ?>"; 
        if (!username) return;

        try {
            const response = await fetch(`/api/user/picture?username=${username}`);
            const result = await response.json();

            if (result.success && result.data) {
                sideGallery.innerHTML = '';
                
                result.data.forEach(photo => {
                    // Creiamo il Wrapper
                    const wrapper = document.createElement('div');
                    wrapper.className = 'post-wrapper';

                    // Creiamo l'Immagine
                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.onclick = () => window.location.href = `/post?post=${photo.file_path}`;
                    img.style.cursor = 'pointer';

                    // Creiamo l'icona del Cestino (FontAwesome)
                    const trash = document.createElement('i');
                    trash.className = 'fa-solid fa-trash trash-icon';
                    
                    trash.onclick = (e) => {
                        e.stopPropagation(); // PROPER TRICK: Evita che il click "passi" all'immagine sotto
                        
                        // Salviamo i riferimenti per usarli dopo
                        fileToDelete = photo.file_path;
                        wrapperToRemove = wrapper;
                        
                        // Mostriamo il popup
                        document.getElementById('delete-modal').style.display = 'flex';
                    };

                    // Assembliamo il tutto
                    wrapper.appendChild(img);
                    wrapper.appendChild(trash);
                    sideGallery.appendChild(wrapper);
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

        // Azione: NO, Annulla
        btnCancel.onclick = () => {
            modal.style.display = 'none';
            fileToDelete = null;
            wrapperToRemove = null;
        };

        // Azione: SÌ, Elimina
        btnConfirm.onclick = async () => {
            if (!fileToDelete) return;

            try {
				const response = await fetch('/api/remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({file_path: fileToDelete})
                });
                const result = await response.json();

                if (result.success) {
                    if (wrapperToRemove) wrapperToRemove.remove(); 
                    modal.style.display = 'none';
                    fileToDelete = null;
                    wrapperToRemove = null;
                } else {
                    console.error("Errore dal server:", result.message);
                }
            } catch (error) {
                console.error("Errore durante la richiesta di eliminazione:", error);
            }
        };
    });

    document.getElementById('editForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const messageElement = document.getElementById('message');
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/api/edit/profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            const result = await response.json();
            if (result.success) {
                messageElement.style.color = 'green';
                messageElement.textContent = result.message;
            } else {
                messageElement.style.color = 'red';
                messageElement.textContent = result.message;
            }
            setTimeout(() => {
                    messageElement.textContent = '';
                }, 2000);
        } catch (error) {
            messageElement.style.color = 'red';
            messageElement.textContent = 'Errore di connessione al server.';
            console.error('Error:', error);
        }
    })
</script>

<style>
	/* --- Contenitore Foto nel Profilo --- */
	.post-wrapper {
		position: relative; /* Fondamentale per posizionare il cestino */
		display: inline-block;
		width: 100%;
	}

	.trash-icon {
		position: absolute;
		top: 10px;
		right: 10px;
		background: rgba(220, 53, 69, 0.85); /* Rosso semi-trasparente */
		color: white;
		padding: 8px;
		border-radius: 5px;
		cursor: pointer;
		font-size: 16px;
		display: none; /* Nascosto di default */
		transition: background 0.2s, transform 0.2s;
	}

	.trash-icon:hover {
		background: rgba(220, 53, 69, 1);
		transform: scale(1.1);
	}

	/* Mostra il cestino solo quando passi il mouse sulla foto */
	.post-wrapper:hover .trash-icon {
		display: block;
	}

	/* --- Modale di Eliminazione --- */
	.modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: rgba(0, 0, 0, 0.6);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
	}

	.modal-content {
		background: white;
		padding: 25px;
		border-radius: 10px;
		text-align: center;
		max-width: 400px;
		box-shadow: 0 4px 15px rgba(0,0,0,0.2);
	}

	.modal-buttons {
		margin-top: 20px;
		display: flex;
		justify-content: center;
		gap: 15px;
	}
</style>