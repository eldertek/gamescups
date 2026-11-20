<?php

declare(strict_types=1);

/**
 * Security helpers for GamesCups (OWASP Top 10:2025 hardening).
 */

function gamescups_init_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header(
        "Content-Security-Policy: default-src 'self'; "
        ."img-src 'self' https: http: data:; "
        ."style-src 'self' 'unsafe-inline'; "
        ."script-src 'self' 'unsafe-inline'; "
        ."base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
}

function gamescups_start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (! headers_sent()) {
            $isHttps = (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.cookie_httponly', '1');

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        @session_start();
    }

    if (empty($_SESSION['csrf_token']) || ! is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function gamescups_csrf_token(): string
{
    gamescups_start_secure_session();

    return (string) $_SESSION['csrf_token'];
}

function gamescups_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'.gamescups_e(gamescups_csrf_token()).'">';
}

function gamescups_verify_csrf(?string $submittedToken): bool
{
    gamescups_start_secure_session();

    if (! is_string($submittedToken) || $submittedToken === '') {
        return false;
    }

    $expected = $_SESSION['csrf_token'] ?? '';

    return is_string($expected) && $expected !== '' && hash_equals($expected, $submittedToken);
}

function gamescups_e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gamescups_safe_url(?string $url, bool $allowRelativeAssets = true): string
{
    if (! is_string($url)) {
        return $allowRelativeAssets ? '' : '#';
    }

    $cleaned = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', '', $url));
    if ($cleaned === '') {
        return $allowRelativeAssets ? '' : '#';
    }

    if ($allowRelativeAssets
        && ! str_contains($cleaned, '..')
        && preg_match('#^/?assets/[a-zA-Z0-9_\-./]+$#', $cleaned) === 1
    ) {
        return str_starts_with($cleaned, '/') ? $cleaned : '/'.$cleaned;
    }

    if (filter_var($cleaned, FILTER_VALIDATE_URL) !== false) {
        $scheme = strtolower((string) parse_url($cleaned, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return $cleaned;
        }
    }

    return $allowRelativeAssets ? '' : '#';
}

function gamescups_configured_admin_token(): string
{
    $envToken = getenv('GAMESCUPS_ADMIN_TOKEN');
    if (is_string($envToken) && $envToken !== '') {
        return $envToken;
    }

    global $gamescupsConfig;
    if (is_array($gamescupsConfig) && isset($gamescupsConfig['admin_token']) && is_string($gamescupsConfig['admin_token'])) {
        return $gamescupsConfig['admin_token'];
    }

    return '';
}

function gamescups_is_admin_authenticated(): bool
{
    gamescups_start_secure_session();

    return ! empty($_SESSION['gamescups_admin_authenticated']) && $_SESSION['gamescups_admin_authenticated'] === true;
}

function gamescups_try_admin_login(?string $candidateToken): bool
{
    gamescups_start_secure_session();

    $expected = gamescups_configured_admin_token();
    if (strlen($expected) < 16 || ! is_string($candidateToken) || $candidateToken === '') {
        return false;
    }

    if (! hash_equals($expected, $candidateToken)) {
        return false;
    }

    if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['gamescups_admin_authenticated'] = true;

    return true;
}

function gamescups_admin_logout(): void
{
    gamescups_start_secure_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies') && ! headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
