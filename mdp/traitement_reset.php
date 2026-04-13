<?php
include('../traitement/fonction.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'];
    $password = $_GET['password'];

    $stmt = $connexion->prepare("SELECT * FROM reset_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $userId = $row['user_id'];
        $hashed = sha1($password); // ou password_hash($password, PASSWORD_DEFAULT)
        
        $stmt = $connexion->prepare("UPDATE medical_users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed, $userId);
        $stmt->execute();

        // Supprimer le token après usage
        $connexion->query("DELETE FROM reset_tokens WHERE user_id = $userId");

         $warning="Mot de passe réinitialisé avec succès.veillez vous connecter";
        header('Location: /medical01/?warning='.$warning);
            exit();
    } else {
         $warning="Lien invalide ou expiré.";
        header('Location: /medical01/?warning='.$warning);
            exit();
    }
}