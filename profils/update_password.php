<?php
session_start();
require_once('../traitement/fonction.php');

$roles_autorises = ['medecin', 'secretaire', 'infirmier', 'dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ancienPassword   = $_POST['ancien_password'] ?? '';
    $nouveauPassword  = $_POST['nouveau_password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';
    $mdp = "updated";

    if (empty($ancienPassword) || empty($nouveauPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
    } else {

        // Vérifier ancien mot de passe avec celui stocké en session
        if (SHA1($ancienPassword) != $_SESSION['password']) {

            $_SESSION['error'] = "Ancien mot de passe incorrect.";
            header("Location: update_password.php");
            exit();

        } elseif ($nouveauPassword !== $confirmPassword) {

            $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas.";
            header("Location: update_password.php");
            exit();

        } elseif (strlen($nouveauPassword) < 6) {

            $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
            header("Location: update_password.php");
            exit();

        } else {

            $newHash = SHA1($nouveauPassword);

            $stmt = $connexion->prepare("UPDATE medical_users SET password = ?, mdp = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newHash, $mdp, $_SESSION['id_user']);
            $stmt->execute();
            $stmt->close();

            // Mettre à jour aussi la session
            $_SESSION['user_password'] = $newHash;

            $_SESSION['success'] = "Mot de passe modifié avec succès.";
            header("Location: /medical01/logout.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../head.php'); ?>

<head>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Police professionnelle -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
        color: #333;
        line-height: 1.5;
    }

    .page-container {
        max-width: 1000px;
        background-color: white;
        padding: 30px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .header-section {
        border-bottom: 2px solid #4a6572;
        padding-bottom: 15px;
        margin-bottom: 25px;
        text-align: center;
    }

    .title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 5px;
    }

    .subtitle {
        color: #546e7a;
        font-size: 1rem;
        margin-bottom: 0;
    }

    .section-title {
        background-color: #e8edf1;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
        padding: 10px 15px;
        border-left: 4px solid #4a6572;
        margin-top: 25px;
        margin-bottom: 20px;
    }

    .form-section {
        margin-bottom: 25px;
    }

    .form-label {
        color: #4a6572;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .required-field::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }

    .form-control,
    .form-select {
        border: 1px solid #d5dde5;
        border-radius: 4px;
        padding: 10px 12px;
        color: #2c3e50;
        font-size: 0.95rem;
        background-color: #ffffff;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        border-color: #e9ecef;
        color: #6c757d;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #4a6572;
        box-shadow: 0 0 0 0.2rem rgba(74, 101, 114, 0.15);
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 5px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .radio-group {
        display: flex;
        justify-content: flex-start;
        /* pousse les radios vers la droite */
        gap: 25px;
        margin-top: 5px;
        margin-left: 15px;
        flex-wrap: wrap;
    }

    .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 60px;
        margin-left: 5px;
        /* évite qu’ils débordent trop */
        justify-content: flex-start;
    }


    .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #4a6572;
        border-color: #4a6572;
        padding: 10px 30px;
        font-weight: 500;
        border-radius: 4px;
    }

    .btn-primary:hover {
        background-color: #3a5460;
        border-color: #3a5460;
    }

    .btn-secondary {
        background-color: #95a5a6;
        border-color: #95a5a6;
        padding: 10px 30px;
        font-weight: 500;
        border-radius: 4px;
    }

    .btn-secondary:hover {
        background-color: #7f8c8d;
        border-color: #7f8c8d;
    }

    .field-hint {
        color: #7f8c8d;
        font-size: 0.85rem;
        margin-top: 5px;
        font-style: italic;
    }

    .footer-note {
        color: #7f8c8d;
        font-size: 0.85rem;
        text-align: center;
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #e1e5e9;
    }


    @media print {
        .page-container {
            box-shadow: none;
            padding: 0;
            margin: 0;
        }

        .btn-primary,
        .btn-secondary {
            display: none;
        }

        .no-print {
            display: none;
        }

        .form-control[readonly] {
            border: none;
            background-color: transparent;
            padding: 0;
        }

        .form-control:not([readonly]) {
            border-bottom: 1px solid #ccc;
            border-top: none;
            border-left: none;
            border-right: none;
            background-color: transparent;
            padding: 5px 0;
        }

    }
    </style>
</head>

<body>


    <div class="page-container container">
        <!-- En-tête -->
        <div class="header-section">
            <h1 class="title">MODIFICATION DU MOT DE PASSE</h1>
            <p class="subtitle">Sécurité du compte - Centre de Santé Universitaire</p>
        </div>
        <!-- Messages d'alerte -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST">

            <div class="section-title">Modification du mot de passe</div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label required-field">Ancien mot de passe</label>
                    <input type="password" name="ancien_password" class="form-control" required>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label required-field">Nouveau mot de passe</label>
                    <input type="password" name="nouveau_password" class="form-control" required minlength="6">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label required-field">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="tableau.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
            </div>

        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profile1 = document.getElementById('profile_1');
            const profile2 = document.getElementById('profile_2');

            const specialites = <?php echo json_encode($specialites); ?>;

            function loadProfile2Options(selectedRole, selectedValue) {
                profile2.innerHTML = '<option value="">Sélectionner</option>';
                let options = [];
                if (selectedRole === 'medecin') options = specialites;
                else if (selectedRole === 'secretaire') options = ['Accueil', ...specialites];
                else if (selectedRole === 'infirmier') options = ['infirmier', 'Administration'];

                options.forEach(opt => {
                    const optionEl = document.createElement('option');
                    optionEl.value = opt;
                    optionEl.textContent = opt;
                    if (opt === selectedValue) optionEl.selected = true;
                    profile2.appendChild(optionEl);
                });
            }

            // Initial load si modification
            loadProfile2Options(profile1.value, "<?= isset($user['profile_2']) ? $user['profile_2'] : '' ?>");

            profile1.addEventListener('change', function() {
                loadProfile2Options(this.value, '');
            });
        });
        </script>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
    // Validation du formulaire
    const form = document.getElementById('identificationForm');
    form.addEventListener('submit', function(event) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            event.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires (*)');
        }
    });
    </script>
    <?php include_once ('../footer.php'); ?>
</body>

</html>