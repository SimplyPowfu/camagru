<h1>Post</h1>
<h3>Post</h3>
<p id ="name"></p>
<div id="post"></div>

<script>
    (function() {
        async function loadPost() {
            const sideGallery = document.getElementById('post');
			const postName = "<?= $_GET['post'] ?? '' ?>";

			if (!postName) return ;

            try {
                const response = await fetch(`/api/post/picture?file_path=${postName}`);
                const result = await response.json();

                if (result.success && result.data) {
                    sideGallery.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = '/uploads/' + result.data.file_path; 
                    sideGallery.appendChild(img);
                }
            } catch (error) {
                console.error("Errore nel caricamento del post:", error);
            }
        }
        loadPost();
    })();
</script>