<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$appConfig = [
    'site_title' => 'Discord-Server Counter',
    'theme_color' => '#c6a0f6',
    'favicon_url' => 'https://jaybelife.de/assets/images/pfp.png',
    'discord_api_base' => 'https://discord.com/api/v10',
    'fontawesome_css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
    'default_avatar_url' => 'https://cdn.discordapp.com/embed/avatars/0.png',
    'social' => [
        'og_title' => 'Discord-Server Counter',
        'og_description' => 'Zeige alle Discord-Server an, auf dem dein Bot vertreten ist',
        'twitter_title' => 'Discord-Server Counter',
        'twitter_description' => 'Zeige alle Discord-Server an, auf dem dein Bot vertreten ist',
        'twitter_site' => '@einfachj4y',
        'twitter_creator' => '@einfachj4y',
        'site_name' => 'Powered by J4YY.DE',
    ],
];
