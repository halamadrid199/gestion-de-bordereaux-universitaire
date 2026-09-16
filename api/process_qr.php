<?php
// api/process_qr.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/auth.php';
    require_once '../config/database.php';
    require_once '../vendor/autoload.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['qr_url'])) {
        echo json_encode(['success' => false, 'message' => 'Requête invalide ou paramètre manquant.']);
        exit();
    }

    $qr_url = trim($_POST['qr_url']);

    if (!isset($_SESSION['etudiant_id']) || !isset($_SESSION['etudiant_matricule'])) {
        echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
        exit();
    }

    $etudiant_id = $_SESSION['etudiant_id'];
    $matricule_etudiant_connecte = trim($_SESSION['etudiant_matricule']);

    if (!filter_var($qr_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'URL invalide.']);
        exit();
    }

    $domaine_autorise = "upn.optsolution.net";
    if (parse_url($qr_url, PHP_URL_HOST) !== $domaine_autorise) {
        echo json_encode(['success' => false, 'message' => 'Domaine non autorisé.']);
        exit();
    }

    // Téléchargement cURL
    $ch = curl_init($qr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1');

    $pdf_binaire = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200 || empty($pdf_binaire)) {
        echo json_encode([
            'success' => false, 
            'message' => "Erreur de téléchargement du PDF (Code HTTP: $http_code | cURL Error: $curl_error)"
        ]);
        exit();
    }

    // Sauvegarde temporaire et parsing
    $chemin_temporaire = tempnam(sys_get_temp_dir(), 'bordereau_');
    file_put_contents($chemin_temporaire, $pdf_binaire);

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($chemin_temporaire);
    $texte_du_pdf = $pdf->getText();

    if (file_exists($chemin_temporaire)) {
        unlink($chemin_temporaire);
    }

    // Vérifications
    if (strpos($texte_du_pdf, "FAUX DOCUEMENT") !== false || strpos($texte_du_pdf, "FAUX") !== false) {
        echo json_encode(['success' => false, 'message' => 'Alerte : Ce bordereau est un faux.']);
        exit();
    }

    if (strpos($texte_du_pdf, $matricule_etudiant_connecte) === false) {
        echo json_encode(['success' => false, 'message' => 'Ce bordereau ne correspond pas à votre matricule (' . $matricule_etudiant_connecte . ').']);
        exit();
    }

    // Extraction basique
    // $numero_transaction = basename(parse_url($qr_url, PHP_URL_PATH));
    // Extraction exacte du numéro de transaction sur le PDF
    $numero_transaction = 'Inconnu';
    if (preg_match('/No\.\s*Transaction\s*[:\-]?\s*([A-Za-z0-9\-]+)/i', $texte_du_pdf, $matches_ref)) {
        $numero_transaction = trim($matches_ref[1]);
    } else {
        // En repli total si vraiment introuvable
        $numero_transaction = 'TX-' . rand(100000, 999999);
    }


    // $montant = 0.00;
    // if (preg_match('/(?:Montant|Total)\s*[:\-]?\s*([0-9\.,]+)/i', $texte_du_pdf, $matches)) {
    //     $montant = (float) str_replace(',', '.', $matches[1]);
    // }

    // Extraction du montant et de la devise (Ex: 240 USD ou 15000 CDF)
    $montant = 0.00;
    $devise = 'USD'; // Valeur par défaut

    if (preg_match('/(?:Montant|Total)\s*[:\-]?\s*([0-9\.,]+)\s*([A-Z]{3}|\$)/i', $texte_du_pdf, $matches_montant)) {
        $valeur_nettoyee = str_replace(',', '.', $matches_montant[1]);
        $montant = (float) $valeur_nettoyee;
        $devise = strtoupper(trim($matches_montant[2]));
        
        // Uniformisation si le symbole '$' est trouvé à la place de 'USD'
        if ($devise === '$') {
            $devise = 'USD';
        }
    }

    // Anti-doublon et insertion
    $stmt_check = $db->prepare("SELECT id FROM bordereau WHERE numero_transaction = ?");
    $stmt_check->execute([$numero_transaction]);
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ce bordereau a déjà été enregistré.']);
        exit();
    }

    // $stmt_insert = $db->prepare("INSERT INTO bordereau (etudiant_id, numero_transaction, type_frais, montant, devise, url_source, statut, date_soumission) VALUES (?, ?, 'Frais Académiques', ?, 'USD', ?, 'En attente', NOW())");
    // $stmt_insert->execute([$etudiant_id, $numero_transaction, $montant, $qr_url]);
    // Extraction exacte du motif (type de frais) sur le PDF



    // $type_frais = 'Frais Généraux';
    // if (preg_match('/Motif\s*[:\-]?\s*([A-Za-z0-9\s\-_]+?)(?=\s+(?:Montant|Total|Date|No\.|$))/i', $texte_du_pdf, $matches_motif)) {
    //     $type_frais = trim($matches_motif[1]);
    // }

    // Extraction simplifiée et robuste du motif sur le PDF
    $type_frais = 'Frais Académiques'; // Valeur par défaut si vraiment introuvable
    
    // On cherche le mot "Motif" suivi de n'importe quel caractère, et on récupère la ligne ou les mots suivants
    if (preg_match('/Motif\s*[:\-]?\s*([^\r\n]+)/i', $texte_du_pdf, $matches_motif)) {
        $type_frais = trim($matches_motif[1]);
    }

    // Insertion en base de données avec le vrai type de frais extrait
    $stmt_insert = $db->prepare("
        INSERT INTO bordereau (etudiant_id, numero_transaction, type_frais, montant, devise, url_source, statut, date_soumission) 
        VALUES (?, ?, ?, ?, ?, ?, 'Validé', NOW())
    ");
    $stmt_insert->execute([$etudiant_id, $numero_transaction, $type_frais, $montant, $devise, $qr_url]);

    echo json_encode(['success' => true, 'message' => 'Bordereau authentifié et soumis avec succès !']);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur critique : ' . $e->getMessage()]);
}
?>