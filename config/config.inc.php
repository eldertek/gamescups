<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    @ini_set('display_errors', '0');
}

require_once __DIR__.'/security.inc.php';

$gamescupsConfig = [];
$localConfigFile = __DIR__.'/config.local.php';
if (is_file($localConfigFile)) {
    require $localConfigFile;
}

gamescups_init_security_headers();
gamescups_start_secure_session();

$dbdsn = getenv('GAMESCUPS_DB_DSN') ?: ($gamescupsConfig['db_dsn'] ?? '');
$dbhost = getenv('GAMESCUPS_DB_HOST') ?: ($gamescupsConfig['db_host'] ?? '127.0.0.1');
$dbport = getenv('GAMESCUPS_DB_PORT') ?: ($gamescupsConfig['db_port'] ?? '3306');
$dbname = getenv('GAMESCUPS_DB_NAME') ?: ($gamescupsConfig['db_name'] ?? 'gamescups');
$dbuser = getenv('GAMESCUPS_DB_USER') ?: ($gamescupsConfig['db_user'] ?? 'gamescups');
$dbpass = getenv('GAMESCUPS_DB_PASS');
if ($dbpass === false) {
    $dbpass = (string) ($gamescupsConfig['db_pass'] ?? '');
}

if ($dbdsn === '') {
    $dbdsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbhost, $dbport, $dbname);
}

$connect = null;

try {
    $connect = new PDO($dbdsn, (string) $dbuser, (string) $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $rawIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $ip = filter_var($rawIp, FILTER_VALIDATE_IP) !== false ? $rawIp : '0.0.0.0';
    $date = date('Y-m-d');
    $driver = (string) $connect->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $sql = 'INSERT INTO stats_visites (ip, date_visite, pages_vues) VALUES (:ip, :date, 1) '
            .'ON CONFLICT(ip, date_visite) DO UPDATE SET pages_vues = pages_vues + 1';
    } else {
        $sql = 'INSERT INTO stats_visites (ip, date_visite, pages_vues) VALUES (:ip, :date, 1) '
            .'ON DUPLICATE KEY UPDATE pages_vues = pages_vues + 1';
    }

    $query = $connect->prepare($sql);
    $query->execute([
        ':ip' => $ip,
        ':date' => $date,
    ]);
} catch (PDOException $e) {
    error_log('[GamesCups] Database connection or visit tracking error: '.$e->getMessage());
    $connect = null;
}
