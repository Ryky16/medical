<?php
// Script pour créer un mot de passe hashé
$password = 'passer';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash pour 'passer' : " . $hash;
?>