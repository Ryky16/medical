<?php
session_start();
require_once('../../traitement/fonction.php');

if (!isset($_SESSION['user_role'])) {
    header('Location: ../../index.php');
    exit();
}

$id_consultation = intval($_GET['id'] ?? 0);

if (!$id_consultation) {
    die("Consultation invalide");
}

// Consultation
$stmt = $connexion->prepare("
    SELECT c.*, p.*
    FROM medical_consultations c
    JOIN medical_patients p ON p.id = c.id_patient
    WHERE c.id = ?
");

$stmt->bind_param("i", $id_consultation);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Consultation introuvable");
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../../head.php'); ?>

<head>
    <meta charset="UTF-8">
    <title>Consultation médicale</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
    }

    .page-container {
        max-width: 950px;
        background: #fff;
        padding: 30px;
        margin: 20px auto;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
    }

    .header-section {
        border-bottom: 2px solid #4a6572;
        padding-bottom: 15px;
        margin-bottom: 25px;
        text-align: center;
    }

    .section-title {
        background-color: #e8edf1;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.1rem;
        padding: 10px 15px;
        border-left: 4px solid #4a6572;
        margin-top: 30px;
        margin-bottom: 20px;
    }

    .info-box {
        background-color: #f4f6f8;
        border: 1px solid #e1e5e9;
        border-radius: 6px;
        padding: 15px;
    }

    .info-line {
        font-size: 0.95rem;
        margin-bottom: 6px;
    }

    .btn-primary {
        background-color: #4a6572;
        border-color: #4a6572;
        padding: 10px 30px;
    }

    .btn-primary:hover {
        background-color: #3a5460;
    }
    </style>
</head>

<body>

<div class="container my-4">

    <!-- HEADER -->
    <div class="text-center mb-4 border-bottom pb-2">
        <h4 class="fw-bold text-primary">FICHE DE CONSULTATION MÉDICALE</h4>
        <small class="text-muted">Centre de Santé Universitaire</small>
    </div>


    <!-- ================= PATIENT ================= -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold">
            Informations du patient
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-md-4">
                    <strong>Numéro : </strong>
                    <?= htmlspecialchars($data['numero_identifiant']) ?>
                </div>

                <div class="col-md-4">
                    <strong>Nom : </strong>
                    <?= htmlspecialchars($data['nom']) ?>
                </div>

                <div class="col-md-4">
                    <strong>Prénom : </strong>
                    <?= htmlspecialchars($data['prenom']) ?>
                </div>

            </div>

            <div class="row mt-3">

                <div class="col-md-4">
                    <strong>Sexe : </strong>
                    <?= htmlspecialchars($data['sexe']) ?>
                </div>

                <div class="col-md-4">
                    <strong>Date naissance : </strong>
                    <?= !empty($data['date_naissance'])
                        ? date('d/m/Y', strtotime($data['date_naissance']))
                        : '—'; ?>
                </div>

                <div class="col-md-4">
                    <strong>
                        <?= $data['type_patient']=='etudiant' ? 'Faculté' : 'Service' ?>
                    : </strong>

                    <?= htmlspecialchars(
                        $data['type_patient']=='etudiant'
                        ? ($data['faculte'] ?? '—')
                        : ($data['service'] ?? '—')
                    ); ?>
                </div>

            </div>
        </div>
    </div>


    <!-- ================= CONSULTATION ================= -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold">
            Détails de la consultation
        </div>

        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Date :</strong>
                    <?= date('d/m/Y', strtotime($data['date_consultation'])) ?>
                </div>

                <div class="col-md-6">
                    <strong>Heure :</strong>
                    <?= htmlspecialchars($data['heure_consultation']) ?>
                </div>
            </div>

            <hr>

            <p><strong>Motif : </strong>
               <i> <?= nl2br(htmlspecialchars($data['motif'] ?? '—')) ?></i>
            </p>

            <p><strong>Signes fonctionnels : </strong>
               <i> <?= nl2br(htmlspecialchars($data['signes_fonctionnels'] ?? '—')) ?></i>
            </p>

            <p><strong>Examen clinique : </strong>
               <i> <?= nl2br(htmlspecialchars($data['examen_clinique'] ?? '—')) ?></i>
            </p>
            <?php if(!empty($data['examen_clinique_pdf'])): ?>
                <a target="_blank"
                   class="btn btn-sm btn-outline-success mb-3"
                   href="../../uploads/consultations/<?= $data['examen_clinique_pdf'] ?>">
                    📄 Voir reference
                </a>
            <?php endif; ?>

        </div>
    </div>


    <!-- ================= EXAMENS ================= -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold">
            Examens complémentaires
        </div>

        <div class="card-body">

            <p>
                <strong>Resulats Analyses : </strong>
                <i><?= htmlspecialchars($data['resultats_analyses'] ?? '—') ?></i>
            </p>

            <?php if(!empty($data['analyses_pdf'])): ?>
                <a target="_blank"
                   class="btn btn-sm btn-outline-success mb-3"
                   href="../../uploads/consultations/<?= $data['analyses_pdf'] ?>">
                    📄 Voir analyses
                </a>
            <?php endif; ?>

            <p>
                <strong>Resulats Imagerie : </strong>
                <i><?= htmlspecialchars($data['resultats_imagerie'] ?? '—') ?></i>
            </p>

            <?php if(!empty($data['imagerie_pdf'])): ?>
                <a target="_blank"
                   class="btn btn-sm btn-outline-success"
                   href="../../uploads/consultations/<?= $data['imagerie_pdf'] ?>">
                    📄 Voir imagerie
                </a>
            <?php endif; ?>

        </div>
    </div>


    <!-- ================= DIAGNOSTIC ================= -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold">
            Conclusion médicale
        </div>

        <div class="card-body">

            <p>
                <strong>Diagnostic(s) retenu(s) : </strong>
               <i> <?= nl2br(htmlspecialchars($data['diagnostic'] ?? '—')) ?></i>
            </p>

            <p>
                <strong>Conduite à tenir : </strong>
                <i><?= htmlspecialchars($data['conduite_a_tenir'] ?? '—') ?></i>
            </p>

        </div>
    </div>


    <!-- RETOUR -->
    <div class="text-end">
        <a href="dossier_medical.php?id=<?= $data['id_patient'] ?>"
           class="btn btn-secondary">
            ← Retour au dossier
        </a>
    </div>

</div>

</body>

</html>