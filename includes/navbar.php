<?php
// includes/navbar.php

// 1. Traitement immédiat du changement d'année dès qu'un choix est fait
if (isset($_GET['annee']) && !empty($_GET['annee'])) {
    $_SESSION['annee_filtre'] = $_GET['annee'];
    
    // Redirection propre pour nettoyer l'URL et recharger la page instantanément
    $page_courante = basename($_SERVER['PHP_SELF']);
    header("Location: " . $page_courante);
    exit();
}

// Récupération de l'année sélectionnée (par défaut l'année en cours)
$annee_selectionnee = $_SESSION['annee_filtre'] ?? '2026';
$page_actuelle = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <span class="text-white">Uni</span>Portail
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $page_actuelle == 'dashboard.php' ? 'active fw-bold text-white' : '' ?>" href="dashboard.php">Tableau de bord</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page_actuelle == 'enregistrement.php' ? 'active fw-bold text-white' : '' ?>" href="enregistrement.php">Scanner un QR Code</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page_actuelle == 'historique.php' ? 'active fw-bold text-white' : '' ?>" href="historique.php">Historique</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page_actuelle == 'notifications.php' ? 'active fw-bold text-white' : '' ?>" href="notifications.php">Notifications</a>
                </li>
            </ul>

            <!-- Formulaire classique avec rechargement PHP instantané -->
            <form method="GET" action="" class="d-flex align-items-center me-3">
                <select name="annee" class="form-select form-select-sm bg-secondary text-white border-0 fw-bold" onchange="this.form.submit()">
                    <?php 
                    $annee_actuelle_sys = 2026; 
                    for ($a = $annee_actuelle_sys; $a >= $annee_actuelle_sys - 3; $a--): 
                    ?>
                        <option value="<?= $a ?>" <?= $annee_selectionnee == $a ? 'selected' : '' ?>>
                            Année <?= $a ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>

            <div class="d-flex align-items-center">
                <a class="btn btn-outline-light btn-sm me-2 <?= $page_actuelle == 'profil.php' ? 'active' : '' ?>" href="profil.php"  >
                     <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">account_circle</span>
                     <?= htmlspecialchars($_SESSION['etudiant_prenom'] . ' ' . $_SESSION['etudiant_nom']) ?>
                </a>
                <a href="deconnexion.php" class="btn btn-danger btn-sm fw-bold">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>