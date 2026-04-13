<?php
session_start();
require_once ('../../traitement/fonction.php');  // Connexion DB + fonctions
//require_once ('../../vendor/autoload.php');  // Dompdf

//use Dompdf\Dompdf;

// Vérification rôle
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'infirmier') {
    header('Location: ../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connexion->begin_transaction();

    try {
        $id_patient = intval($_POST['id_patient']);
        $id_infirmier = $_SESSION['id_user'];
        $date_soin = $_POST['date_soin'];

        $fc = $_POST['FC'] ?? null;
        $fr = $_POST['FR'] ?? null;
        $saturation = $_POST['saturation'] ?? null;
        $glycemie = $_POST['GC'] ?? null;
        $glasgow = $_POST['glasgow'] ?? null;
        $diurese = $_POST['diurese'] ?? null;
        $tension = $_POST['ta'] ?? null;
        $temperature = $_POST['temperature'] ?? null;
        $poids = $_POST['poids'] ?? null;
        $taille = $_POST['taille'] ?? null;
        $imc = $_POST['imc'] ?? null;
        $observations = $_POST['observations'] ?? null;

        $sql = 'INSERT INTO medical_soins_infirmiers 
    (id_patient,id_infirmier,date_soin,fc,fr,saturation,glycemie,glasgow,diurese,
     tension,temperature,poids,taille,imc,observations)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

        $stmt = $connexion->prepare($sql);
        $stmt->bind_param(
            'iissssssssdddss',
            $id_patient, $id_infirmier, $date_soin, $fc, $fr, $saturation,
            $glycemie, $glasgow, $diurese, $tension, $temperature,
            $poids, $taille, $imc, $observations
        );

        $stmt->execute();
        $id_soin = $stmt->insert_id;
        $stmt->close();

        if (!empty($_POST['actes'])) {
            foreach ($_POST['actes'] as $type_acte) {
                // Insérer acte
                $stmtActe = $connexion->prepare(
                    'INSERT INTO medical_soins_actes (id_soin,type_acte) VALUES (?,?)'
                );
                $stmtActe->bind_param('is', $id_soin, $type_acte);
                $stmtActe->execute();
                $id_acte = $stmtActe->insert_id;
                $stmtActe->close();

                //  ENREGISTRER DÉTAILS SELON TYPE
                enregistrerDetailsActe($connexion, $id_acte, $type_acte, $_POST);
            }
        }
        $connexion->commit();
    } catch (Exception $e) {
        $connexion->rollback();
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        header('Location: consultations.php?id=' . $id_patient);
        exit();
    }

   
$_SESSION['success'] = 'Fiche de soins enregistrée avec succès !';
header('Location: ../medecin/dossier_medical.php?id=' . $id_patient);
exit();
}