<?php
session_start();
require_once ('../../traitement/fonction.php');

// Vérifier connexion médecin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: ../../index.php');
    exit();
}
$id_user = $_SESSION['id_user'];
if ($_SESSION['mdp'] == 'default') {
    header('Location: /medical01/profils/update_password.php');
    exit();
}
// Récupérer étudiant
$id_patient = $_GET['id'] ?? '';

if (empty($id_patient)) {
    die('Étudiant non spécifié');
}

$donneesApi = getPatientById($connexion, $id_patient);

if (!$donneesApi) {
    $_SESSION['error'] = 'Étudiant introuvable';
    header('Location: recherche_etudiant.php');
    exit();
}
$id_consultation = $_GET['id_consultation'] ?? null;
$editing = false;
$consultation = [];

if ($id_consultation) {
    $editing = true;
    $consultation = getConsultationById($connexion, $id_consultation);
}
// ===============================
// TRAITEMENT ENREGISTREMENT CONSULTATION
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_patient = intval($_POST['id_patient']);
    $date = $_POST['date_consultation'];
    $heure = $_POST['heure_consultation'];
    $motif = trim($_POST['motif']);
    $signes = trim($_POST['signes_fonctionnels']);
    $examen = trim($_POST['examen_clinique']);
    $analyses = trim($_POST['resultats_analyses']);
    $imagerie = trim($_POST['resultats_imagerie']);
    $diagnostic = trim($_POST['diagnostic']);

    // Conduite à tenir
    $conduite = [];
    if (!empty($_POST['traitement']))
        $conduite[] = 'Traitement';
    if (!empty($_POST['surveillance']))
        $conduite[] = 'Surveillance';
    if (!empty($_POST['reference']))
        $conduite[] = 'Référence';
    if (!empty($_POST['autre']))
        $conduite[] = 'Autre : ' . trim($_POST['autre']);
    $conduite_txt = implode(' | ', $conduite);

    $result = false;

    /*
     * ====================================
     * UPDATE CONSULTATION
     * ====================================
     */
    if (!empty($id_consultation)) {
        // Récupérer anciens fichiers
        $sqlOld = 'SELECT examen_clinique_pdf, analyses_pdf, imagerie_pdf 
               FROM medical_consultations WHERE id=?';
        $stmtOld = $connexion->prepare($sqlOld);
        $stmtOld->bind_param('i', $id_consultation);
        $stmtOld->execute();
        $old = $stmtOld->get_result()->fetch_assoc();
        $stmtOld->close();

        $pdf_examen = $old['examen_clinique_pdf'];
        $pdf_analyses = $old['analyses_pdf'];
        $pdf_imagerie = $old['imagerie_pdf'];

        // Upload nouveaux fichiers avec suppression de l'ancien si existant
        if (!empty($_FILES['examen_clinique_pdf']['name'])) {
            $new_examen = uploadPdf($_FILES['examen_clinique_pdf'], 'examen', $id_consultation);
            if ($new_examen) {
                if ($pdf_examen)
                    deletePdf($pdf_examen);
                $pdf_examen = $new_examen;
            }
        }

        if (!empty($_FILES['analyses_pdf']['name'])) {
            $new_analyses = uploadPdf($_FILES['analyses_pdf'], 'analyses', $id_consultation);
            if ($new_analyses) {
                if ($pdf_analyses)
                    deletePdf($pdf_analyses);
                $pdf_analyses = $new_analyses;
            }
        }

        if (!empty($_FILES['imagerie_pdf']['name'])) {
            $new_imagerie = uploadPdf($_FILES['imagerie_pdf'], 'imagerie', $id_consultation);
            if ($new_imagerie) {
                if ($pdf_imagerie)
                    deletePdf($pdf_imagerie);
                $pdf_imagerie = $new_imagerie;
            }
        }

        // UPDATE complet
        $sql = 'UPDATE medical_consultations SET
                date_consultation=?,
                heure_consultation=?,
                motif=?,
                signes_fonctionnels=?,
                examen_clinique=?,
                examen_clinique_pdf=?,
                resultats_analyses=?,
                analyses_pdf=?,
                resultats_imagerie=?,
                imagerie_pdf=?,
                diagnostic=?,
                conduite_a_tenir=?
            WHERE id=?';
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param(
            'ssssssssssssi',
            $date,
            $heure,
            $motif,
            $signes,
            $examen,
            $pdf_examen,
            $analyses,
            $pdf_analyses,
            $imagerie,
            $pdf_imagerie,
            $diagnostic,
            $conduite_txt,
            $id_consultation
        );
        $result = $stmt->execute();
        $stmt->close();

        $message = 'Consultation modifiée avec succès';
    }
    /*
     * ====================================
     * INSERT CONSULTATION
     * ====================================
     */ else {
        // Ajout initial
        $result = ajouterConsultation(
            $connexion,
            $id_patient,
            $id_user,
            $date,
            $heure,
            $motif,
            $signes,
            $examen,
            $analyses,
            $imagerie,
            $diagnostic,
            $conduite_txt,
            $_FILES
        );

        $message = $result ? 'Consultation médicale enregistrée avec succès' : "Erreur lors de l'enregistrement";
    }

    // Message session
    if ($result) {
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "Erreur lors de l'enregistrement";
    }

    header('Location: add_consultation.php?id=' . $id_patient);
    exit();
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
        max-width: 1100px;
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



    <div class="page-container">

        <div class="header-section">
            <h2>FICHE DE CONSULTATION MÉDICALE</h2>
            <p class="text-muted">Centre de Santé Universitaire</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'];
    unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'];
    unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Infos étudiant -->
        <div class="section-title">Informations de l’étudiant</div>

        <div class="info-box mb-4">
            <div class="row">
                <div class="col-md-4 info-line"><strong>Numéro :</strong>
                    <?= htmlspecialchars($donneesApi['numero_identifiant']) ?></div>
                <div class="col-md-4 info-line"><strong>Nom :</strong> <?= htmlspecialchars($donneesApi['nom']) ?></div>
                <div class="col-md-4 info-line"><strong>Prénom :</strong> <?= htmlspecialchars($donneesApi['prenom']) ?>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4 info-line"><strong>Sexe :</strong> <?= htmlspecialchars($donneesApi['sexe']) ?>
                </div>
                <div class="col-md-4 info-line"><strong>Date naissance :</strong>
                    <?= !empty($donneesApi['date_naissance']) ? date('d/m/Y', strtotime($donneesApi['date_naissance'])) : '—' ?>
                </div>
                <?php if ($donneesApi['type_patient'] === 'etudiant'): ?>
                <div class="col-md-4 info-line"><strong>Faculté :</strong>
                    <?php echo htmlspecialchars($donneesApi['faculte'] ?? 'Neant'); ?>
                </div>
                <?php else: ?>
                <div class="col-md-4 info-line"><strong>Service :</strong>
                    <?php echo htmlspecialchars($donneesApi['service'] ?? 'Neant'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORMULAIRE CONSULTATION -->
        <form action="" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id_patient" value="<?= $donneesApi['id'] ?>">
            <input type="hidden" name="id_consultation" value="<?= $consultation['id'] ?? '' ?>">

            <div class="section-title">SECTION 4 : CONSULTATION MÉDICALE</div>

            <!-- Date / Heure -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><strong>Date de consultation</strong></label>
                    <input type="date" name="date_consultation" class="form-control"
                        value="<?= $consultation['date_consultation'] ?? '' ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Heure</label>
                    <input type="time" name="heure_consultation" class="form-control"
                        value="<?= $consultation['heure_consultation'] ?? '' ?>" required>
                </div>
            </div>

            <!-- Motif -->
            <div class="mb-3">
                <label class="form-label">Motif de consultation</label>
                <textarea name="motif" class="form-control" rows="2"
                    required><?= htmlspecialchars($consultation['motif'] ?? '') ?></textarea>
            </div>

            <!-- Signes fonctionnels -->
            <div class="mb-4">
                <label class="form-label">Signes fonctionnels</label>
                <textarea name="signes_fonctionnels" class="form-control"
                    rows="2"><?= htmlspecialchars($consultation['signes_fonctionnels'] ?? '') ?></textarea>
            </div>

            <!-- EXAMEN CLINIQUE -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Examen clinique</label>
                    <input type="text" name="examen_clinique" class="form-control"
                        value="<?= htmlspecialchars($consultation['examen_clinique'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Document PDF – Examen clinique</label>
                    <input type="file" name="examen_clinique_pdf" class="form-control" accept="application/pdf">

                    <?php if (!empty($consultation['examen_clinique_pdf'])): ?>
                    <a href="../../uploads/consultations/<?= $consultation['examen_clinique_pdf'] ?>" target="_blank">
                        Voir document actuel
                    </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- ANALYSES -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Résultats analyses médicales</label>
                    <input type="text" name="resultats_analyses" class="form-control"
                        value="<?= htmlspecialchars($consultation['resultats_analyses'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Document PDF – Analyses</label>
                    <input type="file" name="analyses_pdf" class="form-control" accept="application/pdf">

                    <?php if (!empty($consultation['analyses_pdf'])): ?>
                    <a href="../../uploads/consultations/<?= $consultation['analyses_pdf'] ?>" target="_blank">
                        Voir document actuel
                    </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- IMAGERIE -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Résultats imagerie</label>
                    <input type="text" name="resultats_imagerie" class="form-control"
                        value="<?= htmlspecialchars($consultation['resultats_imagerie'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Document PDF – Imagerie</label>
                    <input type="file" name="imagerie_pdf" class="form-control" accept="application/pdf">

                    <?php if (!empty($consultation['imagerie_pdf'])): ?>
                    <a href="../../uploads/consultations/<?= $consultation['imagerie_pdf'] ?>" target="_blank">
                        Voir document actuel
                    </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Diagnostic -->
            <div class="mb-4">
                <label class="form-label">Diagnostic(s) retenu(s)</label>
                <textarea name="diagnostic" class="form-control"
                    rows="3"><?= htmlspecialchars($consultation['diagnostic'] ?? '') ?></textarea>
            </div>

            <!-- Conduite à tenir -->
            <?php
            $conduite = $consultation['conduite_a_tenir'] ?? '';
            ?>

            <div class="mb-3">
                <label>Conduite à tenir</label><br>

                <label>
                    <input type="checkbox" name="traitement"
                        <?= str_contains($conduite, 'Traitement') ? 'checked' : '' ?>>
                    Traitement
                </label><br>

                <label>
                    <input type="checkbox" name="surveillance"
                        <?= str_contains($conduite, 'Surveillance') ? 'checked' : '' ?>>
                    Surveillance
                </label><br>

                <label>
                    <input type="checkbox" name="reference"
                        <?= str_contains($conduite, 'Référence') ? 'checked' : '' ?>>
                    Référence
                </label>

                <input type="text" name="autre" class="form-control mt-2" placeholder="Autres (préciser)">
            </div>

            <!-- Boutons -->
            <div class="text-end">
                <a href="dossier_medical.php?id=<?= $donneesApi['id'] ?>" class="btn btn-secondary me-3">Retour</a>

                <button type="submit" class="btn btn-primary">
                    <?= $editing ? 'Modifier la consultation' : 'Enregistrer la consultation' ?>
                </button>

            </div>

        </form>

    </div>
    <?php include_once ('../../footer.php'); ?>
</body>

</html>