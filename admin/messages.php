<?php

declare(strict_types=1);

require_once __DIR__.'/../config/config.inc.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    if (! headers_sent()) {
        header('Location: /');
    }
    exit;
}

$submittedCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
$rawEmail = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
$rawMessage = isset($_POST['message']) && is_string($_POST['message']) ? trim($_POST['message']) : '';

if (! gamescups_verify_csrf($submittedCsrf)
    || filter_var($rawEmail, FILTER_VALIDATE_EMAIL) === false
    || $rawMessage === ''
    || ! ($connect instanceof PDO)
) {
    if (! headers_sent()) {
        header('Location: /');
    }
    exit;
}

try {
    $stmt = $connect->prepare('INSERT INTO messages (email, message) VALUES (:email, :message)');
    $stmt->execute([
        ':email' => mb_substr($rawEmail, 0, 255),
        ':message' => mb_substr($rawMessage, 0, 5000),
    ]);
    echo "<script type='text/javascript'>alert('Message bien envoye ! A bientot !'); window.location.replace('/')</script>";
} catch (PDOException $e) {
    error_log('[GamesCups] Contact form insert error: '.$e->getMessage());
    if (! headers_sent()) {
        header('Location: /');
    }
    exit;
}
