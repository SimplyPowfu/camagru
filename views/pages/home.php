<h1 style="color: var(--primary); font-weight: 800; letter-spacing: -1px; margin-bottom: 10px;">Esplora</h1>

<section class="gallery-container">
    <div id="home-gallery" class="home-grid"></div>
    <div class="pagination-controls" style="display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 30px;">
        <button id="btn-prev" class="btn btn-secondary" style="padding: 8px 20px;">
            <i class="fa-solid fa-chevron-left"></i> Precedente
        </button>
        <span id="page-display" style="font-weight: 600; color: var(--text-main);">Pagina 1</span>
        <button id="btn-next" class="btn btn-primary" style="padding: 8px 20px;">
            Successiva <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
</section>

<script>
    (function() {
        let currentPage = 1;
        const homeGallery = document.getElementById('home-gallery');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const pageDisplay = document.getElementById('page-display');

        async function loadGallery(page) {
            try {
                const response = await fetch(`/api/gallery/picture?page=${page}`);
                const result = await response.json();

                if (result.success && result.data) {
                    homeGallery.innerHTML = '';
                    
                    if (result.data.length === 0) {
                        btnPrev.disabled = false;
                        btnNext.disabled = true;
                        pageDisplay.textContent = `Pagina ${page}`;
                        homeGallery.innerHTML = '<p style="color: var(--text-muted);">Non ci sono più foto da mostrare.</p>';
                        return;
                    }

                    result.data.forEach(photo => {
                        const card = document.createElement('div');
                        card.className = 'photo-card';
                        card.style.position = 'relative';

                        const link = document.createElement('a');
                        link.href = `/post?post=${encodeURIComponent(photo.file_path)}`;
                        link.style.display = 'block';

                        const img = document.createElement('img');
                        img.src = '/uploads/' + photo.file_path;
                        img.alt = "Post di Camagru";
                        img.loading = "lazy";
                        link.appendChild(img);
                        card.appendChild(link);

                        if (photo.filter_3d && photo.filter_3d.trim() !== '') {
                            const iconFileName = photo.filter_3d.replace('.glb', '.png');
                            
                            const icon3D = document.createElement('div');
                            icon3D.className = 'icon-3d-badge';
                            
                            const iconImg = document.createElement('img');
                            iconImg.src = `/filter/3Dicon/${iconFileName}`;
                            iconImg.alt = "3D Interactive";
                            
                            icon3D.appendChild(iconImg);
                            card.appendChild(icon3D);
                        }
                        homeGallery.appendChild(card);
                    });
                    pageDisplay.textContent = `Pagina ${page}`;
                    btnPrev.disabled = (page === 1);
                    btnNext.disabled = (result.data.length < 6);
                }
            } catch (error) {
                console.error("Errore nel caricamento della gallery:", error);
                homeGallery.innerHTML = '<p style="color: var(--danger);">Errore nel caricamento dei contenuti.</p>';
            }
        }
        btnPrev.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                loadGallery(currentPage);
            }
        };

        btnNext.onclick = () => {
            currentPage++;
            loadGallery(currentPage);
        };
        loadGallery(currentPage);
    })();
</script>

<style>
    .icon-3d-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 45px; /* Grandezza totale */
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        pointer-events: none;
        transition: transform 0.3s ease;
    }

    .icon-3d-badge img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.5)); /* Dà un'ombra all'icona stessa per renderla visibile */
    }

    .photo-card:hover .icon-3d-badge {
        transform: scale(1.15) rotate(5deg);
    }
</style>