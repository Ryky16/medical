<?php
session_start();
require_once ('../../traitement/fonction.php');

$roles_autorises = ['dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

$dateRecherche = $_GET['date'] ?? null;
$users = getMedicalUsers($connexion, $dateRecherche);

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
    body {
        background: #eef2f7;
    }

    /* Style document médical */
    .document-container {
        background: white;
        border-radius: 10px;
        padding: 30px;
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
    td strong{
        text-transform: capitalize;
    }

    td a:hover i {
        transform: scale(1.15);
    }
    </style>
</head>

<body>

    <div class="container-fluid mt-4">

        <div class="document-container">
            <!-- Messages -->
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- HEADER DOCUMENT -->
            <div class="document-header d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="document-title">
                        Registre des Utilisateurs
                    </h4>
                    <small class="text-muted">
                        Liste des comptes créés
                    </small>
                </div>

                <!-- Recherche par date -->
                <div class="d-flex gap-2">
                    <a href="add_user" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> ajouter
                    </a>
                </div>

            </div>

            <!-- TABLEAU -->
            <div class="table-responsive">

                <table id="tableOrientation" class="table table-bordered table-hover">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom & Prénom</th>
                            <th>Username</th>
                            <th>Téléphone</th>
                            <th>Sexe</th>
                            <th>Rôle</th>
                            <th>Profil</th>
                            <th>Mdp</th>
                            <th>Statut</th>
                            <th>Dernière Connexion</th>
                            <th>Action(s)</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $counter = 1;
                        foreach ($users as $u): ?>

                        <tr>

                            <td>
                                <?= $counter++ ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                </strong>
                            </td>

                            <td><?= htmlspecialchars($u['username']) ?></td>

                            <td><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>

                            <td><?= ucfirst(htmlspecialchars($u['sexe'] ?? '-')) ?></td>

                            <td>
                                <span class="badge bg-primary">
                                    <?= ucfirst(htmlspecialchars($u['profile_1'] ?? 'User')) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-info text-dark ">
                                    <?= ucfirst(htmlspecialchars($u['profile_2'] ?? '-')) ?>
                                </span>
                            </td>
                            <td>
                                <span class="">
                                    <?= ucfirst(htmlspecialchars($u['mdp'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="text-center">

                                <a href="toggle_user.php?id=<?= $u['id'] ?>"
                                    onclick="return confirm('Changer le statut de cet utilisateur ?')"
                                    style="text-decoration:none;">

                                    <span class="badge 
                                        <?= $u['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $u['is_active'] ? 'Actif' : 'Inactif' ?>
                                    </span>

                                </a>

                            </td>
                            <td>
                                <?= $u['last_login']
                                    ? date('d/m/Y H:i', strtotime($u['last_login']))
                                    : '<span class="text-muted">NULL</span>' ?>
                            </td>
                            <td>
                                <a href="add_user?id=<?= htmlspecialchars($u['id']) ?>"
                                    class="text_decoration-underline text-danger">modifier</a>
                            </td>

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