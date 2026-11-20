<?php

declare(strict_types=1);

/**
 * Copy this file to config/config.local.php for local overrides (ignored by Git),
 * or provide the GAMESCUPS_* environment variables directly.
 */
$gamescupsConfig = [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'gamescups',
    'db_user' => 'gamescups',
    'db_pass' => '',
    // Minimum 16 characters required to enable admin login:
    'admin_token' => '',
];
