<?php
require "../config/config.inc.php";

if (!(isset($_GET['token']))) {
    echo "<script type='text/javascript'>alert('Accès refusé, demandes de l\'aide à l\'administrateur !'); window.location.replace('/')</script>";
}

if (isset($connect)) {
    switch ($_GET['action']) {
        case 'articles':
            if (isset($_POST['title']) && isset($_POST['content']) && isset($_POST['banner'])) {
                try {
                    $preparation = $connect->prepare("INSERT INTO articles (id, title, content, banner) VALUES (NULL, :title, :content, :banner)");
                    $preparation->bindParam(":title", $_POST['title']);
                    $preparation->bindParam(":content", $_POST['content']);
                    $preparation->bindParam(":banner", $_POST['banner']);
                    $preparation->execute();
                    echo "<script type='text/javascript'>alert('Article bien envoyé ! A bientôt !'); window.location.replace('/admin/?token=ok')</script>";
                } catch (PDOException $e) {
                    print($e->getMessage());
                }
            }
            break;
        case 'photos':
            if (isset($_POST['title']) && isset($_POST['image'])) {
                try {
                    $preparation = $connect->prepare("INSERT INTO photos (id, title, image) VALUES (NULL, :title, :image)");
                    $preparation->bindParam(":title", $_POST['title']);
                    $preparation->bindParam(":image", $_POST['image']);
                    $preparation->execute();
                    echo "<script type='text/javascript'>alert('Photo bien envoyé ! A bientôt !'); window.location.replace('/admin/?token=ok')</script>";
                } catch (PDOException $e) {
                    print($e->getMessage());
                }
            }
            break;
        case 'sliders':
            if (isset($_POST['slider'])) {
                try {
                    $preparation = $connect->prepare("INSERT INTO sliders (id, slider) VALUES (NULL, :slider)");
                    $preparation->bindParam(":slider", $_POST['slider']);
                    $preparation->execute();
                    echo "<script type='text/javascript'>alert('Slider bien envoyé ! A bientôt !'); window.location.replace('/admin/?token=ok')</script>";
                } catch (PDOException $e) {
                    print($e->getMessage());
                }
            }
            break;
        case 'partenaires':
            if (isset($_POST['image']) && isset($_POST['website'])) {
                try {
                    $preparation = $connect->prepare("INSERT INTO partenaires (id, image, website) VALUES (NULL, :image, :website)");
                    $preparation->bindParam(":image", $_POST['image']);
                    $preparation->bindParam(":website", $_POST['website']);
                    $preparation->execute();
                    echo "<script type='text/javascript'>alert('Partenaire ajouté ! A bientôt !'); window.location.replace('/admin/?token=ok')</script>";
                } catch (PDOException $e) {
                    print($e->getMessage());
                }
            }
            break;
    }
}
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
<?php require '../components/header.php' ?>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3 sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php if (!(isset($_GET['action']))) {
                            echo "active";
                        } ?>" aria-current="page" href="?token=ok">
                            <span class="align-text-bottom"></span>
                            Tableau de bord
                        </a>
                    </li>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php if ($_GET['action'] == "articles") {
                                echo "active";
                            } ?>" aria-current="page" href="?token=ok&action=articles">
                                <span class="align-text-bottom"></span>
                                Articles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($_GET['action'] == "photos") {
                                echo "active";
                            } ?>" href="?token=ok&action=photos">
                                <span class="align-text-bottom"></span>
                                Photos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($_GET['action'] == "sliders") {
                                echo "active";
                            } ?>" href="?token=ok&action=sliders">
                                <span class="align-text-bottom"></span>
                                Sliders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($_GET['action'] == "partenaires") {
                                echo "active";
                            } ?>" href="?token=ok&action=partenaires">
                                <span class="align-text-bottom"></span>
                                Partenaires
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($_GET['action'] == "messages") {
                                echo "active";
                            } ?>" href="?token=ok&action=messages">
                                <span class="align-text-bottom"></span>
                                Messages
                            </a>
                        </li>
                    </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php if (isset($_GET['action'])) {
                        echo "Tableau de bord des " . $_GET['action'];
                    } else {
                        echo "Tableau de bord";
                    } ?></h1>
            </div>
            <?php
            if (isset($connect)) {
                switch ($_GET['action']) {
                    case 'articles':
                        echo '
                            <h1 class="mt-3">Ajouter un article</h1>
                            <form action="/admin/?token=ok&action=articles" method="post">
                                <div class="mb-3">
                                    <label for="title" class="mt-2 form-label">Titre de l\'article :</label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="Mon super article !">
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="mt-0 form-label">Contenu :</label>
                                    <textarea class="form-control" id="message" rows="3" name="content"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="mt-0 form-label">Lien de l\'image mis en avant :</label>
                                    <input type="text" class="form-control" id="banner" name="banner" placeholder="http://www.webawards_gr1.re/assets/img/photos/3.jpg">
                                </div>
                                <div class="mb-3">
                                    <button class="form-control" type="submit">Envoyer !</button>
                                </div>
                            </form>';
                        break;
                    case 'photos':
                        echo '
                            <h1 class="mt-3">Ajouter une photo à la gallerie</h1>
                            <form action="/admin/?token=ok&action=photos" method="post">
                                <div class="mb-3">
                                    <label for="title" class="mt-2 form-label">Légende de l\'image :</label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="Le petit chat de Manon">
                                </div>
                                <div class="mb-3">
                                    <label for="image" class="mt-2 form-label">Lien de l\'image :</label>
                                    <input type="text" class="form-control" id="image" name="image" placeholder="http://www.webawards_gr1.re/assets/img/photos/3.jpg">
                                </div>
                                <div class="mb-3">
                                    <button class="form-control" type="submit">Envoyer !</button>
                                </div>
                            </form>';
                        break;
                    case 'sliders':
                        echo '
                            <h1 class="mt-3">Ajouter une photo au slider</h1>
                            <form action="/admin/?token=ok&action=sliders" method="post">
                                <div class="mb-3">
                                    <label for="image" class="mt-2 form-label">Lien de l\'image :</label>
                                    <input type="text" class="form-control" id="slider" name="slider" placeholder="http://www.webawards_gr1.re/assets/img/photos/3.jpg">
                                </div>
                                <div class="mb-3">
                                    <button class="form-control" type="submit">Envoyer !</button>
                                </div>
                            </form>';
                        break;
                    case 'partenaires':
                        echo '
                            <h1 class="mt-3">Ajouter un partenaire</h1>
                            <form action="/admin/?token=ok&action=partenaires" method="post">
                                <div class="mb-3">
                                    <label for="website" class="mt-2 form-label">Lien vers le site internet du partenaire :</label>
                                    <input type="text" class="form-control" id="website" name="website" placeholder="https://www.ac-reunion.fr">
                                </div>
                                <div class="mb-3">
                                    <label for="image" class="mt-2 form-label">Lien de l\'image :</label>
                                    <input type="text" class="form-control" id="image" name="image" placeholder="http://www.webawards_gr1.re/assets/img/photos/3.jpg">
                                </div>
                                <div class="mb-3">
                                    <button class="form-control" type="submit">Envoyer !</button>
                                </div>
                            </form>';
                        break;
                    case 'messages':
                        echo '
                            <h1 class="mt-3">Voici vos derniers messages</h1>
                            <table class="table">
                              <thead>
                                <tr>
                                  <th scope="col">Auteur</th>
                                  <th scope="col">Message</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>';

                        foreach ($connect->query("SELECT * FROM messages") as $row) {
                            echo '
                                  <td>' . $row["email"] . '</td>
                                  <td>' . $row["message"] . '</td>
                                </tr>
                            ';
                        }
                        echo '</tbody>
                            </table>';
                        break;
                    default:
                        $q = $connect->query("SELECT COUNT(*) as counted FROM stats_visites");
                        $nb = $q->fetch(PDO::FETCH_OBJ);
                        $nb = $nb->counted;
                        echo '<p class="fs-4">Nombre de visiteurs depuis le début : ' . $nb . '</p>';
                        $q = $connect->prepare("SELECT COUNT(*) as counted FROM stats_visites WHERE date_visite=:current_date");
                        $current_date = date("Y-m-d");
                        $q->bindParam(":current_date", $current_date);
                        $q->execute();
                        $nb = $q->fetch(PDO::FETCH_OBJ);
                        $nb = $nb->counted;
                        echo '<p class="fs-4">Nombre de visiteurs aujourd\'hui ('.date("d-m-Y").'): ' . $nb . '</p>';
                }
            }
            ?>
        </main>
    </div>
</div>
<!-- FOOTER -->
<? require '../components/footer.php' ?>
</body>
</html>
