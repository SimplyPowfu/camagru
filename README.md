# 📸 Camagru (Cloud Edition)

Camagru è un'applicazione web full-stack sviluppata in **PHP 8.2 (Vanilla)** e **Javascript**, progettata come clone moderno di Instagram. Questa versione è ottimizzata per il deploy in ambienti Cloud professionali, utilizzando un'architettura a microservizi scalabile.

Il progetto segue rigidi vincoli strutturali con un focus su architettura **MVC personalizzata**, sicurezza (PDO, CSRF, Hashing) e un'infrastruttura **Dockerizzata**.

---

## 🚀 Architettura Cloud

Per garantire robustezza e persistenza, l'app è stata migrata da un ambiente locale a un'infrastruttura distribuita:
* **Compute:** [Render](https://render.com/) (Docker Runtime).
* **Database:** [Aiven](https://aiven.io/) (Managed MySQL 8.0).
* **Storage:** [Cloudinary](https://cloudinary.com/) (CDN per la gestione e persistenza delle immagini).
* **Email:** [Brevo](https://www.brevo.com/) (SMTP relay) / Cloud MailHog per i test.

---

## ✨ Funzionalità Principali

* **Auth System:** Registrazione, Login e Reset password. Bypass della verifica email integrato per scopi dimostrativi.
* **Editor & Compositing:** * Supporto Webcam e file upload.
  * Applicazione di sticker 2D tramite librerie GD di PHP.
  * Rendering di modelli 3D animati (`.glb`) tramite **Three.js**.
* **Social & Interaction:** Sistema di Like e Commenti in tempo reale (JSON API).
* **Storage Persistente:** Integrazione con l'SDK di Cloudinary per garantire che le foto non vengano perse al riavvio dei container.
* **Responsive UI:** Design mobile-first con CSS variabili e supporto touch (pinch-to-zoom sugli sticker).

---

## 📂 Struttura del Progetto

```text
.
├── config/             # Configurazione PDO e Database
├── public/             # Webroot (Asset statici, sticker, filtri)
│   └── index.php       # Entry point
├── src/                # Logica Backend (MVC)
│   ├── controllers/    # Controller (Cloudinary Integration)
│   ├── models/         # Modelli (Interazione Aiven MySQL)
│   ├── utils/          # Helper (Email SMTP, DB Connection)
│   └── router.php      # Router personalizzato (supporto Health Check)
├── views/              # Frontend (PHP Templates)
├── docker-compose.yml  # Orchestrazione per sviluppo locale
├── Dockerfile          # Configurazione per il deploy su Render
└── Makefile            # Shortcut per lo sviluppo locale
```

## 🛠️ Configurazione Environment (.env)
```
Database (Aiven)
    DB_HOST=
    DB_PORT=
    DB_NAME=
    DB_USER=
    DB_PASS=

Storage (Cloudinary)
    CLOUDINARY_CLOUD_NAME=
    CLOUDINARY_API_KEY=
    CLOUDINARY_API_SECRET=

Email (MailHog / Brevo)
    SMTP_HOST=
    SMTP_PORT=
    SMTP_FROM=
```

## 🌍 Deploy su Render
L'app viene distribuita come Web Service Docker.

  - Il Dockerfile imposta automaticamente la DocumentRoot su /public.

  - Le dipendenze vengono installate durante la build tramite composer install --no-dev.

  - È necessario impostare la variabile PORT=80 su Render per il corretto instradamento del traffico.

## 💻 Sviluppo Locale
Installa le dipendenze Composer:
```
docker compose run --rm backend composer install
```
Avvia l'ambiente
```
make up
```