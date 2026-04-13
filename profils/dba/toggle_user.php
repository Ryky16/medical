<?php
session_start();
require_once('../../traitement/fonction.php');// si nécessaire

// Sécurité : seulement admin ou medecin par exemple
$roles_autorises = ['dba'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises)) {
    header('Location: ../../index.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: all_users.php');
    exit();
}

$id = (int) $_GET['id'];

// Récupérer le statut actuel
$stmt = $connexion->prepare("SELECT is_active FROM medical_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: all_users.php');
    exit();
}

// Inverser le statut
$newStatus = $user['is_active'] ? 0 : 1;

$stmt = $connexion->prepare("UPDATE medical_users SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $newStatus, $id);
$stmt->execute();
$stmt->close();

header('Location: all_users.php');
exit();