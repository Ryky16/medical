-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 07 avr. 2026 à 11:49
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `coud_medical`
--

-- --------------------------------------------------------

--
-- Structure de la table `departement`
--

DROP TABLE IF EXISTS `departement`;
CREATE TABLE IF NOT EXISTS `departement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `libelle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `departement`
--

INSERT INTO `departement` (`id`, `nom`, `libelle`) VALUES
(1, 'DCH', 'Département du Capital humain'),
(2, 'DMG', 'Département des Moyens généraux'),
(3, 'DSAS', 'Département de la santé et de l\'Action social'),
(4, 'DRU', 'Département de la Restauration universitaire'),
(5, 'DCU', 'Département des Cités universitaires'),
(6, 'DACS', 'Département des Activités culturelles et sportives'),
(7, 'DE', 'Département de L\'Environnement'),
(8, 'DI', 'Département de L\'Informatique'),
(9, 'DST', 'Département des Services techniques'),
(10, 'DB', 'Département du Budget'),
(11, 'AC', 'Agence Comptable'),
(12, 'CSA/BAP', 'Bureau de l\'Accueil et du Protocole'),
(13, 'CSA/CC', 'Cellule de la Coopération'),
(14, 'CSA/CPM', 'Cellule de Passation des Marchés'),
(15, 'D/CS', 'Cellule de Suivi'),
(16, 'D/BC', 'Bureau du Courrier'),
(17, 'D/BAD', 'Bureau des Archives et de la Documentation'),
(18, 'D/CACG', 'Cellule Audit et Contrôle de gestion'),
(19, 'D/CC', 'Cellule de Communication');

-- --------------------------------------------------------

--
-- Structure de la table `medical_antecedents`
--

DROP TABLE IF EXISTS `medical_antecedents`;
CREATE TABLE IF NOT EXISTS `medical_antecedents` (
  `id_antecedent` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `antecedents_medicaux` text COLLATE utf8mb4_general_ci,
  `antecedents_chirurgicaux` text COLLATE utf8mb4_general_ci,
  `allergies` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allergies_precision` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `traitement_chronique` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `traitement_precision` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_antecedent`),
  KEY `id_etudiant` (`id_patient`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_antecedents`
--

INSERT INTO `medical_antecedents` (`id_antecedent`, `id_patient`, `antecedents_medicaux`, `antecedents_chirurgicaux`, `allergies`, `allergies_precision`, `traitement_chronique`, `traitement_precision`, `date_enregistrement`) VALUES
(3, 2, 'neant', 'neant', 'Non', '', 'Oui', 'Asthme, tension', '2026-02-26 09:08:40');

-- --------------------------------------------------------

--
-- Structure de la table `medical_consultations`
--

DROP TABLE IF EXISTS `medical_consultations`;
CREATE TABLE IF NOT EXISTS `medical_consultations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_user` int NOT NULL,
  `date_consultation` date DEFAULT NULL,
  `heure_consultation` time DEFAULT NULL,
  `motif` text COLLATE utf8mb4_general_ci,
  `signes_fonctionnels` text COLLATE utf8mb4_general_ci,
  `examen_clinique` text COLLATE utf8mb4_general_ci,
  `examen_clinique_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultats_analyses` text COLLATE utf8mb4_general_ci,
  `analyses_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultats_imagerie` text COLLATE utf8mb4_general_ci,
  `imagerie_pdf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diagnostic` text COLLATE utf8mb4_general_ci,
  `conduite_a_tenir` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_consultations`
--

INSERT INTO `medical_consultations` (`id`, `id_patient`, `id_user`, `date_consultation`, `heure_consultation`, `motif`, `signes_fonctionnels`, `examen_clinique`, `examen_clinique_pdf`, `resultats_analyses`, `analyses_pdf`, `resultats_imagerie`, `imagerie_pdf`, `diagnostic`, `conduite_a_tenir`, `created_at`) VALUES
(2, 2, 0, '2026-02-27', '12:33:00', 'test', 'test', 'test', NULL, 'test', NULL, 'test', NULL, 'test', 'Traitement', '2026-02-27 12:34:13'),
(3, 2, 4, '2026-03-06', '15:55:00', 'test2', 'test 2', 'test2', 'examen_1772207752_354.pdf', '', NULL, '', NULL, 'resultat tes update', 'Surveillance', '2026-02-27 15:55:52'),
(4, 2, 4, '2026-03-06', '09:16:00', 'elhadji test', 'fatigue', 'madfghj test', NULL, 'deux test2', NULL, '', NULL, 'fatique general', 'Traitement | Surveillance', '2026-03-06 09:20:01'),
(5, 2, 4, '2026-03-06', '10:06:00', 'azertyui', 'qsdfghjkl', 'sdfghj', NULL, '', NULL, 'dfg', 'imagerie_consultation_5.pdf', 'bbbbbbbbbbbbbbbbb', 'Surveillance | Référence', '2026-03-06 10:06:27'),
(6, 2, 4, '2026-03-06', '12:25:00', 'aaaaaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaaaa', 'examen_consultation_6_1772800024.pdf', '', NULL, '', NULL, '22222aaaaaaaaaaaaaa', 'Traitement', '2026-03-06 12:25:35');

-- --------------------------------------------------------

--
-- Structure de la table `medical_orientation`
--

DROP TABLE IF EXISTS `medical_orientation`;
CREATE TABLE IF NOT EXISTS `medical_orientation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_user` int NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `statut` enum('en_attente','valide','annule') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `date_sys` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `id_user_traitement` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_etudiant` (`id_patient`),
  KEY `idx_user` (`id_user`),
  KEY `idx_statut` (`statut`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_orientation`
--

INSERT INTO `medical_orientation` (`id`, `id_patient`, `id_user`, `libelle`, `statut`, `date_sys`, `updated_at`, `date_traitement`, `id_user_traitement`) VALUES
(7, 3, 1, 'Neurologue', 'en_attente', '2026-03-03 10:01:49', '2026-03-03 10:01:49', NULL, NULL),
(8, 4, 1, 'Ophtalmologiste', 'en_attente', '2026-03-03 10:31:04', '2026-03-03 10:31:04', NULL, NULL),
(6, 2, 1, 'Infirmier', 'en_attente', '2026-02-16 17:33:10', '2026-02-16 17:33:10', NULL, NULL),
(9, 5, 1, 'Infirmier', 'en_attente', '2026-03-03 11:34:41', '2026-03-03 11:34:41', NULL, NULL),
(10, 2, 1, 'Orthopediste', 'en_attente', '2026-03-03 11:43:18', '2026-03-03 11:43:18', NULL, NULL),
(11, 5, 1, 'Cardiologie;Echo Coeur', 'valide', '2026-03-03 11:49:10', '2026-03-03 11:54:19', '2026-03-03 11:54:19', 2),
(12, 5, 1, 'Cardiologie;Echo Coeur', 'valide', '2026-03-05 07:49:35', '2026-03-05 08:18:54', '2026-03-05 08:18:54', 4),
(13, 4, 1, 'Cardiologie;Echo Coeur', 'valide', '2026-03-05 07:53:37', '2026-03-05 08:24:40', '2026-03-05 08:24:40', 4),
(14, 4, 1, 'Ophtalmologiste', 'en_attente', '2026-03-10 12:35:54', '2026-03-10 12:35:54', NULL, NULL),
(15, 4, 1, 'Infirmier', 'valide', '2026-03-10 12:36:14', '2026-03-10 13:45:28', '2026-03-10 13:45:28', 3),
(16, 5, 1, 'ANALYSE LABO', 'en_attente', '2026-03-10 12:36:37', '2026-03-10 12:36:37', NULL, NULL),
(17, 2, 1, 'Generaliste', 'en_attente', '2026-03-10 12:37:06', '2026-03-10 12:37:06', NULL, NULL),
(18, 4, 1, 'Generaliste', 'en_attente', '2026-03-10 12:45:19', '2026-03-10 12:45:19', NULL, NULL),
(19, 4, 1, 'Gynécologie', 'en_attente', '2026-03-16 10:31:39', '2026-03-16 10:31:39', NULL, NULL),
(20, 6, 1, 'Generaliste', 'en_attente', '2026-03-16 10:36:02', '2026-03-16 10:36:02', NULL, NULL),
(21, 6, 1, 'Infirmier', 'valide', '2026-03-16 10:38:09', '2026-03-16 10:40:05', '2026-03-16 10:40:05', 3);

-- --------------------------------------------------------

--
-- Structure de la table `medical_patients`
--

DROP TABLE IF EXISTS `medical_patients`;
CREATE TABLE IF NOT EXISTS `medical_patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_patient` enum('etudiant','personnel') NOT NULL,
  `numero_identifiant` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(150) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `faculte` varchar(150) DEFAULT NULL,
  `niveau_etude` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fonction` varchar(150) DEFAULT NULL,
  `service` varchar(150) DEFAULT NULL,
  `statut_matrimonial` varchar(30) DEFAULT NULL,
  `groupe_sanguin` varchar(5) DEFAULT NULL,
  `maladies_chroniques` text,
  `mobilite_reduite` varchar(10) DEFAULT 'Non',
  `orphelin` varchar(50) NOT NULL,
  `contact_urgence_nom` varchar(150) DEFAULT NULL,
  `contact_urgence_telephone` varchar(20) DEFAULT NULL,
  `contact_urgence_profession` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_identifiant` (`numero_identifiant`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_patients`
--

INSERT INTO `medical_patients` (`id`, `type_patient`, `numero_identifiant`, `nom`, `prenom`, `date_naissance`, `sexe`, `telephone`, `email`, `adresse`, `faculte`, `niveau_etude`, `fonction`, `service`, `statut_matrimonial`, `groupe_sanguin`, `maladies_chroniques`, `mobilite_reduite`, `orphelin`, `contact_urgence_nom`, `contact_urgence_telephone`, `contact_urgence_profession`, `created_at`) VALUES
(2, 'personnel', '9OOOO/M', 'DIOP', 'EL HADJI MADIOP', '2000-11-16', 'M', '784413400', 'diopelhadjimadiop@gmail.com', 'mbour', NULL, NULL, 'Agent Administratif', 'Département de L\'Informatique', 'Célibataire', 'B+', '', 'Non', 'Père', 'El Hadji Madiop diop', '784413400', 'informaticien', '2026-02-21 17:10:07'),
(4, 'etudiant', '20000ANV', 'SECK', 'Ndeye Mbenda', '2016-03-02', 'F', '784413400', 'ndeyembenda.seck@ucad.edu.sn', '12B_6', 'E.S.P.', 'Premiere Annee du Diplome d\'Etudes Superieures en ', NULL, NULL, 'Marié(e)', 'A+', '', 'Oui', 'Mère', 'El Hadji Madiop diop', '784413400', 'informaticien', '2026-03-03 10:12:48'),
(5, 'etudiant', '20000AC', 'DIOP', 'Ndeye Ndieme Coumba', '2006-09-03', 'F', '784413400', 'ndeyendiemecoumba.diop@ucad.edu.sn', 'mbour', 'E.S.P.', 'Deuxieme Annee du Diplome Universitaire de Technol', NULL, NULL, 'Célibataire', 'A-', '', 'Non', '', 'El Hadji Madiop diop', '764019647', 'tailleur', '2026-03-03 11:33:46'),
(6, 'etudiant', '20000DW', 'SENE', 'Ndeye Coumba Bintou Rassoul', '1970-01-01', 'F', '784413400', 'ndeyecoumbabintou.sene@ucad.edu.sn', '26B_1', 'E.S.P.', 'Deuxieme Annee du Diplome Universitaire de Technologie en Genie Chimique et Biologie Appliquee', NULL, NULL, 'Célibataire', 'O-', '', 'Non', '', 'El Hadji Madiop diop', '784413400', 'informaticien', '2026-03-16 10:35:42');

-- --------------------------------------------------------

--
-- Structure de la table `medical_prescriptions`
--

DROP TABLE IF EXISTS `medical_prescriptions`;
CREATE TABLE IF NOT EXISTS `medical_prescriptions` (
  `id_prescription` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_consultation` int NOT NULL,
  `ordonnance` text COLLATE utf8mb4_general_ci,
  `examens_complementaires` text COLLATE utf8mb4_general_ci,
  `certificat` enum('Oui','Non') COLLATE utf8mb4_general_ci DEFAULT 'Non',
  `type_certificat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_prescription` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_prescription`),
  KEY `id_consultation` (`id_consultation`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_prescriptions`
--

INSERT INTO `medical_prescriptions` (`id_prescription`, `id_user`, `id_consultation`, `ordonnance`, `examens_complementaires`, `certificat`, `type_certificat`, `date_prescription`, `created_at`) VALUES
(1, 0, 1, 'azertyu', 'dftyu vvv', 'Oui', 'repos', '2026-01-29', '2026-01-29 10:27:20'),
(2, 0, 3, 'test', 'ee rrrrtt\r\ntest', 'Non', 'neant', '2026-03-06', '2026-02-27 17:39:43'),
(7, 4, 2, 'test madiop ass', 'ass ZZZZ', 'Oui', 'azertyuio', '2026-03-06', '2026-03-06 09:01:40');

-- --------------------------------------------------------

--
-- Structure de la table `medical_soins_actes`
--

DROP TABLE IF EXISTS `medical_soins_actes`;
CREATE TABLE IF NOT EXISTS `medical_soins_actes` (
  `id_acte` int NOT NULL AUTO_INCREMENT,
  `id_soin` int NOT NULL,
  `type_acte` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_acte`),
  KEY `id_soin` (`id_soin`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_soins_actes`
--

INSERT INTO `medical_soins_actes` (`id_acte`, `id_soin`, `type_acte`, `created_at`) VALUES
(5, 4, 'Injection', '2026-02-25 14:25:43'),
(6, 4, 'Oxygénothérapie', '2026-02-25 14:25:43'),
(7, 5, 'Prelevement', '2026-02-25 14:37:52'),
(8, 5, 'Nursing', '2026-02-25 14:37:52'),
(9, 6, 'Transfusion', '2026-02-25 14:50:04'),
(10, 7, 'Pansement', '2026-02-25 14:53:45'),
(11, 8, 'Sondage/Drainage', '2026-02-25 14:57:25'),
(12, 9, 'Injection', '2026-03-02 09:05:59'),
(13, 9, 'Prelevement', '2026-03-02 09:05:59'),
(15, 10, 'Transfusion', '2026-03-04 16:29:52'),
(16, 10, 'Pansement', '2026-03-04 16:29:52'),
(17, 11, 'Pansement', '2026-03-16 10:42:00');

-- --------------------------------------------------------

--
-- Structure de la table `medical_soins_actes_details`
--

DROP TABLE IF EXISTS `medical_soins_actes_details`;
CREATE TABLE IF NOT EXISTS `medical_soins_actes_details` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_acte` int NOT NULL,
  `champ` varchar(100) NOT NULL,
  `valeur` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_acte` (`id_acte`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_soins_actes_details`
--

INSERT INTO `medical_soins_actes_details` (`id_detail`, `id_acte`, `champ`, `valeur`) VALUES
(13, 5, 'produit', 'Paracétamol'),
(14, 5, 'dose', '1 ml'),
(15, 5, 'voie', 'IM'),
(16, 6, 'debit', '1 L/min'),
(17, 6, 'duree', '30 min'),
(18, 7, 'type', 'Urinaire'),
(19, 7, 'analyse', 'Culture'),
(20, 7, 'heure', '14:37'),
(21, 8, 'date', '2026-02-25'),
(22, 8, 'heure', '14:37'),
(23, 9, 'produit', 'Sang Total'),
(24, 9, 'poches', '1'),
(25, 9, 'heure', '17:49'),
(26, 10, 'type', 'Complexe'),
(27, 10, 'localisation', 'Membre supérieur'),
(28, 11, 'type', 'Sondage vésical'),
(29, 11, 'calibre', 'CH 12'),
(30, 11, 'aspect', 'Clair'),
(31, 12, 'produit', 'paracetamol'),
(32, 12, 'dose', '2'),
(33, 12, 'voie', 'IM'),
(34, 12, 'heure', '10:05'),
(35, 13, 'type', 'GE'),
(36, 13, 'analyse', 'diareh'),
(37, 13, 'heure', '08:05'),
(41, 15, 'produit', 'Plaquettes'),
(42, 15, 'poches', '2'),
(43, 15, 'heure', '15:34'),
(44, 16, 'type', 'Simple'),
(45, 16, 'localisation', 'Membre supérieur'),
(46, 16, 'heure', '16:29'),
(47, 17, 'type', 'Simple'),
(48, 17, 'localisation', 'Membre supérieur'),
(49, 17, 'heure', '10:41');

-- --------------------------------------------------------

--
-- Structure de la table `medical_soins_infirmiers`
--

DROP TABLE IF EXISTS `medical_soins_infirmiers`;
CREATE TABLE IF NOT EXISTS `medical_soins_infirmiers` (
  `id_soin` int NOT NULL AUTO_INCREMENT,
  `id_patient` int NOT NULL,
  `id_infirmier` int NOT NULL,
  `date_soin` datetime NOT NULL,
  `fc` int DEFAULT NULL,
  `fr` int DEFAULT NULL,
  `saturation` varchar(10) DEFAULT NULL,
  `glycemie` varchar(20) DEFAULT NULL,
  `glasgow` int DEFAULT NULL,
  `diurese` varchar(20) DEFAULT NULL,
  `tension` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `poids` decimal(5,1) DEFAULT NULL,
  `taille` decimal(5,1) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `observations` text,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_soin`),
  KEY `id_patient` (`id_patient`),
  KEY `id_infirmier` (`id_infirmier`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_soins_infirmiers`
--

INSERT INTO `medical_soins_infirmiers` (`id_soin`, `id_patient`, `id_infirmier`, `date_soin`, `fc`, `fr`, `saturation`, `glycemie`, `glasgow`, `diurese`, `tension`, `temperature`, `poids`, `taille`, `imc`, `observations`, `pdf_path`, `created_at`) VALUES
(4, 2, 3, '2026-02-25 14:24:00', 70, 16, '', '', 5, '', '120/80', 37.0, 200.0, 200.0, 50.00, 'test 1', '../../uploads/soins/soin_4.pdf', '2026-02-25 14:25:43'),
(5, 2, 3, '2026-02-25 14:37:00', 70, 16, '', '', 5, '', '120/80', 37.0, 200.0, 200.0, 50.00, 'test 3', '../../uploads/soins/soin_5.pdf', '2026-02-25 14:37:52'),
(6, 2, 3, '2026-02-25 14:49:00', 70, 16, '', '', 5, '', '120/80', 37.0, 200.0, 200.0, 50.00, 'madiop', '../../uploads/soins/soin_6.pdf', '2026-02-25 14:50:04'),
(7, 2, 3, '2026-02-25 14:53:00', 70, 16, '', '', 5, '', '120/80', 37.0, 90.0, 200.0, 22.50, 'ass', '../../uploads/soins/soin_7.pdf', '2026-02-25 14:53:45'),
(8, 2, 3, '2026-02-25 14:56:00', 70, 16, '', '', 5, '', '120/80', 37.0, 90.0, 200.0, 22.50, 'test 3', '../../uploads/soins/soin_8.pdf', '2026-02-25 14:57:25'),
(9, 2, 3, '2026-03-02 09:03:00', 70, 16, '', '', 5, '', '120/80', 37.0, 90.0, 183.0, 26.87, 'madiop test', NULL, '2026-03-02 09:05:59'),
(10, 5, 3, '2026-03-04 15:34:00', 70, 16, '', '', 5, '', '120/80', 37.0, 90.0, 183.0, 26.87, 'a faire ...', NULL, '2026-03-04 15:34:44'),
(11, 6, 3, '2026-03-16 10:40:00', 70, 16, '', '', 0, '', '', 0.0, 0.0, 0.0, 0.00, 'qsdfghjk', NULL, '2026-03-16 10:42:00');

-- --------------------------------------------------------

--
-- Structure de la table `medical_users`
--

DROP TABLE IF EXISTS `medical_users`;
CREATE TABLE IF NOT EXISTS `medical_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sexe` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_1` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_2` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `var` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mdp` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `ip_last_login` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int NOT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_users`
--

INSERT INTO `medical_users` (`id`, `nom`, `prenom`, `sexe`, `username`, `email`, `password`, `telephone`, `profile_1`, `profile_2`, `var`, `mdp`, `is_active`, `last_login`, `ip_last_login`, `created_at`, `updated_at`, `updated_by`, `created_by`) VALUES
(1, 'diop', 'Amy', 'F', 'secretaire', 'amyfaye@coud.sn', '11af43dbc3e4d14f498633eba99515ce2d3fd9fc', '7844413400', 'secretaire', 'accueil', NULL, 'updated', 1, '2026-03-24 09:39:30', NULL, '2026-02-04 09:21:39', '2026-03-24 09:39:30', 0, NULL),
(2, 'mme', 'diop', 'F', 'sg2', 'mmesg2@coud.sn', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '7844413400', 'secretaire', 'infirmier', NULL, 'updated', 1, '2026-03-10 13:37:37', NULL, '2026-02-05 10:11:10', '2026-03-10 13:37:37', 0, NULL),
(3, 'diop', 'diop', 'M', 'infirmier', 'infirmier@coud.sn', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '7844413400', 'infirmier', 'infirmier', NULL, 'updated', 1, '2026-03-16 10:38:18', NULL, '2026-02-16 16:52:32', '2026-03-16 10:38:18', 0, NULL),
(4, 'faye', 'faye', 'F', 'medecin', 'medecin@coud.sn', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '7844413400', 'medecin', 'cardiologie', NULL, 'updated', 0, '2026-03-16 10:42:42', NULL, '2026-02-20 15:35:07', '2026-03-16 10:51:52', 0, NULL),
(5, 'dba', 'dba', 'M', 'dba', 'dba@coud.sn', '11af43dbc3e4d14f498633eba99515ce2d3fd9fc', '7844413400', 'dba', 'dba', NULL, 'updated', 1, '2026-03-16 10:49:07', NULL, '2026-03-04 17:50:52', '2026-03-16 10:49:07', 0, NULL),
(6, 'DIOP', 'El Hadji Madiop', 'M', 'madiop2000', 'elhadji1.diop@uadb.edu.sn', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '774412344', 'medecin', 'Generaliste', NULL, 'default', 1, NULL, NULL, '2026-03-04 20:08:39', '2026-03-16 10:51:46', 0, NULL),
(7, 'faye', 'pape waly', 'M', 'waly', 'papewaly@gmail.com', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '784413400', 'medecin', 'Gastrologie', NULL, 'default', 0, NULL, NULL, '2026-03-05 09:07:51', '2026-03-05 09:08:16', 0, NULL),
(8, 'faye', 'El Hadji', 'M', 'ass25', 'diopelhadjimadiop@gmail.com', '9ead80632f1a0ff63cc214fa50b034ae7f48dde4', '784413400', 'medecin', 'Echographie', NULL, 'default', 1, NULL, NULL, '2026-03-05 09:26:23', '2026-03-05 09:26:23', 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reset_tokens`
--

DROP TABLE IF EXISTS `reset_tokens`;
CREATE TABLE IF NOT EXISTS `reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reset_tokens_ibfk_2` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reset_tokens`
--

INSERT INTO `reset_tokens` (`id`, `user_id`, `token`, `expires_at`) VALUES
(2, 8, '38ef855452ded2df5c50a3c9306613025d37f73be016df0b9cf51fe8c8a448cf', '2026-03-10 10:16:18');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `medical_soins_actes`
--
ALTER TABLE `medical_soins_actes`
  ADD CONSTRAINT `medical_soins_actes_ibfk_1` FOREIGN KEY (`id_soin`) REFERENCES `medical_soins_infirmiers` (`id_soin`) ON DELETE CASCADE;

--
-- Contraintes pour la table `medical_soins_actes_details`
--
ALTER TABLE `medical_soins_actes_details`
  ADD CONSTRAINT `medical_soins_actes_details_ibfk_1` FOREIGN KEY (`id_acte`) REFERENCES `medical_soins_actes` (`id_acte`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reset_tokens`
--
ALTER TABLE `reset_tokens`
  ADD CONSTRAINT `reset_tokens_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `medical_users` (`id`) ON DELETE CASCADE;
COMMIT;


CREATE TABLE IF NOT EXISTS `paye_agent` (
    `matricule` varchar(50) NOT NULL,
    `nom` varchar(100) DEFAULT NULL,
    `prenom` varchar(150) DEFAULT NULL,
    `lieu_naiss` varchar(100) DEFAULT NULL,
    `date_naiss` date DEFAULT NULL,
    `sexe` varchar(10) DEFAULT NULL,
    `nin` varchar(50) DEFAULT NULL,
    PRIMARY KEY (`matricule`)
);

INSERT INTO `paye_agent` (`matricule`, `nom`, `prenom`, `lieu_naiss`, `date_naiss`, `sexe`, `nin`) VALUES
('MAT001', 'DIOP', 'Amadou', 'Dakar', '1985-03-15', 'M', 'NIN001'),
('MAT002', 'FALL', 'Fatou', 'Thiès', '1990-07-22', 'F', 'NIN002'),
('MAT003', 'NDIAYE', 'Moussa', 'Saint-Louis', '1988-11-10', 'M', 'NIN003');


CREATE TABLE IF NOT EXISTS `paye_fonction` (
    `matricule` varchar(50) NOT NULL,
    `fonction` varchar(150) DEFAULT NULL,
    PRIMARY KEY (`matricule`)
);


INSERT INTO `paye_fonction` (`matricule`, `fonction`) VALUES
('MAT001', 'Professeur'),
('MAT002', 'Administrateur'),
('MAT003', 'Technicien');


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
