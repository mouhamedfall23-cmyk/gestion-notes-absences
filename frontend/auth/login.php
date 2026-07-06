<?php
/**
 * login.php — Page de connexion
 * Projet : Gestion des Notes et Absences des Étudiants
 * Auteur  : Étudiant 6
 */

session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit();
}

// ── Configuration base de données ──────────────────────────────────────────
$db_host = 'localhost';
$db_name = 'gestion_notes_absences';
$db_user = 'root';
$db_pass = '';

// ── Traitement du formulaire ────────────────────────────────────────────────
$erreur  = '';
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email      = trim($_POST['email'] ?? '');
    $mot_passe  = $_POST['mot_passe'] ?? '';

    // Validation basique
    if (empty($email) || empty($mot_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';

    } else {
        try {
            // Connexion PDO
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Recherche de l'utilisateur
            $stmt = $pdo->prepare(
                "SELECT idUtilisateur, nom, prenom, email, motDePasse, role
                 FROM utilisateur
                 WHERE email = :email
                 LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe (hashé avec password_hash)
            if ($utilisateur && password_verify($mot_passe, $utilisateur['motDePasse'])) {

                // Régénérer l'ID de session (sécurité)
                session_regenerate_id(true);

                // Stocker les données en session
                $_SESSION['user_id']     = $utilisateur['idUtilisateur'];
                $_SESSION['user_nom']    = $utilisateur['nom'];
                $_SESSION['user_prenom'] = $utilisateur['prenom'];
                $_SESSION['user_email']  = $utilisateur['email'];
                $_SESSION['user_role']   = $utilisateur['role'];
                $_SESSION['connecte']    = true;
                $_SESSION['login_time']  = time();

                // Redirection selon le rôle
                switch ($utilisateur['role']) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'enseignant':
                        header('Location: ../enseignant/dashboard.php');
                        break;
                    case 'etudiant':
                        header('Location: ../etudiant/dashboard.php');
                        break;
                    default:
                        header('Location: ../dashboard.php');
                }
                exit();

            } else {
                $erreur = 'Email ou mot de passe incorrect.';
            }

        } catch (PDOException $e) {
            $erreur = 'Erreur de connexion à la base de données. Veuillez réessayer.';
            // En production, logger l'erreur : error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion à l'application de gestion des notes et absences">
    <title>Connexion — Gestion Notes & Absences</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1f3a 0%, #16213e 50%, #0f3460 100%);
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #4f8ef7, #7c3aed);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }

        .logo h1 {
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
        }

        .logo p {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }

        input[type="email"]::placeholder,
        input[type="password"]::placeholder { color: rgba(255,255,255,0.3); }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #4f8ef7;
            background: rgba(79,142,247,0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: rgba(255,255,255,0.8); }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f8ef7, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            margin-top: 8px;
        }
        .btn-login:hover  { opacity: 0.9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
        }

        .footer-text {
            text-align: center;
            margin-top: 28px;
            color: rgba(255,255,255,0.35);
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="login-container">

    <div class="logo">
        <div class="logo-icon">🎓</div>
        <h1>Gestion Notes<br>& Absences</h1>
        <p>Connectez-vous à votre espace</p>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-error" role="alert">⚠️ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alert alert-success" role="alert">✅ <?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="exemple@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
                autocomplete="email"
            >
        </div>

        <div class="form-group">
            <label for="mot_passe">Mot de passe</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="mot_passe"
                    name="mot_passe"
                    placeholder="Votre mot de passe"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Afficher/Masquer le mot de passe">
                    👁️
                </button>
            </div>
        </div>

        <button type="submit" id="btn-connexion" class="btn-login">
            Se connecter
        </button>

    </form>

    <p class="footer-text">© <?= date('Y') ?> Gestion Notes & Absences</p>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('mot_passe');
    input.type = (input.type === 'password') ? 'text' : 'password';
}
</script>

</body>
</html>
