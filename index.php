<?php
session_start();

/* ---------------------------
   CSRF TOKEN
---------------------------- */
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------------------------
   STATE
---------------------------- */
$guilds = [];
$bot = null;
$error = null;

/* ---------------------------
   DISCORD API HELPER
---------------------------- */
function discordApiRequest(string $url, string $token): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot $token",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($curlError) return [null, "cURL Fehler: $curlError"];
    if ($httpCode >= 400) return [null, "HTTP Fehler: $httpCode"];

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, "JSON Fehler"];
    }

    return [$data, null];
}

/* ---------------------------
   HANDLE POST
---------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = "CSRF Fehler";
    } else {

        $token = trim($_POST["token"] ?? "");

        if ($token === "") {
            $error = "Bitte Token eingeben";
        } else {

            [$bot, $error] = discordApiRequest(
                "https://discord.com/api/v10/users/@me",
                $token
            );

            if (!$error && $bot) {

                [$guilds, $error] = discordApiRequest(
                    "https://discord.com/api/v10/users/@me/guilds",
                    $token
                );

                if (is_array($guilds)) {
                    usort($guilds, fn($a, $b) => strcmp($a["name"] ?? "", $b["name"] ?? ""));
                } else {
                    $guilds = [];
                }
            }
        }
    }
}

$guildCount = count($guilds ?? []);
?>

<!DOCTYPE html>
<html lang="de">
<head>
<title>Discord-Server Counter</title>

    <!-- Meta Tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#c6a0f6">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="https://jaybelife.de/assets/images/pfp.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://jaybelife.de/assets/images/pfp.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://jaybelife.de/assets/images/pfp.png">
    <link rel="icon" href="/https://jaybelife.deassets/images/pfp.png" type="image/png">

    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="de_DE">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Discord-Server Counter">
    <meta property="og:description" content="Zeige alle Discord-Server an, auf dem dein Discord-Server ist">
    <meta property="og:image" content="https://jaybelife.de/assets/images/pfp.png">
    <meta property="og:url" content="">
    <meta property="og:site_name" content="Powered by J4YY.DE">

    <!-- Twitter -->
    <meta name="twitter:title" content="Discord-Server Counter">
    <meta name="twitter:description" content="Zeige alle Discord-Server an, auf dem dein Discord-Server ist">
    <meta name="twitter:image" content="https://jaybelife.de/assets/images/pfp.png">
    <meta name="twitter:site" content="@einfachj4y">
    <meta name="twitter:creator" content="@einfachj4y">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">

<style>
:root {
    --base: #24273a;
    --mantle: #1e2030;
    --crust: #181926;
    --surface: #363a4f;
    --text: #cad3f5;
    --subtext: #a5adcb;
    --mauve: #c6a0f6;
    --red: #ed8796;

    --bg: var(--base);
    --panel: var(--mantle);
    --border: var(--surface);
    --accent: var(--mauve);
}

* {
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

body {
    min-height: 100vh;
    padding: 10px;
    background: var(--base);
    font-family: "Poppins", sans-serif;
    color: var(--text);
}

.wrapper {
	width: 100%;
    margin: 0 auto;
}

.panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.section {
    padding: 20px;
    border-bottom: 1px solid var(--border);
}

.section:last-of-type {
    border-bottom: none;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.section-header h2 {
    flex: 1;
    min-width: 0;
}

.badge {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;

    font-size: 12px;
    padding: 4px 10px;
    border-radius: 999px;

    background: rgba(198, 160, 246, 0.15);
    color: var(--mauve);

    white-space: nowrap;
}

h1 {
    margin: 0 0 15px 0;
    font-size: 20px;
}

h2 {
    margin: 0;
    font-size: 16px;
    color: var(--subtext);
}

input {
    width: 100%;
    padding: 12px;
    background: var(--crust);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
}

button {
    margin-top: 10px;
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: var(--accent);
    color: white;
    cursor: pointer;
}

.error {
    padding: 10px 20px;
    color: var(--red);
    border-bottom: 1px solid var(--border);
}

.item-list {
    display: flex;
    flex-direction: column;
}

.item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-top: 1px solid var(--border);
}

.item img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
}

.item .meta {
    display: flex;
    flex-direction: column;
}

.item .meta span {
    color: var(--text);
}

.item .meta code {
    color: var(--subtext);
    font-family: Consolas, monospace;
    font-size: 12px;
    margin-top: 3px;
}

/* FOOTER */
.footer {
    padding: 18px 20px;
}

.footer-inner {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
    text-align: center;
}

.footer-title {
    font-size: 13px;
    color: var(--text);
}

.footer-title strong {
    color: var(--mauve);
}

.footer-text {
    font-size: 12px;
    color: var(--subtext);
}

.footer a {
    color: var(--mauve);
    text-decoration: none;
	font-weight: bold;
}

.footer a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="wrapper">
<div class="panel">

    <div class="section">
        <h1>Discord-Server Counter</h1>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
            <input type="password" name="token" placeholder="Bot Token eingeben..." required>
            <button type="submit">Login</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($bot): ?>
    <div class="section">
        <h2>Angemeldet als:</h2>

        <div class="item">
            <?php
            $botIcon = !empty($bot["avatar"])
                ? "https://cdn.discordapp.com/avatars/" . $bot["id"] . "/" . $bot["avatar"] . ".png"
                : "https://cdn.discordapp.com/embed/avatars/0.png";
            ?>

            <img src="<?= $botIcon ?>">
            <div class="meta">
                <span><?= htmlspecialchars($bot["username"] ?? "Unbekannt") ?></span>
                <code><?= htmlspecialchars($bot["id"] ?? "") ?></code>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($guilds)): ?>
    <div class="section">

        <div class="section-header">
            <h2><?= $guildCount ?> Server</h2>
        </div>

        <div class="item-list">
            <?php foreach ($guilds as $g): ?>
                <?php
                $icon = !empty($g["icon"])
                    ? "https://cdn.discordapp.com/icons/" . $g["id"] . "/" . $g["icon"] . ".png"
                    : "https://cdn.discordapp.com/embed/avatars/0.png";
                ?>

                <div class="item">
                    <img src="<?= $icon ?>">
                    <div class="meta">
                        <span><?= htmlspecialchars($g["name"]) ?></span>
                        <code><?= htmlspecialchars($g["id"]) ?></code>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endif; ?>

    <div class="footer">
        <div class="footer-inner">
            <div class="footer-title">
				Made by <a href="https://jaybelife.de" target="_blank">.jaybelife</a> with <i class="fas fa-heart"></i>
            </div>

            <div class="footer-text">
                Kein offizielles Discord Produkt
            </div>

            <div class="footer-text">
                <a href="https://github.com/jaybelife" target="_blank">Open Source</a> for You!
            </div>
        </div>
    </div>

</div>
</div>

</body>
</html>
