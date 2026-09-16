<?php
// admin/login.php
session_start();
require_once '../config/database.php';
if (!isset($db) && isset($pdo)) { $db = $pdo; }

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $db->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['mot_de_passe'])) {
            // Enregistrement des variables de session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nom'] = $admin['nom'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_departement'] = $admin['departement'];
            $_SESSION['admin_faculte'] = $admin['faculte'];

            // Redirection selon le rôle
            if ($admin['role'] === 'Super-Admin') {
                header('Location: super_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration - UniPortail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styledash.css">
    <link rel="stylesheet" href="../assets/css/styleconn.css">

</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-sm border-0 p-4 bg-white" style="width: 100%; max-width: 400px;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark">UniPortail</h3>
        <p class="text-muted small">Espace d'Administration Sécurisé</p>
    </div>

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger py-2 small"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label fw-bold small">Adresse Email</label>
            <input type="email5" name="email" class="form-control" required placeholder="admin@uniportail.ac">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold small">Mot de passe</label>
            <input type="password" name="password" class="form-control form-control-lg fs-8" required placeholder="***********">
        </div>
        <button type="submit" class="btn btn-dark w-100 fw-bold py-2">Se connecter</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>