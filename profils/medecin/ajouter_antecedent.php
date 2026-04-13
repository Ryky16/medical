<?php
session_start();
require_once ('../../traitement/fonction.php');

$roles_autorises = ['medecin', 'infirmier'];

// Vérifier connexion médecin
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}
if ($_SESSION['mdp'] == "default") {
        header('Location: /medical01/profils/update_password.php');
        exit();
}
// Récupérer étudiant
$id_patient = $_GET['numero'] ?? '';
$donneesApi = null;

if (!empty($id_patient)) {
    $donneesApi = getPatientById($connexion, $id_patient);;

    if ($donneesApi === null) {
        $_SESSION['error'] = "Étudiant non trouvé via l'API UCAD";
        header('Location: recherche.php');
        exit();
    }
}

// Traitement enregistrement antécédents
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ===============================
       TRAITEMENTS CHRONIQUES
    =============================== */

    $traitement_precision = '';

    if (($_POST['traitement_chronique'] ?? 'Non') === 'Oui') {
        $liste_traitements = [];

        // checkbox sélectionnées
        if (!empty($_POST['traitements']) && is_array($_POST['traitements'])) {
            $liste_traitements = $_POST['traitements'];
        }

        // autre traitement
        if (!empty($_POST['traitement_autre'])) {
            $liste_traitements[] = trim($_POST['traitement_autre']);
        }

        // transformation tableau → texte
        $traitement_precision = implode(', ', $liste_traitements);
    }

    /* ===============================
       DONNÉES À ENREGISTRER
    =============================== */

    $data = [
        'id_patient' => intval($_POST['id_patient']),
        'antecedents_medicaux' =>
            trim($_POST['antecedents_medicaux'] ?? ''),
        'antecedents_chirurgicaux' =>
            trim($_POST['antecedents_chirurgicaux'] ?? ''),
        'allergies' =>
            $_POST['allergies'] ?? 'Non',
        'allergies_precision' =>
            ($_POST['allergies'] === 'Oui')
                ? trim($_POST['allergies_precision'])
                : '',
        'traitement_chronique' =>
            $_POST['traitement_chronique'] ?? 'Non',
        'traitement_precision' =>
            $traitement_precision
    ];

    $result = addAntecedents($connexion, $data);

    if ($result === true) {
        $_SESSION['success'] =
            'Nouvelle fiche d’antécédents enregistrée';
    } else {
        $_SESSION['error'] = 'Erreur : ' . $result;
    }

    header('Location: ajouter_antecedent.php?numero=' . $_POST['id_patient']);
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Antécédents médicaux étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
    }

    .page-container {
        max-width: 1000px;
        background: #fff;
        padding: 30px;
        margin: 20px auto;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
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
        margin-top: 25px;
        margin-bottom: 20px;
    }

    .info-box {
        background-color: #f4f6f8;
        border: 1px solid #e1e5e9;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .info-line {
        margin-bottom: 6px;
        font-size: 0.95rem;
    }

    .radio-group {
        display: flex;
        gap: 25px;
        margin-top: 5px;
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

    <?php include_once ('../../head.php'); ?>

    <div class="page-container">

        <div class="header-section">
            <h2>FICHE MÉDICALE – ANTÉCÉDENTS</h2>
            <p class="text-muted">Centre de Santé Universitaire</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success'];
            unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Informations étudiant (lecture seule) -->
        <div class="section-title">Informations de l'étudiant</div>

        <div class="info-box">
            <div class="row">
                <div class="col-md-4 info-line"><strong>Numéro :</strong>
                    <?php echo htmlspecialchars($donneesApi['numero_identifiant']); ?></div>
                <div class="col-md-4 info-line"><strong>Nom :</strong>
                    <?php echo htmlspecialchars($donneesApi['nom']); ?></div>
                <div class="col-md-4 info-line"><strong>Prénom :</strong>
                    <?php echo htmlspecialchars($donneesApi['prenom']); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4 info-line"><strong>Sexe :</strong>
                    <?php echo htmlspecialchars($donneesApi['sexe']); ?></div>
                <div class="col-md-4 info-line"><strong>Date naissance :</strong>
                    <?= !empty($donneesApi['date_naissance'])
                        ? date('d/m/Y', strtotime($donneesApi['date_naissance']))
                        : 'Non renseignée' ?>
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

        <!-- FORMULAIRE ANTÉCÉDENTS -->
        <form action="" method="POST">
            <input type="hidden" name="numero_carte"
                value="<?php echo htmlspecialchars($donneesApi['numero_identifiant']); ?>">
            <input type="hidden" name="id_patient" value="<?php echo htmlspecialchars($donneesApi['id']); ?>">

            <div class="section-title">Volet médical – Antécédents</div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Antécédents médicaux</label>
                    <textarea class="form-control" name="antecedents_medicaux" rows="3"
                        placeholder="Maladies connues..."></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Antécédents chirurgicaux</label>
                    <textarea class="form-control" name="antecedents_chirurgicaux" rows="3"
                        placeholder="Interventions chirurgicales..."></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Allergies connues</label>
                    <div class="radio-group">
                        <label><input type="radio" name="allergies" value="Non" checked> Non</label>
                        <label><input type="radio" name="allergies" value="Oui"> Oui</label>
                    </div>
                    <input type="text" class="form-control mt-2" name="allergies_precision" id="allergies_precision"
                        placeholder="Précisez..." style="display:none;">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Traitements chroniques</label>

                    <div class="radio-group">
                        <label>
                            <input type="radio" class="lg" name="traitement_chronique" value="Non" checked>
                            Non
                        </label>

                        <label>
                            <input type="radio" name="traitement_chronique" value="Oui">
                            Oui
                        </label>
                    </div>

                    <!-- Bloc maladies chroniques -->
                    <div id="bloc_traitement" style="display:none;" class="mt-3">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="traitements[]" value="Asthme">
                            <label class="form-check-label">Asthme</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="traitements[]" value="Diabete">
                            <label class="form-check-label">Diabète</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="traitements[]" value="Drepanocytose">
                            <label class="form-check-label">Drépanocytose</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="traitements[]" value="HTA">
                            <label class="form-check-label">HTA</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="traitements[]" value="Epilepsie">
                            <label class="form-check-label">Épilepsie</label>
                        </div>

                        <!-- Autres -->
                        <input type="text" class="form-control mt-2" name="traitement_autre"
                            placeholder="Autres à préciser...">
                    </div>

                </div>
            </div>

            <div class="text-end mt-4">
                <a href="dossier_medical.php?id=<?= $donneesApi['id'] ?>" class="btn btn-secondary me-3">Retour</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const allergiesRadios = document.getElementsByName('allergies');
        const allergiesInput = document.getElementById('allergies_precision');

        allergiesRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                allergiesInput.style.display = (radio.value === 'Oui' && radio.checked) ?
                    'block' : 'none';
            });
        });

        const traitementRadios = document.getElementsByName('traitement_chronique');
        const blocTraitement = document.getElementById('bloc_traitement');

        traitementRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                blocTraitement.style.display =
                    (radio.value === 'Oui' && radio.checked) ?
                    'block' :
                    'none';
            });
        });
    });
    </script>

</body>

</html>