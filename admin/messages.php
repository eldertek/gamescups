<?php

include "../config/config.inc.php";

if (isset($_POST['email'])) {
    if (isset($_POST['message'])) {
        if (isset($connect)) {
            try {
                $preparation = $connect->prepare("INSERT INTO messages (id, email, message) VALUES (NULL, :email, :message)");
                $preparation->bindParam(":email", $_POST['email']);
                $preparation->bindParam(":message", $_POST['message']);
                $preparation->execute();
                echo "<script type='text/javascript'>alert('Message bien envoyé ! A bientôt !'); window.location.replace('/')</script>";
            } catch (PDOException $e) {
                print($e->getMessage());
            }
        } else {
            header("Location: /");
        }
    } else {
        header("Location: /");
    }
} else {
    header("Location: /");
}
