<h1>Home</h1>
<?php if (isset($_SESSION['user'])): ?>
    <p>Ciao, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong>!</p>
<?php endif; ?>
<aside class="gallery">
    <h3>Galleria</h3>
    <div id="home-gallery"></div>
</aside>
<?php
    phpinfo();
?>

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
                    const link = document.createElement('a');
                    link.href = `/post?post=${photo.file_path}`;
                    link.appendChild(img);
                    sideGallery.appendChild(link);
                });
                }
            } catch (error) {
                console.error("Errore nel caricamento della gallery:", error);
            }
        }
        loadGallery();
    })();
</script>