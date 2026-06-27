<?php
if (session_status() === PHP_SESSION_NONE) session_start();

unset($_SESSION['bot_token'], $_SESSION['bot_id']);

// redirect back to index
header('Location: index.php');
exit;
