<?php
ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_SESSION['bot_token'] ?? null;
$botId = $_SESSION['bot_id'] ?? null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Kein Bot-Token in der Session. Bitte einloggen.']);
    exit;
}

$limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 25;
$after = $_GET['after'] ?? null;

$url = $appConfig['discord_api_base'] . '/users/@me/guilds?limit=' . $limit;
if ($after) {
    $url .= '&after=' . $after;
}

[$guilds, $err] = discordApiRequest($url, $token);

if ($err) {
    http_response_code(502);
    echo json_encode(['error' => $err]);
    exit;
}

$result = [];

// helper: perform parallel requests in batches using curl_multi
function multiFetch(array $urls, string $token, int $batchSize = 6): array {
    $out = [];
    $chunks = array_chunk($urls, $batchSize, true);

    foreach ($chunks as $chunk) {
        $multi = curl_multi_init();
        $handles = [];

        foreach ($chunk as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bot $token", "Content-Type: application/json"],
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[(int)$ch] = ['handle' => $ch, 'key' => $key];
        }

        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi, 0.5);
        } while ($running > 0);

        foreach ($handles as $h) {
            $ch = $h['handle'];
            $key = $h['key'];
            $content = curl_multi_getcontent($ch);
            $err = curl_error($ch);
            if ($err) {
                $out[$key] = ['error' => $err, 'body' => null];
            } else {
                $out[$key] = ['error' => null, 'body' => $content];
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
    }

    return $out;
}

if (is_array($guilds)) {
    // prepare urls
    $detailUrls = [];
    $memberUrls = [];
    foreach ($guilds as $g) {
        $id = $g['id'] ?? null;
        if (!$id) continue;
        $detailUrls[$id] = $appConfig['discord_api_base'] . '/guilds/' . $id . '?with_counts=true';
        if ($botId) {
            $memberUrls[$id] = $appConfig['discord_api_base'] . '/guilds/' . $id . '/members/' . $botId;
        }
    }

    // fetch details in batches
    $detailsRes = multiFetch($detailUrls, $token, 6);

    // fetch member entries in batches (if botId)
    $membersRes = $botId ? multiFetch($memberUrls, $token, 6) : [];

    foreach ($guilds as $g) {
        $id = $g['id'] ?? null;
        if (!$id) continue;

        $entry = [
            'id' => $id,
            'name' => $g['name'] ?? null,
            'icon' => $g['icon'] ?? null,
            'member_count' => null,
            'joined_at' => null,
        ];

        if (isset($detailsRes[$id]) && $detailsRes[$id]['error'] === null) {
            $decoded = json_decode($detailsRes[$id]['body'], true);
            if (is_array($decoded)) {
                $entry['member_count'] = $decoded['approximate_member_count'] ?? $decoded['member_count'] ?? null;
            }
        }

        if ($botId && isset($membersRes[$id]) && $membersRes[$id]['error'] === null) {
            $decoded = json_decode($membersRes[$id]['body'], true);
            if (is_array($decoded) && !empty($decoded['joined_at'])) {
                $entry['joined_at'] = $decoded['joined_at'];
            }
        }

        $result[] = $entry;
    }
}

// sort by joined_at descending (newest first). Nulls go last.
usort($result, function ($a, $b) {
    $ta = !empty($a['joined_at']) ? strtotime($a['joined_at']) : 0;
    $tb = !empty($b['joined_at']) ? strtotime($b['joined_at']) : 0;
    
    if ($ta === false) $ta = 0;
    if ($tb === false) $tb = 0;

    return $tb <=> $ta;
});

$discordLastId = (is_array($guilds) && !empty($guilds)) ? end($guilds)['id'] : null;
$hasMore = (is_array($guilds) && count($guilds) === $limit);

if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'guilds' => $result,
    'lastId' => $discordLastId,
    'hasMore' => $hasMore
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
