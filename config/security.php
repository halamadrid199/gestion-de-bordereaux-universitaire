<?php
// config/security.php

// 1. Récupérer la vraie adresse IP
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// 2. Vérifier si l'IP est actuellement bloquée
function estIpBloquee($db) {
    $ip = getClientIp();
    $stmt = $db->prepare("SELECT tentatives, date_deblocage FROM ip_bloquee WHERE adresse_ip = ?");
    $stmt->execute([$ip]);
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultat) {
        // Si elle a atteint 5 tentatives et que le temps de déblocage n'est pas dépassé
        if ($resultat['tentatives'] >= 5) {
            $maintenant = new DateTime();
            $deblocage = new DateTime($resultat['date_deblocage']);

            if ($maintenant < $deblocage) {
                return true; // Toujours bloqué
            } else {
                // Le délai d'une heure est passé, on réinitialise l'IP
                $stmtReset = $db->prepare("DELETE FROM ip_bloquee WHERE adresse_ip = ?");
                $stmtReset->execute([$ip]);
                return false;
            }
        }
    }
    return false;
}

// 3. Enregistrer un échec de connexion
function enregistrerEchec($db) {
    $ip = getClientIp();
    
    // On regarde si l'IP existe déjà dans la table
    $stmt = $db->prepare("SELECT tentatives FROM ip_bloquee WHERE adresse_ip = ?");
    $stmt->execute([$ip]);
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultat) {
        $nouvelles_tentatives = $resultat['tentatives'] + 1;
        
        if ($nouvelles_tentatives >= 5) {
            // Blocage pour 1 heure (décalage horaire basé sur le serveur)
            $date_deblocage = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmtUpdate = $db->prepare("UPDATE ip_bloquee SET tentatives = ?, date_deblocage = ? WHERE adresse_ip = ?");
            $stmtUpdate->execute([$nouvelles_tentatives, $date_deblocage, $ip]);
        } else {
            $stmtUpdate = $db->prepare("UPDATE ip_bloquee SET tentatives = ? WHERE adresse_ip = ?");
            $stmtUpdate->execute([$nouvelles_tentatives, $ip]);
        }
    } else {
        // Première tentative ratée, on insère l'IP
        $stmtInsert = $db->prepare("INSERT INTO ip_bloquee (adresse_ip, tentatives) VALUES (?, 1)");
        $stmtInsert->execute([$ip]);
    }
}
?>