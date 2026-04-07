<button id="open-sidebar" class="sidebar-toggle-btn">
    <i class="fa-solid fa-images"></i>
</button>

<aside class="sidebar" id="main-sidebar">
    <div class="sidebar-header">
        <h3>Le tue creazioni</h3>
        <button id="close-sidebar" class="btn-close-sidebar">&times;</button>
    </div>
    <div id="side-gallery"></div>
</aside>

<script>
    const sidebar = document.getElementById('main-sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    const openBtn = document.getElementById('open-sidebar');

    if (openBtn) openBtn.onclick = () => sidebar.classList.add('active');
    if (closeBtn) closeBtn.onclick = () => sidebar.classList.remove('active');

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

                    const link = document.createElement('a');
                    link.href = `/post?post=${encodeURIComponent(photo.file_path)}`;

                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.alt = "Creazione";
                    img.title = "Clicca per vedere il post";

                    link.appendChild(img);
                    card.appendChild(link);
                    
                    if (photo.filter_3d && photo.filter_3d.trim() !== '') {
                        const iconFileName = photo.filter_3d.replace('.glb', '.png');
                        
                        const icon3D = document.createElement('div');
                        icon3D.className = 'icon-3d-badge badge-side';
                        
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