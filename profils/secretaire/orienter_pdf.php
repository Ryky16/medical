<?php
session_start();
require_once('../../traitement/fonction.php');
require_once('../../vendor/autoload.php');

use Dompdf\Dompdf;

$roles_autorises = ['medecin', 'infirmier', 'secretaire'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

$id_orienter = intval($_GET['id'] ?? 0);
$orientation = getOrientationById($connexion, $id_orienter);
$patient = $orientation;

$age = calculerAge($patient['date_naissance'] ?? '');

// Logo
$chemin_logo = $_SERVER['DOCUMENT_ROOT'] . '/medical01/assets/images/logo.png';
$logo_base64 = imageToBase64($chemin_logo);

$dompdf = new Dompdf();

$html = '
<html lang="fr">
<head>
<meta charset="UTF-8">

<style>

@page {
    size: A6 landscape;
    margin: 6mm;
}

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:11px;
    margin:0;
    padding:0;
}

.ticket{
    width:100%;
    height:auto;
    padding:8px;
    box-sizing:border-box;
}

.header{
    text-align:center;
    border-bottom:1px dashed #000;
    padding-bottom:4px;
    margin-bottom:6px;
}

.logo{
    max-height:35px;
}

.hospital{
    font-size:11px;
    font-weight:bold;
    letter-spacing:1px;
}

.title{
    text-align:center;
    font-size:14px;
    font-weight:bold;
    margin:6px 0;
    text-transform:uppercase;
}

.info{
    margin:3px 0;
}

.orientation{
    margin-top:6px;
    padding:6px;
    border:1px dashed #000;
    font-size:12px;
    text-align:center;
}

.orientation strong{
    font-size:13px;
}

.signature{
    margin-top:12px;
    text-align:right;
    font-size:10px;
}

.footer{
    margin-top:6px;
    text-align:center;
    font-size:8px;
    border-top:1px dashed #000;
    padding-top:3px;
}

</style>

</head>

<body>

<div class="ticket">

<div class="header">
    <img src="'.$logo_base64.'" class="logo"><br>
    <div class="hospital">SERVICE MEDICAL</div>
</div>

<div class="title">FICHE D\'ORIENTATION</div>

<div class="info">
<span>Patient(e) :  '.htmlspecialchars($patient['prenom'].' '.$patient['nom']).'</span>
</div>

<div class="info">
<span>Age : '.$age.'</span>
</div>

<div class="info">
<span>Date : '.date('d/m/Y à H:i', strtotime($patient['date_sys'])).'</span>
</div>

<div class="info">
<span>N° :'.$id_orienter.'</span> 
</div>

<div class="orientation">

Le patient est orienté vers :<br><br>

<strong>'.htmlspecialchars($orientation['libelle']).'</strong>

</div>

<div class="signature">
M/Mme : '.htmlspecialchars($_SESSION['nom'] ?? '').'<br>
Signature
</div>

<div class="footer">
Document remis au patient
</div>

</div>

</body>
</html>
';

// Génération PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A6', 'landscape');
$dompdf->render();
$dompdf->stream("orientation_{$id_orienter}.pdf", ['Attachment' => false]);
exit();
?>