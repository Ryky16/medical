<?php
session_start();
require_once('../../traitement/fonction.php');

$roles_autorises = ['medecin', 'infirmier'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../index.php');
    exit();
}
if ($_SESSION['mdp'] == "default") {
        header('Location: /medical01/profils/update_password.php');
        exit();
}

$id_patient = $_GET['id'] ?? 6;
if (!$id_patient) {
    header('Location: recherche.php');
    exit();
}

$patient = getPatientsById($connexion, $id_patient);
$soins = getSoinsInfirmiersByPatient($connexion, $id_patient);
$dernierAntecedent = getDernierAntecedentByPatient($connexion, $id_patient);
$consultations = getConsultationsByPatient($connexion, $id_patient);

?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once('../../head.php'); ?>

<head>
    <style>
    body {
        background: #f4f6f9;
    }

    .card {
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
    }

    .badge-info {
        font-size: 14px;
    }

    table thead {
        font-size: 15px;
    }

    .profile-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #3777B0;
        color: #fff;
        font-size: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pdf-container {
        /* Format A4 */
        margin: 0 auto;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 5px;
        margin-top: 20px;
    }

    /* En-tête */
    .pdf-header {
        text-align: center;
        border-bottom: 2px solid #2c5282;
        padding-bottom: 10px;
        margin-bottom: 10px;
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
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 5px;
    }

    .info-item {
        margin-bottom: 5px;
    }

    .info-label {
        font-weight: bold;
        color: #4a5568;
        font-size: 15px;
    }

    .info-value {
        color: #2d3748;
        font-size: 15px;
    }

    .search-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 15px;
    }

    .search-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        padding: 2rem;
    }

    .student-result {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 0rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .student-result:hover {
        border-color: var(--primary-color);
        box-shadow: 0 5px 15px rgba(55, 119, 176, 0.1);
    }
    </style>
    <!-- GOOGLE FONT MODERNE (à mettre une seule fois dans ton layout principal) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    .card-medical {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
    }

    .label-medical {
        font-weight: 500;
        color: #555;
    }

    .value-medical {
        color: #222;
    }
    </style>
</head>

<div class="pdf-container container-fluid mt-2">
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
            <h1>DOSSIER MEDICAL</h1>
            <div class="subtitle">Service de Santé Universitaire</div>
        </div>
        <!-- Informations étudiant -->
        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Patient(e) :</span>
                    <span
                        class="info-value"><?php echo htmlspecialchars($patient['prenom'].' '.$patient['nom']); ?></span>
                </div>
                <!-- <div class="info-item">
                    <span class="info-label">Type :</span>
                    <span class="badge bg-<?= $patient['type_patient'] === 'personnel' ? 'dark' : 'primary' ?>">
                        <?= ucfirst(htmlspecialchars($patient['type_patient'])) ?>
                    </span>
                </div> -->

                <div class="info-item">
                    <span class="info-label">
                        <?= $patient['type_patient'] === 'personnel' ? 'Matricule :' : 'Numéro :' ?>
                    </span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['numero_identifiant']) ?>
                    </span>
                </div>

                <?php if ($patient['type_patient'] === 'etudiant'): ?>
                <div class="info-item">
                    <span class="info-label">Faculté :</span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['faculte'] ?? 'Non spécifiée') ?>
                    </span>
                </div>
                <?php else: ?>
                <div class="info-item">
                    <span class="info-label">Fonction :</span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['fonction'] ?? 'Non spécifiée') ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="info-label">Service :</span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['service'] ?? 'Non spécifié') ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-4">

            <!-- IDENTIFICATION patient -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-id-card"></i> Section 1 : Identification de l’étudiant</span>
                </div>

                <div class="card-body">

                    <p class="mb-1">
                        <strong>Sexe :</strong>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($patient['sexe'] ?? 'N/A') ?>
                        </span>
                    </p>

                    <p class="mb-1">
                        <strong>Date de naissance :</strong>
                        <?= !empty($patient['date_naissance']) 
            ? date('d/m/Y', strtotime($patient['date_naissance'])) 
            : 'Non renseignée' ?>
                    </p>

                    <?php if ($patient['type_patient'] === 'etudiant'): ?>

                    <p class="mb-1">
                        <strong>Faculté :</strong>
                        <?= htmlspecialchars($patient['faculte'] ?? 'Non renseignée') ?>
                    </p>

                    <p class="mb-1">
                        <strong>Niveau :</strong>
                        <?= htmlspecialchars($patient['niveau_etude'] ?? 'Non renseigné') ?>
                    </p>

                    <?php else: ?>

                    <p class="mb-1">
                        <strong>Fonction :</strong>
                        <?= htmlspecialchars($patient['fonction'] ?? 'Non renseignée') ?>
                    </p>

                    <p class="mb-1">
                        <strong>Service :</strong>
                        <?= htmlspecialchars($patient['service'] ?? 'Non renseigné') ?>
                    </p>

                    <?php endif; ?>

                    <p class="mb-1">
                        <strong>Résidence :</strong>
                        <?= htmlspecialchars($patient['adresse'] ?? 'Non renseignée') ?>
                    </p>

                    <p class="mb-1">
                        <strong>Téléphone :</strong>
                        <?= htmlspecialchars($patient['telephone'] ?? 'Non renseigné') ?>
                    </p>

                    <p class="mb-1">
                        <strong>Statut matrimonial :</strong>
                        <?= htmlspecialchars($patient['statut_matrimonial'] ?? 'Non renseigné') ?>
                    </p>

                    <p class="mb-1">
                        <strong>Orphelin :</strong>
                        Père :
                        <span
                            class="badge bg-<?= ($patient['orphelin_pere'] ?? 'Non') === 'Oui' ? 'danger' : 'secondary' ?>">
                            <?= htmlspecialchars($patient['orphelin_pere'] ?? 'Non') ?>
                        </span>
                        /
                        Mère :
                        <span
                            class="badge bg-<?= ($patient['orphelin_mere'] ?? 'Non') === 'Oui' ? 'danger' : 'secondary' ?>">
                            <?= htmlspecialchars($patient['orphelin_mere'] ?? 'Non') ?>
                        </span>
                    </p>

                    <p class="mb-1">
                        <strong>Personne à mobilité réduite :</strong>
                        <span
                            class="badge bg-<?= ($patient['pmr'] ?? 'Non') === 'Oui' ? 'warning text-dark' : 'success' ?>">
                            <?= htmlspecialchars($patient['pmr'] ?? 'Non') ?>
                        </span>
                    </p>

                </div>

            </div>

            <!-- ANTECEDENTS MEDICAUX -->
            <?php if (($_SESSION['user_role']) == "medecin" ) {?>
            <div class="card mb-3 card-medical">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-notes-medical"></i>
                        Section 3 : Antécédents médicaux
                    </span>
                    <?php if (!empty($dernierAntecedent)): ?>
                    <small class="text-muted text-end">
                        Dernière mise à jour :
                        <?= date('d/m/Y', strtotime($dernierAntecedent['date_enregistrement'])) ?>
                    </small>
                    <?php endif; ?>
                </div>

                <div class="card-body">

                    <?php if (empty($dernierAntecedent)): ?>

                    <!-- Aucun antécédent -->
                    <p class="text-muted text-center mb-1">
                        <i class="fas fa-info-circle"></i>
                        Aucun antécédent médical enregistré.
                    </p>

                    <?php else: ?>

                    <p class="text-muted mb-1">
                        <span class="label-medical">Antécédents médicaux : </span>
                        <span class="value-medical">
                            <?= nl2br(htmlspecialchars($dernierAntecedent['antecedents_medicaux'] ?: 'Néant')) ?>
                        </span>
                    </p>

                    <p class="text-muted mb-1">
                        <span class="label-medical">Antécédents chirurgicaux : </span>
                        <span class="value-medical">
                            <?= nl2br(htmlspecialchars($dernierAntecedent['antecedents_chirurgicaux'] ?: 'Néant')) ?>
                        </span>
                    </p>

                    <p class="text-muted mb-1">
                        <span class="label-medical">Allergies connues :</span>
                        <?php if ($dernierAntecedent['allergies'] === 'Oui'): ?>
                        <span class="badge bg-danger ms-2">Oui</span><br>
                        <small class="text-muted">
                            Précision :
                            <?= htmlspecialchars($dernierAntecedent['allergies_precision'] ?: 'Non précisée') ?>
                        </small>
                        <?php else: ?>
                        <span class="badge bg-success ms-2">Non</span>
                        <?php endif; ?>
                    </p>

                    <p class="text-muted mb-1">
                        <span class="label-medical">Traitements chroniques :</span>
                        <?php if ($dernierAntecedent['traitement_chronique'] === 'Oui'): ?>
                        <span class="badge bg-warning text-dark ms-2">Oui</span><br>
                        <small class="text-muted">
                            Précision :
                            <?= htmlspecialchars($dernierAntecedent['traitement_precision'] ?: 'Non précisée') ?>
                        </small>
                        <?php else: ?>
                        <span class="badge bg-success ms-2">Non</span>
                        <?php endif; ?>
                    </p>

                    <?php endif; ?>

                </div>

                <!-- Action -->
                <div class="card-footer text-end bg-white">
                    <a href="ajouter_antecedent.php?numero=<?= $patient['id'] ?>"
                        class="text-primary small text-decoration-none">
                        <i class="fas fa-plus"></i> Ajouter un antécédent
                    </a>
                </div>
            </div>
            <?php } ?>


            <!-- TUTEUR / CONTACT URGENCE -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-phone-alt"></i> Tuteur / Personne à prévenir</span>
                </div>

                <div class="card-body">
                    <p><strong>Nom : </strong> <?= htmlspecialchars($patient['contact_urgence_nom'] ?? 'Aucune') ?></p>
                    <p><strong>Téléphone : </strong>
                        <?= htmlspecialchars($patient['contact_urgence_telephone'] ?? 'Aucune') ?>
                    </p>
                    <p><strong>Profession : </strong>
                        <?= htmlspecialchars($patient['contact_urgence_profession'] ?? 'Aucune') ?></p>
                </div>
            </div>

        </div>

        <div class="col-md-8">
            <!-- SOINS INFIRMIERS -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-nurse"></i> Section 2 : Soins infirmiers</span>
                    <a href="../infirmiers/consultations?id=<?= $id_patient ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Soins
                    </a>
                </div>

                <div class="card-body p-0">

                    <table class="table table-bordered table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>TA</th>
                                <th>T (°C)</th>
                                <th>Poids(kg)</th>
                                <th>FC</th>
                                <th>FR</th>
                                <th>Actes réalisés</th>
                                <th class="text-center">Detail(s)</th>
                                <th class="text-center">action(s)</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($soins)): ?>
                            <tr>
                                <td colspan="15" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle"></i>
                                    Aucun soin infirmier enregistré pour le moment.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($soins as $s): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($s['date_soin'])) ?></td>
                                <td><?= htmlspecialchars($s['tension'] ?: '—') ?></td>
                                <td><?= $s['temperature'] ? htmlspecialchars($s['temperature']) : '—' ?></td>
                                <td><?= $s['poids'] ? htmlspecialchars($s['poids']) : '—' ?></td>
                                <td><?= htmlspecialchars($s['fc'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($s['fr'] ?: '—') ?></td>
                                <td style="max-width: 300px;">
                                    <?php if (!empty($s['actes'])): ?>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($s['actes'] as $acte): ?>
                                        <li>
                                            <strong><?= htmlspecialchars($acte['type_acte']) ?></strong>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <a href="../infirmiers/soins_pdf.php?soin=<?= htmlspecialchars($s['id_soin']) ?>&id=<?= htmlspecialchars($id_patient) ?>"
                                        target="_blank" class="text-decoration-underline text-primary">
                                        details
                                    </a>
                                </td>
                                <td class="text-center">
                                    <?php
                                        $dateSoin = strtotime($s['date_soin']);
                                        $now = time();
                                        $difference = $now - $dateSoin;
                                        $modifiable = ($difference <= 86400); // 24H = 86400 sec
                                    ?>
                                    <?php if ($modifiable && $s['id_infirmier'] == $_SESSION['id_user']): ?>
                                    <a href="../infirmiers/consultations?id=<?= $id_patient ?>&id_soin=<?= htmlspecialchars($s['id_soin']) ?>"
                                        class="text-decoration-underline text-primary">
                                        Modifier
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted text-decoration-underline">Modifier</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>


                </div>
            </div>

            <br>
            <!-- CONSULTATIONS -->
            <?php if (($_SESSION['user_role']) == "medecin" ) {?>
            <div class="card mb-4 card-medical">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-stethoscope"></i>
                        Section 4 : Consultations médicales
                    </span>

                    <a href="add_consultation.php?id=<?= $id_patient ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Nouvelle consultation
                    </a>
                </div>

                <div class="card-body p-0">

                    <table class="table table-hover table-bordered align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Motif</th>
                                <th>Diagnostic</th>
                                <th>Conduite</th>
                                <th class="text-center">Prescription</th>
                                <th class="text-center">Action(s)</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($consultations)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Aucune consultation médicale enregistrée.
                                </td>
                            </tr>

                            <?php else: ?>
                            <?php foreach ($consultations as $c): ?>

                            <tr>

                                <!-- DATE -->
                                <td>
                                    <?= date('d/m/Y', strtotime($c['date_consultation'])) ?>
                                </td>

                                <!-- MOTIF -->
                                <td>
                                    <?= htmlspecialchars(substr($c['motif'],0,40)) ?>
                                </td>

                                <!-- DIAGNOSTIC -->
                                <td>
                                    <?= htmlspecialchars(substr($c['diagnostic'],0,40)) ?>
                                </td>

                                <!-- CONDUITE -->
                                <td>
                                    <?= htmlspecialchars(substr($c['conduite_a_tenir'],0,35)) ?>
                                </td>


                                <!-- ================= PRESCRIPTION ================= -->
                                <td class="text-center">

                                    <?php if (!empty($c['id_prescription'])): ?>

                                    <a href="prescription.php?id=<?= $c['id_prescription'] ?>&id_consultation=<?= $c['id_consultation'] ?>&id_patient=<?= $id_patient ?>"
                                        class="text-decoration-underline text-success">
                                        Voir
                                    </a>

                                    <?php else: ?>

                                    <a href="prescription.php?id_consultation=<?= $c['id_consultation'] ?>&id_patient=<?= $id_patient ?>"
                                        class="text-decoration-underline">
                                        Ajouter
                                    </a>

                                    <?php endif; ?>

                                </td>


                                <!-- ================= ACTION ================= -->
                                <td class="text-center">

                                    <a href="consultation_detail.php?id=<?= $c['id_consultation'] ?>"
                                        class="text-decoration-underline">
                                        Voir
                                    </a> |
                                    <?php
                                        $dateC = strtotime($c['date_consultation']);
                                        $now = time();
                                        $difference = $now - $dateC;
                                        $modifiable_2 = ($difference <= 86400); // 24H = 86400 sec
                                    ?>
                                    <?php if ($modifiable_2 && $c['id_user'] == $_SESSION['id_user']): ?>
                                    <a href="add_consultation.php?id_consultation=<?= $c['id_consultation'] ?>&id=<?= $id_patient ?>"
                                        class="text-decoration-underline text-warning">
                                        Modifier
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted text-decoration-underline">Modifier</span>
                                    <?php endif; ?>

                                </td>

                            </tr>

                            <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>
                    </table>

                </div>
            </div>
            <?php } ?>


        </div>
    </div>

</div>
<?php include_once('../../footer.php'); ?>
</body>

</html>