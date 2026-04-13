<?php
// Si vous voulez garder une version AJAX, voici une version simple qui fonctionne
header('Content-Type: application/json; charset=utf-8');
session_start();

// Mode test - permet de tester sans session
$testMode = true;

if (!$testMode && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'medecin')) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Simuler toujours un succès pour les tests
$code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

echo json_encode([
    'success' => true,
    'message' => 'Code généré avec succès',
    'code' => $code,
    'student_name' => 'Jean Dupont',
    'student_email' => 'jean.dupont@test.fr'
], JSON_PRETTY_PRINT);
?>