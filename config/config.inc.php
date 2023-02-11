<?php

# Connexion à la base de donnée / original
$dbhost = "127.0.0.1";
$dbport = "3306";
$dbname = "groupe1";
$dbuser = "groupe1";
$dbpass = "Gr1Motdep@sse";

try {
    $connect = new PDO("mysql:host=localhost;dbname=groupe1", $dbuser, $dbpass);
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d');
    $query = $connect->prepare("INSERT INTO stats_visites (ip , date_visite , pages_vues) VALUES (:ip , :date , 1) ON DUPLICATE KEY UPDATE pages_vues = pages_vues + 1");
    $query->execute(array(
        ':ip' => $ip,
        ':date' => $date
    ));
} catch (PDOException $e) {
    print "Erreur de traitement: " . $e->getMessage();
}


