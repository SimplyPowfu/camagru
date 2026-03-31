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

        async function loadGallery(page, exist=true) {
            const homeGallery = document.getElementById('home-gallery'); 
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

                        const link = document.createElement('a');
                        link.href = `/post?post=${encodeURIComponent(photo.file_path)}`;
                        link.style.display = 'block';

                        const img = document.createElement('img');
                        img.src = '/uploads/' + photo.file_path;
                        img.alt = "Post di Camagru";
                        img.loading = "lazy";

                        link.appendChild(img);
                        card.appendChild(link);
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