<?php
session_start();
require_once ('../../traitement/fonction.php');

$roles_autorises = ['medecin', 'secretaire', 'infirmier', 'dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}
if ($_SESSION['mdp'] == "default") {
        header('Location: /medical01/profils/update_password.php');
        exit();
}
$role = $_SESSION['user_role'];

$patients = [];
$donneesApi = null;
$personnel = null;

if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);

    // 1 Recherche locale dans medical_patients
    $patients = searchPatients($connexion, $search);

    if (empty($patients)) {
        // 2 Recherche API étudiant
        $donneesApi = getDonneesEtudiant($search);
        if (!empty($donneesApi)) {
            $anneeEtudiant = $donneesApi['annee'];  // ex: 2024_2025
            $anneeFin = intval(substr($anneeEtudiant, -4));  // 2025
            $annee = date('Y');
            $anneeCourante = intval(substr($annee, -4));

            if (($anneeCourante - $anneeFin) >= 2) {
                $_SESSION['error'] = "Année académique invalide (ancien étudiant - $anneeEtudiant)";

                header('Location: ' . $_SERVER['PHP_SELF'] . '?search=' . urlencode($search));
                exit();
            }
        }

        if (!$donneesApi) {
            // 3 Recherche dans table personnels locale
            $personnel = searchPersonnel($connexion_2, $search);
        }
    }
}
?>
<?php

// var_dump($donneesApi);
// Tableau des spécialités
$specialites = [
    'Generaliste',
    'Cardiologie;Echo Coeur',
    'Gynécologie',
    'Infirmier',
    'Echographie',
    'Ophtalmologiste',
    'Neurologue',
    'Psychologue',
    'Diabétologue',
    'Orthopediste',
    'Gastrologie',
    'Pediatrie',
    'Chirurgie',
    'ANALYSE LABO',
    'ORL avec P.C',
    'ORL',
    'Urologue',
    'FIBRO FOGD - Rectosigmoide - Anu restoscopie',
    // HALD
    'Dermatologie : H.A.L.D',
    'Cardio : H.A.L.D',
    'Medecine Interne : H.A.L.D',
    'Rhumatologie : H.A.L.D'
];

// Séparer HALD et non-HALD
$normal = [];
$hald = [];

foreach ($specialites as $spec) {
    if (stripos($spec, 'H.A.L.D') !== false) {
        $hald[] = $spec;
    } else {
        $normal[] = $spec;
    }
}

// Trier alphabétiquement les normaux
sort($normal, SORT_NATURAL | SORT_FLAG_CASE);

// Fusionner en mettant HALD à la fin
$specialites_final = array_merge($normal, $hald);
?>

<!DOCTYPE html>
<html lang="fr">
<?php
include_once ('../../head.php');
?>

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
    .search-container {
        max-width: 950px;
        margin: 1rem auto;
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
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    .student-result:hover {
        border-color: var(--primary-color);
        box-shadow: 0 10px 15px rgba(55, 119, 176, 0.1);
    }

    .btn-access {
        background-color: var(--secondary-color);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
        border: none;
        transition: all 0.3s;
    }

    .btn-access:hover {
        background-color: #3d8b40;
    }

    .orientation-card {
        background: #f9fbfd;
        border: 1px solid #e3e9f0;
        border-radius: 12px;
        padding: 15px;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }

    .orientation-title {
        font-size: 14px;
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 10px;
    }

    .orientation-card .form-check-label {
        font-size: 14px;
        cursor: pointer;
    }

    .btn-orient {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #fff;
        border-radius: 20px;
        padding: 5px 18px;
        font-size: 15px;
    }

    .btn-orient:hover {
        opacity: 0.9;
    }
    </style>
</head>
<!-- Navbar simplifiée -->

<body>
    <div class="search-container">
        <div class="search-card">
            <h3 class="text-center mb-4">
                <i class="fas fa-search mr-2"></i>Rechercher un Patient
            </h3>
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
            <form action="" method="GET" class="mb-4">
                <div class="input-group input-group-lg">
                    <input type="text" name="search" class="form-control"
                        placeholder="Nom, prénom ou numéro étudiant..."
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" required>
                    <div class="input-group-lg">
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </div>
                </div>
            </form>

            <!-- Résultats de recherche -->
            <div id="results">
                <?php if (!empty($patients)): ?>
                <?php foreach ($patients as $patient): ?>
                <div class="student-result border-primary">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="text-primary mb-1">
                                <i class="fas fa-user-graduate"></i>
                                <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?>
                            </h5>

                            <p class="mb-1"><strong>N° Carte :</strong>
                                <?= htmlspecialchars($patient['numero_identifiant']) ?></p>
                            <p class="mb-1"><strong>Date naissance :</strong>
                                <?= htmlspecialchars((new DateTime($patient['date_naissance']))->format('d/m/Y')) ?></p>
                            <p class="mb-1"><strong>Email :</strong> <?= htmlspecialchars($patient['email']) ?></p>
                            <p class="mb-1"><strong>Téléphone :</strong> <?= htmlspecialchars($patient['telephone']) ?>
                            </p>
                            <p class="mb-1"><strong>Groupe sanguin :</strong>
                                <?= htmlspecialchars($patient['groupe_sanguin']) ?></p>

                        </div>

                        <div class="col-md-4 text-end">
                            <?php if ($role === 'secretaire' && $_SESSION['profile_2'] === 'accueil'): ?>
                            <form action="../secretaire/orienter_etudiant" method="POST">
                                <input type="hidden" name="id_patient" value="<?= (int) $patient['id'] ?>">

                                <div class="mt-3">
                                    <label class="form-label small text-muted">
                                        Orientation médicale <span class="text-danger">*</span>
                                    </label>

                                    <!-- Select simple, pas multiple -->
                                    <select name="libelle" style="font-size:17px;" class="form-select form-select-lg" required>
                                        <option value="">-- Choisir une spécialité --</option>
                                        <?php foreach ($specialites_final as $index => $spec): ?>
                                        <option value="<?= htmlspecialchars($spec) ?>">
                                            <?= ($index + 1) . '. ' . htmlspecialchars($spec) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Bouton -->
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-lg btn-orient">
                                        <i class="fas fa-paper-plane"></i> Orienter
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>


                            <?php if ($role === 'medecin'): ?>
                            <a href="dossier_medical?id=<?= (int) $patient['id'] ?>" class="btn btn-lg btn-success mb-2">
                                <i class="fas fa-folder-open"></i> Accéder au dossier
                            </a>
                            <?php endif; ?>
                            <?php if ($role === 'dba'): ?>
                            <a href="../secretaire/ajouter_etudiant.php?id=<?= (int) $patient['id'] ?>"
                                class="btn btn-lg btn-warning mb-2">
                                <i class="fas fa-pencil"></i> Modifier
                            </a>
                            <?php endif; ?>

                            <?php if ($role === 'infirmier'): ?>
                            <a href="dossier_medical?id=<?= (int) $patient['id'] ?>" class="btn btn-lg btn-primary">
                                <i class="fas fa-notes-medical"></i> Consultation
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php elseif ($donneesApi !== null): ?>
                <div class="student-result border-warning">
                    <h5 class="text-warning">
                        <i class="fas fa-user-plus"></i>
                        <?= htmlspecialchars($donneesApi['prenom'] . ' ' . $donneesApi['nom']) ?>
                    </h5>

                    <p><strong>N° Carte :</strong> <?= htmlspecialchars($donneesApi['numero_carte']) ?></p>
                    <p><strong>Faculté :</strong> <?= htmlspecialchars($donneesApi['faculte']) ?></p>
                    <p><strong>Niveau :</strong> <?= htmlspecialchars($donneesApi['niveau_formation']) ?></p>

                    <?php if ($role === 'secretaire'): ?>
                    <a href="../secretaire/ajouter_etudiant.php?numero=<?= urlencode($_GET['search']) ?>"
                        class="btn btn-lg btn-warning">
                        <i class="fas fa-plus"></i> Ajouter Patient
                    </a>
                    <?php else: ?>
                    <p class="text-muted mt-2">
                        <i class="fas fa-lock"></i> Ajout réservé à la secrétaire
                    </p>
                    <?php endif; ?>
                </div>
                <?php elseif ($personnel !== null): ?>
                <div class="student-result border-warning">
                    <h5 class="text-warning">
                        <i class="fas fa-user-plus"></i>
                        <?= htmlspecialchars($personnel['prenom'] . ' ' . $personnel['nom']) ?>
                    </h5>

                    <p><strong>N° Carte :</strong> <?= htmlspecialchars($personnel['matricule']) ?></p>
                    <p><strong>Lieu Naissance :</strong> <?= htmlspecialchars($personnel['lieu_naiss']) ?></p>
                    <p><strong>Sexe :</strong> <?= htmlspecialchars($personnel['sexe']) ?></p>

                    <?php if ($role === 'secretaire'): ?>
                    <a href="../secretaire/ajouter_etudiant.php?numero=<?= urlencode($_GET['search']) ?>"
                        class="btn btn-lg btn-warning">
                        <i class="fas fa-plus"></i> Ajouter Patient
                    </a>
                    <?php else: ?>
                    <p class="text-muted mt-2">
                        <i class="fas fa-lock"></i> Ajout réservé à la secrétaire
                    </p>
                    <?php endif; ?>
                </div>
                <?php elseif (!empty($_GET['search'])): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-times-circle"></i>
                    Aucun Patient trouvé
                </div>
                <?php endif; ?>


            </div>
        </div>
    </div>

    <!-- Version SIMPLE sans AJAX -->
    <script>
    // Fonction simple pour demander l'accès (redirection directe)
    function requestAccessDirect(studentId) {
        if (confirm('Un code sera envoyé à l\'étudiant pour autoriser l\'accès. Continuer?')) {
            // Rediriger vers verify_code.php qui s'occupera d'envoyer le code
            window.location.href = 'verify_code.php?id=' + studentId;
        }
    }
    </script>
    <?php include_once ('../../footer.php');?>
</body>

</html>