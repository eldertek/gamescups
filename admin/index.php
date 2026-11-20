<?php

declare(strict_types=1);

require_once __DIR__.'/../config/config.inc.php';

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$rawAction = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';
$allowedActions = ['articles', 'photos', 'sliders', 'partenaires', 'messages', 'login', 'logout'];
$action = in_array($rawAction, $allowedActions, true) ? $rawAction : '';
$flashMessage = '';
$flashType = 'success';

if ($requestMethod === 'POST' && $action === 'login') {
    $submittedCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $submittedToken = isset($_POST['admin_token']) && is_string($_POST['admin_token']) ? trim($_POST['admin_token']) : null;

    if (gamescups_verify_csrf($submittedCsrf) && gamescups_try_admin_login($submittedToken)) {
        if (! headers_sent()) {
            header('Location: /admin/');
        }
        exit;
    }

    $flashMessage = 'Identifiants invalides ou session expiree.';
    $flashType = 'danger';
}

if ($requestMethod === 'POST' && $action === 'logout' && gamescups_is_admin_authenticated()) {
    $submittedCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if (gamescups_verify_csrf($submittedCsrf)) {
        gamescups_admin_logout();
        if (! headers_sent()) {
            header('Location: /');
        }
        exit;
    }
}

if (! gamescups_is_admin_authenticated()) {
    if (! headers_sent()) {
        http_response_code(403);
    }
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GamesCups - Authentification Administration</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php require __DIR__.'/../components/header.php'; ?>
<main class="container py-5" style="max-width: 480px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Acces Administration</h1>
            <?php if ($flashMessage !== '') : ?>
                <div class="alert alert-<?= gamescups_e($flashType) ?>" role="alert">
                    <?= gamescups_e($flashMessage) ?>
                </div>
            <?php endif; ?>
            <form action="/admin/?action=login" method="post" autocomplete="off">
                <?= gamescups_csrf_field() ?>
                <div class="mb-3">
                    <label for="admin_token" class="form-label">Cle d'administration :</label>
                    <input type="password" class="form-control" id="admin_token" name="admin_token" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">Se connecter</button>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__.'/../components/footer.php'; ?>
</body>
</html>
    <?php
    exit;
}

if ($connect instanceof PDO && $requestMethod === 'POST') {
    $submittedCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (! gamescups_verify_csrf($submittedCsrf)) {
        if (! headers_sent()) {
            http_response_code(403);
        }
        $flashMessage = 'Jeton CSRF invalide. Veuillez reessayer.';
        $flashType = 'danger';
    } else {
        try {
            switch ($action) {
                case 'articles':
                    $title = trim((string) ($_POST['title'] ?? ''));
                    $content = trim((string) ($_POST['content'] ?? ''));
                    $banner = gamescups_safe_url(isset($_POST['banner']) && is_string($_POST['banner']) ? $_POST['banner'] : null, true);

                    if ($title !== '' && $content !== '' && $banner !== '') {
                        $stmt = $connect->prepare('INSERT INTO articles (title, content, banner) VALUES (:title, :content, :banner)');
                        $stmt->execute([
                            ':title' => mb_substr($title, 0, 255),
                            ':content' => mb_substr($content, 0, 10000),
                            ':banner' => $banner,
                        ]);
                        $flashMessage = 'Article bien enregistre.';
                    } else {
                        $flashMessage = 'Champs ou lien d\'image invalides.';
                        $flashType = 'danger';
                    }

                    break;

                case 'photos':
                    $title = trim((string) ($_POST['title'] ?? ''));
                    $image = gamescups_safe_url(isset($_POST['image']) && is_string($_POST['image']) ? $_POST['image'] : null, true);

                    if ($title !== '' && $image !== '') {
                        $stmt = $connect->prepare('INSERT INTO photos (title, image) VALUES (:title, :image)');
                        $stmt->execute([
                            ':title' => mb_substr($title, 0, 255),
                            ':image' => $image,
                        ]);
                        $flashMessage = 'Photo bien enregistree.';
                    } else {
                        $flashMessage = 'Legende ou lien d\'image invalide.';
                        $flashType = 'danger';
                    }

                    break;

                case 'sliders':
                    $slider = gamescups_safe_url(isset($_POST['slider']) && is_string($_POST['slider']) ? $_POST['slider'] : null, true);

                    if ($slider !== '') {
                        $stmt = $connect->prepare('INSERT INTO sliders (slider) VALUES (:slider)');
                        $stmt->execute([':slider' => $slider]);
                        $flashMessage = 'Slider bien enregistre.';
                    } else {
                        $flashMessage = 'Lien d\'image du slider invalide.';
                        $flashType = 'danger';
                    }

                    break;

                case 'partenaires':
                    $image = gamescups_safe_url(isset($_POST['image']) && is_string($_POST['image']) ? $_POST['image'] : null, true);
                    $website = gamescups_safe_url(isset($_POST['website']) && is_string($_POST['website']) ? $_POST['website'] : null, false);

                    if ($image !== '' && $website !== '#') {
                        $stmt = $connect->prepare('INSERT INTO partenaires (image, website) VALUES (:image, :website)');
                        $stmt->execute([
                            ':image' => $image,
                            ':website' => $website,
                        ]);
                        $flashMessage = 'Partenaire ajoute.';
                    } else {
                        $flashMessage = 'Site partenaire ou lien d\'image invalide.';
                        $flashType = 'danger';
                    }

                    break;
            }
        } catch (PDOException $e) {
            error_log('[GamesCups] Admin mutation error: '.$e->getMessage());
            $flashMessage = 'Une erreur interne est survenue lors de l\'enregistrement.';
            $flashType = 'danger';
        }
    }
}

$sectionLabels = [
    'articles' => 'articles',
    'photos' => 'photos',
    'sliders' => 'sliders',
    'partenaires' => 'partenaires',
    'messages' => 'messages',
];
$headingText = isset($sectionLabels[$action])
    ? 'Tableau de bord des '.$sectionLabels[$action]
    : 'Tableau de bord';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GamesCups - Administration</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<!-- HEADER -->
<?php require __DIR__.'/../components/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3 sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $action === '' ? 'active' : '' ?>" aria-current="page" href="/admin/">
                            <span class="align-text-bottom"></span>
                            Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'articles' ? 'active' : '' ?>" href="?action=articles">
                            <span class="align-text-bottom"></span>
                            Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'photos' ? 'active' : '' ?>" href="?action=photos">
                            <span class="align-text-bottom"></span>
                            Photos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'sliders' ? 'active' : '' ?>" href="?action=sliders">
                            <span class="align-text-bottom"></span>
                            Sliders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'partenaires' ? 'active' : '' ?>" href="?action=partenaires">
                            <span class="align-text-bottom"></span>
                            Partenaires
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $action === 'messages' ? 'active' : '' ?>" href="?action=messages">
                            <span class="align-text-bottom"></span>
                            Messages
                        </a>
                    </li>
                    <li class="nav-item mt-3 px-3">
                        <form action="/admin/?action=logout" method="post">
                            <?= gamescups_csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Deconnexion</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= gamescups_e($headingText) ?></h1>
            </div>

            <?php if ($flashMessage !== '') : ?>
                <div class="alert alert-<?= gamescups_e($flashType) ?>" role="alert">
                    <?= gamescups_e($flashMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($connect instanceof PDO) : ?>
                <?php switch ($action) :
                    case 'articles': ?>
                        <h2 class="h4 mt-3">Ajouter un article</h2>
                        <form action="/admin/?action=articles" method="post">
                            <?= gamescups_csrf_field() ?>
                            <div class="mb-3">
                                <label for="title" class="mt-2 form-label">Titre de l'article :</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="Mon super article !" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="mt-0 form-label">Contenu :</label>
                                <textarea class="form-control" id="content" rows="3" name="content" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="banner" class="mt-0 form-label">Lien de l'image mise en avant :</label>
                                <input type="text" class="form-control" id="banner" name="banner" placeholder="/assets/img/photos/3.jpg" required>
                            </div>
                            <div class="mb-3">
                                <button class="form-control" type="submit">Envoyer !</button>
                            </div>
                        </form>
                        <?php break; ?>

                    <?php case 'photos': ?>
                        <h2 class="h4 mt-3">Ajouter une photo a la galerie</h2>
                        <form action="/admin/?action=photos" method="post">
                            <?= gamescups_csrf_field() ?>
                            <div class="mb-3">
                                <label for="title" class="mt-2 form-label">Legende de l'image :</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="Finale regionale" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="mt-2 form-label">Lien de l'image :</label>
                                <input type="text" class="form-control" id="image" name="image" placeholder="/assets/img/photos/3.jpg" required>
                            </div>
                            <div class="mb-3">
                                <button class="form-control" type="submit">Envoyer !</button>
                            </div>
                        </form>
                        <?php break; ?>

                    <?php case 'sliders': ?>
                        <h2 class="h4 mt-3">Ajouter une photo au slider</h2>
                        <form action="/admin/?action=sliders" method="post">
                            <?= gamescups_csrf_field() ?>
                            <div class="mb-3">
                                <label for="slider" class="mt-2 form-label">Lien de l'image :</label>
                                <input type="text" class="form-control" id="slider" name="slider" placeholder="/assets/img/slides/1.png" required>
                            </div>
                            <div class="mb-3">
                                <button class="form-control" type="submit">Envoyer !</button>
                            </div>
                        </form>
                        <?php break; ?>

                    <?php case 'partenaires': ?>
                        <h2 class="h4 mt-3">Ajouter un partenaire</h2>
                        <form action="/admin/?action=partenaires" method="post">
                            <?= gamescups_csrf_field() ?>
                            <div class="mb-3">
                                <label for="website" class="mt-2 form-label">Lien vers le site internet du partenaire :</label>
                                <input type="url" class="form-control" id="website" name="website" placeholder="https://www.ac-reunion.fr" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="mt-2 form-label">Lien de l'image :</label>
                                <input type="text" class="form-control" id="image" name="image" placeholder="/assets/img/ac-reunion.jpg" required>
                            </div>
                            <div class="mb-3">
                                <button class="form-control" type="submit">Envoyer !</button>
                            </div>
                        </form>
                        <?php break; ?>

                    <?php case 'messages': ?>
                        <h2 class="h4 mt-3">Voici vos derniers messages</h2>
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col">Auteur</th>
                                <th scope="col">Message</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($connect->query('SELECT email, message FROM messages ORDER BY id DESC') as $row) : ?>
                                <tr>
                                    <td><?= gamescups_e((string) ($row['email'] ?? '')) ?></td>
                                    <td><?= nl2br(gamescups_e((string) ($row['message'] ?? ''))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php break; ?>

                    <?php default:
                        $qTotal = $connect->query('SELECT COUNT(*) AS counted FROM stats_visites');
                        $totalRow = $qTotal ? $qTotal->fetch(PDO::FETCH_OBJ) : null;
                        $nbTotal = $totalRow !== false && $totalRow !== null ? (int) $totalRow->counted : 0;

                        $qToday = $connect->prepare('SELECT COUNT(*) AS counted FROM stats_visites WHERE date_visite = :current_date');
                        $qToday->execute([':current_date' => date('Y-m-d')]);
                        $todayRow = $qToday->fetch(PDO::FETCH_OBJ);
                        $nbToday = $todayRow !== false && $todayRow !== null ? (int) $todayRow->counted : 0;
                        ?>
                        <p class="fs-4">Nombre de visiteurs depuis le debut : <?= $nbTotal ?></p>
                        <p class="fs-4">Nombre de visiteurs aujourd'hui (<?= gamescups_e(date('d-m-Y')) ?>) : <?= $nbToday ?></p>
                <?php endswitch; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<!-- FOOTER -->
<?php require __DIR__.'/../components/footer.php'; ?>
</body>
</html>
