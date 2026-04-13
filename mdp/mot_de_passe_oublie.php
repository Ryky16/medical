<?php
session_start();

include('../traitement/fonction.php');
//include('../../traitement/requete.php');

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        --primary: #4361ee;
        --primary-light: #e6f0ff;
        --success: #28a745;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #212529;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fb;
    }

    .password-container {
        max-width: 500px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .password-title {
        color: var(--primary);
        font-weight: 600;
        margin-bottom: 1.5rem;
        text-align: center;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-light);
    }

    .form-label {
        font-weight: 500;
        color: var(--dark);
    }

    .form-control {
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
    }

    .password-toggle {
        cursor: pointer;
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--dark);
    }

    .password-input-group {
        position: relative;
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
        padding: 0.5rem 1.5rem;
        font-weight: 500;
    }

    .btn-primary:hover {
        background-color: #3a56d4;
        border-color: #3a56d4;
    }

    .alert {
        border-radius: 6px;
    }

    .error-message {
        color: var(--success);
        background: rgb(224, 242, 247);
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        text-align: center;
    }
    .app-name {
        font-family: 'Roboto', sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #1e3a5f;
        /* bleu médical sobre */
        margin-left: 8px;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    @media (max-width: 576px) {
        .password-container {
            padding: 1.5rem;
            margin: 1rem;
        }
    }
    </style>
</head>

<body>
    <header
        style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
        <div style="display: flex; align-items: center;">
            <img src="../assets/images/logo.png" alt="Logo" style="height: 40px;">
            <span class="app-name" style="margin-left: 10px; font-weight: bold; font-size: 18px;">MEDICOUD</span>
        </div>
    </header>
    <div class="container">
        <div class="password-container">
            <h2 class="password-title">
                <i class="fas fa-key me-2"></i>réinitialisation
            </h2>
            <?php if (isset($_GET['warning'])): ?>
            <div class="error-message"><?= htmlspecialchars($_GET['warning']) ?></div>
            <?php endif; ?>
            <form method="get" action="envoyer_mail_reset">
                <!-- Ancien mot de passe -->
                <div class="mb-3">
                    <label for="current_password" class="form-label">Votre adresse email :</label>
                    <div class="">
                        <input type="email" class="form-control"  name="email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="current_password" class="form-label">Telephone :</label>
                    <div class="password-input-group">
                        <input type="text" class="form-control"  name="telephone" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="../index" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Envoyer un lien de réinitialisation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <?php include('../footer.php'); ?>
</body>

</html>