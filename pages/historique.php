<?php
// pages/historique.php
require_once '../includes/auth.php';
require_once '../config/database.php';

$etudiant_id = $_SESSION['etudiant_id'];

// Récupération de l'année filtrée (par défaut l'année en cours, ex: 2026)
$annee_filtre = $_SESSION['annee_filtre'] ?? date('Y');

// 1. Gestion du filtrage par statut via l'URL
$filtre = isset($_GET['filtre']) ? trim($_GET['filtre']) : 'Tous';
$statuts_autorises = ['Tous', 'En attente', 'Validé', 'Rejeté'];

if (!in_array($filtre, $statuts_autorises)) {
    $filtre = 'Tous';
}

try {
    // 2. Construction de la requête SQL en fonction du filtre et de l'année sélectionnée
    if ($filtre === 'Tous') {
        $sql = "SELECT numero_transaction, type_frais, montant, max(devise) as devise, url_source, statut, date_soumission 
                FROM bordereau 
                WHERE etudiant_id = ? AND YEAR(date_soumission) = ? 
                GROUP BY numero_transaction, type_frais, montant, url_source, statut, date_soumission
                ORDER BY date_soumission DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$etudiant_id, $annee_filtre]);
    } else {
        $sql = "SELECT numero_transaction, type_frais, montant, max(devise) as devise, url_source, statut, date_soumission 
                FROM bordereau 
                WHERE etudiant_id = ? AND statut = ? AND YEAR(date_soumission) = ? 
                GROUP BY numero_transaction, type_frais, montant, url_source, statut, date_soumission
                ORDER BY date_soumission DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$etudiant_id, $filtre, $annee_filtre]);
    }
    
    $bordereaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement de l'historique.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPortail - Mon Historique</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .filter-btn { border-radius: 20px; font-weight: 600; font-size: 0.9rem; }
        .table-card { border: none; border-radius: 12px; }
    </style>
    <link rel="stylesheet" href="../assets/css/styledash.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
</head>
<body>

<!-- Inclusion de la Navbar commune -->
<?php require_once '../includes/navbar.php'; ?>

<div class="container mb-5">
    
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Historique de mes paiements (<?= htmlspecialchars($annee_filtre) ?>)</h3>
            <p class="text-dark small mb-0">Retrouvez ici tous les bordereaux que vous avez scannés pour l'année <?= htmlspecialchars($annee_filtre) ?>.</p>
        </div>
        <a href="enregistrement.php" class="btn btn-primary fw-bold btn-sm px-3">+ Nouveau scan</a>
    </div>

    <!-- Barre de filtrage par statut -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="historique.php?filtre=Tous" class="btn filter-btn <?= $filtre === 'Tous' ? 'btn-dark' : 'btn-outline-dark bg-white' ?>">Tous</a>
        <a href="historique.php?filtre=En attente" class="btn filter-btn <?= $filtre === 'En attente' ? 'btn-warning text-dark' : 'btn-outline-warning bg-white text-dark' ?>">En attente</a>
        <a href="historique.php?filtre=Validé" class="btn filter-btn <?= $filtre === 'Validé' ? 'btn-success' : 'btn-outline-success bg-white' ?>">Validés</a>
        <a href="historique.php?filtre=Rejeté" class="btn filter-btn <?= $filtre === 'Rejeté' ? 'btn-danger' : 'btn-outline-danger bg-white' ?>">Rejetés</a>
    </div>

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger shadow-sm"><?= $erreur ?></div>
    <?php endif; ?>

    <!-- Conteneur du tableau -->
    <div class="card table-card shadow-sm p-4 bg-white">
        <?php if (empty($bordereaux)): ?>
            <div class="text-center py-5">
                <p class="text-muted mb-0">Aucun enregistrement ne correspond à ce filtre pour l'année <?= htmlspecialchars($annee_filtre) ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th>Date d'enregistrement</th>
                            <th>Numéro de Transaction</th>
                            <th>Type de Frais</th>
                            <th>Montant</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Document Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bordereaux as $b): ?>
                            <tr>
                                <td class="text-muted small"><?= date('d/m/Y à H:i', strtotime($b['date_soumission'])) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($b['numero_transaction']) ?></td>
                                <td><?= htmlspecialchars($b['type_frais']) ?></td>
                                <td class="fw-semibold text-primary">
                                    <?= number_format($b['montant'], 2, ',', ' ') . ' ' . htmlspecialchars($b['devise']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($b['statut'] === 'Validé'): ?>
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Validé</span>
                                    <?php elseif ($b['statut'] === 'Rejeté'): ?>
                                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill">Rejeté</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($b['url_source']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1">
                                        Voir l'original ↗
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>