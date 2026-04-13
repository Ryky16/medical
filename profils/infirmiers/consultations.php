<?php
session_start();
require_once('../../traitement/fonction.php');

// Vérifier connexion infirmier
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] == 'secretaire') {
    header('Location: ../../index.php');
    exit();
}
if ($_SESSION['mdp'] == "default") {
        header('Location: /medical01/profils/update_password.php');
        exit();
}

// Récupérer l'étudiant
$id_patient = $_GET['id'] ?? null;
if (!$id_patient) {
    die('Étudiant non spécifié');
}

$patient = getPatientById($connexion, $id_patient);
if (!$patient) {
    die('Étudiant introuvable');
}

// Vérifier si c'est une modification
$id_soin = $_GET['id_soin'] ?? null;
$consultation = null;
$isEdit = false;
$actes_existants = [];
$details_actes = [];

if ($id_soin) {
    // Récupérer les données de la consultation
    $stmt = $connexion->prepare("SELECT * FROM medical_soins_infirmiers WHERE id_soin = ? AND id_patient = ?");
    $stmt->bind_param("ii", $id_soin, $id_patient);
    $stmt->execute();
    $result = $stmt->get_result();
    $consultation = $result->fetch_assoc();
    $stmt->close();
    
    if ($consultation) {
    $isEdit = true;
    // Formater la date pour l'input datetime-local
    $date_consultation = date('Y-m-d\TH:i', strtotime($consultation['date_soin']));
    
    // Récupérer les actes associés à cette consultation
    $stmt_actes = $connexion->prepare("
        SELECT a.id_acte, a.type_acte, d.champ, d.valeur
        FROM medical_soins_actes a 
        LEFT JOIN medical_soins_actes_details d ON a.id_acte = d.id_acte 
        WHERE a.id_soin = ?
    ");
    $stmt_actes->bind_param("i", $id_soin);
    $stmt_actes->execute();
    $result_actes = $stmt_actes->get_result();
    
    $actes_existants = [];
    $details_actes = [];
    
    while ($row = $result_actes->fetch_assoc()) {
        $type_acte = $row['type_acte'];
        
        // Ajouter le type d'acte à la liste des actes existants (éviter les doublons)
        if (!in_array($type_acte, $actes_existants)) {
            $actes_existants[] = $type_acte;
        }
        
        // Initialiser le tableau de détails pour ce type d'acte s'il n'existe pas
        if (!isset($details_actes[$type_acte])) {
            $details_actes[$type_acte] = [];
        }
        
        // Ajouter le détail (champ => valeur)
        if (!empty($row['champ'])) {
            $details_actes[$type_acte][$row['champ']] = $row['valeur'];
        }
    }
    $stmt_actes->close();
}
}

// Date actuelle par défaut (ou date de la consultation si modification)
$date_actuelle = $isEdit ? $date_consultation : date('Y-m-d\TH:i');
//var_dump($details_actes);
?>
<!DOCTYPE html>
<html lang="fr">
<?php include_once('../../head.php'); ?>

<head>
    <style>
    /* Style PDF minimaliste */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', 'Helvetica', sans-serif;
        background: #f5f5f5;
        color: #333;
        line-height: 1.4;
        padding: 20px;
    }

    .pdf-container {
        max-width: 280mm;
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
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 5px;
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

    input[type="datetime-local"],
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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .imc-text {
        display: block;
        margin-top: 5px;
        font-weight: 600;
    }

    .imc-normal {
        color: #28a745;
    }

    /* Vert */
    .imc-surpoids {
        color: #fd7e14;
    }

    /* Orange */
    .imc-obesite {
        color: #dc3545;
    }

    /* Rouge */
    .imc-insuffisant {
        color: #007bff;
    }

    .acte-details {
        display: none;
        transition: all 0.3s ease;
    }

    input[name="actes[]"]:checked+label {
        color: #2c5282;
        font-weight: 700;
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
            <h1>FICHE DE SOINS INFIRMIERS</h1>
            <div class="subtitle">Service de Santé Universitaire</div>
        </div>

        <!-- Informations étudiant -->
        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nom :</span>
                    <span class="info-value"><?php echo htmlspecialchars($patient['nom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Prénom :</span>
                    <span class="info-value"><?php echo htmlspecialchars($patient['prenom']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Numero Patient(e) :</span>
                    <span class="info-value"><?php echo htmlspecialchars($patient['numero_identifiant']); ?></span>
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

        <!-- Formulaire soins -->
        <form action="<?php echo $isEdit ? 'traitement_modification_soins' : 'traitement_soins'; ?>" method="POST">
            <input type="hidden" name="id_patient" value="<?php echo $id_patient; ?>">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id_soin" value="<?php echo $id_soin; ?>">
            <?php endif; ?>

            <!-- Date -->
            <div class="form-section">
                <div class="section-title">DATE DE PRISE EN CHARGE</div>
                <div class="form-group">
                    <input type="datetime-local" name="date_soin" value="<?php echo $date_actuelle; ?>" required
                        class="full-width">
                </div>
            </div>

            <!-- Paramètres vitaux -->
            <div class="form-section">
                <div class="section-title">CONSTANTES</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Frequence Cardiaque (bat/min)</label>
                        <input type="text" name="FC" placeholder="Ex: 70"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['fc']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Frequence Respiratoire (cycle/min)</label>
                        <input type="text" name="FR" placeholder="Ex: 15"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['fr']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Saturation en Oxygene (SpO2)</label>
                        <input type="text" name="saturation" placeholder="Ex: 95%"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['saturation']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Glycémie Capillaire (g/dl)</label>
                        <input type="text" name="GC" placeholder="Ex: 0,80"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['glycemie']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Score de Glasgow</label>
                        <input type="text" name="glasgow" placeholder="Ex: 8"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['glasgow']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Diurése (ml/kg/H)</label>
                        <input type="text" name="diurese" placeholder="Ex: 0,5"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['diurese']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Tension artérielle (mmHg)</label>
                        <input type="text" name="ta" placeholder="Ex: 120/80"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['tension']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Température (°C)</label>
                        <input type="number" step="0.1" name="temperature" placeholder="37.0" min="30" max="45"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['temperature']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Poids (kg)</label>
                        <input type="number" step="0.1" name="poids" id="poids" placeholder="Poids (kg)" min="0"
                            max="300" value="<?php echo $isEdit ? htmlspecialchars($consultation['poids']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Taille (cm)</label>
                        <input type="number" step="0.1" name="taille" id="taille" placeholder="Taille (cm)" min="0"
                            max="300" value="<?php echo $isEdit ? htmlspecialchars($consultation['taille']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>IMC</label>
                        <input type="text" id="imc" name="imc"
                            value="<?php echo $isEdit ? htmlspecialchars($consultation['imc']) : ''; ?>"
                            placeholder="IMC" readonly>

                        <!-- Interpretation ici -->
                        <small id="imcInterpretation" class="imc-text"></small>
                    </div>
                </div>
            </div>

            <!-- Actes infirmiers -->
            <!-- ACTES INFIRMIERS RÉALISÉS -->
            <div class="form-section">
                <div class="section-title">ACTES INFIRMIERS RÉALISÉS</div>
                <ul>
                    <!-- INJECTION -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Injection"
                                        id="acteInjection"
                                        <?php echo $isEdit && in_array('Injection', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteInjection">
                                        Injection
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Injection', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Produit -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="injection_produit" placeholder="Produit"
                                            value="<?php echo isset($details_actes['Injection']['produit']) ? htmlspecialchars($details_actes['Injection']['produit']) : ''; ?>" />
                                    </div>
                                    <!-- Dose -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="injection_dose" placeholder="Dose"
                                            value="<?php echo isset($details_actes['Injection']['dose']) ? htmlspecialchars($details_actes['Injection']['dose']) : ''; ?>" />
                                    </div>
                                    <!-- Voie -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="injection_voie">
                                            <option value="">Voie</option>
                                            <option value="IM"
                                                <?php echo (isset($details_actes['Injection']['voie']) && $details_actes['Injection']['voie'] == 'IM') ? 'selected' : ''; ?>>
                                                Intramusculaire (IM)</option>
                                            <option value="IV"
                                                <?php echo (isset($details_actes['Injection']['voie']) && $details_actes['Injection']['voie'] == 'IV') ? 'selected' : ''; ?>>
                                                Intraveineuse (IV)</option>
                                            <option value="SC"
                                                <?php echo (isset($details_actes['Injection']['voie']) && $details_actes['Injection']['voie'] == 'SC') ? 'selected' : ''; ?>>
                                                Sous-cutanée (SC)</option>
                                            <option value="ID"
                                                <?php echo (isset($details_actes['Injection']['voie']) && $details_actes['Injection']['voie'] == 'ID') ? 'selected' : ''; ?>>
                                                Intradermique (ID)</option>
                                        </select>
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="injection_heure"
                                            value="<?php echo isset($details_actes['Injection']['heure']) ? htmlspecialchars($details_actes['Injection']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- PERFUSION -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Perfusion"
                                        id="actePerfusion"
                                        <?php echo $isEdit && in_array('Perfusion', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="actePerfusion">
                                        Perfusion
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Perfusion', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Produit -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="perfusion_produit" placeholder="Produit"
                                            value="<?php echo isset($details_actes['Perfusion']['produit']) ? htmlspecialchars($details_actes['Perfusion']['produit']) : ''; ?>" />
                                    </div>
                                    <!-- Dose / Volume -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="perfusion_dose" placeholder="Dose"
                                            value="<?php echo isset($details_actes['Perfusion']['volume']) ? htmlspecialchars($details_actes['Perfusion']['volume']) : ''; ?>" />
                                    </div>
                                    <!-- Débit -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="perfusion_debit" placeholder="Debit"
                                            value="<?php echo isset($details_actes['Perfusion']['debit']) ? htmlspecialchars($details_actes['Perfusion']['debit']) : ''; ?>" />
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="perfusion_heure"
                                            value="<?php echo isset($details_actes['Perfusion']['heure']) ? htmlspecialchars($details_actes['Perfusion']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- TRANSFUSION (déjà fait) -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Transfusion"
                                        id="acteTransfusion"
                                        <?php echo $isEdit && in_array('Transfusion', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteTransfusion">
                                        Transfusion
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Transfusion', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Produit -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="transfusion_produit">
                                            <option value="">Produit</option>
                                            <option value="CGR"
                                                <?php echo (isset($details_actes['Transfusion']['produit']) && $details_actes['Transfusion']['produit'] == 'CGR') ? 'selected' : ''; ?>>
                                                Concentré de Globules Rouges (CGR)</option>
                                            <option value="Plasma Frais"
                                                <?php echo (isset($details_actes['Transfusion']['produit']) && $details_actes['Transfusion']['produit'] == 'Plasma Frais') ? 'selected' : ''; ?>>
                                                Plasma Frais Congelé (PFC)</option>
                                            <option value="Plaquettes"
                                                <?php echo (isset($details_actes['Transfusion']['produit']) && $details_actes['Transfusion']['produit'] == 'Plaquettes') ? 'selected' : ''; ?>>
                                                Concentré Plaquettaire</option>
                                            <option value="Sang Total"
                                                <?php echo (isset($details_actes['Transfusion']['produit']) && $details_actes['Transfusion']['produit'] == 'Sang Total') ? 'selected' : ''; ?>>
                                                Sang Total</option>
                                        </select>
                                    </div>
                                    <!-- Nombre de poches -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="transfusion_poches">
                                            <option value="">Nbre de poches</option>
                                            <option value="1"
                                                <?php echo (isset($details_actes['Transfusion']['poches']) && $details_actes['Transfusion']['poches'] == '1') ? 'selected' : ''; ?>>
                                                1 poche</option>
                                            <option value="2"
                                                <?php echo (isset($details_actes['Transfusion']['poches']) && $details_actes['Transfusion']['poches'] == '2') ? 'selected' : ''; ?>>
                                                2 poches</option>
                                            <option value="3"
                                                <?php echo (isset($details_actes['Transfusion']['poches']) && $details_actes['Transfusion']['poches'] == '3') ? 'selected' : ''; ?>>
                                                3 poches</option>
                                            <option value="4"
                                                <?php echo (isset($details_actes['Transfusion']['poches']) && $details_actes['Transfusion']['poches'] == '4') ? 'selected' : ''; ?>>
                                                4 poches</option>
                                        </select>
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="transfusion_heure"
                                            value="<?php echo isset($details_actes['Transfusion']['heure']) ? htmlspecialchars($details_actes['Transfusion']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- OXYGÉNOTHÉRAPIE -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]"
                                        value="Oxygénothérapie" id="acteOxygene"
                                        <?php echo $isEdit && in_array('Oxygénothérapie', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteOxygene">
                                        Oxygénothérapie
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Oxygénothérapie', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Débit -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="oxygene_debit" placeholder="Débit"
                                            value="<?php echo isset($details_actes['Oxygénothérapie']['debit']) ? htmlspecialchars($details_actes['Oxygénothérapie']['debit']) : ''; ?>" />
                                    </div>
                                    <!-- Durée -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="oxygene_duree">
                                            <option value="">Durée</option>
                                            <option value="30 min"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '30 min') ? 'selected' : ''; ?>>
                                                30 min</option>
                                            <option value="1 heure"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '1 heure') ? 'selected' : ''; ?>>
                                                1 heure</option>
                                            <option value="2 heures"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '2 heures') ? 'selected' : ''; ?>>
                                                2 heures</option>
                                            <option value="6 heures"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '6 heures') ? 'selected' : ''; ?>>
                                                6 heures</option>
                                            <option value="12 heures"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '12 heures') ? 'selected' : ''; ?>>
                                                12 heures</option>
                                            <option value="24 heures"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == '24 heures') ? 'selected' : ''; ?>>
                                                24 heures</option>
                                            <option value="Continue"
                                                <?php echo (isset($details_actes['Oxygénothérapie']['duree']) && $details_actes['Oxygénothérapie']['duree'] == 'Continue') ? 'selected' : ''; ?>>
                                                Continue</option>
                                        </select>
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="oxygene_heure"
                                            value="<?php echo isset($details_actes['Oxygénothérapie']['heure']) ? htmlspecialchars($details_actes['Oxygénothérapie']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- NÉBULISATION -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Nebulisation"
                                        id="acteNebulisation"
                                        <?php echo $isEdit && in_array('Nebulisation', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteNebulisation">
                                        Nébulisation
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Nebulisation', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Produit -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="neb_produit">
                                            <option value="">Produit</option>
                                            <option value="Salbutamol"
                                                <?php echo (isset($details_actes['Nebulisation']['produit']) && $details_actes['Nebulisation']['produit'] == 'Salbutamol') ? 'selected' : ''; ?>>
                                                Salbutamol</option>
                                            <option value="Atrovent"
                                                <?php echo (isset($details_actes['Nebulisation']['produit']) && $details_actes['Nebulisation']['produit'] == 'Atrovent') ? 'selected' : ''; ?>>
                                                Atrovent</option>
                                            <option value="Adrénaline"
                                                <?php echo (isset($details_actes['Nebulisation']['produit']) && $details_actes['Nebulisation']['produit'] == 'Adrénaline') ? 'selected' : ''; ?>>
                                                Adrénaline</option>
                                            <option value="Sérum physiologique"
                                                <?php echo (isset($details_actes['Nebulisation']['produit']) && $details_actes['Nebulisation']['produit'] == 'Sérum physiologique') ? 'selected' : ''; ?>>
                                                Sérum physiologique</option>
                                        </select>
                                    </div>
                                    <!-- Dose -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="neb_dose">
                                            <option value="">Dose</option>
                                            <option value="2.5 mg"
                                                <?php echo (isset($details_actes['Nebulisation']['dose']) && $details_actes['Nebulisation']['dose'] == '2.5 mg') ? 'selected' : ''; ?>>
                                                2.5 mg</option>
                                            <option value="5 mg"
                                                <?php echo (isset($details_actes['Nebulisation']['dose']) && $details_actes['Nebulisation']['dose'] == '5 mg') ? 'selected' : ''; ?>>
                                                5 mg</option>
                                            <option value="0.5 ml"
                                                <?php echo (isset($details_actes['Nebulisation']['dose']) && $details_actes['Nebulisation']['dose'] == '0.5 ml') ? 'selected' : ''; ?>>
                                                0.5 ml</option>
                                            <option value="1 ml"
                                                <?php echo (isset($details_actes['Nebulisation']['dose']) && $details_actes['Nebulisation']['dose'] == '1 ml') ? 'selected' : ''; ?>>
                                                1 ml</option>
                                        </select>
                                    </div>
                                    <!-- Nombre de séances -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="neb_seances">
                                            <option value="">Nbre de séances</option>
                                            <option value="1"
                                                <?php echo (isset($details_actes['Nebulisation']['seances']) && $details_actes['Nebulisation']['seances'] == '1') ? 'selected' : ''; ?>>
                                                1 séance</option>
                                            <option value="2"
                                                <?php echo (isset($details_actes['Nebulisation']['seances']) && $details_actes['Nebulisation']['seances'] == '2') ? 'selected' : ''; ?>>
                                                2 séances</option>
                                            <option value="3"
                                                <?php echo (isset($details_actes['Nebulisation']['seances']) && $details_actes['Nebulisation']['seances'] == '3') ? 'selected' : ''; ?>>
                                                3 séances</option>
                                            <option value="4"
                                                <?php echo (isset($details_actes['Nebulisation']['seances']) && $details_actes['Nebulisation']['seances'] == '4') ? 'selected' : ''; ?>>
                                                4 séances</option>
                                            <option value="Plus de 4"
                                                <?php echo (isset($details_actes['Nebulisation']['seances']) && $details_actes['Nebulisation']['seances'] == 'Plus de 4') ? 'selected' : ''; ?>>
                                                Plus de 4</option>
                                        </select>
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="neb_heure"
                                            value="<?php echo isset($details_actes['Nebulisation']['heure']) ? htmlspecialchars($details_actes['Nebulisation']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- PANSEMENT -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Pansement"
                                        id="actePansement"
                                        <?php echo $isEdit && in_array('Pansement', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="actePansement">
                                        Pansement
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Pansement', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Type -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="pansement_type">
                                            <option value="">Type</option>
                                            <option value="Simple"
                                                <?php echo (isset($details_actes['Pansement']['type']) && $details_actes['Pansement']['type'] == 'Simple') ? 'selected' : ''; ?>>
                                                Simple</option>
                                            <option value="Complexe"
                                                <?php echo (isset($details_actes['Pansement']['type']) && $details_actes['Pansement']['type'] == 'Complexe') ? 'selected' : ''; ?>>
                                                Complexe</option>
                                        </select>
                                    </div>
                                    <!-- Localisation -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="pansement_localisation">
                                            <option value="">Localisation</option>
                                            <option value="Membre supérieur"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Membre supérieur') ? 'selected' : ''; ?>>
                                                Membre supérieur</option>
                                            <option value="Membre inférieur"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Membre inférieur') ? 'selected' : ''; ?>>
                                                Membre inférieur</option>
                                            <option value="Abdomen"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Abdomen') ? 'selected' : ''; ?>>
                                                Abdomen</option>
                                            <option value="Thorax"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Thorax') ? 'selected' : ''; ?>>
                                                Thorax</option>
                                            <option value="Dos"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Dos') ? 'selected' : ''; ?>>
                                                Dos</option>
                                            <option value="Autre"
                                                <?php echo (isset($details_actes['Pansement']['localisation']) && $details_actes['Pansement']['localisation'] == 'Autre') ? 'selected' : ''; ?>>
                                                Autre</option>
                                        </select>
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="pansement_heure"
                                            value="<?php echo isset($details_actes['Pansement']['heure']) ? htmlspecialchars($details_actes['Pansement']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- SONDAGE/DRAINAGE -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]"
                                        value="Sondage/Drainage" id="acteSondage"
                                        <?php echo $isEdit && in_array('Sondage/Drainage', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteSondage">
                                        Sondage / Drainage
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Sondage/Drainage', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Type -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="sondage_type">
                                            <option value="">Type</option>
                                            <option value="Sondage vésical"
                                                <?php echo (isset($details_actes['Sondage/Drainage']['type']) && $details_actes['Sondage/Drainage']['type'] == 'Sondage vésical') ? 'selected' : ''; ?>>
                                                Sondage vésical</option>
                                            <option value="Sonde nasogastrique"
                                                <?php echo (isset($details_actes['Sondage/Drainage']['type']) && $details_actes['Sondage/Drainage']['type'] == 'Sonde nasogastrique') ? 'selected' : ''; ?>>
                                                Sonde nasogastrique</option>
                                            <option value="Drain thoracique"
                                                <?php echo (isset($details_actes['Sondage/Drainage']['type']) && $details_actes['Sondage/Drainage']['type'] == 'Drain thoracique') ? 'selected' : ''; ?>>
                                                Drain thoracique</option>
                                            <option value="Drain abdominal"
                                                <?php echo (isset($details_actes['Sondage/Drainage']['type']) && $details_actes['Sondage/Drainage']['type'] == 'Drain abdominal') ? 'selected' : ''; ?>>
                                                Drain abdominal</option>
                                            <option value="Autre"
                                                <?php echo (isset($details_actes['Sondage/Drainage']['type']) && $details_actes['Sondage/Drainage']['type'] == 'Autre') ? 'selected' : ''; ?>>
                                                Autre</option>
                                        </select>
                                    </div>
                                    <!-- Calibre -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="sondage_calibre" placeholder="Calibre (CH)"
                                            value="<?php echo isset($details_actes['Sondage/Drainage']['calibre']) ? htmlspecialchars($details_actes['Sondage/Drainage']['calibre']) : ''; ?>" />
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="sondage_heure"
                                            value="<?php echo isset($details_actes['Sondage/Drainage']['heure']) ? htmlspecialchars($details_actes['Sondage/Drainage']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- PRÉLÈVEMENT -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Prelevement"
                                        id="actePrelevement"
                                        <?php echo $isEdit && in_array('Prelevement', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="actePrelevement">
                                        Prélèvement
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Prelevement', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Type -->
                                    <div class="col-md-3">
                                        <select class="form-select" name="prelevement_type">
                                            <option value="">Type</option>
                                            <option value="TDR"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'TDR') ? 'selected' : ''; ?>>
                                                TDR</option>
                                            <option value="GE"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'GE') ? 'selected' : ''; ?>>
                                                Goutte Épaisse (GE)</option>
                                            <option value="Sanguin"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'Sanguin') ? 'selected' : ''; ?>>
                                                Sanguin</option>
                                            <option value="Urinaire"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'Urinaire') ? 'selected' : ''; ?>>
                                                Urinaire</option>
                                            <option value="Crachat"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'Crachat') ? 'selected' : ''; ?>>
                                                Crachat</option>
                                            <option value="Autre"
                                                <?php echo (isset($details_actes['Prelevement']['type']) && $details_actes['Prelevement']['type'] == 'Autre') ? 'selected' : ''; ?>>
                                                Autre</option>
                                        </select>
                                    </div>
                                    <!-- Examen / Analyse -->
                                    <div class="col-md-3">
                                        <input class="form-control" name="prelevement_analyse"
                                            placeholder="Analyse Prélever"
                                            value="<?php echo isset($details_actes['Prelevement']['analyse']) ? htmlspecialchars($details_actes['Prelevement']['analyse']) : ''; ?>" />
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="prelevement_heure"
                                            value="<?php echo isset($details_actes['Prelevement']['heure']) ? htmlspecialchars($details_actes['Prelevement']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- NURSING -->
                    <li class="mb-3">
                        <div class="row align-items-center">
                            <!-- Checkbox -->
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actes[]" value="Nursing"
                                        id="acteNursing"
                                        <?php echo $isEdit && in_array('Nursing', $actes_existants) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="acteNursing">
                                        Nursing
                                    </label>
                                </div>
                            </div>

                            <!-- Détails -->
                            <div class="col-md-9 acte-details"
                                <?php echo $isEdit && in_array('Nursing', $actes_existants) ? 'style="display:block;"' : ''; ?>>
                                <div class="row g-2">
                                    <!-- Date -->
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" name="nursing_date"
                                            value="<?php echo isset($details_actes['Nursing']['date']) ? htmlspecialchars($details_actes['Nursing']['date']) : ''; ?>">
                                    </div>
                                    <!-- Heure -->
                                    <div class="col-md-3">
                                        <input type="time" class="form-control" name="nursing_heure"
                                            value="<?php echo isset($details_actes['Nursing']['heure']) ? htmlspecialchars($details_actes['Nursing']['heure']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Observations -->
            <div class="form-section">
                <div class="section-title">OBSERVATIONS / RECOMMANDATIONS</div>
                <div class="form-group">
                    <textarea name="observations" rows="4"
                        placeholder="État général, suivi nécessaire, remarques..."><?php echo $isEdit ? htmlspecialchars($consultation['observations']) : ''; ?></textarea>
                </div>
            </div>

            <!-- Infirmier -->
            <div class="infirmier-info">
                Fiche remplie par : <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Infirmier(e)'); ?>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="../medecin/dossier_medical.php?id=<?php echo htmlspecialchars($patient['id']); ?>"
                    class="btn btn-secondary">Retour à la liste</a>
                <button type="submit" class="btn btn-primary">
                    <?php echo $isEdit ? 'Mettre à jour la fiche' : 'Enregistrer la fiche'; ?>
                </button>
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

        // Calculer IMC si en modification
        <?php if ($isEdit && !empty($consultation['poids']) && !empty($consultation['taille'])): ?>
        setTimeout(calculIMC, 100);
        <?php endif; ?>
    });

    // Gestion de l'impression
    function printForm() {
        window.print();
    }

    function interpretationIMC(imc) {
        if (imc < 18.5) {
            return {
                texte: "Insuffisance pondérale",
                classe: "imc-insuffisant"
            };
        } else if (imc < 25) {
            return {
                texte: "Poids normal",
                classe: "imc-normal"
            };
        } else if (imc < 30) {
            return {
                texte: "Surpoids",
                classe: "imc-surpoids"
            };
        } else {
            return {
                texte: "Obésité",
                classe: "imc-obesite"
            };
        }
    }

    function calculIMC() {
        let poids = parseFloat(document.getElementById("poids").value);
        let tailleCm = parseFloat(document.getElementById("taille").value);
        let imcField = document.getElementById("imc");
        let interpretationField = document.getElementById("imcInterpretation");

        if (poids > 0 && tailleCm > 0) {
            let tailleM = tailleCm / 100;
            let imc = poids / (tailleM * tailleM);
            imc = imc.toFixed(2);

            imcField.value = imc;

            let resultat = interpretationIMC(parseFloat(imc));

            interpretationField.textContent = resultat.texte;
            interpretationField.className = "imc-text " + resultat.classe;

        } else {
            imcField.value = "";
            interpretationField.textContent = "";
            interpretationField.className = "imc-text";
        }
    }

    document.getElementById("poids").addEventListener("input", calculIMC);
    document.getElementById("taille").addEventListener("input", calculIMC);

    document.addEventListener("DOMContentLoaded", function() {
        const actes = document.querySelectorAll("input[name='actes[]']");

        actes.forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                const parentRow = this.closest(".row");
                const details = parentRow.querySelector(".acte-details");

                if (details) {
                    if (this.checked) {
                        details.style.display = "block";
                    } else {
                        details.style.display = "none";
                    }
                }
            });
        });
    });
    </script>

    <?php include_once('../../footer.php'); ?>
</body>

</html>