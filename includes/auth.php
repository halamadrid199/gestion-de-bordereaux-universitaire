<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Si l'identifiant de l'étudiant n'est pas en session, on bloque tout
if (!isset($_SESSION['etudiant_id'])) {
    header("Location: connexion.php");
    exit();
}
?>