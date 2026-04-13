<?php
 if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Toujours démarrer la session si elle n'est pas active
}
// 

// Vérifie si l'utilisateur est connecté
 if (empty($_SESSION['username'])) {
    session_unset();
    session_destroy();
    header('Location: ../');
    exit();
}

// Vérifie si le mot de passe est encore celui par défaut
if (isset($_SESSION['type_mdp']) && $_SESSION['type_mdp'] === 'default') {
    header('Location: ../profils/admin/update_mdp.php');
    exit();
} 

?>