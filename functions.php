<?php
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

    if ($curlError) {
        return [null, "cURL Fehler: $curlError"];
    }

    if ($httpCode >= 400) {
        return [null, "HTTP Fehler: $httpCode"];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, 'JSON Fehler'];
    }

    return [$data, null];
}

function getBotAvatarUrl(array $bot): string
{
    if (!empty($bot['avatar'])) {
        return 'https://cdn.discordapp.com/avatars/' . $bot['id'] . '/' . $bot['avatar'] . '.png';
    }

    return 'https://cdn.discordapp.com/embed/avatars/0.png';
}

function getGuildIconUrl(array $guild): string
{
    if (!empty($guild['icon'])) {
        return 'https://cdn.discordapp.com/icons/' . $guild['id'] . '/' . $guild['icon'] . '.png';
    }

    return 'https://cdn.discordapp.com/embed/avatars/0.png';
}

function h($value = ''): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
