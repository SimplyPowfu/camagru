<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camagru</title>
    <link rel="stylesheet" href="/css/style.css"> 
</head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<body>
    <nav>
        <a href="/">Home</a>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="/editing">Editor</a>
            <a href="/profile">Profile</a>
            <a href="/logout">Logout</a>
        <?php else: ?>
            <a href="/login">Login</a>
        <?php endif; ?>
    </nav>
    <hr>