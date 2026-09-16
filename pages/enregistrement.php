<?php
session_start();
// pages/enregistrement.php
require_once '../includes/auth.php';
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniPortail - Enregistrer un Bordereau</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border: 2px dashed #0d6efd;
            border-radius: 8px;
            overflow: hidden;
            background-color: #000;
        }
        .preview-box { background-color: #ffffff; border-radius: 12px; }
    </style>
    <link rel="stylesheet" href="../assets/css/styledash.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
</head>
<body>

<?php require_once '../includes/navbar.php'; ?>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card preview-box shadow-sm p-4 bg-info-subtle">
                <h3 class="fw-bold text-dark text-center mb-2">Scanner le Bordereau</h3>
                <p class="text-muted text-center small mb-4">
                    Autorisez l'accès à votre caméra et placez le code QR du bordereau officiel de la banque devant l'objectif.
                </p>

                <!-- Zone d'affichage des messages de retour de l'API -->
                <div id="feedback-message" class="d-none alert mb-4 small"></div>

                <!-- Le scanner vidéo -->
                <div id="reader"></div>

                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-light border fw-bold text-secondary">Annuler et retour</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion de la bibliothèque de scan QR via CDN -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
function onScanSuccess(decodedText, decodedResult) {
    // 1. Arrêter le scanner immédiatement après une lecture réussie pour éviter les doubles scans
    html5QrcodeScanner.clear();
    
    const feedback = document.getElementById('feedback-message');
    feedback.className = "alert alert-info small";
    feedback.innerHTML = "Code QR détecté ! Analyse en cours par le serveur...";
    feedback.classList.remove('d-none');

    // 2. Envoi de l'URL lue au back-end via Fetch API
    fetch('../api/process_qr.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'qr_url=' + encodeURIComponent(decodedText)
    })
    .then(response => response.json())
    .then(data => {
        feedback.classList.remove('alert-info');
        
        if(data.success) {
            feedback.className = "alert alert-success small";
            feedback.innerHTML = `<strong>Succès !</strong> ${data.message}`;
            // Redirection vers l'historique après 2 secondes pour que l'étudiant lise le succès
            setTimeout(() => { window.location.href = 'historique.php'; }, 2500);
        } else {
            feedback.className = "alert alert-danger small";
            feedback.innerHTML = `<strong>Erreur de sécurité :</strong> ${data.message}`;
            // Optionnel : Offrir un bouton pour recharger la page et scanner à nouveau
            feedback.innerHTML += `<br><button onclick="window.location.reload();" class="btn btn-sm btn-outline-danger mt-2">Réessayer le scan</button>`;
        }
    })
    .catch(error => {
        feedback.className = "alert alert-danger small";
        feedback.innerHTML = "Une erreur réseau est survenue. Veuillez vérifier votre connexion.";
    });
}

function onScanFailure(error) {
    // Cette fonction s'exécute en continu à chaque frame si aucun QR n'est trouvé.
    // On la laisse vide pour éviter de surcharger la console.
}

// Initialisation et démarrage du scanner (25 frames par seconde)
let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);
html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

</body>
</html>