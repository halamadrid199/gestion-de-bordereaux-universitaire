<?php
// pages/dashboard.php
require_once '../includes/auth.php';
require_once '../config/database.php';

$etudiant_id = $_SESSION['etudiant_id'];

// Récupération de l'année filtrée (par défaut l'année en cours, ex: 2026)
$annee_filtre = $_SESSION['annee_filtre'] ?? date('Y');

try {
    // 1. Récupération des statistiques filtrées par année
    $stmt_stats = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'En attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut = 'Validé' THEN 1 ELSE 0 END) as valides,
            SUM(CASE WHEN statut = 'Rejeté' THEN 1 ELSE 0 END) as rejetes
        FROM bordereau 
        WHERE etudiant_id = ? AND YEAR(date_soumission) = ?
    ");
    $stmt_stats->execute([$etudiant_id, $annee_filtre]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Initialisation par défaut si aucun bordereau n'existe pour cette année
    $total = $stats['total'] ?? 0;
    $en_attente = $stats['en_attente'] ?? 0;
    $valides = $stats['valides'] ?? 0;
    $rejetes = $stats['rejetes'] ?? 0;

    // 2. Récupération des 5 derniers bordereaux enregistrés pour cette année
    $stmt_recents = $db->prepare("
        SELECT numero_transaction, type_frais, montant, max(devise) as devise, statut, date_soumission 
        FROM bordereau 
        WHERE etudiant_id = ? AND YEAR(date_soumission) = ?
        GROUP BY numero_transaction, type_frais, montant, statut, date_soumission
        ORDER BY date_soumission DESC 
        LIMIT 5
    ");
    $stmt_recents->execute([$etudiant_id, $annee_filtre]);
    $bordereaux_recents = $stmt_recents->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur_systeme = "Impossible de charger vos statistiques pour le moment.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPortail - Tableau de bord</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .stat-card { border: none; border-radius: 10px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-13px); position: relative; }
    </style>
    <link rel="stylesheet" href="../assets/css/styledash.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
</head>
<body>

<!-- Inclusion de la Navbar commune -->
<?php require_once '../includes/navbar.php'; ?>

<div class="container mb-5">
    
    <!-- Message de bienvenue -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-4 rounded shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Ravi de vous revoir, <?= htmlspecialchars($_SESSION['etudiant_prenom']) ?> !</h2>
                    <p class="text-muted mb-0">
                        Matricule : <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['etudiant_matricule']) ?></span> | 
                        Promotion : <strong><?= htmlspecialchars($_SESSION['etudiant_promotion']) ?></strong> | 
                        Année affichée : <span class="badge bg-primary"><?= htmlspecialchars($annee_filtre) ?></span>
                    </p>
                </div>
                <a href="enregistrement.php" class="btn btn-primary fw-bold px-4 py-2">+ Scanner un Bordereau</a>
            </div>
        </div>
    </div>

    <?php if (isset($erreur_systeme)): ?>
        <div class="alert alert-danger shadow-sm"><?= $erreur_systeme ?></div>
    <?php endif; ?>

    <!-- Grille des Statistiques -->
    <div class="row g-3 mb-4">
        <!-- Total -->
        <div class="col-md-3">
            <div class="card stat-card bg-white p-3 shadow-sm border-start border-primary border-4">
                <div class="text-muted small fw-bold text-uppercase">Total Soumis (<?= $annee_filtre ?>)</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $total ?></div>
            </div>
        </div>
        <!-- En attente -->
        <div class="col-md-3">
            <div class="card stat-card bg-white p-3 shadow-sm border-start border-warning border-4">
                <div class="text-muted small fw-bold text-uppercase">En attente</div>
                <div class="fs-2 fw-bold text-warning mt-1"><?= $en_attente ?></div>
            </div>
        </div>
        <!-- Validés -->
        <div class="col-md-3">
            <div class="card stat-card bg-white p-3 shadow-sm border-start border-success border-4">
                <div class="text-muted small fw-bold text-uppercase">Validés</div>
                <div class="fs-2 fw-bold text-success mt-1"><?= $valides ?></div>
            </div>
        </div>
        <!-- Rejetés -->
        <div class="col-md-3">
            <div class="card stat-card bg-white p-3 shadow-sm border-start border-danger border-4">
                <div class="text-muted small fw-bold text-uppercase">Rejetés</div>
                <div class="fs-2 fw-bold text-danger mt-1"><?= $rejetes ?></div>
            </div>
        </div>
    </div>

    <!-- Section Activité Récente -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Enregistrements Récents (<?= $annee_filtre ?>)</h5>
                    <a href="historique.php" class="text-primary text-decoration-none small fw-bold">Voir tout l'historique</a>
                </div>

                <?php if (empty($bordereaux_recents)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Vous n'avez enregistré aucun bordereau pour l'année <?= $annee_filtre ?>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th>Date d'envoi</th>
                                    <th>N° Transaction</th>
                                    <th>Type de Frais</th>
                                    <th>Montant</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bordereaux_recents as $b): ?>
                                    <tr>
                                        <td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($b['date_soumission'])) ?></td>  
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($b['numero_transaction']) ?></td>
                                        <td><?= htmlspecialchars($b['type_frais']) ?></td>
                                        <td class="fw-bold"><?= number_format($b['montant'], 2, ',', ' ') . ' ' . htmlspecialchars($b['devise']) ?></td>
                                        <td class="text-center">
                                            <?php if ($b['statut'] === 'Validé'): ?>
                                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Validé</span>
                                            <?php elseif ($b['statut'] === 'Rejeté'): ?>
                                                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill">Rejeté</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">En attente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>
<?php require_once '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>