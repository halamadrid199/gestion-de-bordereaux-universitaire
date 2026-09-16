<?php
// admin/dashboard.php
session_start();
require_once '../config/database.php';

// Connexion PDO (gestion de la variable de connexion)
if (!isset($db) && isset($pdo)) { $db = $pdo; }

// Sécurité : Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_nom = $_SESSION['admin_nom'] ?? 'Administrateur';
$admin_departement = $_SESSION['admin_departement'] ?? 'math-info';
$admin_faculte = $_SESSION['admin_faculte'] ?? 'sciences et tech';

// Gestion de l'envoi d'une notification ciblée
$notif_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_notification'])) {
    $titre = trim($_POST['titre']);
    $message = trim($_POST['message']);
    
    if (!empty($titre) && !empty($message)) {
        $stmtNotif = $db->prepare("INSERT INTO notification (departement, faculte, admin_id, titre, message, date_envoi) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmtNotif->execute([$admin_departement, $admin_faculte, $_SESSION['admin_id'], $titre, $message]);
        $notif_message = "Notification diffusée avec succès aux étudiants de votre département !";
    }
}

// Récupération des filtres de recherche et de tri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$motif = isset($_GET['motif']) ? trim($_GET['motif']) : '';
$tri = isset($_GET['tri']) && $_GET['tri'] === 'ASC' ? 'ASC' : 'DESC';

// Construction de la requête SQL dynamique
$sql = "SELECT b.*, e.nom, e.post_nom, e.prenom, e.matricule, e.filiere, e.faculte 
        FROM bordereau b 
        JOIN etudiant e ON b.etudiant_id = e.id";

$params = array();

// Gestion des droits Super-Admin ("Tous") vs Admin de département
if ($admin_departement !== 'Tous') {
    $sql .= " WHERE e.departement = ?";
    array_push($params, $admin_departement);
} else {
    $sql .= " WHERE 1=1";
}

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE ? OR e.nom LIKE ? OR e.post_nom LIKE ? OR e.prenom LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($motif)) {
    $sql .= " AND b.type_frais = ?";
    array_push($params, $motif);
}

// Tri par montant
$sql .= " ORDER BY b.montant " . $tri;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bordereaux = $stmt->fetchAll();

// Statistiques spécifiques au département
$sqlStats = "
    SELECT 
        COUNT(b.id) as total_bordereaux,
        SUM(b.montant) as montant_total,
        SUM(CASE WHEN b.statut = 'Validé' THEN 1 ELSE 0 END) as total_valides
    FROM bordereau b
    JOIN etudiant e ON b.etudiant_id = e.id";

if ($admin_departement !== 'Tous') {
    $sqlStats .= " WHERE e.departement = ?";
    $stmtStats = $db->prepare($sqlStats);
    $stmtStats->execute(array($admin_departement));
} else {
    $stmtStats = $db->prepare($sqlStats);
    $stmtStats->execute();
}

$stats = $stmtStats->fetch();

// Récupérer la liste unique des motifs pour le filtre déroulant
$stmtMotifs = $db->prepare("SELECT DISTINCT type_frais FROM bordereau");
$stmtMotifs->execute();
$list_motifs = $stmtMotifs->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Département <?php echo htmlspecialchars($admin_departement); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../assets/css/styledash.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    
</head>
<body>

<!-- Barre de navigation Admin -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">UniPortail | Espace Administration</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3 small">Connecté : <strong><?php echo htmlspecialchars($admin_nom); ?></strong> (Département : <span class="badge bg-primary"><?php echo htmlspecialchars($admin_departement); ?></span>)</span>
            <a href="login.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container my-4">

    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-dark">Tableau de Bord - Département : <?php echo ucwords(htmlspecialchars($admin_departement)); ?></h2>
            <p class="text-muted">Faculté : <?php echo ucwords(htmlspecialchars($admin_faculte)); ?></p>
            
            <?php if (!empty($notif_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $notif_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cartes Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0 bg-white p-3">
                <h6 class="text-muted small uppercase mb-1">Total Bordereaux du Département</h6>
                <h3 class="fw-bold text-primary mb-0"><?php echo $stats['total_bordereaux'] ?? 0; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0 bg-white p-3" style="border-left-color: #198754;">
                <h6 class="text-muted small uppercase mb-1">Montant Global Enregistré</h6>
                <h3 class="fw-bold text-success mb-0"><?php echo number_format($stats['montant_total'] ?? 0, 2); ?> USD</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm border-0 bg-white p-3" style="border-left-color: #0dcaf0;">
                <h6 class="text-muted small uppercase mb-1">Bordereaux Validés</h6>
                <h3 class="fw-bold text-info mb-0"><?php echo $stats['total_valides'] ?? 0; ?></h3>
            </div>
        </div>
    </div>

    <!-- Barre d'outils de Filtres, Recherche et Notification -->
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Rechercher par matricule ou nom..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="motif" class="form-select">
                    <option value="">Tous les motifs de frais</option>
                    <?php foreach ($list_motifs as $m): ?>
                        <option value="<?php echo htmlspecialchars($m['type_frais']); ?>" <?php if($motif === $m['type_frais']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($m['type_frais']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="tri" class="form-select">
                    <option value="DESC" <?php if($tri=='DESC') echo 'selected'; ?>>Montant : Décroissant</option>
                    <option value="ASC" <?php if($tri=='ASC') echo 'selected'; ?>>Montant : Croissant</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Filtrer</button>
                <a href="dashboard.php" class="btn btn-outline-secondary">Effacer</a>
            </div>
        </form>
        <div class="mt-3 text-end border-top pt-3">
            <button class="btn btn-success btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNotification">
                <span class="material-symbols-outlined align-middle fs-6">notifications_active</span> Envoyer une notification au département
            </button>
        </div>
    </div>

    <!-- Tableau des Bordereaux -->
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Liste des Bordereaux (<?php echo count($bordereaux); ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Matricule</th>
                            <th>Étudiant</th>
                            <th>N° Transaction</th>
                            <th>Motif</th>
                            <th>Montant</th>
                            <th>Date Soumission</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bordereaux) > 0): ?>
                            <?php foreach ($bordereaux as $b): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($b['matricule']); ?></span></td>
                                    <td><?php echo htmlspecialchars(strtoupper($b['nom']) . ' ' . ucwords($b['post_nom'] . ' ' . $b['prenom'])); ?></td>
                                    <td><code><?php echo htmlspecialchars($b['numero_transaction']); ?></code></td>
                                    <td><small><?php echo htmlspecialchars($b['type_frais']); ?></small></td>
                                    <td class="fw-bold"><?php echo number_format($b['montant'], 2); ?> <?php echo htmlspecialchars($b['devise']); ?></td>
                                    <td><small><?php echo htmlspecialchars($b['date_soumission']); ?></small></td>
                                    <td>
                                        <?php if($b['statut'] === 'Validé'): ?>
                                            <span class="badge bg-success">Validé</span>
                                        <?php elseif($b['statut'] === 'Rejeté'): ?>
                                            <span class="badge bg-danger">Rejeté</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Aucun bordereau trouvé dans votre département pour ces critères.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour envoyer une notification ciblée -->
<div class="modal fade" id="modalNotification" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="dashboard.php" class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Diffuser une notification</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Ce message sera publié et visible uniquement par les étudiants inscrits dans le département : <strong><?php echo htmlspecialchars($admin_departement); ?></strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Titre de l'annonce</label>
                    <input type="text" name="titre" class="form-control" required placeholder="Ex: Clôture soumission frais académiques">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Message</label>
                    <textarea name="message" rows="4" class="form-control" required placeholder="Rédigez votre message ici..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" name="envoyer_notification" class="btn btn-success btn-sm fw-bold">Diffuser l'annonce</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>