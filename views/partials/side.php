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
                    const card = document.createElement('div');
                    card.className = 'side-card';
                    card.style.position = 'relative';

                    const link = document.createElement('a');
                    link.href = `/post?post=${encodeURIComponent(photo.file_path)}`;
                    link.style.display = 'block';

                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.alt = "Creazione";
                    img.title = "Clicca per vedere il post";

                    link.appendChild(img);
                    card.appendChild(link);
                    if (photo.filter_3d && photo.filter_3d.trim() !== '') {
                        const iconFileName = photo.filter_3d.replace('.glb', '.png');
                        
                        const icon3D = document.createElement('div');
                        icon3D.className = 'icon-3d';
                        
                        const iconImg = document.createElement('img');
                        iconImg.src = `/filter/3Dicon/${iconFileName}`;
                        iconImg.alt = "3D Interactive";
                        
                        icon3D.appendChild(iconImg);
                        card.appendChild(icon3D);
                    }
                    sideGallery.appendChild(card);
                });
            }
        } catch (error) {
            console.error("Errore nel caricamento della gallery laterale:", error);
        }
    };
    document.addEventListener('DOMContentLoaded', window.loadUserGallery);
</script>

<style>
    .icon-3d {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        pointer-events: none;
        transition: transform 0.3s ease;
    }

    .icon-3d img {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        background: transparent !important; 
        border: none !important; 
        box-shadow: none !important; 
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.5));
    }

    .side-card:hover .icon-3d {
        transform: scale(1.15) rotate(5deg);
    }
</style>