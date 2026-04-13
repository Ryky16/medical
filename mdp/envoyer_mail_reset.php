<?php
include( '../traitement/fonction.php' );
// ta connexion

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'GET' ) {
    $email = $_GET[ 'email' ];

    $stmt = $connexion->prepare( 'SELECT id FROM medical_users WHERE email = ?' );
    $stmt->bind_param( 's', $email );
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ( $user ) {
        $userId = $user[ 'id' ];
        $token = bin2hex( random_bytes( 32 ) );
        $expires = date( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );

        // Supprimer anciens tokens
        $connexion->query( "DELETE FROM reset_tokens WHERE user_id = $userId" );

        // Insérer le nouveau token
        $stmt = $connexion->prepare( 'INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)' );
        $stmt->bind_param( 'iss', $userId, $token, $expires );
        $stmt->execute();

        // Envoi de l'email (exemple simple)
        $resetLink = "http://localhost/medical01/mdp/reset?token=$token";
        $subject = "Reinitialisation de votre mot de passe";
        $message = "Cliquez ici pour réinitialiser votre mot de passe : $resetLink";
        //mail($email, $subject, $message);
        envoyerEmail($email, $subject, $message);

        $warning="Un lien de réinitialisation a été envoyé à votre email.";
        header('Location: /medical01/mdp/mot_de_passe_oublie?warning='.$warning);
            exit();
    } else {
        $warning="failed! cette email est inconnue.";
        header('Location: /medical01/mdp/mot_de_passe_oublie?warning='.$warning );
        exit();
    }
}