<?php
session_start();
require_once('../../traitement/fonction.php');

if (!isset($_SESSION['user_role'])) {
    header('Location: ../../index.php');
    exit();
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;
$redirect = $_GET['redirect'] ?? null;

if (!$id || !$action) {
    header('Location: liste_orienter.php');
    exit();
}

// traitement
traiterOrientation($connexion, $id, $action, $_SESSION['id_user']);

// redirection
if ($redirect === "dossier" && $patient_id) {
    header("Location: ../medecin/dossier_medical?id=".$patient_id."&id_o=".$id);
} else {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}

exit();
?>