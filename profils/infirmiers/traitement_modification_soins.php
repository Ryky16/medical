<?php
session_start();
require_once('../../traitement/fonction.php');  // Connexion DB + fonctions

// Vérification rôle
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'infirmier') {
    header('Location: ../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connexion->begin_transaction();

    try {
        $id_patient = intval($_POST['id_patient']);
        $id_soin = intval($_POST['id_soin']); // Récupération de l'ID du soin à modifier
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

        // 1. MISE À JOUR DES INFORMATIONS PRINCIPALES DU SOIN
        $sql = 'UPDATE medical_soins_infirmiers 
                SET id_infirmier = ?, 
                    date_soin = ?,
                    fc = ?,
                    fr = ?,
                    saturation = ?,
                    glycemie = ?,
                    glasgow = ?,
                    diurese = ?,
                    tension = ?,
                    temperature = ?,
                    poids = ?,
                    taille = ?,
                    imc = ?,
                    observations = ?
                WHERE id_soin = ? AND id_patient = ?';

        $stmt = $connexion->prepare($sql);
        $stmt->bind_param(
            'isssssssssddssii',
            $id_infirmier, 
            $date_soin, 
            $fc, 
            $fr, 
            $saturation,
            $glycemie, 
            $glasgow, 
            $diurese, 
            $tension, 
            $temperature,
            $poids, 
            $taille, 
            $imc, 
            $observations,
            $id_soin,
            $id_patient
        );

        $stmt->execute();
        $stmt->close();

        // 2. SUPPRIMER LES ANCIENS ACTES POUR CE SOIN
        // D'abord récupérer les IDs des actes pour supprimer leurs détails
        $stmtSelect = $connexion->prepare("SELECT id_acte FROM medical_soins_actes WHERE id_soin = ?");
        $stmtSelect->bind_param("i", $id_soin);
        $stmtSelect->execute();
        $result = $stmtSelect->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Supprimer les détails de chaque acte
            $stmtDeleteDetails = $connexion->prepare("DELETE FROM medical_soins_actes_details WHERE id_acte = ?");
            $stmtDeleteDetails->bind_param("i", $row['id_acte']);
            $stmtDeleteDetails->execute();
            $stmtDeleteDetails->close();
        }
        $stmtSelect->close();

        // Supprimer les actes
        $deleteActes = $connexion->prepare('DELETE FROM medical_soins_actes WHERE id_soin = ?');
        $deleteActes->bind_param('i', $id_soin);
        $deleteActes->execute();
        $deleteActes->close();

        // 3. RÉINSERTION DES NOUVEAUX ACTES (si présents)
        if (!empty($_POST['actes'])) {
            foreach ($_POST['actes'] as $type_acte) {
                // Insérer le nouvel acte
                $stmtActe = $connexion->prepare(
                    'INSERT INTO medical_soins_actes (id_soin, type_acte) VALUES (?, ?)'
                );
                $stmtActe->bind_param('is', $id_soin, $type_acte);
                $stmtActe->execute();
                $id_acte = $stmtActe->insert_id;
                $stmtActe->close();

                // Enregistrer les détails spécifiques selon le type d'acte
                enregistrerDetailsActe($connexion, $id_acte, $type_acte, $_POST);
            }
        }

        // Validation de la transaction
        $connexion->commit();
        
        $_SESSION['success'] = 'Fiche de soins modifiée avec succès !';
        
    } catch (Exception $e) {
        $connexion->rollback();
        $_SESSION['error'] = 'Erreur lors de la modification : ' . $e->getMessage();
    }
    
    // Redirection vers le dossier médical
    header('Location: ../medecin/dossier_medical.php?id=' . $id_patient);
    exit();
}
?>