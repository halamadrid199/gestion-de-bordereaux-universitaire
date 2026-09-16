<?php
// config/database.php

$host = 'localhost';
$db_name = 'gestion_bordereaux';
$username = 'user';
$password = 'userpass'; 

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Activation des erreurs PDO sous forme d'exceptions pour la sécurité
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Désactivation de l'émulation des requêtes préparées
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Message générique pour l'utilisateur, log réel caché
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}
?>