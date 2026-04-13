<?php
session_start(); // Fichier de connexion MySQLi
include('fonction.php'); // Vos fonctions

$error = "";

if (!empty($_GET['username_user']) && !empty($_GET['password_user'])) {
    $username = trim($_GET['username_user']);
    $password = $_GET['password_user'];
    
    // Utiliser votre fonction login existante
    $row = login($username, $password);
    
    if ($row) {
        // Détruire l'ancienne session si elle existe
        if (isset($_SESSION)) {
            session_regenerate_id(true);
        }
        
        // Stocker les informations de session
        $_SESSION['id_user'] = $row['id'];  
        $_SESSION['username'] = $row['username'];
        $_SESSION['nom'] = $row['nom'];
        $_SESSION['prenom'] = $row['prenom'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['profile_2'] = $row['profile_2'];
        $_SESSION['mdp'] = $row['mdp'];
        $_SESSION['password'] = $row['password'];
        
        // Déterminer le rôle/profil
        if (isset($row['profile_1'])) {
            $_SESSION['user_role'] = $row['profile_1'];
            $role = $row['profile_1'];
        } else {
            $_SESSION['user_role'] = $row['profile_1'];
            $role = $row['profile_1'];
        }
        
        // Mettre à jour la dernière connexion dans la base
        updateLastLogin($row['id']);
        
        // Redirection basée sur le rôle
        switch($role) {
            case 'medecin':
                header('Location: ../profils/secretaire/liste_orienter');
                break;
            case 'secretaire':
                header('Location: ../profils/secretaire/liste_orienter');
                break;
            case 'infirmier':
                header('Location: ../profils/secretaire/liste_orienter');
                break;
            case 'dba':
                header('Location: ../profils/tableau');
                break;
            default:
                header('Location: ../index.php?error=Rôle non reconnu');
                break;
        }
        exit();
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect";
        header('Location: ../index.php?error=' . urlencode($error));
        exit();
    }
} else {
    $error = "Veuillez remplir tous les champs";
    header('Location: ../index.php?error=' . urlencode($error));
    exit();
}

// Fonction pour mettre à jour la dernière connexion
function updateLastLogin($userId) {
    global $connexion;
    
    $query = "UPDATE medical_users SET last_login = NOW() WHERE id = ?";
    $stmt = $connexion->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}
?>