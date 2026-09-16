<?php
// admin/reset_admin.php
require_once '../config/database.php';
if (!isset($db) && isset($pdo)) { $db = $pdo; }

$email = 'johnnybad0875@gmail.com';
$nouveau_mdp = 'Admin123';
$hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

// 1. Vérifier si l'admin existe déjà
$stmt = $db->prepare("SELECT id FROM admin WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin) {
    // Si le compte existe, on force la mise à jour du mot de passe et du rôle
    $stmtUp = $db->prepare("UPDATE admin SET mot_de_passe = ?, role = 'Super-Admin' WHERE email = ?");
    $stmtUp->execute([$hash, $email]);
    echo "<h3 style='color: green;'>Le compte existant a été mis à jour avec succès !</h3>";
} else {
    // Si le compte n'existe pas du tout, on l'insère directement dans la table
    $stmtIns = $db->prepare("INSERT INTO admin (nom, email, mot_de_passe, role, departement, faculte) VALUES (?, ?, ?, 'Super-Admin', 'Tous', 'Toutes')");
    $stmtIns->execute(['Superviseur Général', $email, $hash]);
    echo "<h3 style='color: green;'>Le compte Super-Admin a été créé avec succès dans la BDD !</h3>";
}

echo "<hr>";
echo "<p>Identifiants de connexion :</p>";
echo "<ul>";
echo "<li><strong>Email :</strong> $email</li>";
echo "<li><strong>Mot de passe :</strong> $nouveau_mdp</li>";
echo "</ul>";
echo "<p style='color: red; font-weight: bold;'>⚠️ N'oublie pas de supprimer ce fichier (reset_admin.php) de ton serveur une fois connecté !</p>";
?>