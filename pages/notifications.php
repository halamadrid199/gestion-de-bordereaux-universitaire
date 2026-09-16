<?php
// pages/notifications.php
require_once '../includes/auth.php';
require_once '../config/database.php';

try {
    // Récupération des notifications avec le nom de l'admin émetteur
    $sql = "SELECT n.titre, n.message, n.date_envoi, a.nom as admin_nom 
            FROM notification n
            INNER JOIN admin a ON n.admin_id = a.id
            ORDER BY n.date_envoi DESC";
            
    $stmt = $db->query($sql);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur = "Impossible de charger les annonces pour le moment.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPortail - Communications Officielles</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .notification-card {
            border: none;
            border-left: 4px solid #0d6efd; /* Barre bleue distinctive */
            border-radius: 8px;
            background-color: #ffffff;
        }
        .time-badge { font-size: 0.75rem; }
    </style>
    <link rel="stylesheet" href="../assets/css/styledash.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
</head>
<body>

<!-- Inclusion de la Navbar commune -->
<?php require_once '../includes/navbar.php'; ?>

<div class="container mb-5" style="max-width: 800px;">
    
    <!-- En-tête -->
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">Annonces & Notifications</h3>
        <p class="text-dark small">Retrouvez ici les communiqués officiels de la direction et du secrétariat général.</p>
    </div>

    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger shadow-sm"><?= $erreur ?></div>
    <?php endif; ?>

    <!-- Liste des messages -->
    <?php if (empty($notifications)): ?>
        <div class="card border-0 shadow-sm p-5 text-center bg-info-subtle">
            <p class="text-muted mb-0">Aucune notification pour le moment. Vous êtes à jour !</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($notifications as $n): ?>
                <div class="card notification-card shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($n['titre']) ?></h5>
                        <span class="text-secondary time-badge bg-light px-2 py-1 rounded fw-semibold">
                            📅 <?= date('d/m/Y à H:i', strtotime($n['date_envoi'])) ?>
                        </span>
                    </div>
                    
                    <!-- Formatage automatique des sauts de ligne du message -->
                    <p class="text-secondary small mb-3 style-message">
                        <?= nl2br(htmlspecialchars($n['message'])) ?>
                    </p>
                    
                    <div class="border-top pt-2 mt-2">
                        <span class="text-muted small">Par : <strong><?= htmlspecialchars($n['admin_nom']) ?></strong> (Administration)</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>