# Discord Server Counter

Ein kleines PHP Tool, das Discord Bot Informationen und alle Server (Guilds) eines Bots anzeigt.

---

## 📌 Features

- Login mit Discord Bot Token
- Anzeige von Bot Username + ID
- Anzeige aller Server (Guilds), in denen der Bot ist
- Alphabetisch sortierte Serverliste
- Modernes Dark UI

---

## 🔐 Sicherheit

- Token wird **nicht gespeichert**
- Token wird nur für API Requests genutzt
- CSRF Schutz ist integriert
- Keine Datenbank erforderlich

---

## ⚙️ Funktionsweise

Das Tool nutzt die Discord API:

- `/users/@me`
- `/users/@me/guilds`

Die Daten werden serverseitig mit PHP (cURL) abgefragt und im Browser dargestellt.

---

## ⚠️ Hinweis

Kein offizielles Discord Produkt.

---

## 👤 Credits

Made by .jaybelife  
GitHub: https://github.com/jaybelife

---

## 📄 License

Dieses Projekt ist unter der MIT License veröffentlicht.
