<h1>Home</h1>
<?php if (isset($_SESSION['user'])): ?>
    <p>Ciao, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong>!</p>
    <a href="/logout">Logout</a>
<?php else: ?>
    <a href="/login">Login</a>
<?php endif; ?>
<br><a href="/editing">Editing</a><br>
<aside class="gallery">
    <h3>Galleria</h3>
    <div id="home-gallery"></div>
</aside>
<?php
    phpinfo();
?>
<style>
    .gallery {
        margin-top: 20px;
        padding: 10px;
    }

    #home-gallery {
        display: grid;
        grid-template-columns: repeat(5, 1fr); 
        gap: 15px; /* Spazio tra le foto */
        padding: 10px;
    }

    #home-gallery img {
        width: 100%; /* La foto occupa tutta la sua cella della griglia */
        aspect-ratio: 1 / 1; /* Rende le foto quadrate (molto stile Instagram) */
        object-fit: cover; /* Taglia l'immagine per riempire il quadrato senza distorcerla */
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }

    #home-gallery img:hover {
        transform: scale(1.05); /* Effetto zoom al passaggio del mouse */
        cursor: pointer;
    }

    /* per schermi piccoli (tablet/mobile) riduciamo le colonne */
    @media (max-width: 900px) {
        #home-gallery { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 500px) {
        #home-gallery { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<script>
    (function() {
        async function loadGallery() {
            const sideGallery = document.getElementById('home-gallery'); 

            try {
                const response = await fetch(`/api/gallery/picture`);
                const result = await response.json();

                if (result.success && result.data) {
                    sideGallery.innerHTML = '';
                    result.data.forEach(photo => {
                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path; 
                    sideGallery.appendChild(img);
                });
                }
            } catch (error) {
                console.error("Errore nel caricamento della gallery:", error);
            }
        }
        loadGallery();
    })();
</script>