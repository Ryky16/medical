<?php
session_start();
require_once ('../../traitement/fonction.php');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../index.php');
    exit();
}
$edit = isset($_GET['edit']) && $_GET['edit'] == 1;
$id_consultation = $_GET['id_consultation'] ?? null;
if (!$id_consultation) {
    die('Consultation non spécifiée');
}
$id_prescription = $_GET['id'] ?? null;
$prescription = null;
$id_user = $_SESSION['id_user'];
// Charger prescription si mode VOIR
if ($id_prescription) {
    $prescription = getPrescriptionById($connexion, $id_prescription);
}
$consultation = getConsultationById($connexion, $id_consultation);
if (!$consultation) {
    die('Consultation introuvable');
}

$patient = getPatientById($connexion, $consultation['id_patient']);
$date_actuelle = date('Y-m-d');

// Vérifier que le POST contient les données nécessaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_consultation = intval($_POST['id_consultation'] ?? 0);
    $id_prescription = intval($_POST['id_prescription'] ?? 0);

    $ordonnance = trim($_POST['ordonnance'] ?? '');
    $examens = trim($_POST['examens'] ?? '');
    $certificat = ($_POST['certificat'] === 'Oui') ? 'Oui' : 'Non';
    $type_certificat = ($certificat === 'Oui') ? trim($_POST['type_certificat'] ?? '') : '';

    if (!$id_consultation) {
        $_SESSION['error'] = 'Consultation invalide.';
        header('Location: prescription.php?id_consultation=' . $id_consultation);
        exit();
    }

    /* ===============================
       UPDATE si prescription existe
    =============================== */

    if ($id_prescription) {
        $sql = 'UPDATE medical_prescriptions 
                SET ordonnance=?, examens_complementaires=?, certificat=?, type_certificat=? 
                WHERE id_prescription=?';

        $stmt = $connexion->prepare($sql);

        if (!$stmt) {
            $_SESSION['error'] = 'Erreur préparation : ' . $connexion->error;
            header('Location: prescription.php?id_consultation=' . $id_consultation);
            exit();
        }

        $stmt->bind_param(
            'ssssi',
            $ordonnance,
            $examens,
            $certificat,
            $type_certificat,
            $id_prescription
        );

        $message = 'Prescription modifiée avec succès.';
        if ($stmt->execute()) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = 'Erreur : ' . $stmt->error;
        }

        $stmt->close();

        header('Location: dossier_medical.php?id=' . $patient['id']);
        exit();
    } else {
        /* ===============================
           INSERT nouvelle prescription
        =============================== */

        $sql = 'INSERT INTO medical_prescriptions 
                (id_consultation, id_user, ordonnance, examens_complementaires, certificat, type_certificat, date_prescription) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())';

        $stmt = $connexion->prepare($sql);

        if (!$stmt) {
            $_SESSION['error'] = 'Erreur préparation : ' . $connexion->error;
            header('Location: prescription.php?id_consultation=' . $id_consultation);
            exit();
        }

        $stmt->bind_param(
            'iissss',
            $id_consultation,
            $id_user,
            $ordonnance,
            $examens,
            $certificat,
            $type_certificat
        );

        $message = 'Prescription enregistrée avec succès.';
        if ($stmt->execute()) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = 'Erreur : ' . $stmt->error;
        }

        $stmt->close();

        header('Location: prescription.php?id_consultation=' . $id_consultation);
        exit();
    }

    /* ===============================
       EXECUTION
    =============================== */
}
$readonly = ($prescription && !$edit) ? 'readonly' : '';
$disabled = ($prescription && !$edit) ? 'disabled' : '';
$date_prescription = strtotime($prescription['date_prescription'] ?? 0);
$now = time();
$difference = $now - $date_prescription;
$modifiable = ($difference <= 86400 && $prescription['id_user'] == $_SESSION['id_user']);  // 24H = 86400 sec
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../../head.php'); ?>

<head>
    <style>
    /* Style PDF minimaliste */

    body {
        background: #f5f5f5;
    }

    .pdf-container {
        max-width: 250mm;
        /* Format A4 */
        margin: 0 auto;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-top: 15px;
    }

    /* En-tête */
    .pdf-header {
        text-align: center;
        border-bottom: 2px solid #2c5282;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .pdf-header h1 {
        color: #2c5282;
        font-size: 24px;
        margin-bottom: 5px;
    }

    .pdf-header .subtitle {
        color: #666;
        font-size: 14px;
    }

    /* Section étudiant */
    .student-info {
        background: #f8fafc;
        border-left: 4px solid #4299e1;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 0 4px 4px 0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }

    .info-item {
        margin-bottom: 5px;
    }

    .info-label {
        font-weight: bold;
        color: #4a5568;
        font-size: 13px;
    }

    .info-value {
        color: #2d3748;
        font-size: 14px;
    }

    /* Formulaire */
    .form-section {
        margin-bottom: 25px;
    }

    .section-title {
        font-size: 16px;
        color: #2c5282;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 5px;
    }

    input[type="date"],
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e0;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
    }

    input:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    textarea {
        min-height: 80px;
        resize: vertical;
    }

    /* Boutons */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    .btn {
        padding: 10px 25px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-primary {
        background: #2c5282;
        color: white;
    }

    .btn-primary:hover {
        background: #2a4365;
    }

    .btn-secondary {
        background: #cbd5e0;
        color: #4a5568;
    }

    .btn-secondary:hover {
        background: #a0aec0;
    }

    /* Infirmier info */
    .infirmier-info {
        font-size: 12px;
        color: #718096;
        text-align: right;
        margin-top: 5px;
    }

    /* Footer PDF */
    .pdf-footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
        font-size: 11px;
        color: #718096;
        text-align: center;
    }

    /* Impression */
    @media print {
        body {
            background: white;
            padding: 0;
        }

        .pdf-container {
            box-shadow: none;
            max-width: 100%;
            padding: 20px;
        }

        .form-actions,
        .btn {
            display: none !important;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    }
    </style>
</head>

<body>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        <?php
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="pdf-container">
        <!-- En-tête -->
        <div class="pdf-header">
            <h1>PRESCRIPTION MÉDICALE</h1>
            <div class="subtitle">Associée à une consultation</div>
        </div>

        <div class="student-info">
            <div class="info-grid">
                <div>
                    <span class="info-label">Patient(e) :</span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?>
                    </span>

                </div>

                <div>
                    <span class="info-label">N° Patient(e) :</span>
                    <span class="info-value"><?= htmlspecialchars($patient['numero_identifiant']) ?></span>
                </div>
                <div>
                    <span class="info-label">Date Naissance :</span>
                    <span class="info-value">
                        <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>
                    </span>
                </div>

                <div>
                    <span class="info-label">Date consultation :</span>
                    <span class="info-value">
                        <?= date('d/m/Y', strtotime($consultation['date_consultation'])) ?>
                    </span>
                </div>

                <div>
                    <span class="info-label">Motif :</span>
                    <span class="info-value"><?= htmlspecialchars($consultation['motif']) ?></span>
                </div>
            </div>
        </div>


        <!-- Formulaire soins -->
        <form action="" method="POST">
            <input type="hidden" name="id_prescription" value="<?= $prescription['id_prescription'] ?? 0 ?>">
            <input type="hidden" name="id_consultation" value="<?= $id_consultation ?>">

            <!-- Ordonnance -->
            <div class="form-section">
                <div class="section-title">ORDONNANCE</div>
                <div class="form-group">
                    <textarea name="ordonnance" <?= $readonly ?> rows="4" placeholder="Médicaments prescrits..."><?=
htmlspecialchars($prescription['ordonnance'] ?? '')
?></textarea>
                </div>
            </div>

            <!-- Examens complémentaires -->
            <div class="form-section">
                <div class="section-title">EXAMENS COMPLÉMENTAIRES DEMANDÉS</div>
                <div class="form-group">
                    <textarea name="examens" <?= $readonly ?> rows="3" placeholder="Examens complémentaires..."><?=
htmlspecialchars($prescription['examens_complementaires'] ?? '')
?></textarea>
                </div>
            </div>

            <!-- Certificat médical -->
            <div class="form-section">
                <div class="section-title">CERTIFICAT MÉDICAL</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Certificat médical ?</label>
                        <select name="certificat" <?= $disabled ?>>
                            <option value="Non"
                                <?= (($prescription['certificat'] ?? '') === 'Non') ? 'selected' : '' ?>>
                                Non
                            </option>

                            <option value="Oui"
                                <?= (($prescription['certificat'] ?? '') === 'Oui') ? 'selected' : '' ?>>
                                Oui
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type de certificat</label>
                        <input type="text" name="type_certificat" <?= $readonly ?>
                            value="<?= htmlspecialchars($prescription['type_certificat'] ?? '') ?>" placeholder="...">
                    </div>
                </div>
            </div>

            <!-- Médecin -->
            <div class="infirmier-info">
                Prescription établie par Dr:
                <?= htmlspecialchars($_SESSION['nom'] ?? 'Médecin') ?>
            </div>

            <!-- Actions -->
            <div class="form-actions">

                <a href="dossier_medical?id=<?= $patient['id'] ?>" class="btn btn-secondary me-3">
                    Retour
                </a>

                <?php if (!$prescription || $edit): ?>

                <!-- AJOUT -->
                <button type="submit" class="btn btn-primary">
                    Enregistrer la prescription
                </button>

                <?php else: ?>

                <!-- PDF ORDONNANCE -->
                <a href="ordonnance_pdf.php?id=<?= $prescription['id_prescription'] ?>" target="_blank"
                    class="btn btn-success">
                    Imprimer Ordonnance
                </a>

                <!-- PDF EXAMENS -->
                <a href="examens_pdf.php?id=<?= $prescription['id_prescription'] ?>" target="_blank"
                    class="btn btn-info">
                    Imprimer Examen(s)
                </a>
                <?php if ($modifiable): ?>
                <a href="?id_consultation=<?= $id_consultation ?>&id=<?= $prescription['id_prescription'] ?>&edit=1"
                    class="btn btn-warning">
                    Modifier
                </a>
                <?php else: ?>
                <span class="text-danger fw-bold">
                    Modification impossible (délai dépassé 24H)
                </span>
                <?php endif; ?>

                <?php endif; ?>

            </div>
        </form>


        <!-- Footer -->
        <div class="pdf-footer">
            Document confidentiel - Service de Santé Universitaire - <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <script>
    // Auto-remplissage de la date
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.querySelector('input[name="date_soin"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = '<?php echo $date_actuelle; ?>';
        }

        // Focus sur le premier champ après la date
        document.querySelector('input[name="ta"]')?.focus();
    });

    // Gestion de l'impression
    function printForm() {
        window.print();
    }
    </script>

    <?php include_once ('../../footer.php'); ?>
</body>

</html>