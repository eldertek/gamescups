<?php require_once __DIR__.'/config/config.inc.php'; ?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GamesCups - Accueil</title>
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/bootstrap.css" rel="stylesheet">
  </head>
  <body>
    <!-- HEADER -->
    <?php require __DIR__.'/components/header.php'; ?>
    <!-- CARROUSEL -->
    <?php require __DIR__.'/components/carrousel.php'; ?>
    <!-- ACTUALITES -->
    <?php require __DIR__.'/components/news.php'; ?>
    <!-- CONTACTS & PARTENAIRES -->
    <?php require __DIR__.'/components/contact.php'; ?>
    <!-- FOOTER -->
    <?php require __DIR__.'/components/footer.php'; ?>
  </body>
</html>
