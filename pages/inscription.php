<?php
session_start();

// pages/inscription.php
require_once '../config/database.php';
require_once '../config/security.php';

$message = "";
$type_message = "";

// Redirection ou blocage si l'IP a déjà tenté de brute-forcer une page
if (estIpBloquee($db)) {
    die("Votre adresse IP est temporairement bloquée pour des raisons de sécurité suite à de trop nombreuses tentatives infructueuses. Réessayez dans une heure.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. On vérifie le token CSRF UNIQUEMENT lors de la soumission du formulaire
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Action non autorisée (Erreur de sécurité CSRF).");
    }

    // Nettoyage strict des entrées pour éviter les failles XSS
    $matricule = htmlspecialchars(trim($_POST['matricule']));
    $nom = htmlspecialchars(trim($_POST['nom']));
    $post_nom = htmlspecialchars(trim($_POST['post_nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];
    $promotion = htmlspecialchars(trim($_POST['promotion']));
    $departement = htmlspecialchars(trim($_POST['departement']));
    $filiere = htmlspecialchars(trim($_POST['filiere']));
    $faculte = htmlspecialchars(trim($_POST['faculte']));
    $annee_academique = htmlspecialchars(trim($_POST['annee_academique']));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'adresse email invalide.";
        $type_message = "danger";
    } else {
        try {
            // Vérifier si le matricule ou l'email existe déjà
            $check = $db->prepare("SELECT id FROM etudiant WHERE matricule = ? OR email = ?");
            $check->execute([$matricule, $email]);
            
            if ($check->rowCount() > 0) {
                $message = "Ce matricule ou cet email est déjà enregistré.";
                $type_message = "warning";
            } else {
                // Hachage sécurisé du mot de passe
                $mdp_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                // Insertion sécurisée incluant le département
                $sql = "INSERT INTO etudiant (matricule, nom, post_nom, prenom, email, mot_de_passe, promotion, departement, filiere, faculte, annee_academique) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$matricule, $nom, $post_nom, $prenom, $email, $mdp_hache, $promotion, $departement, $filiere, $faculte, $annee_academique]);

                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                $type_message = "success";
            }
        } catch (PDOException $e) {
            $message = "Une erreur système est survenue. Veuillez réessayer : " . $e->getMessage();
            $type_message = "danger";
        }
    }
}

// 2. On génère le token pour le formulaire (s'il n'existe pas encore)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Université - Inscription Étudiant</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-inscription { max-width: 650px; margin: 40px auto; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
    <link rel="stylesheet" href="../assets/css/styledash.css">
</head>
<body>

<div class="container">
    <div class="card card-inscription p-4">
        <h2 class="text-center mb-4 text-primary fw-bold">Créer un compte Étudiant</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $type_message ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="inscription.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Matricule</label>
                    <input type="text" name="matricule" class="form-control" placeholder="Ex: 2501000121" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Post-nom</label>
                    <input type="text" name="post_nom" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Adresse Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" minlength="6" required>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Promotion</label>
                    <select name="promotion" class="form-select" required>
                        <option value="L1">Licence 1</option>
                        <option value="L2" selected>Licence 2</option>
                        <option value="L3">Licence 3</option>
                        <option value="M1">Master 1</option>
                        <option value="M2">Master 2</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Département</label>
                    <input type="text" name="departement" class="form-control" placeholder="Ex: genie informatique" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Filière</label>
                    <input type="text" name="filiere" class="form-control" placeholder="Ex: Génie Logiciel" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Faculté</label>
                    <input type="text" name="faculte" class="form-control" placeholder="Ex: sciences et tech" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Année Académique</label>
                    <input type="text" name="annee_academique" class="form-control" placeholder="Ex: 2025-2026" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-3 fw-bold">S'inscrire</button>
        </form>
        
        <div class="text-center mt-3">
            <p class="mb-0">Déjà inscrit ? <a href="connexion.php" class="text-decoration-none fw-bold">Connectez-vous ici</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>