<?php
session_start();
require_once('../../traitement/fonction.php');

if (!isset($_SESSION['id_user'])) {
    header('Location: ../../index.php');
    exit();
}

if (!isset($connexion) || !$connexion instanceof mysqli) {
    die('Connexion à la base de données indisponible');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$id_patient    = (int)($_POST['id_patient'] ?? 0);
$libelles = trim($_POST['libelle'] ?? '');
$idUser   = (int)$_SESSION['id_user'];

if (ajouterOrientationMedicale($connexion, $id_patient, $idUser, $libelles)) {
    $_SESSION['success'] = 'Patient orienté avec succès';
    header('Location: liste_orienter.php?success=1');
} else {
    $_SESSION['error'] = var_dump($libelles).' Erreur lors de l’orientation';
    header('Location: ../medecin/recherche.php?error=1');
}
exit();
}
var_dump($idEtu);var_dump($idEtu);
