<?php
session_start();
require_once ('../traitement/fonction.php');

$roles_autorises = ['medecin', 'secretaire', 'infirmier', 'dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../index.php');
    exit();
}
if ($_SESSION['mdp'] == 'default') {
    header('Location: /medical01/profils/update_password.php');
    exit();
}

$conn = $connexion;

// ==============================
// FILTRE MOIS / ANNEE
// ==============================

$annee = $_GET['annee'] ?? date('Y');
$mois = $_GET['mois'] ?? 'all';

$whereClause = "YEAR(date_creation) = '$annee'";

if ($mois !== 'all') {
    $whereClause .= " AND MONTH(date_creation) = '$mois'";
}
// ==============================
// TOTAL PATIENTS
// ==============================

$sqlPatients = "SELECT COUNT(*) as total 
                FROM medical_patients 
                WHERE YEAR(created_at) = '$annee'";

if ($mois !== 'all') {
    $sqlPatients .= " AND MONTH(created_at) = '$mois'";
}

$totalPatients = mysqli_fetch_assoc(mysqli_query($connexion, $sqlPatients))['total'];

// ==============================
// TOTAL ETUDIANTS
// ==============================

$sqlEtudiants = "SELECT COUNT(*) as total 
                 FROM medical_patients 
                 WHERE type_patient = 'etudiant'
                 AND YEAR(created_at) = '$annee'";

if ($mois !== 'all') {
    $sqlEtudiants .= " AND MONTH(created_at) = '$mois'";
}

$totalEtudiants = mysqli_fetch_assoc(mysqli_query($connexion, $sqlEtudiants))['total'];

// ==============================
// TOTAL PERSONNELS
// ==============================

$sqlPersonnels = "SELECT COUNT(*) as total 
                  FROM medical_patients 
                  WHERE type_patient = 'personnel'
                  AND YEAR(created_at) = '$annee'";

if ($mois !== 'all') {
    $sqlPersonnels .= " AND MONTH(created_at) = '$mois'";
}

$totalPersonnels = mysqli_fetch_assoc(mysqli_query($connexion, $sqlPersonnels))['total'];

// ==============================
// TOTAL ORIENTATIONS
// ==============================

$sqlTotalOrientation = "SELECT COUNT(*) as total
                        FROM medical_orientation o
                        JOIN medical_patients p ON o.id_patient=p.id
                        WHERE YEAR(o.date_sys) = '$annee'";

if ($mois !== 'all') {
    $sqlTotalOrientation .= " AND MONTH(date_sys) = '$mois'";
}

$totalOrientation = mysqli_fetch_assoc(mysqli_query($connexion, $sqlTotalOrientation))['total'];

// ==============================
// TOTAL CONSULTATIONS
// ==============================

$sqlConsult = "SELECT COUNT(*) as total 
               FROM medical_consultations 
               WHERE YEAR(date_consultation) = '$annee'";

if ($mois !== 'all') {
    $sqlConsult .= " AND MONTH(date_consultation) = '$mois'";
}

$resultConsult = mysqli_query($connexion, $sqlConsult);
$totalConsult = mysqli_fetch_assoc($resultConsult)['total'];

// ==============================
// TOTAL SOINS INFIRMIERS
// ==============================

$sqlSoins = "SELECT COUNT(*) as total 
             FROM medical_soins_infirmiers s
             JOIN medical_patients p ON s.id_patient=p.id
             WHERE YEAR(s.created_at) = '$annee'";

if ($mois !== 'all') {
    $sqlSoins .= " AND MONTH(s.created_at) = '$mois'";
}

$resultSoins = mysqli_query($connexion, $sqlSoins);
$totalSoins = mysqli_fetch_assoc($resultSoins)['total'];

// ==============================
// ORIENTATION PAR LIBELLE
// ==============================

$sqlOrientation = "SELECT libelle, COUNT(*) as total 
                   FROM medical_orientation o
                   JOIN medical_patients p ON o.id_patient=p.id
                   WHERE YEAR(o.date_sys) = '$annee'";

if ($mois !== 'all') {
    $sqlOrientation .= " AND MONTH(o.date_sys) = '$mois'";
}

$sqlOrientation .= ' GROUP BY libelle ORDER BY total DESC';

$resultOrientation = mysqli_query($connexion, $sqlOrientation);

$labelsOrientation = [];
$dataOrientation = [];

$resultOrientationChart = mysqli_query($connexion, $sqlOrientation);

while ($row = mysqli_fetch_assoc($resultOrientationChart)) {
    $labelsOrientation[] = $row['libelle'];
    $dataOrientation[] = $row['total'];
}

// ==============================
// STATISTIQUES SUPPLEMENTAIRES
// ==============================

// Évolution mensuelle des consultations
$sqlEvolution = "SELECT 
                    MONTH(date_consultation) as mois,
                    COUNT(*) as total
                 FROM medical_consultations
                 WHERE YEAR(date_consultation) = '$annee'
                 GROUP BY MONTH(date_consultation)
                 ORDER BY mois";
$resultEvolution = mysqli_query($connexion, $sqlEvolution);

// Top 5 des motifs de consultation
$sqlMotifs = "SELECT 
                motif,
                COUNT(*) as total
              FROM medical_consultations
              WHERE YEAR(date_consultation) = '$annee'
              GROUP BY motif
              ORDER BY total DESC
              LIMIT 5";
$resultMotifs = mysqli_query($connexion, $sqlMotifs);

?>

<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../head.php'); ?>

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .stat-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 10px;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .card-icon {
        font-size: 2.5rem;
        opacity: 0.7;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
    }

    .chart-container {
        height: 300px;
        margin-top: 20px;
    }

    .card {
        border: 1px solid #e4e6ef;
        border-radius: 8px;
        box-shadow: none;
        transition: all 0.2s ease;
        background: #fff;
    }

    .card:hover {
        transform: translateY(-2px);
        border-color: #d0d5dd;
    }

    .card-body {
        padding: 18px;
    }

    .card h2 {
        font-size: 28px;
    }

    .card {
        border-radius: 12px;
        transition: 0.3s ease;

    }

    .card:hover {
        transform: translateY(-3px);
    }

    .card h2 {
        font-size: 28px;
    }

    .stat-box {
        border-left: 4px solid #0d6efd;
    }
    </style>
</head>

<body>

    <div class="container mt-4 px-4">

        <!-- EN-TÊTE -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary">
                <i class="bi bi-activity me-2"></i>Tableau de Bord Médical
            </h3>
            <div>
                <span class="badge bg-primary p-2">
                    <i class="bi bi-calendar3 me-1"></i>Période :
                    <?= $mois != 'all' ? "Mois $mois " : "Toute l'année " ?><?= $annee ?>
                </span>
            </div>
        </div>

        <!-- FILTRE AMÉLIORÉ -->
        <div class="filter-section">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Année</label>
                    <select name="annee" class="form-select">
                        <?php
                        for ($i = date('Y'); $i >= 2025; $i--) {
                            $selected = ($annee == $i) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Mois</label>
                    <select name="mois" class="form-select">
                        <option value="all">Toute l'année</option>
                        <?php
                        $mois_noms = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($mois == $m) ? 'selected' : '';
                            echo "<option value='$m' $selected>" . $mois_noms[$m - 1] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="?" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- SECTION GRAPHIQUES ET TABLEAUX -->
        <div class="row g-4">
            <!-- Tableau des orientations -->
            <!-- <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-diagram-3 me-2"></i>Récapitulatif des Orientations
                        </h6>
                        <span class="badge bg-secondary">
                            Total : <?= mysqli_num_rows($resultOrientation) ?> libellés
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Libellé</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalOrientations = 0;
                                    $orientations = [];

                                    // Premier passage pour calculer le total
                                    $resultOrientationCount = mysqli_query($connexion, $sqlOrientation);
                                    while ($row = mysqli_fetch_assoc($resultOrientationCount)) {
                                        $totalOrientations += $row['total'];
                                        $orientations[] = $row;
                                    }

                                    // Deuxième passage pour afficher
                                    foreach ($orientations as $row) {
                                        $pourcentage = $totalOrientations > 0 ? round(($row['total'] / $totalOrientations) * 100, 1) : 0;
                                        ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['libelle']) ?></td>
                                        <td class="text-center"><?= number_format($row['total'], 0, ',', ' ') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?= $pourcentage ?>%</span>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <?php if (empty($orientations)) { ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="bi bi-info-circle me-2"></i>Aucune orientation pour cette période
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total général</th>
                                        <th class="text-center"><?= number_format($totalOrientations, 0, ',', ' ') ?>
                                        </th>
                                        <th class="text-center">100%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div> -->

            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-bar-chart me-2"></i>Orientations par type
                        </h6>
                    </div>

                    <div class="card-body">
                        <div style="height:350px;">
                            <canvas id="chartOrientation"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br><br>
        <!-- CARTES STATISTIQUES -->
        <div class="row g-4 mb-4">

            <!-- Total Patients -->
            <div class="col-md-3">
                <div class="card stat-box border-red">
                    <div class="card-body">
                        <h6 class="text-muted">Total Fiche(s)</h6>
                        <h2 class="fw-bold text-primary"><?= number_format($totalPatients) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Étudiants -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Étudiants</h6>
                        <h2 class="fw-bold text-success"><?= number_format($totalEtudiants) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Personnels -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Personnels</h6>
                        <h2 class="fw-bold text-warning"><?= number_format($totalPersonnels) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Consultations -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Consultations</h6>
                        <h2 class="fw-bold text-danger"><?= number_format($totalConsult) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Soins -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Soins Infirmiers</h6>
                        <h2 class="fw-bold text-info"><?= number_format($totalSoins) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Orientations -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Orientations</h6>
                        <h2 class="fw-bold text-secondary"><?= number_format($totalOrientation) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Ratio -->
            <div class="col-md-3">
                <div class="card stat-box">
                    <div class="card-body">
                        <h6 class="text-muted">Consultations / Patient</h6>
                        <h2 class="fw-bold">
                            <?= $totalPatients > 0 ? round($totalConsult / $totalPatients, 2) : 0 ?>
                        </h2>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script>
    // Graphique d'évolution
    const ctx = document.getElementById('chartEvolution').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($moisData) ?>,
            datasets: [{
                label: 'Nombre de consultations',
                data: <?= json_encode($consultData) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
    <script>
    const ctxOrientation = document.getElementById('chartOrientation');

    new Chart(ctxOrientation, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsOrientation) ?>,
            datasets: [{
                label: 'Nombre d’orientations',
                data: <?= json_encode($dataOrientation) ?>,
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b',
                    '#858796'
                ],
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
    <?php include_once ('../footer.php'); ?>
</body>

</html>