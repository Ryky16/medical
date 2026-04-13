<?php
session_start();
require_once ('../../traitement/fonction.php');
require_once ('../../vendor/autoload.php');

use Dompdf\Dompdf;


$roles_autorises = ['medecin', 'infirmier'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

$id_prescription = intval($_GET['id'] ?? 0);
$prescription = getPrescriptionById($connexion, $id_prescription);
$patient = $prescription;

$structure = ($patient['type_patient'] === 'etudiant')
    ? ($patient['faculte'] ?? 'Non spécifiée')
    : ($patient['service'] ?? 'Non spécifié');


$age = calculerAge($patient['date_naissance'] ?? '');

// Convertir le logo en base64
$chemin_logo = $_SERVER['DOCUMENT_ROOT'] . '/medical01/assets/images/logo.png';
$logo_base64 = imageToBase64($chemin_logo);

$dompdf = new Dompdf();

$html = '
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { 
        font-family: DejaVu Sans, sans-serif; 
        font-size:13px; 
        color:#2d3748; 
        margin:0; 
        padding:0; 
    }
    .container { 
        max-width:210mm; 
        margin:0 auto; 
        background:white; 
        padding:15px; 
    }
    /* En-tête avec 3 colonnes */
    .header {
        display: table;
        width: 100%;
        border-bottom: 2px solid #2c5282;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .header-left, .header-center, .header-right {
        display: table-cell;
        vertical-align: middle;
    }
    .header-left {
        width: 30%;
        text-align: left;
        font-size: 11px;
        color: #2c5282;
    }
    .header-center {
        width: 40%;
        text-align: center;
    }
    .header-right {
        width: 30%;
        text-align: right;
        font-size: 11px;
        color: #2c5282;
    }
    .hospital-name {
        font-weight: bold;
        font-size: 14px;
    }
    .hospital-desc {
        font-size: 10px;
        color: #718096;
    }
    .logo {
        min-height: 70px;
        min-width: 200px;
        max-height: 70px;
        max-width: 200px;
    }
    .date {
        font-weight: bold;
    }
    
    /* Style pour l\'ordonnance */
    .ordonnance-title {
        text-align: center;
        font-size: 22px;
        font-weight: bold;
        color: #2c5282;
        margin: 15px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    
    .patient-info {
        background: #f7fafc;
        border-left: 4px solid #2c5282;
        padding: 10px 15px;
        margin: 15px 0;
        font-size: 15px;
    }
    
    .patient-info-line {
        margin: 5px 0;
    }
    
    .patient-name {
        font-weight: bold;
        font-size: 16px;
        color: #1a202c;
    }
    
    .ordonnance-content {
        margin: 25px 0;
        padding: 15px;
        border: 1px dashed #cbd5e0;
        min-height: 200px;
    }
    
    .ordonnance-text {
        font-size: 14px;
        line-height: 1.6;
    }
    
    .medecin-signature {
        margin-top: 40px;
        text-align: right;
        font-style: italic;
    }
    
    .pdf-footer {
        position: fixed;
        bottom: 10px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 9px;
        color: #718096;
        border-top: 1px solid #cbd5e0;
        padding-top: 5px;
    }
</style>
</head>
<body>
<div class="container">

    <!-- EN-TÊTE AVEC 3 COLONNES -->
    <div class="header">
        <div class="header-left">
            <div class="hospital-name">CENTRE HOSPITALIER UNIVERSITAIRE</div>
            <div class="hospital-desc">Service de Médecine<br>Tél: +221 78 441 34 00<br>Email: contact@coud.sn</div>
        </div>
        <div class="header-center">
            <img src="' . $logo_base64 . '" class="logo" alt="Logo Hôpital">
        </div>
        <div class="header-right">
            <div class="date">Dakar, le ' . date('d/m/Y', strtotime($patient['date_prescription'])) . '</div>
            <div style="font-size: 10px;">N° : ' . htmlspecialchars($id_prescription) . '</div>
        </div>
    </div>

    <!-- TITRE ORDONNANCE -->
    <div class="ordonnance-title">BULLETIN D\'EXAMEN</div>

    <!-- INFORMATIONS PATIENT SIMPLES -->
    <div class="patient-info">
        <div class="patient-info-line">
            <span class="patient-name">Patient(e): ' . htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) . '</span>
        </div>
        <div class="patient-info-line">
            <span>Âge: ' . $age . '</span>
        </div>
    </div>

    <!-- CONTENU DE L\'ORDONNANCE -->
    <div class="ordonnance-content">
        <div class="ordonnance-text">
            <p> <strong>Diagnostic : </strong> ' . nl2br(htmlspecialchars($prescription['diagnostic'] ?? 'Aucune diagnostic')) . '</p>
            <p> <strong>Examen(e) Demandé(s) : </strong> ' . nl2br(htmlspecialchars($prescription['examens_complementaires'] ?? 'Aucune examen')) . '</p>
        </div>
        
        <!-- SIGNATURE DU MÉDECIN -->
        <div class="medecin-signature">
            <p><strong>Dr. ' . htmlspecialchars($_SESSION['nom'] ?? 'Médecin') . '</strong><br>
            <span style="font-size: 12px;">Médecin traitant</span></p>
            <p style="margin-top: 20px;">Signature:</p>
        </div>
    </div>

    <!-- PIED DE PAGE -->
    <div class="pdf-footer">
        Document confidentiel - Service de Santé Universitaire - ' . date('d/m/Y H:i') . '
    </div>

</div>
</body>
</html>';

// Génération PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ordonnance_{$id_prescription}.pdf", ['Attachment' => false]);
exit();
?>