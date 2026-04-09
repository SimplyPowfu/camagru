<h1 class="page-title">Esplora</h1>

<section class="gallery-container">
    <div id="home-gallery" class="home-grid"></div>
    <div class="pagination-controls">
        <button id="btn-prev" class="btn btn-secondary">
            <i class="fa-solid fa-chevron-left"></i> Precedente
        </button>
        <span id="page-display" class="pagination-text">Pagina 1</span>
        <button id="btn-next" class="btn btn-primary">
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
                        homeGallery.innerHTML = '<p class="comment-empty">Non ci sono più foto da mostrare.</p>';
                        return;
                    }

                    result.data.forEach(photo => {
                        const card = document.createElement('div');
                        card.className = 'photo-card';

                        const link = document.createElement('a');
                        link.href = `/post?post=${encodeURIComponent(photo.file_path)}`;

                        const img = document.createElement('img');
                        img.src = photo.file_path;
                        img.alt = "Post di Camagru";
                        img.loading = "lazy";
                        
                        link.appendChild(img);
                        card.appendChild(link);

                        if (photo.filter_3d && photo.filter_3d.trim() !== '') {
                            const iconFileName = photo.filter_3d.replace('.glb', '.png');
                            const icon3D = document.createElement('div');
                            icon3D.className = 'icon-3d-badge badge-top-right';
                            
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
                homeGallery.innerHTML = '<p class="form-message text-danger">Errore nel caricamento dei contenuti.</p>';
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