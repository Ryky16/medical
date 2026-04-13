<?php
if (!empty($_SESSION['username']) && !empty($_SESSION['mdp'])) {
  session_destroy();
}
//include('activite.php');

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDICOUD - Connexion</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
    body {
        background-color: #f4f6f9;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .navbar {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        background-color: #3777B0;
    }

    .login-container {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        padding: 30px;
    }

    .login-card h3 {
        text-align: center;
        color: #3777B0;
        margin-bottom: 25px;
        font-weight: bold;
    }

    .form-control {
        border-radius: 8px;
    }

    .btn-login {
        width: 100%;
        background-color: #3777B0;
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .btn-login:hover {
        background-color: #3777B0;
    }

    .forgot-link {
        display: block;
        text-align: right;
        margin-top: 10px;
        font-size: 14px;
    }

    .forgot-link a {
        color: #3777B0;
        text-decoration: none;
    }

    .forgot-link a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>

    <!-- Barre de navigation -->
    <nav class="navbar">
        <a class="navbar-brand mx-auto" href="#">
            <img src="assets/images/logo.png" width="200" height="90" alt="Logo COUD'MAINT">
        </a>
    </nav>

    <!-- Conteneur principal -->
    <div class="login-container">
        <div class="login-card">
            <h3>Connexion</h3>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['warning'])): ?>
            <div class="alert alert-warning text-center" role="alert">
                <?= htmlspecialchars($_GET['warning'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <form action="traitement/connect.php" method="get">
                <div class="form-group">
                    <label for="login">Nom d'utilisateur</label>
                    <input type="text" name="username_user" class="form-control" id="login"
                        placeholder="Entrez votre identifiant" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password_user" class="form-control" id="password"
                        placeholder="Entrez votre mot de passe" required>
                </div>
                <button type="submit" class="btn btn-login mt-3">Se connecter</button>
                <div class="forgot-link">
                    <a href="mdp/mot_de_passe_oublie.php">Mot de passe oublié ?</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>