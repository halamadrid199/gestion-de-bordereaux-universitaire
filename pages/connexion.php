<?php
// pages/connexion.php
session_start();
// session_destroy();
require_once '../config/database.php';
require_once '../config/security.php';

$message = "";
$type_message = "";

// 1. Vérification immédiate du statut de l'adresse IP
if (estIpBloquee($db)) {
    die("Votre adresse IP est temporairement bloquée pour des raisons de sécurité suite à de trop nombreuses tentatives infructueuses. Réessayez dans une heure.");
}

// Rediriger l'étudiant s'il est déjà connecté
if (isset($_SESSION['etudiant_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. On vérifie le token CSRF UNIQUEMENT lors de la soumission du formulaire
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Action non autorisée (Erreur de sécurité).");
    }

    // Nettoyage de l'email
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'adresse email invalide.";
        $type_message = "danger";
    } else {
        try {
            // Recherche de l'étudiant dans la base de données
            $stmt = $db->prepare("SELECT * FROM etudiant WHERE email = ?");
            $stmt->execute([$email]);
            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification de l'existence et du mot de passe haché
            if ($etudiant && password_verify($mot_de_passe, $etudiant['mot_de_passe'])) {
                
                // Connexion réussie : On nettoie l'historique des échecs de cette IP
                $ip = getClientIp();
                $stmtReset = $db->prepare("DELETE FROM ip_bloquee WHERE adresse_ip = ?");
                $stmtReset->execute([$ip]);

                // Initialisation des variables de session globales
                $_SESSION['etudiant_id'] = $etudiant['id'];
                $_SESSION['etudiant_matricule'] = $etudiant['matricule'];
                $_SESSION['etudiant_nom'] = $etudiant['nom'];
                $_SESSION['etudiant_post_nom'] = $etudiant['post_nom'];
                $_SESSION['etudiant_prenom'] = $etudiant['prenom'];
                $_SESSION['etudiant_promotion'] = $etudiant['promotion'];
                $_SESSION['etudiant_annee'] = $etudiant['annee_academique'];

                // Redirection immédiate vers le tableau de bord
                header("Location: dashboard.php");
                exit();
                
            } else {
                // Échec : on enregistre l'incident dans la table ip_bloquee
                enregistrerEchec($db);
                
                // Récupération du nombre actuel de tentatives pour calculer le reste
                $ip = getClientIp();
                $stmtTentatives = $db->prepare("SELECT tentatives FROM ip_bloquee WHERE adresse_ip = ?");
                $stmtTentatives->execute([$ip]);
                $tentatives = $stmtTentatives->fetchColumn();
                
                $essais_restants = 5 - ($tentatives ? $tentatives : 0);

                if ($essais_restants <= 0) {
                    $message = "Sécurité : Votre adresse IP a été bloquée pour 1 heure suite à 5 échecs consécutifs.";
                } else {
                    $message = "Identifiants incorrects. Attention, il vous reste <strong>$essais_restants</strong> tentative(s) avant le blocage de votre accès.";
                }
                $type_message = "danger";
            }
        } catch (PDOException $e) {
            $message = "Une erreur système est survenue. Veuillez contacter un administrateur.";
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
    <title>Université - Connexion Portail Bordereaux</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-connexion {
            width: 100%;
            max-width: 420px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            border-radius: 12px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/styleconn.css">
    <link rel="stylesheet" href="../assets/css/styledash.css">


</head>
<body>
<div class="container">
    <div class="card card-connexion p-5 bg-white">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark mt-2">Portail Numérique</h3>
            <p class="text-muted small">Enregistrement et Validation des Bordereaux</p>
        </div>

        <!-- Affichage dynamique des alertes de sécurité ou d'erreur -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $type_message ?> alert-dismissible fade show small" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
            
                <!-- On glisse le token invisiblement ici -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-secondary">Adresse Email Étudiant</label>
                    <input type="email" name="email" id="email" class="form-control form-control-lg fs-6" placeholder="nom.prenom@gmail.com" required autocomplete="email">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="mot_de_passe" class="form-label small fw-semibold text-secondary">Mot de passe</label>
                    </div>
                    <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control form-control-lg fs-6" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold mt-2">Se connecter</button>
            
        </form>

        <div class="text-center mt-4 pt-2 border-top">
            <p class="mb-0 text-muted small">Nouvel étudiant ? <a href="inscription.php" class="text-primary text-decoration-none fw-bold">Créer un compte</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>