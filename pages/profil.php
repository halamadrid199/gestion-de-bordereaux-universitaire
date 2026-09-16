<?php
// pages/profil.php
require_once '../includes/auth.php';
require_once '../config/database.php';

$etudiant_id = $_SESSION['etudiant_id'];

try {
    // Récupération de toutes les données de l'étudiant depuis la BDD y compris filiere et faculte
    $stmt = $db->prepare("SELECT matricule, nom, post_nom, prenom, email, promotion, filiere, faculte, annee_academique, date_inscription FROM etudiant WHERE id = ?");
    $stmt->execute([$etudiant_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        // Sécurité résiduelle si le compte a été supprimé entre-temps
        header("Location: deconnexion.php");
        exit();
    }
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement des informations de votre profil.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPortail - Mon Profil</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
    
    <style>
        body { background-color: #f4f6f9; }
        .avatar-circle {
            width: 80px;
            height: 80px;
            background-color: #0d6efd;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
        }
        .profile-card { border: none; border-radius: 12px; }
        .info-label { font-size: 0.85rem; font-weight: 600; color: #6c757d; }
        .info-value { font-size: 1rem; font-weight: 500; color: #212529; }
    </style>
    <link rel="stylesheet" href="../assets/css/styleconn.css">
    <link rel="stylesheet" href="../assets/css/styledash.css">
</head>
<body>

<!-- Inclusion de la Navbar commune -->
<?php require_once '../includes/navbar.php'; ?>

<div class="container mb-5" style="max-width: 500px;">
    
    <!-- <div class="mb-4 text-center text-sm-start">
        <h3 class="fw-bold text-dark mb-1">Mon Compte Étudiant</h3>
        <p class="text-muted small">Consultez vos détails académiques et personnels enregistrés dans le système.</p>
    </div> -->

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger shadow-sm"><?= $erreur ?></div>
    <?php endif; ?>

    <div class="card profile-card shadow-sm p-4 bg-white">
        <!-- Badge Avatar avec les initiales de l'étudiant -->
        <div class="text-center border-bottom pb-4 mb-4">
            <div class="avatar-circle shadow-sm">
                <?= strtoupper(substr($info['prenom'], 0, 1) . substr($info['nom'], 0, 1)) ?>
            </div>
            <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($info['prenom'] . ' ' . $info['nom'] . ' ' . $info['post_nom']) ?></h4>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold">
                N° Matricule : <?= htmlspecialchars($info['matricule']) ?>
            </span>
        </div>

        <!-- Grille complète des détails du compte -->
        <div class="row g-4">
            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Faculté</div>
                <div class="info-value mt-1"><?= htmlspecialchars($info['faculte']) ?></div>
            </div>

            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Filière</div>
                <div class="info-value mt-1"><?= htmlspecialchars($info['filiere']) ?></div>
            </div>

            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Promotion</div>
                <div class="info-value mt-1"><?= htmlspecialchars($info['promotion']) ?></div>
            </div>
            
            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Année Académique</div>
                <div class="info-value mt-1"><?= htmlspecialchars($info['annee_academique']) ?></div>
            </div>

            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Adresse Email</div>
                <div class="info-value mt-1"><?= htmlspecialchars($info['email']) ?></div>
            </div>

            <div class="col-sm-6">
                <div class="info-label text-uppercase tracking-wider">Membre depuis le</div>
                <div class="info-value mt-1"><?= date('d F Y', strtotime($info['date_inscription'])) ?></div>
            </div>
        </div>

        <!-- Note de sécurité -->
        <div class="alert alert-light border mt-4 mb-0 d-flex align-items-center gap-2 small text-secondary">
            <span>🔒 Modifications verrouillées. Pour tout changement de promotion, filière ou d'identité, veuillez contacter le secrétariat de votre faculté muni de votre carte d'étudiant.</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>