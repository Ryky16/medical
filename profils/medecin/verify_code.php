<?php
session_start();
require_once('../../traitement/fonction.php');

/* =======================
   SÉCURITÉ & CONTRÔLES
======================= */

// Vérifier rôle
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../index.php');
    exit();
}

// Vérifier ID étudiant
if (empty($_GET['id'])) {
    header('Location: recherche.php?error=ID étudiant manquant');
    exit();
}

$studentId = (int) $_GET['id'];
$medecinId = (int) $_SESSION['id_user'];

$error = '';
$sentCode = null;

/* =======================
   ENVOI DU CODE
======================= */
// Envoi uniquement au chargement GET
if (!empty($_GET['id'])) {
   // $sentCode = generateAndSendAccessCode($studentId, $medecinId);
}

/* =======================
   VÉRIFICATION DU CODE
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = trim($_POST['access_code'] ?? '');

   /*  if (strlen($code) !== 6) {
        $error = "Le code doit contenir exactement 6 chiffres";
    } elseif (!verifyAccessCode($code, $studentId, $medecinId)) {
        $error = "Code invalide ou expiré";
    } else {
        // Accès autorisé
        $_SESSION['authorized_student'] = $studentId;
        $_SESSION['access_time'] = time();

        header("Location: dossier_medical.php?id=$studentId");
        exit;
    } */
     header("Location: dossier_medical.php?id=$studentId");
        exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COUD'MEDICAL</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">

    <style>
    .verification-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 2rem;
    }

    .verification-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 2.5rem;
        text-align: center;
    }

    .code-input {
        font-size: 2rem;
        letter-spacing: 0.5rem;
        text-align: center;
        padding: 1rem;
        font-family: monospace;
    }
    </style>
</head>

<body>
    <?php
    include_once ('../../head.php');
    ?>
    <div class="verification-container">
        <div class="verification-card">
            <h3 class="mb-4">Accès au dossier médical</h3>

            <p class="text-muted mb-4">
                Un code à usage unique a été envoyé à l’étudiant.
                Veuillez le saisir ci-dessous.
            </p>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="codeForm">

                <div class="form-group">
                    <input type="text" name="access_code" id="access_code" class="form-control code-input" maxlength="6"
                        pattern="[0-9]{6}" placeholder="000000" required autofocus
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">

                    <small class="form-text text-muted">
                        Code à 6 chiffres envoyé par email
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Vérifier et accéder au dossier
                </button>

                <div class="mt-3">
                    <a href="verify_code.php?id=<?php echo $studentId; ?>&resend=1"
                        class="btn btn-outline-primary btn-sm"
                        onclick="return confirm('Un nouveau code sera envoyé. Continuer ?')">
                        🔄 Renvoyer le code
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($sentCode && isset($_GET['debug'])): ?>
    <div class="alert alert-warning text-center mt-3">
        <strong>MODE DÉVELOPPEMENT :</strong>
        Code généré = <?php echo $sentCode; ?>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

    <script>
    document.getElementById('codeForm').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = 'Vérification en cours...';
        btn.disabled = true;
    });
    </script>

</body>

</html>