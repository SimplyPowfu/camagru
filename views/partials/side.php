<aside class="sidebar">
	<h3>Le tue creazioni</h3>
	<div id="side-gallery"></div>
</aside>

<script>
    // Definiamo la funzione come globale (window.) per essere sicuri che l'editor possa chiamarla dopo il save
    window.loadUserGallery = async function() {
        const sideGallery = document.getElementById('side-gallery');
        if (!sideGallery) return;

        const username = "<?= $_SESSION['user']['username'] ?? '' ?>"; 
        if (!username) return;

        try {
            const response = await fetch(`/api/user/picture?username=${username}`);
            const result = await response.json();

            if (result.success && result.data) {
                sideGallery.innerHTML = '';
                result.data.forEach(photo => {
                    const img = document.createElement('img');
                    img.src = '/uploads/' + photo.file_path;
                    img.onclick = () => window.location.href = `/post?post=${photo.file_path}`;
                    img.style.cursor = 'pointer';
                    sideGallery.appendChild(img);
                });
            }
        } catch (error) {
            console.error("Errore nel caricamento della gallery laterale:", error);
        }
    };
    document.addEventListener('DOMContentLoaded', window.loadUserGallery);
</script>