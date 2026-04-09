# 📸 Camagru

Camagru è un'applicazione web full-stack sviluppata in puro PHP (PDO) e Javascript (Vanilla), progettata come clone essenziale di Instagram. Permette agli utenti di registrarsi, scattare foto tramite webcam o caricare immagini, applicare sticker 2D e modelli 3D interattivi, e interagire tramite like e commenti.

Questo progetto è stato realizzato seguendo rigidi vincoli strutturali (niente framework backend/frontend pesanti) con un focus su architettura MVC personalizzata, sicurezza (PDO, CSRF, Hashing) e un'infrastruttura Dockerizzata "Robust & Proper".

---

## ✨ Funzionalità Principali

* **Auth System Sicuro:** Registrazione con validazione email, Login, Reset della password via email e "Deep Linking" (se clicchi un link protetto, dopo il login verrai reindirizzato esattamente dove volevi andare).
* **Editor Avanzato:** * Supporto per Webcam (frontale su mobile) e file upload.
  * Drag & Drop e Pinch-to-Zoom (supporto touch completo) per gli sticker 2D.
  * Rendering di modelli 3D animati (`.glb`) sovrapposti alla scena tramite **Three.js**.
* **Interazioni Social:** Like, commenti e notifiche via email quando qualcuno commenta i tuoi post.
* **Condivisione Nativa:** Integrazione della Web Share API e Meta Tag Open Graph. Grazie al tunnel Ngrok integrato, i post possono essere condivisi su Instagram/WhatsApp mostrando le anteprime reali.
* **Gestione Automatica:** Setup automatico del database e dei permessi delle cartelle all'avvio dei container.

---

## 📂 Struttura del Progetto

```text
.
├── config/                 # File di configurazione e connessione PDO
│   ├── database.php
│   └── setup.php           # Script per la creazione automatica di tabelle
├── public/                 # Risorse accessibili dal browser (Webroot Nginx)
│   ├── css/                # Fogli di stile modulari e variabili CSS
│   ├── filter/             # Sticker 2D (.png) e modelli 3D (.glb)
│   ├── uploads/            # Cartella immagini generate dagli utenti
│   └── index.php           # Entry point dell'applicazione
├── src/                    # Logica Core (Backend MVC)
│   ├── controllers/        # Gestione logica delle rotte (Auth, Home, Photo)
│   ├── models/             # Interazioni col Database (User, Photo)
│   ├── utils/              # Helper (connessione DB, invio Email)
│   ├── AuthMiddleware.php  # Protezione delle rotte private
│   ├── controller.php      # Classe base dei controller e rendering viste
│   └── router.php          # Sistema di routing personalizzato
├── views/                  # UI e Frontend
│   ├── pages/              # Le singole schermate (Home, Post, Editing...)
│   └── partials/           # Componenti riutilizzabili (Header, Footer, Sidebar)
├── docker-compose.yml      # Orchestrazione dei container
├── Dockerfile              # Configurazione dell'immagine Backend (PHP-FPM)
├── Makefile                # Comandi rapidi per build e avvio
├── nginx.conf              # Configurazione del server web
└── setup.sh                # Entrypoint Docker: permessi e attesa DB
```

## Credenziali Database
```
MYSQL_ROOT_PASSWORD=root_password
MYSQL_DATABASE=camagru
MYSQL_USER=camagru_user
MYSQL_PASSWORD=camagru_password

# Host del DB (nome del servizio in docker-compose)
DB_HOST=nome_del_servizio_docker-compose
DB_NAME=nome_del_db
DB_USER=user_del_db
DB_PASS=pass_del_db

## Token di Ngrok (necessario per l'HTTPS e la condivisione social)
NGROK_AUTHTOKEN=inserisci_qui_il_tuo_token
```

## Run del progetto
Apri il terminale nella root del progetto e lancia:
```
make
```
Il file setup.sh si occuperà automaticamente di assegnare i permessi chmod 777 alla cartella public/uploads e di aspettare che MySQL sia pronto prima di eseguire config/setup.php per creare il database.
