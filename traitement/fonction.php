<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();  // Toujours démarrer la session si elle n'est pas active
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

// Inclure la connexion à la base de données
function connexionBD()
{
    $connexion = mysqli_connect('localhost', 'root', '', 'coud_medical');

    // Vérifiez la connexion
    if ($connexion === false) {
        die('Erreur : Impossible de se connecter. ' . mysqli_connect_error());
    }

    // Fix UTF-8 pour les caractères spéciaux
    mysqli_set_charset($connexion, 'utf8mb4');

    return $connexion;
}

function connexionBD_2()
{
    $connexion_2 = mysqli_connect('localhost', 'root', '', 'coud_medical');

    // Vérifiez la connexion
    if ($connexion_2 === false) {
        die('Erreur : Impossible de se connecter. ' . mysqli_connect_error());
    }

    // Fix UTF-8 pour les caractères spéciaux
    mysqli_set_charset($connexion_2, 'utf8mb4');

    return $connexion_2;
}

$connexion = connexionBD();
$connexion_2 = connexionBD_2();

function login($username, $password)
{
    global $connexion;

    // Nettoyer les entrées
    $username = trim($username);

    // Version 1 : Avec SHA1 (si vous utilisez encore SHA1)
    $hashed_password = sha1($password);

    // Version 2 : Avec password_verify (recommandé) - À adapter selon votre structure
    // Si vos mots de passe sont hashés avec password_hash()

    // Requête SQL pour vérifier l'utilisateur avec son rôle
    $query = '
        SELECT u.*
        FROM medical_users u 
        WHERE u.username = ? 
        AND u.password = ? 
        AND u.is_active = 1
        LIMIT 1
    ';

    // Préparer et exécuter la requête
    $stmt = $connexion->prepare($query);
    if (!$stmt) {
        error_log('Erreur de préparation: ' . $connexion->error);
        return false;
    }

    $stmt->bind_param('ss', $username, $hashed_password);
    $stmt->execute();

    // Récupérer le résultat
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();

        // Alternative : Si vous utilisez password_verify()
        // if (password_verify($password, $user['password_hash'])) {
        //     return $user;
        // }

        return $user;
    }

    $stmt->close();
    return false; 
}

// FONCTION POUR UNE ORIENTATION MEDICALES
function ajouterOrientationMedicale(mysqli $connexion, int $id_patient, int $idUser, string $libelles): bool
{
    if ($id_patient <= 0 || $idUser <= 0 || empty($libelles)) {
        return false;
    }

    // Nettoyage des valeurs
    // $libelles = array_map('trim', $libelles);
    // $libelles = array_filter($libelles);
    // $libelles = array_unique($libelles);

    if (empty($libelles)) {
        return false;
    }

    // Transformer le tableau en chaîne
    // $libelleString = implode(', ', $libelles);
    $libelleString = $libelles;

    // Insertion directe
    $stmt = $connexion->prepare('
        INSERT INTO medical_orientation (id_patient, id_user, libelle)
        VALUES (?, ?, ?)
    ');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iis', $id_patient, $idUser, $libelleString);
    $result = $stmt->execute();

    $stmt->close();

    return $result;
}

// ################################
// Fonction pour vérifier si un utilisateur est connecté
function isLoggedIn()
{
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

// Fonction pour vérifier le rôle de l'utilisateur
function checkRole($requiredRole)
{
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    // Si plusieurs rôles sont acceptés
    if (is_array($requiredRole)) {
        return in_array($_SESSION['user_role'], $requiredRole);
    }

    return $_SESSION['user_role'] === $requiredRole;
}

// Fonction pour déconnecter l'utilisateur
function logout()
{
    session_start();
    $_SESSION = array();

    // Détruire le cookie de session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }

    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Fonction pour vérifier un code d'accès
function verifyAccessCode($code, $studentId, $medecinId)
{
    global $connexion;

    $now = date('Y-m-d H:i:s');

    $query = '
        SELECT id_code, id_patient, id_medecin 
        FROM codes_acces 
        WHERE code = ? 
        AND id_patient = ? 
        AND id_medecin = ? 
        AND utilise = 0 
        AND date_expiration > ? 
        LIMIT 1
    ';
    $code_hash = ($code);
    $stmt = $connexion->prepare($query);
    $stmt->bind_param('siis', $code_hash, $studentId, $medecinId, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $codeData = $result->fetch_assoc();

        // Marquer le code comme utilisé
        $updateQuery = 'UPDATE codes_acces SET utilise = 1, date_utilisation = NOW() WHERE id_code = ?';
        $updateStmt = $connexion->prepare($updateQuery);
        $updateStmt->bind_param('i', $codeData['id_code']);
        $updateStmt->execute();
        $updateStmt->close();

        $stmt->close();
        return $codeData;
    }

    $stmt->close();
    return false;
}

function sendMail($to, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'diopelhadjimadiop@gmail.com';
        $mail->Password = 'xfuy gpeo oisv gvya';  // PAS le mot de passe Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Encodage
        $mail->CharSet = 'UTF-8';

        // Expéditeur & destinataire
        $mail->setFrom('diopelhadjimadiop@gmail.com', 'Service Médical COUD');
        $mail->addAddress($to, $toName);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur Mail : {$mail->ErrorInfo}");
        return false;
    }
}

function generateAndSendAccessCode($studentId, $medecinId)
{
    global $connexion;

    $now = date('Y-m-d H:i:s');

    // Vérifier code existant
    $sql = 'SELECT code FROM codes_acces 
            WHERE id_patient=? AND id_medecin=? 
            AND utilise=0 AND date_expiration > ? LIMIT 1';

    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('iis', $studentId, $medecinId, $now);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['code'];
    }

    // Infos étudiant
    $sql = 'SELECT email, nom, prenom FROM etudiants WHERE id_etudiant=?';
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student)
        return false;

    // Nouveau code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $code_hash = ($code);
    $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Insert
    $sql = 'INSERT INTO codes_acces 
            (id_patient, id_medecin, code, date_expiration, ip_creation)
            VALUES (?, ?, ?, ?, ?)';

    $stmt = $connexion->prepare($sql);
    $stmt->bind_param(
        'iisss',
        $studentId,
        $medecinId,
        $code_hash,
        $expiration,
        $_SERVER['REMOTE_ADDR']
    );

    if (!$stmt->execute())
        return false;

    // Email HTML
    $body = "
        <p>Bonjour <strong>{$student['prenom']} {$student['nom']}</strong>,</p>
        <p>Un médecin souhaite accéder à votre dossier médical.</p>
        <p style='font-size:20px'>
            <strong>Code d'accès : {$code}</strong>
        </p>
        <p>⏳ Valable 15 minutes.</p>
        <p>Service Médical COUD</p>
    ";

    sendMail(
        $student['email'],
        $student['prenom'] . ' ' . $student['nom'],
        "Code d'accès – COUD'MEDICAL",
        $body
    );

    return $code;
}

function getDonneesEtudiant($numero_carte)
{
    $result = [
        'faculte' => null,
        'departement' => null,
        'numero_carte' => null,
        'nom' => null,
        'prenom' => null,
        'date_naissance' => null,
        'lieu_naissance' => null,
        'sexe' => null,
        'num_identite' => null,
        'telephone' => null,
        'etat_inscription' => null,
        'niveau_formation' => null,
        'email_ucad' => null,
        'payant' => null,
        'annee' => null
    ];

    try {
        $json_url = "etudiant/$numero_carte";
        $json = @file_get_contents($json_url);

        // Si l'API ne répond pas ou retourne une erreur
        if ($json === false) {
            return null;
        }

        $data = json_decode($json);

        // Vérifie si $data contient bien un index 0 et que c’est un objet
        if (isset($data[0]) && is_object($data[0])) {
            $result['faculte'] = $data[0]->faculte ?? null;
            $result['departement'] = $data[0]->departement ?? null;
            $result['nom'] = $data[0]->nom ?? null;
            $result['prenom'] = $data[0]->prenom ?? null;
            $result['date_naissance'] = $data[0]->date_naissance ?? null;
            $result['lieu_naissance'] = $data[0]->lieu_naissance ?? null;
            $result['sexe'] = $data[0]->sexe ?? null;
            $result['num_identite'] = $data[0]->num_identite ?? null;
            $result['telephone'] = $data[0]->telephone ?? null;
            $result['niveau_formation'] = $data[0]->niveau_formation ?? null;
            $result['etat_inscription'] = $data[0]->etat_inscription ?? null;
            $result['email_ucad'] = $data[0]->email_ucad ?? null;
            $result['payant'] = $data[0]->payant ?? null;
            $result['numero_carte'] = $data[0]->numero_carte ?? null;
            $result['annee'] = $data[0]->annee ?? null;
        } else {
            // Si l'API ne renvoie rien ou pas de résultat
            return null;
        }
    } catch (Exception $e) {
        return null;
    }

    return $result;
}

/**
 * Ajouter un étudiant dans la table patients
 *
 * @param mysqli $connexion
 * @param array $data
 * @return bool|string
 */
function addPatient(mysqli $connexion, array $data)
{
    /* ===============================
       FORMATAGE DATE DE NAISSANCE
    ================================ */
    if (empty($data['date_naissance'])) {
        return 'Date de naissance manquante';
    }

    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
    $dateNaissance = null;

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $data['date_naissance']);
        if ($date && $date->format($format) === $data['date_naissance']) {
            $dateNaissance = $date->format('Y-m-d');
            break;
        }
    }

    if (!$dateNaissance) {
        return 'Format de date de naissance invalide';
    }

    /* ===============================
       REQUÊTE SQL
    ================================ */
    $sql = 'INSERT INTO medical_patients
    (type_patient, numero_identifiant, nom, prenom, date_naissance,
     telephone, email, adresse, sexe, statut_matrimonial,
     groupe_sanguin, maladies_chroniques, mobilite_reduite,
     orphelin, contact_urgence_nom, contact_urgence_telephone,
     contact_urgence_profession, faculte, niveau_etude,
     fonction, service
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

    $stmt = $connexion->prepare($sql);
    if (!$stmt) {
        return 'Erreur préparation SQL : ' . $connexion->error;
    }

    $faculte = $data['faculte'] ?? null;
    $niveau_etude = $data['niveau_etude'] ?? null;
    $fonction = $data['fonction'] ?? null;
    $service = $data['service'] ?? null;

    $stmt->bind_param(
        'sssssssssssssssssssss',
        $data['type_patient'],
        $data['numero_identifiant'],
        $data['nom'],
        $data['prenom'],
        $dateNaissance,
        $data['telephone'],
        $data['email'],
        $data['adresse'],
        $data['sexe'],
        $data['statut_matrimonial'],
        $data['groupe_sanguin'],
        $data['maladies_chroniques'],
        $data['mobilite_reduite'],
        $data['orphelin'],
        $data['contact_urgence_nom'],
        $data['contact_urgence_telephone'],
        $data['contact_urgence_profession'],
        $faculte,
        $niveau_etude,
        $fonction,
        $service
    );

    /* ===============================
       EXÉCUTION
    ================================ */
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    }

    $error = $stmt->error;
    $stmt->close();
    return 'Erreur SQL : ' . $error;
}

function updatePatient($connexion, $id, $data)
{
    $sql = 'UPDATE medical_patients SET 
        type_patient = ?,
        numero_identifiant = ?,
        nom = ?,
        prenom = ?,
        date_naissance = ?,
        telephone = ?,
        adresse = ?,
        email = ?,
        maladies_chroniques = ?,
        groupe_sanguin = ?,
        sexe = ?,
        statut_matrimonial = ?,
        mobilite_reduite = ?,
        orphelin = ?,
        contact_urgence_nom = ?,
        contact_urgence_telephone = ?,
        contact_urgence_profession = ?,
        faculte = ?,
        niveau_etude = ?,
        fonction = ?,
        service = ?
    WHERE id = ?';

    $stmt = $connexion->prepare($sql);

    $stmt->bind_param(
        'sssssssssssssssssssssi',
        $data['type_patient'],
        $data['numero_identifiant'],
        $data['nom'],
        $data['prenom'],
        $data['date_naissance'],
        $data['telephone'],
        $data['adresse'],
        $data['email'],
        $data['maladies_chroniques'],
        $data['groupe_sanguin'],
        $data['sexe'],
        $data['statut_matrimonial'],
        $data['mobilite_reduite'],
        $data['orphelin'],
        $data['contact_urgence_nom'],
        $data['contact_urgence_telephone'],
        $data['contact_urgence_profession'],
        $data['faculte'],
        $data['niveau_etude'],
        $data['fonction'],
        $data['service'],
        $id
    );

    return $stmt->execute();
}

/**
 * Rechercher des étudiants dans la table patients
 *
 * @param mysqli $connexion
 * @param string $search
 * @param int $limit
 * @return array
 */
function searchPatients($connexion, $search, $limit = 20)
{
    $resultats = [];

    if (empty($search)) {
        return $resultats;
    }

    $searchTerm = '%' . $search . '%';

    $sql = '
        SELECT *
        FROM medical_patients
        WHERE nom LIKE ?
           OR prenom LIKE ?
           OR numero_identifiant LIKE ?
           OR email LIKE ?
        ORDER BY nom ASC, prenom ASC
        LIMIT ?
    ';

    $stmt = $connexion->prepare($sql);
    if (!$stmt) {
        return $resultats;
    }

    $stmt->bind_param(
        'ssssi',
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $limit
    );

    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $resultats[] = $row;
    }

    $stmt->close();

    return $resultats;
}

function allServices($connexion)
{
    $sql = 'SELECT DISTINCT nom, libelle FROM departement ORDER BY nom ASC;';
    $stmt = $connexion->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Échec de la préparation de la requête : ' . $connexion->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $services;
}

function searchPersonnel($connexion_2, $search)
{
    $search = '%' . $search . '%';

    $stmt = $connexion_2->prepare('
        SELECT pa.nom, pa.matricule, pa.prenom, pa.lieu_naiss, pa.date_naiss, pa.sexe, pa.nin, pf.matricule AS mat, pf.fonction 
        FROM paye_agent pa
        LEFT JOIN paye_fonction pf ON pf.matricule=pa.matricule
        WHERE nom LIKE ?
        OR prenom LIKE ?
        OR pa.matricule LIKE ?
    ');

    $stmt->bind_param('sss', $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function getPatientsById($connexion, $id)
{
    $stmt = $connexion->prepare('SELECT * FROM medical_patients WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Récupère tous les soins infirmiers complets d’un patient
 * (avec actes et détails)
 *
 * @param mysqli $connexion
 * @param int $id_patient
 * @return array
 */
function getSoinsInfirmiersByPatient(mysqli $connexion, int $id_patient): array
{
    $soins = [];

    // 1️⃣ Récupérer les soins principaux
    $sql = '
        SELECT *
        FROM medical_soins_infirmiers
        WHERE id_patient = ?
        ORDER BY id_soin DESC
    ';

    if ($stmt = $connexion->prepare($sql)) {
        $stmt->bind_param('i', $id_patient);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($soin = $result->fetch_assoc()) {
            $id_soin = $soin['id_soin'];

            // 2️⃣ Récupérer les actes du soin
            $actes = [];

            $sqlActes = '
                SELECT *
                FROM medical_soins_actes
                WHERE id_soin = ?
            ';

            if ($stmtActe = $connexion->prepare($sqlActes)) {
                $stmtActe->bind_param('i', $id_soin);
                $stmtActe->execute();
                $resultActes = $stmtActe->get_result();

                while ($acte = $resultActes->fetch_assoc()) {
                    $id_acte = $acte['id_acte'];

                    // 3️⃣ Récupérer les détails de l’acte
                    $details = [];

                    $sqlDetails = '
                        SELECT champ, valeur
                        FROM medical_soins_actes_details
                        WHERE id_acte = ?
                    ';

                    if ($stmtDetail = $connexion->prepare($sqlDetails)) {
                        $stmtDetail->bind_param('i', $id_acte);
                        $stmtDetail->execute();
                        $resultDetails = $stmtDetail->get_result();

                        while ($detail = $resultDetails->fetch_assoc()) {
                            $details[$detail['champ']] = $detail['valeur'];
                        }

                        $stmtDetail->close();
                    }

                    $acte['details'] = $details;
                    $actes[] = $acte;
                }

                $stmtActe->close();
            }

            $soin['actes'] = $actes;
            $soins[] = $soin;
        }

        $stmt->close();
    }

    return $soins;
}

function addAntecedents(mysqli $connexion, array $data)
{
    $sql = '
        INSERT INTO medical_antecedents
        (id_patient, antecedents_medicaux, antecedents_chirurgicaux,
         allergies, allergies_precision, traitement_chronique, traitement_precision)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ';

    $stmt = $connexion->prepare($sql);
    if (!$stmt) {
        return 'Erreur préparation requête';
    }

    $stmt->bind_param(
        'issssss',
        $data['id_patient'],
        $data['antecedents_medicaux'],
        $data['antecedents_chirurgicaux'],
        $data['allergies'],
        $data['allergies_precision'],
        $data['traitement_chronique'],
        $data['traitement_precision']
    );

    if ($stmt->execute()) {
        $stmt->close();
        return true;
    }

    $error = $stmt->error;
    $stmt->close();
    return $error;
}

/**
 * Récupère le dernier antécédent médical d'un étudiant
 * (historique conservé, dernier affiché)
 */
function getDernierAntecedentByPatient(mysqli $connexion, int $id_patient): ?array
{
    $sql = '
        SELECT *
        FROM medical_antecedents
        WHERE id_patient = ?
        ORDER BY date_enregistrement DESC
        LIMIT 1
    ';

    $stmt = $connexion->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id_patient);
    $stmt->execute();

    $result = $stmt->get_result();
    $antecedent = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    $stmt->close();

    return $antecedent;
}

function getAntecedentsByPatient(mysqli $connexion, int $id_patient): array
{
    $antecedents = [];

    $stmt = $connexion->prepare('
        SELECT *
        FROM medical_antecedents
        WHERE id_patient = ?
        ORDER BY date_enregistrement DESC
    ');

    $stmt->bind_param('i', $id_patient);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $antecedents[] = $row;
    }

    $stmt->close();

    return $antecedents;
}

function getConsultationsByPatient($connexion, $id_patient)
{
    $sql = '
        SELECT 
            c.id AS id_consultation,
            c.date_consultation,
            c.id_user,
            c.heure_consultation,
            c.motif,
            c.diagnostic,
            c.conduite_a_tenir,
            p.id_prescription
        FROM medical_consultations c
        LEFT JOIN medical_prescriptions p 
            ON p.id_consultation = c.id
        WHERE id_patient = ?
        ORDER BY date_consultation DESC, heure_consultation DESC
    ';

    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $id_patient);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

/**
 * Récupère une consultation par son ID (MySQLi)
 *
 * @param mysqli $connexion  Objet MySQLi de connexion
 * @param int $id_consultation
 * @return array|null         Tableau associatif de la consultation ou null si introuvable
 */
function getConsultationById($connexion, $id_consultation)
{
    $sql = 'SELECT * FROM medical_consultations WHERE id = ? LIMIT 1';

    $stmt = $connexion->prepare($sql);
    if (!$stmt) {
        // Erreur de préparation
        return null;
    }

    $stmt->bind_param('i', $id_consultation);
    $stmt->execute();
    $result = $stmt->get_result();

    $consultation = $result->fetch_assoc() ?? null;

    $stmt->close();
    return $consultation;
}

// ###########################"

/**
 * Récupère les antécédents d'un étudiant
 */
function getAntecedentsById($connexion, $id_patient)
{
    $sql = 'SELECT * FROM antecedents_medicaux WHERE id_patient = ?';
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $id_patient);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

/**
 * Met à jour les antécédents d'un étudiant
 */
function updateAntecedents($connexion, $data)
{
    $sql = 'UPDATE antecedents_medicaux SET 
            antecedents_medicaux = ?,
            antecedents_chirurgicaux = ?,
            allergies = ?,
            allergies_details = ?,
            traitements_chroniques = ?,
            traitements_details = ?,
            date_maj = NOW()
            WHERE id_patient = ?';

    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('ssssssi',
        $data['antecedents_medicaux'],
        $data['antecedents_chirurgicaux'],
        $data['allergies'],
        $data['allergies_details'],
        $data['traitements_chroniques'],
        $data['traitements_details'],
        $data['id_patient']);

    return $stmt->execute();
}

// ############# FONCTION POUR RECUPERER LES ORIENTATION SECRETAIRE PAR JOUR ##############
function getOrientationsParDate(mysqli $connexion, ?string $date = null, ?string $libelle = null): array
{
    if ($date === null) {
        $date = date('Y-m-d');
    }

    $sql = '
        SELECT 
            mo.id,
            mo.libelle,
            mo.statut,
            mo.date_sys,

            e.id AS patient_id,
            e.nom,
            e.prenom,
            e.numero_identifiant,
            e.telephone,
            e.type_patient,
            e.sexe,
            e.faculte,
            e.fonction,
            e.service,

            mu.nom AS user_nom,
            mu.prenom AS user_prenom

        FROM medical_orientation mo
        INNER JOIN medical_patients e ON e.id = mo.id_patient
        LEFT JOIN medical_users mu ON mu.id = mo.id_user

        WHERE DATE(mo.date_sys) = ?
    ';

    $params = [$date];
    $types = 's';

    // Filtre libelle sauf si accueil
    if (!empty($libelle) && strtolower($libelle) !== 'accueil') {
        $sql .= ' AND mo.libelle LIKE ?';
        $params[] = "%$libelle%";
        $types .= 's';
    }

    /* TRI PRIORITAIRE */
    $sql .= "
        ORDER BY 
            CASE 
                WHEN mo.statut = 'en_attente' THEN 1
                WHEN mo.statut = 'valide' THEN 2
                WHEN mo.statut = 'annule' THEN 3
                ELSE 4
            END,
            mo.date_sys ASC
    ";

    $stmt = $connexion->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $stats = [
        'en_attente' => 0,
        'valide' => 0,
        'annule' => 0
    ];

    foreach ($data as $row) {
        if (isset($stats[$row['statut']])) {
            $stats[$row['statut']]++;
        }
    }

    $stmt->close();
    return [
        'data' => $data,
        'stats' => $stats
    ];
}

function getPatientById($connexion, $id)
{
    $stmt = $connexion->prepare('SELECT * FROM medical_patients WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function enregistrerDetailsActe($connexion, $id_acte, $type, $data)
{
    $details = [];

    switch ($type) {
        case 'Injection':
            $details = [
                'produit' => $data['injection_produit'] ?? null,
                'dose' => $data['injection_dose'] ?? null,
                'voie' => $data['injection_voie'] ?? null,
                'heure' => $data['injection_heure'] ?? null,
            ];
            break;

        case 'Perfusion':
            $details = [
                'produit' => $data['perfusion_produit'] ?? null,
                'volume' => $data['perfusion_dose'] ?? null,
                'debit' => $data['perfusion_debit'] ?? null,
                'heure' => $data['perfusion_heure'] ?? null,
            ];
            break;

        case 'Transfusion':
            $details = [
                'produit' => $data['transfusion_produit'] ?? null,
                'poches' => $data['transfusion_poches'] ?? null,
                'heure' => $data['transfusion_heure'] ?? null,
            ];
            break;

        case 'Oxygénothérapie':
            $details = [
                'debit' => $data['oxygene_debit'] ?? null,
                'duree' => $data['oxygene_duree'] ?? null,
                'heure' => $data['oxygene_heure'] ?? null,
            ];
            break;

        case 'Nebulisation':
            $details = [
                'produit' => $data['neb_produit'] ?? null,
                'dose' => $data['neb_dose'] ?? null,
                'seances' => $data['neb_seances'] ?? null,
                'heure' => $data['neb_heure'] ?? null,
            ];
            break;

        case 'Pansement':
            $details = [
                'type' => $data['pansement_type'] ?? null,
                'localisation' => $data['pansement_localisation'] ?? null,
                'heure' => $data['pansement_heure'] ?? null,
            ];
            break;

        case 'Sondage/Drainage':
            $details = [
                'type' => $data['sondage_type'] ?? null,
                'calibre' => $data['sondage_calibre'] ?? null,
                'heure' => $data['sondage_heure'] ?? null,
            ];
            break;

        case 'Prelevement':
            $details = [
                'type' => $data['prelevement_type'] ?? null,
                'analyse' => $data['prelevement_analyse'] ?? null,
                'heure' => $data['prelevement_heure'] ?? null,
            ];
            break;

        case 'Nursing':
            $details = [
                'date' => $data['nursing_date'] ?? null,
                'heure' => $data['nursing_heure'] ?? null,
            ];
            break;
    }

    foreach ($details as $champ => $valeur) {
        if (!empty($valeur)) {
            $stmtDetail = $connexion->prepare(
                'INSERT INTO medical_soins_actes_details (id_acte,champ,valeur)
                 VALUES (?,?,?)'
            );

            $stmtDetail->bind_param('iss', $id_acte, $champ, $valeur);
            $stmtDetail->execute();
            $stmtDetail->close();
        }
    }
}

/**
 * Récupère un soin infirmier par son ID
 *
 * @param mysqli $connexion
 * @param int $id_soin
 * @return array|null
 */
function getSoinById(mysqli $connexion, int $id_soin): ?array
{
    $sql = 'SELECT * FROM medical_soins_infirmiers WHERE id_soin = ? LIMIT 1';

    if ($stmt = $connexion->prepare($sql)) {
        $stmt->bind_param('i', $id_soin);
        $stmt->execute();
        $result = $stmt->get_result();

        $soin = $result->fetch_assoc() ?: null;

        $stmt->close();
        return $soin;
    }

    return null;
}

function getPrescriptionById($connexion, $id)
{
    $sql = 'SELECT 
    p.ordonnance,
    p.id_prescription,
    p.id_user,
    p.examens_complementaires,
    p.certificat,
    p.type_certificat,
    p.date_prescription,
    c.date_consultation,
    c.diagnostic,
    pt.*
    FROM medical_prescriptions p
    JOIN medical_consultations c ON c.id = p.id_consultation
    JOIN medical_patients pt ON pt.id = c.id_patient
     WHERE id_prescription = ?';
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}
function getOrientationById($connexion, $id)
{
    $sql = 'SELECT 
    p.nom,
    p.prenom,
    p.date_naissance,
    o.*
    FROM medical_orientation o
    JOIN medical_patients p ON o.id_patient = p.id
     WHERE o.id = ?';
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function imageToBase64($path)
{
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    // Image par défaut si le logo n'existe pas (optionnel)
    return '';
}

function calculerAge($date_naissance)
{
    if (empty($date_naissance))
        return 'Non renseigné';
    $dateNaissance = new DateTime($date_naissance);
    $dateAujourdhui = new DateTime();
    $age = $dateAujourdhui->diff($dateNaissance);
    return $age->y . ' ans';
}

function getMedicalUsers(mysqli $connexion, ?string $dateRecherche = null): array
{
    // Si une date est fournie → filtrer
    if (!empty($dateRecherche)) {
        $stmt = $connexion->prepare('
            SELECT * 
            FROM medical_users
            WHERE DATE(created_at) = ?
            ORDER BY nom ASC, prenom ASC, created_at DESC
        ');

        $stmt->bind_param('s', $dateRecherche);
    } else {
        // Sinon → afficher tout
        $stmt = $connexion->prepare('
            SELECT * 
            FROM medical_users
            ORDER BY nom ASC, prenom ASC, created_at DESC
        ');
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $users;
}

function traiterOrientation($connexion, $id, $action, $id_user)
{
    // Vérifier action autorisée
    if (!in_array($action, ['valide', 'annule'])) {
        return false;
    }

    $stmt = $connexion->prepare('
        UPDATE medical_orientation
        SET statut = ?, date_traitement = NOW(), id_user_traitement = ?
        WHERE id = ?
    ');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sii', $action, $id_user, $id);
    $result = $stmt->execute();

    $stmt->close();
 
    return $result;
}

function ajouterUtilisateur($connexion, $nom, $prenom, $username, $email, $telephone, $sexe, $profile_1, $profile_2, $created_by)
{
    $password = sha1('coud2025');
    $default_mdp = 'coud2025';

    $stmt = $connexion->prepare('
        INSERT INTO medical_users
        (nom, prenom, username, email, telephone, sexe, profile_1, profile_2, password, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
    ');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'sssssssssi',
        $nom,
        $prenom,
        $username,
        $email,
        $telephone,
        $sexe,
        $profile_1,
        $profile_2,
        $password,
        $created_by
    );

    $result = $stmt->execute();

    $stmt->close();
    sms_compte_created($telephone, $username, $prenom, $default_mdp);

    return $result;
}

function modifierUtilisateur($connexion, $id, $nom, $prenom, $username, $email, $telephone, $sexe, $profile_1, $profile_2, $is_active, $updated_by)
{
    $stmt = $connexion->prepare('
        UPDATE medical_users SET
        nom = ?, 
        prenom = ?, 
        username = ?, 
        email = ?, 
        telephone = ?, 
        sexe = ?, 
        profile_1 = ?, 
        profile_2 = ?, 
        is_active = ?, 
        updated_by = ?
        WHERE id = ?
    ');

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'ssssssssiii',
        $nom,
        $prenom,
        $username,
        $email,
        $telephone,
        $sexe,
        $profile_1,
        $profile_2,
        $is_active,
        $updated_by,
        $id
    );

    $result = $stmt->execute();

    $stmt->close();

    return $result;
}

// Fonction Envoi SMS Apres Creation Compte///////////////////////////////////

function sms_compte_created($numero_destinataire, $login, $prenoms, $default_mdp)
{
    $user = 'admin';
    $mot_de_passe = 'Pw@';

    // NOUVEAU CODE
    $message = 'Bonjour ' . $prenoms . '. Voici vos infos de connexion sur https://medicoud.com. Utilisateur: NumeroCarte. Mot de passe: ' . $default_mdp . '.';
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://167.240.133.897/SW',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
    "ws_key": "' . $user . '",
    "ws_secret": "' . $mot_de_passe . '",
    "message": "' . $message . '",
    "to": "' . $numero_destinataire . '"
}',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    //curl_close($curl);

    /*if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      echo $response;
    }*/
    /*if($err)
        echo "Erreur: le SMS n'a pas été envoyé !";
    else
        echo "Votre mot de passe vous a été envoyé par SMS au ".$numero_destinataire;*/
}

function ajouterConsultation(
    $connexion,
    $id_patient,
    $id_user,
    $date,
    $heure,
    $motif,
    $signes,
    $examen,
    $analyses,
    $imagerie,
    $diagnostic,
    $conduite,
    $files  // tableau $_FILES provenant du formulaire
) {
    // 1 INSERT initial sans les fichiers
    $sql = 'INSERT INTO medical_consultations (
                id_patient,
                id_user,
                date_consultation,
                heure_consultation,
                motif,
                signes_fonctionnels,
                examen_clinique,
                resultats_analyses,
                resultats_imagerie,
                diagnostic,
                conduite_a_tenir
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)';

    $stmt = $connexion->prepare($sql);

    if (!$stmt)
        return false;

    $stmt->bind_param(
        'iisssssssss',
        $id_patient,
        $id_user,
        $date,
        $heure,
        $motif,
        $signes,
        $examen,
        $analyses,
        $imagerie,
        $diagnostic,
        $conduite
    );

    $result = $stmt->execute();

    if (!$result) {
        $stmt->close();
        return false;
    }

    $id_consultation = $connexion->insert_id;
    $stmt->close();

    // 2 Upload fichiers et normalisation nom
    $pdf_examen = uploadPdf($files['examen_clinique_pdf'] ?? null, 'examen', $id_consultation);
    $pdf_analyses = uploadPdf($files['analyses_pdf'] ?? null, 'analyses', $id_consultation);
    $pdf_imagerie = uploadPdf($files['imagerie_pdf'] ?? null, 'imagerie', $id_consultation);

    // 3 Mettre à jour la ligne avec les fichiers uploadés
    $sqlUpdate = 'UPDATE medical_consultations SET
                    examen_clinique_pdf=?,
                    analyses_pdf=?,
                    imagerie_pdf=?
                  WHERE id=?';

    $stmtUpdate = $connexion->prepare($sqlUpdate);
    if (!$stmtUpdate)
        return false;

    $stmtUpdate->bind_param(
        'sssi',
        $pdf_examen,
        $pdf_analyses,
        $pdf_imagerie,
        $id_consultation
    );

    $result = $stmtUpdate->execute();
    $stmtUpdate->close();

    return $result ? $id_consultation : false;  // retourne l'id consultation si OK
}

// Gestion upload PDF
function uploadPdf($file, $prefix, $consultation_id)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        return null;
    }

    $dir = '../../uploads/consultations/';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // nom unique
    $filename = $prefix . '_consultation_' . $consultation_id . '_' . time() . '.pdf';

    $path = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $filename;
    }

    return null;
}

/**
 * Supprimer un PDF existant
 */
function deletePdf($filename)
{
    $path = '../../uploads/consultations/' . $filename;
    if (file_exists($path))
        unlink($path);
}

function envoyerEmail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        // Config SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // serveur SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'diopelhadjimadiop@gmail.com';
        $mail->Password = 'xfuy gpeo oisv gvya';  // pas le vrai mdp !
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur
        $mail->setFrom('diopelhadjimadiop@gmail.com', 'MEDICOUD');

        // Destinataire
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Envoi
        $mail->send();
        return true;
    } catch (Exception $e) {
        return 'Erreur : ' . $mail->ErrorInfo;
    }
}

function enregistrerIntervention($connexion, $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne) {
    $stmt = $connexion->prepare('INSERT INTO Intervention (date_intervention, description_action, personne_agent, date_sys, resultat, id_chef_atelier, id_panne) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sssssii', $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function updateIntervention($connexion, $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne, $intervention_id) {
    $stmt = $connexion->prepare('UPDATE Intervention SET date_intervention = ?, description_action = ?, personne_agent = ?, date_sys = ?, resultat = ?, id_chef_atelier = ?, id_panne = ? WHERE id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sssssiii', $date_intervention, $description_action, $personne_agent, $date_sys, $resultat, $id_chef_atelier, $id_panne, $intervention_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>