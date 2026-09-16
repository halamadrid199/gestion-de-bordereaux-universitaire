<?php
// admin/super_dashboard.php
session_start();
require_once '../config/database.php';
if (!isset($db) && isset($pdo)) { $db = $pdo; }

// Sécurité : Vérifier si l'utilisateur est connecté et est un Super-Admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Super-Admin') {
    header('Location: login.php');
    exit();
}

$success_msg = "";
$error_msg = "";

// Traitement de l'ajout d'un nouvel administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_admin'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $departement = trim($_POST['departement']);
    $faculte = trim($_POST['faculte']);
    $role = 'Administrateur'; // Rôle par défaut pour les nouveaux

    if (!empty($nom) && !empty($email) && !empty($_POST['password']) && !empty($departement)) {
        try {
            $stmtCheck = $db->prepare("SELECT id FROM admin WHERE email = ?");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->rowCount() > 0) {
                $error_msg = "Cette adresse email est déjà utilisée par un autre administrateur.";
            } else {
                $stmtInsert = $db->prepare("INSERT INTO admin (nom, email, mot_de_passe, role, departement, faculte) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsert->execute([$nom, $email, $password, $role, $departement, $faculte]);
                $success_msg = "Nouvel administrateur créé avec succès pour le département de $departement !";
            }
        } catch (PDOException $e) {
            $error_msg = "Erreur lors de la création : " . $e->getMessage();
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération de la liste de tous les administrateurs
$stmtAdmins = $db->query("SELECT * FROM admin ORDER BY date_creation DESC");
$admins = $stmtAdmins->fetchAll();

// Statistiques globales de toute l'université pour le Super-Admin
$statsGlobales = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM bordereau) as total_bordereaux,
        (SELECT SUM(montant) FROM bordereau WHERE statut = 'Validé') as montant_global,
        (SELECT COUNT(*) FROM etudiant) as total_etudiants,
        (SELECT COUNT(*) FROM admin) as total_admins
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - UniPortail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/styledash.css">

</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">UniPortail | Super Administration</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3 small">Super-Admin : <strong><?php echo htmlspecialchars($_SESSION['admin_nom']); ?></strong></span>
            <a href="login.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container my-4">

    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">Tableau de Bord Global (Super-Admin)</h2>
            <p class="text-muted">Vue d'ensemble de l'université et gestion centralisée des comptes administrateurs.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cartes Statistiques Globales -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-primary border-4">
                <h6 class="text-muted small uppercase">Total Étudiants</h6>
                <h3 class="fw-bold text-primary mb-0"><?php echo $statsGlobales['total_etudiants']; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-success border-4">
                <h6 class="text-muted small uppercase">Montant Global Encaissé</h6>
                <h3 class="fw-bold text-success mb-0"><?php echo number_format($statsGlobales['montant_global'] ?? 0, 2); ?> USD</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-info border-4">
                <h6 class="text-muted small uppercase">Bordereaux Totaux</h6>
                <h3 class="fw-bold text-info mb-0"><?php echo $statsGlobales['total_bordereaux']; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 bg-white border-start border-warning border-4">
                <h6 class="text-muted small uppercase">Administrateurs Actifs</h6>
                <h3 class="fw-bold text-warning mb-0"><?php echo $statsGlobales['total_admins']; ?></h3>
            </div>
        </div>
    </div>

    <!-- Bouton d'action pour enrôler un nouvel admin -->
    <div class="mb-4 text-end">
        <button class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddAdmin">
            <span class="material-symbols-outlined align-middle fs-6">person_add</span> Créer un nouvel administrateur départemental
        </button>
    </div>

    <!-- Liste des administrateurs -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Comptes Administrateurs Enregistrés</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Département</th>
                            <th>Faculté</th>
                            <th>Date de création</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $adm): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($adm['nom']); ?></td>
                                <td><?php echo htmlspecialchars($adm['email']); ?></td>
                                <td>
                                    <?php if($adm['role'] === 'Super-Admin'): ?>
                                        <span class="badge bg-danger">Super-Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Administrateur</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($adm['departement']); ?></td>
                                <td><?php echo htmlspecialchars($adm['faculte']); ?></td>
                                <td><small><?php echo htmlspecialchars($adm['date_creation']); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création d'un admin -->
<div class="modal fade" id="modalAddAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="super_dashboard.php" class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Nouvel Administrateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nom complet</label>
                    <input type="text" name="nom" class="form-control" required placeholder="Ex: Jean Dupont">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Adresse Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin.genie@uniportail.ac">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mot de passe temporaire</label>
                    <input type="password" name="password" class="form-control" required placeholder="********">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Département / Filière assignée</label>
                    <input type="text" name="departement" class="form-control" required placeholder="Ex: genie informatique">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Faculté</label>
                    <input type="text" name="faculte" class="form-control" required placeholder="Ex: sciences et tech">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" name="ajouter_admin" class="btn btn-dark btn-sm fw-bold">Créer le compte</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>