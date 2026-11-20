<?php

declare(strict_types=1);

/**
 * Deterministic OWASP Top 10:2025 & PHP 8.4 security test suite for GamesCups.
 * Runs standalone via: php -d error_reporting=E_ALL tests/SecurityAuditTest.php
 */

final class SecurityAuditRunner
{
    private string $repoRoot;

    public int $assertions = 0;

    public function __construct(string $repoRoot)
    {
        $this->repoRoot = $repoRoot;
    }

    public function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    public function assertSame($expected, $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException(
                ($message !== '' ? $message.': ' : '')
                .sprintf('Expected %s, got %s.', var_export($expected, true), var_export($actual, true))
            );
        }
    }

    public function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (! str_contains($haystack, $needle)) {
            throw new RuntimeException(
                ($message !== '' ? $message.': ' : '')
                .sprintf('Expected output to contain %s.', var_export($needle, true))
            );
        }
    }

    public function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException(
                ($message !== '' ? $message.': ' : '')
                .sprintf('Expected output NOT to contain %s.', var_export($needle, true))
            );
        }
    }

    /**
     * Create a temporary SQLite database with the GamesCups schema.
     */
    private function createTempDatabase(): array
    {
        $dbFile = tempnam(sys_get_temp_dir(), 'gc_db_');
        $pdo = new PDO('sqlite:'.$dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('CREATE TABLE stats_visites (
            ip TEXT NOT NULL,
            date_visite TEXT NOT NULL,
            pages_vues INTEGER NOT NULL DEFAULT 1,
            PRIMARY KEY (ip, date_visite)
        )');
        $pdo->exec('CREATE TABLE articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            banner TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            image TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE sliders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slider TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE partenaires (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            image TEXT NOT NULL,
            website TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            message TEXT NOT NULL
        )');

        return [$dbFile, $pdo];
    }

    /**
     * Execute a PHP entrypoint in an isolated subprocess simulating an HTTP request.
     *
     * @param array<string, mixed> $spec
     * @return array{stdout: string, stderr: string, code: int}
     */
    private function runPhpRequest(string $relScript, array $spec = []): array
    {
        $scriptPath = $this->repoRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relScript);
        $scriptDir = dirname($scriptPath);

        $bootstrap = tempnam(sys_get_temp_dir(), 'gc_req_');
        $payload = [
            'script' => $scriptPath,
            'dir' => $scriptDir,
            'method' => $spec['method'] ?? 'GET',
            'get' => $spec['get'] ?? [],
            'post' => $spec['post'] ?? [],
            'session' => $spec['session'] ?? [],
            'env' => $spec['env'] ?? [],
        ];

        $runnerCode = "<?php\n"
            ."error_reporting(E_ALL);\n"
            ."ini_set('display_errors', '1');\n"
            .'$p = '.var_export($payload, true).";\n"
            ."foreach (\$p['env'] as \$k => \$v) { putenv(\$k.'='.\$v); \$_ENV[\$k] = \$v; }\n"
            ."\$_SERVER['REQUEST_METHOD'] = \$p['method'];\n"
            ."\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';\n"
            ."\$_GET = \$p['get'];\n"
            ."\$_POST = \$p['post'];\n"
            ."if (!empty(\$p['session'])) {\n"
            ."    if (session_status() === PHP_SESSION_NONE) { session_start(); }\n"
            ."    foreach (\$p['session'] as \$sk => \$sv) { \$_SESSION[\$sk] = \$sv; }\n"
            ."}\n"
            ."chdir(\$p['dir']);\n"
            ."require \$p['script'];\n";

        file_put_contents($bootstrap, $runnerCode);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open([PHP_BINARY, '-d', 'short_open_tag=0', $bootstrap], $descriptors, $pipes, $scriptDir);
        if (! is_resource($proc)) {
            @unlink($bootstrap);

            throw new RuntimeException('Unable to spawn PHP subprocess');
        }

        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        @unlink($bootstrap);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'code' => $exitCode,
        ];
    }

    public function test_a02_a10_no_hardcoded_secrets_and_no_pdo_exception_leaks(): void
    {
        $configSrc = (string) file_get_contents($this->repoRoot.'/config/config.inc.php');
        $this->assertStringNotContains('Gr1Motdep@sse', $configSrc, 'Hardcoded DB password must be removed from config.inc.php');

        $res = $this->runPhpRequest('index.php', [
            'env' => [
                'GAMESCUPS_DB_DSN' => 'mysql:host=127.0.0.1;port=1;dbname=unreachable_probe',
                'GAMESCUPS_DB_USER' => 'probe',
                'GAMESCUPS_DB_PASS' => 'probe',
            ],
        ]);

        $this->assertStringNotContains('SQLSTATE', $res['stdout'], 'PDO SQLSTATE details must never leak to public HTML');
        $this->assertStringNotContains('Erreur de traitement:', $res['stdout'], 'Raw DB error banner must never leak to public HTML');
        $this->assertStringContains('<title>GamesCups - Accueil</title>', $res['stdout']);
    }

    public function test_a01_a07_unauthenticated_admin_blocks_data_exfiltration_and_mutations(): void
    {
        [$dbFile, $pdo] = $this->createTempDatabase();

        try {
            $pdo->exec("INSERT INTO messages (email, message) VALUES ('sentinel@probe.test', 'CONFIDENTIAL_PROBE_MESSAGE')");

            $env = [
                'GAMESCUPS_DB_DSN' => 'sqlite:'.$dbFile,
                'GAMESCUPS_ADMIN_TOKEN' => 's3nt1n3l-strong-admin-secret-2026',
            ];

            // 1. Unauthenticated GET /admin/?action=messages (no token) must NOT leak messages
            $noToken = $this->runPhpRequest('admin/index.php', [
                'method' => 'GET',
                'get' => ['action' => 'messages'],
                'env' => $env,
            ]);
            $this->assertStringNotContains('CONFIDENTIAL_PROBE_MESSAGE', $noToken['stdout'], 'Unauthenticated request leaked private messages');
            $this->assertStringNotContains('sentinel@probe.test', $noToken['stdout']);

            // 2. Legacy hardcoded ?token=ok must also be rejected
            $weakToken = $this->runPhpRequest('admin/index.php', [
                'method' => 'GET',
                'get' => ['token' => 'ok', 'action' => 'messages'],
                'env' => $env,
            ]);
            $this->assertStringNotContains('CONFIDENTIAL_PROBE_MESSAGE', $weakToken['stdout'], 'Weak legacy ?token=ok must be rejected');

            // 3. Unauthenticated POST /admin/?action=articles must NOT insert into DB
            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'articles'],
                'post' => [
                    'title' => 'Unauthorized Article',
                    'content' => 'Injected without auth',
                    'banner' => '/assets/img/articles/1.jpg',
                ],
                'env' => $env,
            ]);
            $count = (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
            $this->assertSame(0, $count, 'Unauthenticated POST inserted an article');
        } finally {
            unset($pdo);
            @unlink($dbFile);
        }
    }

    public function test_a01_csrf_protection_enforced_on_admin_and_contact_mutations(): void
    {
        [$dbFile, $pdo] = $this->createTempDatabase();

        try {
            $env = [
                'GAMESCUPS_DB_DSN' => 'sqlite:'.$dbFile,
                'GAMESCUPS_ADMIN_TOKEN' => 's3nt1n3l-strong-admin-secret-2026',
            ];

            // Authenticated session WITHOUT CSRF token must be rejected
            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'articles'],
                'post' => [
                    'title' => 'Missing CSRF',
                    'content' => 'Body',
                    'banner' => '/assets/img/articles/1.jpg',
                ],
                'session' => [
                    'gamescups_admin_authenticated' => true,
                    'csrf_token' => 'expected-csrf-sentinel-token-0001',
                ],
                'env' => $env,
            ]);
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(), 'Admin POST without CSRF token must fail');

            // Authenticated session WITH matching CSRF token succeeds
            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'articles'],
                'post' => [
                    'csrf_token' => 'expected-csrf-sentinel-token-0001',
                    'title' => 'Tournoi Regional',
                    'content' => 'Ouverture des inscriptions.',
                    'banner' => '/assets/img/articles/1.jpg',
                ],
                'session' => [
                    'gamescups_admin_authenticated' => true,
                    'csrf_token' => 'expected-csrf-sentinel-token-0001',
                ],
                'env' => $env,
            ]);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(), 'Valid authenticated + CSRF admin POST must insert article');

            // Public contact form without CSRF token must be rejected
            $this->runPhpRequest('admin/messages.php', [
                'method' => 'POST',
                'post' => [
                    'email' => 'qa-bot@example.test',
                    'message' => 'Missing CSRF contact',
                ],
                'session' => [
                    'csrf_token' => 'contact-csrf-token-0002',
                ],
                'env' => $env,
            ]);
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(), 'Contact POST without CSRF token must fail');

            // Public contact form with valid CSRF token + valid email succeeds
            $this->runPhpRequest('admin/messages.php', [
                'method' => 'POST',
                'post' => [
                    'csrf_token' => 'contact-csrf-token-0002',
                    'email' => 'qa-bot@example.test',
                    'message' => 'Bonjour, question sur le tournoi.',
                ],
                'session' => [
                    'csrf_token' => 'contact-csrf-token-0002',
                ],
                'env' => $env,
            ]);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(), 'Valid contact POST must insert message');
        } finally {
            unset($pdo);
            @unlink($dbFile);
        }
    }

    public function test_a05_reflected_and_stored_xss_escaped_across_all_views(): void
    {
        [$dbFile, $pdo] = $this->createTempDatabase();

        try {
            $stmtMsg = $pdo->prepare('INSERT INTO messages (email, message) VALUES (:e, :m)');
            $stmtMsg->execute([
                ':e' => 'probe@example.test',
                ':m' => '<script>alert("stored-msg-xss")</script>',
            ]);

            $stmtArt = $pdo->prepare('INSERT INTO articles (title, content, banner) VALUES (:t, :c, :b)');
            $stmtArt->execute([
                ':t' => 'Titre <script>alert(1)</script>',
                ':c' => 'Contenu <img src=x onerror=alert(1)>',
                ':b' => 'javascript:alert(1)',
            ]);

            $stmtPhoto = $pdo->prepare('INSERT INTO photos (title, image) VALUES (:t, :i)');
            $stmtPhoto->execute([
                ':t' => 'Photo "><script>alert(2)</script>',
                ':i' => '/assets/img/photos/2.jpg',
            ]);

            $stmtPartner = $pdo->prepare('INSERT INTO partenaires (image, website) VALUES (:i, :w)');
            $stmtPartner->execute([
                ':i' => '/assets/img/webcup.jpg',
                ':w' => 'javascript:alert(3)',
            ]);

            $env = [
                'GAMESCUPS_DB_DSN' => 'sqlite:'.$dbFile,
                'GAMESCUPS_ADMIN_TOKEN' => 's3nt1n3l-strong-admin-secret-2026',
            ];

            // 1. Reflected XSS on admin action parameter
            $adminReflected = $this->runPhpRequest('admin/index.php', [
                'method' => 'GET',
                'get' => ['action' => '<script>alert("reflected")</script>'],
                'session' => ['gamescups_admin_authenticated' => true],
                'env' => $env,
            ]);
            $this->assertStringNotContains('<script>alert("reflected")</script>', $adminReflected['stdout']);

            // 2. Stored XSS in admin messages view
            $adminMsgs = $this->runPhpRequest('admin/index.php', [
                'method' => 'GET',
                'get' => ['action' => 'messages'],
                'session' => ['gamescups_admin_authenticated' => true],
                'env' => $env,
            ]);
            $this->assertStringNotContains('<script>alert("stored-msg-xss")</script>', $adminMsgs['stdout']);
            $this->assertStringContains('&lt;script&gt;alert(&quot;stored-msg-xss&quot;)&lt;/script&gt;', $adminMsgs['stdout']);

            // 3. Stored XSS & javascript: URI neutralization on public home and photos pages
            $home = $this->runPhpRequest('index.php', ['env' => $env]);
            $this->assertStringNotContains('<script>alert(1)</script>', $home['stdout']);
            $this->assertStringNotContains('<img src=x onerror=alert(1)>', $home['stdout']);
            $this->assertStringNotContains('javascript:alert', $home['stdout']);
            $this->assertStringContains('rel="noopener noreferrer"', $home['stdout']);

            $photos = $this->runPhpRequest('photos/index.php', ['env' => $env]);
            $this->assertStringNotContains('<script>alert(2)</script>', $photos['stdout']);
        } finally {
            unset($pdo);
            @unlink($dbFile);
        }
    }

    public function test_php84_zero_warnings_and_no_short_open_tags(): void
    {
        [$dbFile, $pdo] = $this->createTempDatabase();

        try {
            $env = [
                'GAMESCUPS_DB_DSN' => 'sqlite:'.$dbFile,
                'GAMESCUPS_ADMIN_TOKEN' => 's3nt1n3l-strong-admin-secret-2026',
            ];

            foreach (['index.php', 'photos/index.php', 'contact/index.php'] as $entry) {
                $res = $this->runPhpRequest($entry, ['env' => $env]);
                $combined = $res['stdout'].$res['stderr'];
                $this->assertStringNotContains('Warning:', $combined, "PHP Warning emitted on {$entry}");
                $this->assertStringNotContains('Deprecated:', $combined, "PHP Deprecated emitted on {$entry}");
                $this->assertStringNotContains('<? ', $res['stdout'], "Unparsed PHP short open tag on {$entry}");
            }

            $adminDash = $this->runPhpRequest('admin/index.php', [
                'method' => 'GET',
                'session' => ['gamescups_admin_authenticated' => true],
                'env' => $env,
            ]);
            $combinedAdmin = $adminDash['stdout'].$adminDash['stderr'];
            $this->assertStringNotContains('Warning:', $combinedAdmin, 'PHP Warning emitted on admin/index.php');
            $this->assertStringNotContains('Deprecated:', $combinedAdmin, 'PHP Deprecated emitted on admin/index.php');
            $this->assertStringNotContains('<? require', $adminDash['stdout'], 'Short open tag in admin footer must be fixed');
            $this->assertStringContains('LAURET - ARMOUGOM', $adminDash['stdout'], 'Footer must render on admin dashboard');
        } finally {
            unset($pdo);
            @unlink($dbFile);
        }
    }

    public function test_a07_admin_mutations_validate_urls_for_photos_sliders_and_partners(): void
    {
        [$dbFile, $pdo] = $this->createTempDatabase();

        try {
            $env = [
                'GAMESCUPS_DB_DSN' => 'sqlite:'.$dbFile,
                'GAMESCUPS_ADMIN_TOKEN' => 's3nt1n3l-strong-admin-secret-2026',
            ];
            $authSession = [
                'gamescups_admin_authenticated' => true,
                'csrf_token' => 'crud-csrf-sentinel-token-0003',
            ];

            // Reject javascript: website on partenaires
            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'partenaires'],
                'post' => [
                    'csrf_token' => 'crud-csrf-sentinel-token-0003',
                    'website' => 'javascript:alert(1)',
                    'image' => '/assets/img/webcup.jpg',
                ],
                'session' => $authSession,
                'env' => $env,
            ]);
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM partenaires')->fetchColumn());

            // Accept valid https:// website and local asset path on partenaires, photos, sliders
            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'partenaires'],
                'post' => [
                    'csrf_token' => 'crud-csrf-sentinel-token-0003',
                    'website' => 'https://www.ac-reunion.fr',
                    'image' => '/assets/img/ac-reunion.jpg',
                ],
                'session' => $authSession,
                'env' => $env,
            ]);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM partenaires')->fetchColumn());

            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'photos'],
                'post' => [
                    'csrf_token' => 'crud-csrf-sentinel-token-0003',
                    'title' => 'Finale Tournoi',
                    'image' => '/assets/img/photos/2.jpg',
                ],
                'session' => $authSession,
                'env' => $env,
            ]);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM photos')->fetchColumn());

            $this->runPhpRequest('admin/index.php', [
                'method' => 'POST',
                'get' => ['action' => 'sliders'],
                'post' => [
                    'csrf_token' => 'crud-csrf-sentinel-token-0003',
                    'slider' => '/assets/img/slides/1.png',
                ],
                'session' => $authSession,
                'env' => $env,
            ]);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM sliders')->fetchColumn());
        } finally {
            unset($pdo);
            @unlink($dbFile);
        }
    }
}

$runner = new SecurityAuditRunner(dirname(__DIR__));
$methods = array_values(array_filter(
    get_class_methods($runner),
    static fn (string $m): bool => str_starts_with($m, 'test_')
));

$passed = 0;
$failures = [];
foreach ($methods as $method) {
    try {
        $runner->{$method}();
        $passed++;
        echo "[PASS] {$method}\n";
    } catch (Throwable $e) {
        $failures[] = "[FAIL] {$method}: {$e->getMessage()}";
        echo "[FAIL] {$method}: {$e->getMessage()}\n";
    }
}

printf("\nSummary: %d/%d tests passed (%d assertions).\n", $passed, count($methods), $runner->assertions);
exit($failures === [] ? 0 : 1);
