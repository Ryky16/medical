<?php
session_start();
require_once ('../../traitement/fonction.php');

// Vérifier que l'utilisateur est connecté et est médecin
$roles_autorises = ['secretaire', 'dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

// Récupérer le numéro de carte depuis l'URL
$numero = $_GET['numero'] ?? '';
$donneesApi = null;
$type_patient = null;

$id = $_GET['id'] ?? null;
$patient = null;

if ($id) {
    $patient = getPatientsById($connexion, $id);

    if (!$patient) {
        $_SESSION['error'] = 'Patient introuvable.';
        header('Location: ../medecin/recherche.php');
        exit();
    }

    $type_patient = $patient['type_patient'];
}

if (!empty($numero)) {
    // Recherche API étudiant
    $donneesApi = getDonneesEtudiant($numero);

    if ($donneesApi) {
        $type_patient = 'etudiant';
    } else {
        // Recherche personnel
        $personnel = searchPersonnel($connexion_2, $numero);

        if ($personnel) {
            $donneesApi = $personnel;
            $type_patient = 'personnel';
        }
    }

    if (!$donneesApi) {
        $_SESSION['error'] = 'Aucun étudiant ou personnel trouvé.';
        header('Location: ../medecin/recherche.php');
        exit();
    }
}
// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les maladies chroniques sous forme de tableau
    $maladies_chroniques = [];
    if (isset($_POST['maladie_diabete']) && $_POST['maladie_diabete'] == 'Diabète') {
        $maladies_chroniques[] = 'Diabète';
    }
    if (isset($_POST['maladie_drepano']) && $_POST['maladie_drepano'] == 'Drépanocytaire') {
        $maladies_chroniques[] = 'Drépanocytaire';
    }
    if (isset($_POST['maladie_asthme']) && $_POST['maladie_asthme'] == 'Asthme') {
        $maladies_chroniques[] = 'Asthme';
    }
    if (isset($_POST['maladie_autres']) && $_POST['maladie_autres'] == 'Autres') {
        $autres_maladies = $_POST['maladie_autres_precision'] ?? '';
        $maladies_chroniques[] = 'Autres: ' . $autres_maladies;
    }
    $maladies_chroniques_str = implode(', ', $maladies_chroniques);

    // Récupérer l'état orphelin
    $orphelin = '';
    if (isset($_POST['orphelin_pere']) && $_POST['orphelin_pere'] == '1') {
        $orphelin .= 'Père ';
    }
    if (isset($_POST['orphelin_mere']) && $_POST['orphelin_mere'] == '1') {
        $orphelin .= 'Mère ';
    }
    $orphelin = trim($orphelin);

    // Récupérer le contact du tuteur
    $tuteur_info = '';
    if (!empty($_POST['tuteur_nom']) && !empty($_POST['tuteur_telephone']) && !empty($_POST['tuteur_profession'])) {
        $tuteur_info = $_POST['tuteur_nom'] . ' - ' . $_POST['tuteur_telephone'] . ' - ' . $_POST['tuteur_profession'];
    }

    $data = [
        'type_patient' => $_POST['type_patient'],
        'numero_identifiant' => $_POST['numero_identifiant'],
        'nom' => $_POST['nom'],
        'prenom' => $_POST['prenom'],
        'date_naissance' => $_POST['date_naissance'],
        'telephone' => $_POST['telephone'],
        'adresse' => $_POST['adresse_residence'],
        'email' => $_POST['email'],
        'maladies_chroniques' => $maladies_chroniques_str,
        'groupe_sanguin' => $_POST['groupe_sanguin'],
        'sexe' => $_POST['sexe'],
        'statut_matrimonial' => $_POST['statut_matrimonial'],
        'mobilite_reduite' => $_POST['mobilite_reduite'],
        'orphelin' => $orphelin,
        'contact_urgence_nom' => $_POST['tuteur_nom'],
        'contact_urgence_telephone' => $_POST['tuteur_telephone'],
        'contact_urgence_profession' => $_POST['tuteur_profession'],
    ];
    if ($_POST['type_patient'] === 'etudiant') {
        $data['faculte'] = $_POST['faculte'] ?? '';
        $data['niveau_etude'] = $_POST['niveau_formation'] ?? '';
    } else {
        $data['fonction'] = $donneesApi['fonction'] ?? '';
        $data['service'] = $_POST['service'] ?? '';
    }

    // Validation minimale des champs requis
    $champs_requis = ['numero_identifiant', 'nom', 'prenom', 'date_naissance', 'telephone', 'adresse'];
    $champs_vides = [];

    foreach ($champs_requis as $champ) {
        if (empty($data[$champ])) {
            $champs_vides[] = $champ;
        }
    }

    if (!empty($champs_vides)) {
        $_SESSION['error'] = 'Veuillez remplir tous les champs obligatoires (*)';
    } else {
        // Appeler la fonction d'ajout avec les données complètes
        // Déterminer si c'est un ajout ou une modification
        if ($id) {
            // UPDATE
            $result = updatePatient($connexion, $id, $data);

            if ($result === true) {
                $_SESSION['success'] = 'Patient modifié avec succès';

                $numero = $_POST['numero_identifiant'];

                header('Location: ../medecin/recherche.php?search=' . urlencode($numero));
                exit();
            } else {
                $_SESSION['error'] = $result;
            }
        } else {
            // INSERT
            $result = addPatient($connexion, $data);

            if ($result === true) {
                $_SESSION['success'] = 'Patient ajouté avec succès';

                header('Location: ../medecin/recherche.php');
                exit();
            } else {
                $_SESSION['error'] = $result;
            }
        }
    }
}
$services = allServices($connexion);
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once ('../../head.php'); ?>

<head>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Police professionnelle -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
        color: #333;
        line-height: 1.5;
    }

    .page-container {
        max-width: 1000px;
        background-color: white;
        padding: 30px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .header-section {
        border-bottom: 2px solid #4a6572;
        padding-bottom: 15px;
        margin-bottom: 25px;
        text-align: center;
    }

    .title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 5px;
    }

    .subtitle {
        color: #546e7a;
        font-size: 1rem;
        margin-bottom: 0;
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

    .form-section {
        margin-bottom: 25px;
    }

    .form-label {
        color: #4a6572;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .required-field::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }

    .form-control,
    .form-select {
        border: 1px solid #d5dde5;
        border-radius: 4px;
        padding: 10px 12px;
        color: #2c3e50;
        font-size: 0.95rem;
        background-color: #ffffff;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        border-color: #e9ecef;
        color: #6c757d;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #4a6572;
        box-shadow: 0 0 0 0.2rem rgba(74, 101, 114, 0.15);
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 5px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .radio-group {
        display: flex;
        justify-content: flex-start;
        /* pousse les radios vers la droite */
        gap: 25px;
        margin-top: 5px;
        margin-left: 15px;
        flex-wrap: wrap;
    }

    .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 60px;
        margin-left: 5px;
        /* évite qu’ils débordent trop */
        justify-content: flex-start;
    }


    .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #4a6572;
        border-color: #4a6572;
        padding: 10px 30px;
        font-weight: 500;
        border-radius: 4px;
    }

    .btn-primary:hover {
        background-color: #3a5460;
        border-color: #3a5460;
    }

    .btn-secondary {
        background-color: #95a5a6;
        border-color: #95a5a6;
        padding: 10px 30px;
        font-weight: 500;
        border-radius: 4px;
    }

    .btn-secondary:hover {
        background-color: #7f8c8d;
        border-color: #7f8c8d;
    }

    .field-hint {
        color: #7f8c8d;
        font-size: 0.85rem;
        margin-top: 5px;
        font-style: italic;
    }

    .footer-note {
        color: #7f8c8d;
        font-size: 0.85rem;
        text-align: center;
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #e1e5e9;
    }


    @media print {
        .page-container {
            box-shadow: none;
            padding: 0;
            margin: 0;
        }

        .btn-primary,
        .btn-secondary {
            display: none;
        }

        .no-print {
            display: none;
        }

        .form-control[readonly] {
            border: none;
            background-color: transparent;
            padding: 0;
        }

        .form-control:not([readonly]) {
            border-bottom: 1px solid #ccc;
            border-top: none;
            border-left: none;
            border-right: none;
            background-color: transparent;
            padding: 5px 0;
        }

    }
    </style>
</head>

<body>


    <div class="page-container container">
        <!-- En-tête -->
        <div class="header-section">
            <h1 class="title">
                FICHE D'IDENTIFICATION <?= $type_patient === 'personnel' ? 'PERSONNEL' : 'ÉTUDIANT' ?>
            </h1>
            <p class="subtitle">Centre de Santé Universitaire - Service Secrétariat</p>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" action="" id="identificationForm">
            <!-- Champs cachés pour les données API -->
            <input type="hidden" name="type_patient" value="<?= $type_patient ?>">
            <input type="hidden" name="numero_identifiant" value="<?= htmlspecialchars(
    $patient['numero_identifiant']
        ?? ($type_patient === 'etudiant'
            ? $donneesApi['numero_carte']
            : $donneesApi['matricule'])
) ?>">

            <!-- SECTION 1 : IDENTIFICATION DE L'ÉTUDANT -->
            <div class="section-title">Volet 1 : IDENTIFICATION DE L'ÉTUDANT</div>

            <div class="form-section">
                <div class="row">
                    <!-- Prénom -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Prénoms</label>
                        <input type="text" class="form-control" name="prenom"
                            value="<?= htmlspecialchars($patient['prenom'] ?? $donneesApi['prenom'] ?? '') ?>" required
                            readonly>
                    </div>

                    <!-- Nom -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Nom</label>
                        <input type="text" class="form-control" name="nom"
                            value="<?= htmlspecialchars($patient['nom'] ?? $donneesApi['nom'] ?? '') ?>" required
                            readonly>
                    </div>
                </div>

                <div class="row">
                    <!-- Sexe -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sexe</label>
                        <div class="radio-group justify-end">

                            <?php $sexe = $patient['sexe'] ?? $donneesApi['sexe'] ?? ''; ?>

                            <div class="radio-item">
                                <input class="form-check-input" type="radio" name="sexe" id="sexe_m" value="M"
                                    <?= $sexe === 'M' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="sexe_m">Masculin</label>
                            </div>

                            <div class="radio-item">
                                <input class="form-check-input" type="radio" name="sexe" id="sexe_f" value="F"
                                    <?= $sexe === 'F' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sexe_f">Féminin</label>
                            </div>

                        </div>
                    </div>

                    <!-- Date de naissance -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Date de naissance</label>

                        <?php
                        $date_naissance = $patient['date_naissance']
                            ?? ($type_patient === 'etudiant'
                                ? $donneesApi['date_naissance']
                                : $donneesApi['date_naiss']);
                        ?>

                        <input type="text" class="form-control" name="date_naissance"
                            value="<?= htmlspecialchars(date('d/m/Y', strtotime($date_naissance ?? ''))) ?>" required
                            readonly>
                    </div>

                    <?php if ($type_patient === 'personnel'): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lieu de naissance</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($donneesApi['lieu_naiss'] ?? '') ?>" readonly>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row">

                    <!-- Faculté / École -->
                    <?php if ($type_patient === 'etudiant'): ?>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Faculté</label>
                        <input type="text" class="form-control" name="faculte"
                            value="<?= htmlspecialchars($patient['faculte'] ?? $donneesApi['faculte'] ?? '') ?>"
                            readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Niveau</label>
                        <input type="text" class="form-control" name="niveau_formation"
                            value="<?= htmlspecialchars($patient['niveau_etude'] ?? $donneesApi['niveau_formation'] ?? '') ?>"
                            readonly>
                    </div>

                    <?php else: ?>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fonction</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($patient['fonction'] ?? $donneesApi['fonction'] ?? '') ?>"
                            readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Departement</label>

                        <?php $service_patient = $patient['service'] ?? $donneesApi['service'] ?? ''; ?>

                        <select name="service" class="form-select" required>
                            <option value="">-- Sélectionner un service --</option>

                            <?php foreach ($services as $service): ?>
                            <option value="<?= htmlspecialchars($service['libelle']) ?>"
                                <?= $service_patient === $service['libelle'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($service['nom']) ?>
                            </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <?php endif; ?>
                </div>

                <div class="row">

                    <!-- Adresse -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Adresse / Résidence universitaire</label>
                        <input type="text" class="form-control" name="adresse_residence"
                            value="<?= htmlspecialchars($patient['adresse'] ?? $donneesApi['adresse'] ?? '') ?>"
                            required>
                        <div class="field-hint">Ex: Grand_Campus Pav 23B</div>
                    </div>

                    <!-- Téléphone -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone"
                            value="<?= htmlspecialchars($patient['telephone'] ?? $donneesApi['telephone2'] ?? '') ?>"
                            required>
                    </div>

                    <!-- Email -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" class="form-control" name="email"
                            value="<?= htmlspecialchars($patient['email'] ?? $donneesApi['email_ucad'] ?? '') ?>"
                            required>
                    </div>

                </div>
            </div>

            <!-- SECTION 2 : INFORMATIONS COMPLÉMENTAIRES -->
            <div class="section-title">Volet 2 : INFORMATIONS COMPLÉMENTAIRES</div>

            <?php
            $statut = $patient['statut_matrimonial'] ?? '';
            $groupe = $patient['groupe_sanguin'] ?? '';
            $mobilite = $patient['mobilite_reduite'] ?? 'Non';
            $orphelin = $patient['orphelin'] ?? '';
            ?>

            <div class="form-section">
                <div class="row">

                    <!-- Statut matrimonial -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Statut matrimonial</label>
                        <select class="form-select" name="statut_matrimonial">
                            <option value="">Sélectionner</option>
                            <option value="Célibataire" <?= $statut === 'Célibataire' ? 'selected' : '' ?>>Célibataire
                            </option>
                            <option value="Marié(e)" <?= $statut === 'Marié(e)' ? 'selected' : '' ?>>Marié(e)</option>
                            <option value="Divorcé(e)" <?= $statut === 'Divorcé(e)' ? 'selected' : '' ?>>Divorcé(e)
                            </option>
                            <option value="Veuf/Veuve" <?= $statut === 'Veuf/Veuve' ? 'selected' : '' ?>>Veuf/Veuve
                            </option>
                        </select>
                    </div>

                    <!-- Groupe sanguin -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Groupe sanguin</label>
                        <select class="form-select" name="groupe_sanguin">
                            <option value="">Sélectionner</option>
                            <option value="A+" <?= $groupe === 'A+' ? 'selected' : '' ?>>A+</option>
                            <option value="A-" <?= $groupe === 'A-' ? 'selected' : '' ?>>A-</option>
                            <option value="B+" <?= $groupe === 'B+' ? 'selected' : '' ?>>B+</option>
                            <option value="B-" <?= $groupe === 'B-' ? 'selected' : '' ?>>B-</option>
                            <option value="AB+" <?= $groupe === 'AB+' ? 'selected' : '' ?>>AB+</option>
                            <option value="AB-" <?= $groupe === 'AB-' ? 'selected' : '' ?>>AB-</option>
                            <option value="O+" <?= $groupe === 'O+' ? 'selected' : '' ?>>O+</option>
                            <option value="O-" <?= $groupe === 'O-' ? 'selected' : '' ?>>O-</option>
                        </select>
                    </div>

                    <!-- Mobilité réduite -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Personne à mobilité réduite</label>
                        <div class="radio-group justify-end">

                            <div class="radio-item">
                                <input class="form-check-input" type="radio" name="mobilite_reduite" id="mobilite_oui"
                                    value="Oui" <?= $mobilite === 'Oui' ? 'checked' : '' ?>>

                                <label class="form-check-label" for="mobilite_oui">Oui</label>
                            </div>

                            <div class="radio-item">
                                <input class="form-check-input" type="radio" name="mobilite_reduite" id="mobilite_non"
                                    value="Non" <?= $mobilite === 'Non' ? 'checked' : '' ?>>

                                <label class="form-check-label" for="mobilite_non">Non</label>
                            </div>

                        </div>
                    </div>

                    <!-- Orphelin -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Orphelin</label>

                        <div class="checkbox-group">

                            <div class="checkbox-item">
                                <input type="checkbox" name="orphelin_pere" id="orphelin_pere" value="1"
                                    <?= strpos($orphelin, 'Père') !== false ? 'checked' : '' ?>>

                                <label for="orphelin_pere">Père</label>
                            </div>

                            <div class="checkbox-item">
                                <input type="checkbox" name="orphelin_mere" id="orphelin_mere" value="1"
                                    <?= strpos($orphelin, 'Mère') !== false ? 'checked' : '' ?>>

                                <label for="orphelin_mere">Mère</label>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION 3 : CONTACT D'URGENCE -->
            <div class="section-title">Volet 3 : CONTACT D'URGENCE / TUTEUR</div>

            <?php
            $tuteur_nom = $patient['contact_urgence_nom'] ?? '';
            $tuteur_tel = $patient['contact_urgence_telephone'] ?? '';
            $tuteur_prof = $patient['contact_urgence_profession'] ?? '';
            ?>

            <div class="form-section">
                <div class="row">

                    <!-- Nom du tuteur -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Nom du tuteur</label>
                        <input type="text" class="form-control" name="tuteur_nom" placeholder="Nom complet du tuteur"
                            value="<?= htmlspecialchars($tuteur_nom) ?>" required>
                    </div>

                    <!-- Téléphone du tuteur -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Téléphone du tuteur</label>
                        <input type="tel" class="form-control" name="tuteur_telephone" placeholder="Numéro de téléphone"
                            value="<?= htmlspecialchars($tuteur_tel) ?>" required>
                    </div>

                    <!-- Profession du tuteur -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Profession du tuteur</label>
                        <input type="text" class="form-control" name="tuteur_profession"
                            placeholder="Profession du tuteur" value="<?= htmlspecialchars($tuteur_prof) ?>" required>
                    </div>

                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <a href="../medecin/recherche.php" class="btn btn-secondary me-3">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= isset($id) ? 'Modifier le patient' : 'Enregistrer le patient' ?>
                    </button>
                </div>
            </div>

            <!-- Note de pied de page -->
            <div class="footer-note">
                <p>Formulaire d'identification étudiant - Centre de Santé Universitaire<br>
                    Tous les champs marqués d'un astérisque (*) sont obligatoires</p>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
    // Afficher/masquer le champ "autres précisions" pour les maladies
    document.addEventListener('DOMContentLoaded', function() {
        const maladieAutresCheckbox = document.getElementById('maladie_autres');
        const autresPrecisionInput = document.getElementById('autres_precision');

        maladieAutresCheckbox.addEventListener('change', function() {
            if (this.checked) {
                autresPrecisionInput.style.display = 'block';
                autresPrecisionInput.required = true;
            } else {
                autresPrecisionInput.style.display = 'none';
                autresPrecisionInput.required = false;
                autresPrecisionInput.value = '';
            }
        });

        // Validation du formulaire
        const form = document.getElementById('identificationForm');
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires (*)');
            }
        });
    });
    </script>

</body>

</html>