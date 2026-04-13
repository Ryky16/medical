<?php
session_start();
require_once ('../../traitement/fonction.php');

$roles_autorises = ['medecin', 'secretaire', 'infirmier'];

//require_once ('../../activite.php');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}
if ($_SESSION['mdp'] == 'default') {
    header('Location: /medical01/profils/update_password.php');
    exit();
}
$dateRecherche = $_GET['date'] ?? date('Y-m-d');
$results = getOrientationsParDate($connexion, $dateRecherche, $_SESSION['profile_2']);

$orientations = $results['data'];
$stats = $results['stats'];
?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../../head.php'); ?>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- jQuery (OBLIGATOIRE) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <style>
    /* Style document médical */
    .document-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }

    /* Header style PDF */
    .document-header {
        border-bottom: 2px solid #3777B0;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    .document-title {
        font-weight: bold;
        color: #3777B0;
    }

    /* Tableau style dossier */
    .table thead {
        background: #3777B0;
        color: white;
        font-size: 13px;
    }

    .badge-specialite {
        background: #e3f2fd;
        color: #0d47a1;
        margin-right: 5px;
        padding: 5px 10px;
    }

    .dataTables_paginate .pagination {
        justify-content: center;
    }

    .dataTables_paginate .page-link {
        color: #3777B0;
    }

    .dataTables_paginate .active .page-link {
        background-color: #3777B0;
        border-color: #3777B0;
    }

    td a i {
        font-size: 1.1rem;
        transition: transform 0.15s ease;
    }

    td a:hover i {
        transform: scale(1.15);
    }
    </style>
</head>

<body>

    <div class="container-fluid mt-2">

        <div class="document-container">
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

            <!-- HEADER DOCUMENT -->
            <div class="document-header d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="document-title">
                        Registre des Orientations Médicales 
                    </h4>
                    <small class="text-muted">
                        Consultation journalière - <span style="font-size:20px;color:red;">Total en_entente : <?= $stats['en_attente'] ?> </span>
                    </small>
                </div>

                <!-- Recherche par date -->
                <form method="GET" class="d-flex gap-2">
                    <input type="date" name="date" value="<?= $dateRecherche ?>" class="form-control form-control-sm">

                    <button class="btn btn-primary btn-sm">
                        Rechercher
                    </button>
                </form>

            </div>

            <!-- TABLEAU -->
            <div class="table-responsive">

                <table id="tableOrientation" class="table table-bordered table-hover">

                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Patient(e)</th>
                            <th>Numéro Carte</th>
                            <th>Télèphone</th>
                            <th>Spécialité</th>
                            <th>Sexe</th>
                            <th>Type</th>
                            <th>Structure</th>
                            <!-- <th>Orienté par</th> -->
                            <?php if (($_SESSION['user_role'] == 'secretaire' && $_SESSION['profile_2'] == 'accueil')) { ?>
                            <th>Imprimer</th>
                            <?php } ?>
                            <th>Statut</th>
                            <?php if (($_SESSION['user_role'] == 'infirmier' || $_SESSION['user_role'] == 'medecin')) { ?>
                            <th>consultation</th>
                            <?php } ?>
                            <?php if (($_SESSION['user_role'] == 'secretaire' || $_SESSION['user_role'] == 'medecin') && ($_SESSION['profile_2'] !== 'accueil')) { ?>
                            <th>Action(s)</th>
                            <?php } ?>

                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($orientations as $o): ?>

                        <tr>

                            <td>
                                <?= date('H:i', strtotime($o['date_sys'])) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($o['prenom'] . ' ' . $o['nom']) ?>
                                </strong>
                            </td>

                            <td><?= htmlspecialchars($o['numero_identifiant']) ?></td>
                            <td><?= htmlspecialchars($o['telephone']) ?></td>

                            <td>
                                <?php
                                $specs = explode(',', $o['libelle']);
                                foreach ($specs as $sp):
                                    ?>
                                <span class="badge badge-specialite">
                                    <?= trim($sp) ?>
                                </span>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($o['sexe']) ?></td>
                            <td><?= htmlspecialchars($o['type_patient']) ?></td>
                            <td>
                                <?= htmlspecialchars(($o['type_patient'] ?? '') === 'etudiant' ? ($o['faculte'] ?? '') : ($o['fonction'] ?? '')) ?>
                            </td>
                            <!-- <td>
                                 //htmlspecialchars($o['user_prenom'].' '.$o['user_nom']); 
                            </td> -->
                            <?php if (($_SESSION['user_role'] == 'secretaire' && $_SESSION['profile_2'] == 'accueil')) { ?>
                            <td><a href="orienter_pdf?id=<?= $o['id'] ?>" class="text-decoration-underline" target="_blank">Imprimer</a></td>
                            <?php } ?>

                            <td>
                                <span class="badge 
                                <?= $o['statut'] === 'valide'
                                    ? 'bg-success'
                                    : ($o['statut'] === 'annule' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= ucfirst($o['statut']) ?>
                                </span>
                            </td>
                            <?php if (($_SESSION['user_role'] == 'infirmier' || $_SESSION['user_role'] == 'medecin')) { ?>
                            <td class="text-center align-middle">

                                <?php if ($o['statut'] === 'valide'): ?>

                                <a href="../medecin/dossier_medical?id=<?= $o['patient_id'] ?>" class="text-info">
                                    consulter
                                </a>

                                <?php else: ?>

                                <a href="action_orientation.php?action=valide&id=<?= $o['id'] ?>&patient_id=<?= $o['patient_id'] ?>&redirect=dossier"
                                    class="text-info" onclick="return confirm('Accéder au dossier ?')">
                                    consulter
                                </a>

                                <?php endif; ?>

                            </td>
                            <?php } ?>
                            <?php if (($_SESSION['user_role'] == 'secretaire' || $_SESSION['user_role'] == 'medecin') && ($_SESSION['profile_2'] !== 'accueil')) { ?>
                            <td class="text-center align-middle">

                                <?php if ($o['statut'] === 'en_attente'): ?>

                                <div class="d-flex justify-content-center align-items-center gap-2 small">

                                    <!-- Valider -->
                                    <a href="action_orientation.php?id=<?= $o['id'] ?>&action=valide"
                                        class="text-success text-decoration-underline" title="Valider l’orientation"
                                        onclick="return confirm('Valider cette orientation ?')">
                                        <i class="bi bi-check-circle-fill">valider</i>
                                    </a>

                                    <span class="text-muted">|</span>

                                    <!-- Annuler -->
                                    <a href="action_orientation.php?id=<?= $o['id'] ?>&action=annule"
                                        class="text-danger text-decoration-underline" title="Annuler l’orientation"
                                        onclick="return confirm('Annuler cette orientation ?')">
                                        <i class="bi bi-x-circle-fill">annulé</i>
                                    </a>

                                </div>

                                <?php else: ?>

                                <span class="text-muted small">—</span>

                                <?php endif; ?>

                            </td>
                            <?php } ?>
                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#tableOrientation').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json"
            },
            pageLength: 25,
            lengthChange: true,
            searching: true,
            paging: true,
            info: true,
            ordering: false,
            dom: 'lfrtip'
        });
    });
    </script>

    <?php include_once ('../../footer.php'); ?>
</body>

</html>