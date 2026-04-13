<?php
session_start();
require_once ('../../traitement/fonction.php');
require_once ('../../vendor/autoload.php');  // Dompdf

use Dompdf\Dompdf;

$roles_autorises = ['medecin', 'infirmier'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

$id_patient = intval($_GET['id'] ?? 0);
$id_soin = intval($_GET['soin'] ?? 0);

if (!$id_patient || !$id_soin) {
    exit('Patient ou soin invalide.');
}

$patient = getPatientById($connexion, $id_patient);
$soin = getSoinById($connexion, $id_soin);

$age = calculerAge($patient['date_naissance'] ?? '');
// Convertir le logo en base64
$chemin_logo = $_SERVER['DOCUMENT_ROOT'] . '/medical01/assets/images/logo.png';
$logo_base64 = imageToBase64($chemin_logo);
if (!$patient || !$soin) {
    exit('Données patient ou soin introuvables.');
}

$structure = ($patient['type_patient'] === 'etudiant')
    ? ($patient['faculte'] ?? 'Non spécifiée')
    : ($patient['service'] ?? 'Non spécifié');

function afficherValeur($valeur, $unite = '')
{
    return ($valeur !== null && $valeur !== '')
        ? htmlspecialchars($valeur) . $unite
        : '—';
}

$actes_html = '
<table width="100%" style="border-collapse:collapse;">
';

$stmtActes = $connexion->prepare(
    'SELECT id_acte, type_acte 
     FROM medical_soins_actes 
     WHERE id_soin=?'
);

$stmtActes->bind_param('i', $id_soin);
$stmtActes->execute();
$resultActes = $stmtActes->get_result();

$colonne = 0;

if ($resultActes->num_rows > 0) {
    $actes_html .= '<tr>';

    while ($rowActe = $resultActes->fetch_assoc()) {
        // Nouvelle ligne après 3 TD
        if ($colonne == 3) {
            $actes_html .= '</tr><tr>';
            $colonne = 0;
        }

        $actes_html .= '
        <td style="
            border:1px solid #cbd5e0;
            padding:6px;
            vertical-align:top;
            width:33%;
        ">
            <ul style="margin:0; padding-left:12px;">
                <li>
                    <strong>' . htmlspecialchars($rowActe['type_acte']) . '</strong>
        ';

        // ===== DETAILS =====
        $stmtDetails = $connexion->prepare(
            'SELECT champ, valeur 
             FROM medical_soins_actes_details 
             WHERE id_acte=?'
        );

        $stmtDetails->bind_param('i', $rowActe['id_acte']);
        $stmtDetails->execute();
        $resultDetails = $stmtDetails->get_result();

        if ($resultDetails->num_rows > 0) {
            $actes_html .= '<ul style="margin:2px 0 0 10px;">';

            while ($rowDetail = $resultDetails->fetch_assoc()) {
                $actes_html .= '
                    <li>
                        ' . htmlspecialchars($rowDetail['champ']) . ' : 
                        ' . htmlspecialchars($rowDetail['valeur']) . '
                    </li>';
            }

            $actes_html .= '</ul>';
        }

        $stmtDetails->close();

        $actes_html .= '
                </li>
            </ul>
        </td>';

        $colonne++;
    }

    // compléter la dernière ligne
    while ($colonne < 3) {
        $actes_html .= '<td></td>';
        $colonne++;
    }

    $actes_html .= '</tr>';
} else {
    $actes_html .= '
    <tr>
        <td colspan="3">
            <ul><li>Aucun acte enregistré</li></ul>
        </td>
    </tr>';
}

$actes_html .= '</table>';

$stmtActes->close();

$dompdf = new Dompdf();

$html = '
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size:13px; color:#2d3748; margin:0; padding:0; }
.container { max-width:210mm; margin:0 auto; background:white; padding:15px; border-radius:5px; }
.header { text-align:center; border-bottom:2px solid #2c5282; padding-bottom:10px; margin-bottom:15px; }
.header h1 { color:#2c5282; font-size:16px; margin:0; }
.header .subtitle { color:#718096; font-size:10px; }
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
        /* Style pour l\'soin */
    .soin-title {
        text-align: center;
        font-size: 15px;
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
.section { margin-bottom:20px; }
.section-title { font-size:12px; font-weight:bold; color:#2c5282; border-bottom:1px solid #cbd5e0; padding-bottom:3px; margin-bottom:5px; }
.info-line { margin-bottom:2px; }
.label { width:120px; font-weight:bold; }
.table-constants { width:100%; border-collapse:collapse; margin-top:5px; }
.table-constants th, .table-constants td { border:1px solid #cbd5e0; padding:5px; text-align:left; font-size:13px; }
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
.table-constants td{
    width:25%;
    border:none;
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
            <div class="date">Dakar, le ' . date('d/m/Y', strtotime($soin['created_at'])) . '</div>
            <div style="font-size: 10px;">N° : ' . htmlspecialchars($id_soin) . '</div>
        </div>
    </div>

    <!-- TITRE soin -->
    <div class="soin-title">FICHE DE SOINS INFIRMIERS</div>

    <!-- INFORMATIONS PATIENT SIMPLES -->
    <div class="patient-info">
        <div class="patient-info-line">
            <span class="patient-name">Patient(e): ' . htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) . '</span>
        </div>
        <div class="patient-info-line">
            <span>Âge: ' . $age . '</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">DÉTAILS DU SOIN</div>
        <div class="info-line"><span class="label">Date :</span> ' . date('d/m/Y', strtotime($soin['date_soin'])) . ' ' . date('H:i', strtotime($soin['date_soin'])) . '</div>
        <div class="info-line"><span class="label">Infirmier :</span> ' . htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) . '</div>
    </div>

   <div class="section">
    <div class="section-title">CONSTANTES</div>

    <table class="table-constants">
        <tr>
            <td><strong>FC :</strong>' . afficherValeur($soin['fc'], ' bat/mn') . '</td>
            <td><strong>FR :</strong> ' . afficherValeur($soin['fr'], ' cle/mn') . '</td>
            <td><strong>TA :</strong> ' . afficherValeur($soin['tension'], ' mmHg') . '</td>
            <td><strong>Température :</strong> ' . afficherValeur($soin['temperature'], ' °C') . '</td>
        </tr>

        <tr>
            <td><strong>Poids :</strong> ' . afficherValeur($soin['poids'], ' Kg') . '</td>
            <td><strong>Taille :</strong> ' . afficherValeur($soin['taille'], ' Cm') . '</td>
            <td><strong>IMC :</strong> ' . afficherValeur($soin['imc']) . '</td>
            <td><strong>Saturation :</strong> ' . afficherValeur($soin['saturation']) . '</td>
        </tr>

        <tr>
            <td><strong>Glycémie :</strong> ' . afficherValeur($soin['glycemie'], ' g/dl') . '</td>
            <td><strong>Glasgow :</strong> ' . afficherValeur($soin['glasgow']) . '</td>
            <td><strong>Diurèse :</strong> ' . afficherValeur($soin['diurese']) . '</td>
            <td></td>
        </tr>
    </table>
    </div>

    <div class="section">
        <div class="section-title">ACTES INFIRMIERS RÉALISÉS</div>
        ' . $actes_html . '
    </div>

    <div class="section">
        <div class="section-title">OBSERVATIONS / RECOMMANDATIONS</div>
        <div>' . nl2br(htmlspecialchars($soin['observations'] ?? '—')) . '</div>
    </div>
    </div>

    <!-- SIGNATURE DU MÉDECIN -->
        <div class="medecin-signature">
            <p><strong>Dr. ' . htmlspecialchars($_SESSION['nom'] ?? 'Médecin') . '</strong><br>
            <span style="font-size: 12px;">Médecin traitant</span></p>
            <p style="margin-top: 20px;">Signature:</p>
        </div>

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
$dompdf->stream("fiche_soin_{$id_soin}.pdf", ['Attachment' => false]);
exit();