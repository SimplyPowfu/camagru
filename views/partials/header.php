<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camagru</title>
    <link rel="stylesheet" href="/css/main.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script type="importmap">
        {
            "imports": {
                "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
                "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
            }
        }
    </script>
</head>
<body>

<div class="app-container">
    <header>
        <div class="logo">
            <a href="/">CAMAGRU<span class="text-secondary">.</span></a>
        </div>
        <nav>
            <a href="/"><i class="fa-solid fa-house"></i> Home</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="/editing"><i class="fa-solid fa-camera"></i> Editor</a>
                <a href="/profile"><i class="fa-solid fa-user"></i> Profilo</a>
                <a href="/logout" class="btn btn-primary">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php else: ?>
                <a href="/login" class="btn btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </header>