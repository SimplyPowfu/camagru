<button id="open-sidebar" class="sidebar-toggle-btn">
    <i class="fa-solid fa-images"></i>
</button>

<aside class="sidebar" id="main-sidebar">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; border: none;">Le tue creazioni</h3>
        <button id="close-sidebar" class="btn" style="background:none; color:var(--text-muted); font-size: 1.5rem; display: none;">&times;</button>
    </div>
    
    <div id="side-gallery"></div>
</aside>

<script>
    // Gestione apertura/chiusura Sidebar (Off-canvas)
    const sidebar = document.getElementById('main-sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    const openBtn = document.getElementById('open-sidebar');

    if (openBtn) openBtn.onclick = () => sidebar.classList.add('active');
    if (closeBtn) closeBtn.onclick = () => sidebar.classList.remove('active');

    // La tua logica API originale (INTATTA)
    window.loadUserGallery = async function() {
        const sideGallery = document.getElementById('side-gallery');
        if (!sideGallery) return;

        const username = "<?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"; 
        if (!username) return;

        try {
            const response = await fetch(`/api/user/picture?username=${username}`);
            const result = await response.json();

            if (result.success && result.data) {
                sideGallery.innerHTML = '';
                result.data.forEach(photo => {
                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.alt = "Creazione";
                    img.title = "Clicca per vedere il post";
                    img.onclick = () => window.location.href = `/post?post=${photo.file_path}`;
                    sideGallery.appendChild(img);
                });
            }
        } catch (error) {
            console.error("Errore nel caricamento della gallery laterale:", error);
        }
    };
    document.addEventListener('DOMContentLoaded', window.loadUserGallery);
</script>