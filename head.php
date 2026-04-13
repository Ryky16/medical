
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDICOUD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Police professionnelle -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
    .user-banner {
        height: 80px;
        /* plus de place pour les deux lignes */
        background-color: #f8f9fa;
        border-bottom: 1px solid #e5e5e5;
        font-family: 'Roboto', sans-serif;
        display: flex;
        align-items: center;
    }

    .user-banner .container {
        width: 100%;
        display: flex;
        flex-direction: column;
        /* empiler les textes */
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .banner-text {
        font-size: 22px;
        font-weight: 500;
        color: #333;
        letter-spacing: 0.3px;
    }

    .banner-text .app-name2 {
        background: linear-gradient(90deg, #1e3a5f, #0d6efd);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 24px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-left: 8px;
    }

    .banner-text i {
        font-size: 14px;
        color: #6c757d;
        /* gris neutre */
        display: block;
        margin-top: 2px;
    }

    .banner-text small {
        font-size: 12px;
        color: #dc3545;
        /* rouge pour le rôle */
        display: block;
        margin-top: 2px;
    }

    nav a {
        font-weight: 500;
        /* text-transform: uppercase; */
    }

    .app-name {
        font-family: 'Roboto', sans-serif;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #1e3a5f;
        /* bleu médical sobre */
        margin-left: 8px;
        text-transform: uppercase;
        transition: all 0.3s ease;
    }

    /* Petit effet au survol */
    .navbar-brand:hover .app-name {
        color: #0d6efd;
        letter-spacing: 2px;
    }

    .app-name2 {
        background: linear-gradient(90deg, #1e3a5f, #0d6efd);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-left: 8px;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/medical01/profils/tableau">
                <img src="/medical01/assets/images/logo.png" height="40" alt="logo" title="logo" />
                <span class="app-name">MEDICOUD</span>
            </a>
            <div class="navbar-nav ml-auto">
                <a class="nav-item nav-link active text-decoration-underline" href="/medical01/profils/tableau">Acceuil</a>
                <?php if (!($_SESSION['user_role'] === 'secretaire' && $_SESSION['profile_2'] !== 'accueil')): ?>
                <a class="nav-item nav-link active text-decoration-underline" href="/medical01/profils/medecin/recherche.php">
                    Recherche
                </a>
                <?php endif; ?>
                <?php if (($_SESSION['user_role'] == "secretaire") || ($_SESSION['user_role'] == "infirmier") || ($_SESSION['user_role'] == "medecin")) {?>
                <a class="nav-item nav-link active text-decoration-underline" href="/medical01/profils/secretaire/liste_orienter">Orientations</a>
                <?php } ?>
                <?php if (($_SESSION['user_role'] == "dba") ) {?>
                <a class="nav-item nav-link active text-decoration-underline" href="/medical01/profils/dba/all_users">Utilisateur(s)</a>
                <?php } ?>
                <a class="nav-item nav-link text-danger text-decoration-underline"
                    href="/medical01/profils/update_password.php">Mdp</a>
                <a class="nav-item nav-link text-danger text-decoration-underline"
                    onclick="return confirm('Etes-vous sûre de vouloir Deconnecter ?')"
                    href="/medical01/logout.php">Déconnexion</a>
            </div>
        </div>
    </nav>
    <div class="user-banner">
        <div class="container">
            <span class="banner-text">
                Bienvenue Dans <span class="app-name2">MEDICOUD</span>
                <i>
                    <?= (isset($_SESSION['sexe']) && $_SESSION['sexe'] === 'F') ? 'Mme. ' : 'M. ' ?>
                    <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
                </i>
                <small>
                    | <?= ucfirst(htmlspecialchars($_SESSION['user_role'] ?? 'user')) ?>
                    [ <?= ucfirst(htmlspecialchars($_SESSION['profile_2'] ?? 'user')) ?> ]
                </small>
            </span>
        </div>
    </div>