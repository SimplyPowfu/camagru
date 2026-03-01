camagru/
├── config/             # Configurazione e Setup
│   ├── database.php    # Connessione PDO
│   └── setup.php       # Script per creare tabelle/DB
├── public/             # File accessibili dal browser
│   ├── css/            # Fogli di stile (Layout responsivo)
│   ├── js/             # Script (Webcam, anteprime)
│   ├── images/         # Filtri (immagini con canale alpha)
│   └── index.php       # Punto di ingresso (Router)
├── src/                # Logica del Backend
│   ├── Controllers/    # Gestione richieste (Login, Upload)
│   ├── Models/         # Query SQL (User, Image, Comment)
│   └── Utils/          # Email, Validazioni, Image Processing
├── views/              # Template HTML/PHP (Layout)
│   ├── partials/       # Header, Footer, Side
│   └── pages/          # Home, Gallery, Editing
├── uploads/            # Foto finali salvate (Server-side)
├── .env                # Credenziali (DA IGNORARE IN GIT)
└── docker-compose.yml  # Containerizzaze
