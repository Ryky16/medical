<?php
session_start();
include 'fonction.php';

// Vérifier si les données du formulaire sont définies
//#################################### DEBUT Enregister une Panne #####################################################
if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['type_panne']) && isset($_POST['localisation']) &&
    isset($_POST['description']) && isset($_POST['niveau_urgence']) &&
    isset($_SESSION['id_user'])) {

    $type_panne = $_POST['type_panne'];
    $localisation = $_POST['localisation'];
    $description = $_POST['description'];
    $niveau_urgence = $_POST['niveau_urgence'];
    $date_enregistrement = date('d/m/Y') ;// La date actuelle

    $id_chef_residence = $_SESSION['id_user'];
    $profil1 = $_SESSION['profil2'];

    // Ajouter la valeur du profil1 à la localisation
    $localisation = $profil1 . " | " . $localisation;

    if (insertPanne($connexion, $type_panne, $date_enregistrement, $description, $localisation, $niveau_urgence, $id_chef_residence)) {
        if (strtolower($niveau_urgence) === 'Élevée') {
            notifierUrgence($connexion, $type_panne, $description, $localisation);
        }
        header('Location: ../profils/residence/ajoutPanne?success=1');
        exit();
    } else {
        header('Location: ../profils/residence/ajoutPanne');
        exit();
    }
} 
//#################################### FIN Enregister une Panne #####################################################

//#################################### DEBUT Enregister une Observation #####################################################
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['evaluation']) && isset($_POST['commentaire']) &&
    isset($_POST['idPanne']) && isset($_POST['idIntervention']) &&
    isset($_POST['idObservation'])) {

    $evaluationQualite = $_POST['evaluation'];
    $commentaireSuggestion = $_POST['commentaire'];
    $idUtilisateur = $_SESSION['id_user'];
    $idPanne = $_POST['idPanne'];
    $idIntervention = $_POST['idIntervention'];
    $idObservation = isset($_POST['idObservation']) ? $_POST['idObservation'] : null;
    $date_observation = date('d/m/Y'); // La date actuelle

    if (enregistrerObservation($connexion, $idPanne, $idUtilisateur, $idIntervention, $evaluationQualite, $date_observation, $commentaireSuggestion, $idObservation)) {
        header('Location: ../profils/residence/listPannes?obs=1');
        exit();
    } else {
        header('Location: ../profils/residence/observation');
        exit();
    }
}
//#################################### FIN Enregister une Observation #####################################################

//#################################### DEBUT Enregister une Intervention #####################################################
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['agent'], $_POST['details'], $_POST['idPanne'], $_POST['date_intervention'])
) {
    $personne_agent = trim($_POST['agent']);
    $description_action = trim($_POST['details']);
    $id_chef_atelier = $_SESSION['id_user'] ?? null;
    $id_panne = (int) $_POST['idPanne'];
    $date_intervention = $_POST['date_intervention'];

    $intervention_id = isset($_POST['intervention_id']) && is_numeric($_POST['intervention_id']) && $_POST['intervention_id'] > 0
        ? (int) $_POST['intervention_id']
        : null;

    // Convertir la date de dd/mm/yyyy vers yyyy-mm-dd
    $date_intervention = date('d/m/Y', strtotime(str_replace('-', '/', $date_intervention)));
    $date_sys = date('d/m/Y');;

    $resultat = "en cours";

    $isCreation = is_null($intervention_id);

    $success = $isCreation
        ? enregistrerIntervention($connexion, $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne)
        : updateIntervention($connexion, $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne, $intervention_id);

    header('Location: ' . ($success
        ? '../profils/dst/listPannes'
        : '../profils/dst/intervention'));
    exit();
}
//#################################### FIN Enregister une Intervention #####################################################


//########################## pour supprimer Intervention ######################################################

elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['intervention_id']) ) {
    $intervention_id = $_GET['intervention_id'];
    // Supprimer la panne de la base de données
    $sql = "DELETE FROM Intervention WHERE id = ?";
    if ($stmt = $connexion->prepare($sql)) {
        $stmt->bind_param("i", $intervention_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ../profils/dst/listPannes');
    exit();
    }

    header('Location: ../profils/dst/listPannes?echec=');
    exit();
} 
//########################### Fin pour supprimer Intervention #####################################################

//############### pour supprimer Panne ##################################
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panneDelete'])) {
    $panne_id = $_POST['panneDelete'];

    // Supprimer la panne de la base de données
    $sql = "DELETE FROM panne WHERE id = ?";
    if ($stmt = $connexion->prepare($sql)) {
        $stmt->bind_param("i", $panne_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ../profils/residence/listPannes');
    exit();
    }

    header('Location: ../profils/residence/listPannes?echec='.$panne_id);
    exit();
} 

// ##################################################################################################

//########################### pour Enregistrer une Imputation #######################################
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['idPanne'], $_POST['instruction'], $_POST['userId'], $_POST['type_panne'], $_POST['imputation_id'])) {

    $idPanne = (int) $_POST['idPanne'];
    $idChefDst = (int) $_POST['userId'];
    $instruction = trim($_POST['instruction']);
    $type_panne = htmlspecialchars($_POST['type_panne']);
    $dateImputation = date('d/m/Y');
    $resultat = "imputer";

    // Vérifie que imputation_id est défini et numérique
    $imputationId = null;
    if (!empty($_POST['imputation_id']) && is_numeric($_POST['imputation_id'])) {
        $imputationId = (int) $_POST['imputation_id'];
    }

    try {
        $success = enregistrerImputation($connexion, $idPanne, $idChefDst, $instruction, $resultat, $dateImputation, $imputationId);

        if ($success) {
            header("Location: ../profils/dst/listPannes?success=2&type_panne=" . urlencode($type_panne));
            exit();
        } else {
            throw new Exception("Échec de l'enregistrement de l'imputation.");
        }
    } catch (Exception $e) {
        // Tu peux enregistrer l’erreur dans un log ici si besoin
        header("Location: ../profils/dst/imputation?error=" . urlencode($e->getMessage()));
        exit();
    }
} 

//########################### FIN Enregistrer une Imputation #######################################
//############### pour supprimer imputation ##################################

elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['imputation_id']) ) {
    $imputation_id = $_GET['imputation_id'];
    // Supprimer la panne de la base de données
    $sql = "DELETE FROM imputation WHERE id = ?";
    if ($stmt = $connexion->prepare($sql)) {
        $stmt->bind_param("i", $imputation_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ../profils/dst/listPannes');
    exit();
    }

    header('Location: ../profils/dst/listPannes?echec=');
    exit();
} 
//############### Fin pour supprimer imputation ##################################

//############### Pour Ajouter un Utilisateur ##################################
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['username']) && isset($_POST['nom']) &&
    isset($_POST['prenom']) && isset($_POST['telephone']) &&
    isset($_POST['email']) && isset($_POST['profil1']) && 
    isset($_POST['profil2'])) {

    // Récupérer les données du formulaire
    $username = $_POST['username'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    $profil1 = $_POST['profil1'];
    $profil2 = $_POST['profil2'];
    $email = $_POST['email'];

    // Vérifier si c'est une mise à jour ou un nouvel utilisateur
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // --- MISE À JOUR ---
        $id = $_POST['id'];
        $motDePasse = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;

        // Vérifier si username ou email est utilisé par un autre utilisateur
        $stmt = $connexion->prepare("SELECT id FROM utilisateur WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Un autre utilisateur utilise déjà ce username ou cet email
            header('Location: ../profils/admin/addUser?user_id=' . $id . '&erreur=exist');
            exit();
        }

        $stmt->close();

        // Mise à jour si OK
        if (updateUtilisateur($connexion, $id, $username, $nom, $prenom, $email, $telephone, $profil1, $profil2, $motDePasse)) {
            header('Location: ../profils/admin/users?profil2='.$profil1);
            exit();
        } else {
            header('Location: ../profils/admin/addUser?user_id=' . $id . '&erreur=save');
            exit();
        }

    } else {
        // --- CRÉATION ---
        $motDePasse = $_POST['password'];

        // Vérifier si username ou email existe déjà
        $stmt = $connexion->prepare("SELECT id FROM utilisateur WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Username ou email déjà pris
            header('Location: ../profils/admin/addUser?erreur=exist');
            exit();
        }

        $stmt->close();

        // Enregistrement si OK
        if (enregistrerUtilisateur($connexion, $username, $nom, $prenom, $email, $telephone, $motDePasse, $profil1, $profil2)) {
            header('Location: ../profils/admin/users');
            exit();
        } else {
            header('Location: ../profils/admin/addUser?erreur=save');
            exit();
        }
    }
}



//############### pour supprimer Utilisateur ##################################
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'changeUserStatus') {
        $userId = $_POST['userStatusChange'];
        $newStatus = $_POST['newStatus'];

        // Mettre à jour le statut de l'utilisateur dans la base de données
        $sql = "UPDATE Utilisateur SET statut = ? WHERE id = ?";
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param('ii', $newStatus, $userId);
        if ($stmt->execute()) {
            
            header('Location: ../profils/admin/users?message=Statut modifié avec succès');
            exit();
        } else {
            header('Location: ../profils/admin/users?message=error');
            exit();
        }
        exit();
    }
}

//########################### pour Enregistrer une Imputation #######################################
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['article_id']) && isset($_POST['intervention_id']) &&
    isset($_POST['quantite']) && isset($_POST['date_sortie']) && isset($_POST['remarque'])) {

    $article_id = $_POST['article_id'];
    $intervention_id = $_POST['intervention_id'];
    $quantite = $_POST['quantite'];
    $date_sortie = $_POST['date_sortie'];
    $remarque = $_POST['remarque'];

    if (enregistrerSortie($connexion, $article_id, $intervention_id, $quantite, $date_sortie, $remarque)) {
        header('Location: ../profils/stock/nouvelle_sortie?success=2');
        exit();
    } else {
        header('Location: ../profils/stock/nouvelle_sortie');
        exit();
    }
}
//########################### FIN Enregistrer une Imputation #######################################

    
?>