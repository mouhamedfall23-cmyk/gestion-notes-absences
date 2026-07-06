<?php
/**
 * session.php — Gestionnaire de session
 * Projet : Gestion des Notes et Absences des Étudiants
 * Auteur  : Étudiant 6
 *
 * Usage :
 *   require_once 'session.php';               // vérifier la connexion
 *   require_once 'session.php'; verifier_role('admin');  // vérifier le rôle
 */

session_start();

// ── Durée maximale de session : 30 minutes d'inactivité ───────────────────
define('SESSION_DUREE', 1800);

// ── Vérifier si la session a expiré ────────────────────────────────────────
if (isset($_SESSION['login_time'])) {
    $inactivite = time() - $_SESSION['login_time'];
    if ($inactivite > SESSION_DUREE) {
        // Session expirée : déconnecter proprement
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: login.php?expire=1');
        exit();
    }
    // Renouveler le timer d'activité
    $_SESSION['login_time'] = time();
}

// ── Vérifier si l'utilisateur est connecté ─────────────────────────────────
function est_connecte(): bool {
    return isset($_SESSION['connecte']) && $_SESSION['connecte'] === true
        && isset($_SESSION['user_id']);
}

// ── Forcer la connexion (à appeler en haut de chaque page protégée) ─────────
function exiger_connexion(): void {
    if (!est_connecte()) {
        header('Location: login.php?acces=interdit');
        exit();
    }
}

// ── Vérifier le rôle de l'utilisateur ─────────────────────────────────────
function verifier_role(string ...$roles): void {
    exiger_connexion();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../erreurs/403.php';
        exit();
    }
}

// ── Obtenir les infos de l'utilisateur connecté ───────────────────────────
function get_utilisateur(): array {
    return [
        'id'     => $_SESSION['user_id']     ?? null,
        'nom'    => $_SESSION['user_nom']    ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
        'role'   => $_SESSION['user_role']   ?? '',
    ];
}

// ── Obtenir le prénom + nom formaté ───────────────────────────────────────
function get_nom_complet(): string {
    return htmlspecialchars(
        ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')
    );
}

// ── Vérifier si l'utilisateur a un rôle spécifique ────────────────────────
function a_role(string $role): bool {
    return ($_SESSION['user_role'] ?? '') === $role;
}

// ── Affichage de la page de test de session (accès direct au fichier) ──────
// Cette section s'affiche uniquement si on accède directement à session.php
if (basename($_SERVER['PHP_SELF']) === 'session.php') {
    exiger_connexion();
    $user = get_utilisateur();
    $temps_restant = SESSION_DUREE - (time() - ($_SESSION['login_time'] ?? time()));
    $minutes = floor($temps_restant / 60);
    $secondes = $temps_restant % 60;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Active — Gestion Notes & Absences</title>
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

        .card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        .badge-admin     { background: rgba(239,68,68,0.2); color: #fca5a5; }
        .badge-enseignant{ background: rgba(79,142,247,0.2); color: #93c5fd; }
        .badge-etudiant  { background: rgba(34,197,94,0.2); color: #86efac; }

        h1 { color: #fff; font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .subtitle { color: rgba(255,255,255,0.5); font-size: 14px; margin-bottom: 32px; }

        .info-grid {
            display: grid;
            gap: 14px;
            margin-bottom: 32px;
        }

        .info-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .info-icon { font-size: 22px; width: 32px; text-align: center; flex-shrink: 0; }

        .info-label { color: rgba(255,255,255,0.4); font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #fff; font-size: 15px; font-weight: 500; margin-top: 2px; }

        .timer {
            background: rgba(79,142,247,0.1);
            border: 1px solid rgba(79,142,247,0.2);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .timer-value { color: #93c5fd; font-size: 22px; font-weight: 700; }
        .timer-label { color: rgba(255,255,255,0.4); font-size: 12px; }

        .btn-logout {
            display: block;
            width: 100%;
            padding: 14px;
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
            margin-top: 24px;
        }
        .btn-logout:hover { background: rgba(239,68,68,0.25); }
    </style>
</head>
<body>
<div class="card">

    <span class="badge badge-<?= htmlspecialchars($user['role']) ?>">
        <?= htmlspecialchars($user['role']) ?>
    </span>

    <h1>Session Active</h1>
    <p class="subtitle">Informations de votre session en cours</p>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-icon">👤</span>
            <div>
                <div class="info-label">Utilisateur connecté</div>
                <div class="info-value"><?= get_nom_complet() ?></div>
            </div>
        </div>
        <div class="info-item">
            <span class="info-icon">📧</span>
            <div>
                <div class="info-label">Email</div>
                <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
        </div>
        <div class="info-item">
            <span class="info-icon">🔑</span>
            <div>
                <div class="info-label">ID de session</div>
                <div class="info-value" style="font-size:12px;word-break:break-all;"><?= session_id() ?></div>
            </div>
        </div>
    </div>

    <div class="timer">
        <span class="info-icon">⏱️</span>
        <div>
            <div class="info-label">Expiration dans</div>
            <div class="timer-value"><?= $minutes ?>min <?= $secondes ?>s</div>
        </div>
    </div>

    <a href="logout.php" class="btn-logout">🚪 Se déconnecter</a>

</div>

<script>
// Compte à rebours en temps réel
let secondes = <?= $temps_restant ?>;
const timerEl = document.querySelector('.timer-value');

setInterval(() => {
    if (secondes <= 0) {
        timerEl.textContent = 'Expirée !';
        window.location.href = 'login.php?expire=1';
        return;
    }
    secondes--;
    const m = Math.floor(secondes / 60);
    const s = secondes % 60;
    timerEl.textContent = `${m}min ${s}s`;
}, 1000);
</script>

</body>
</html>
<?php
}
// ── Fin de la page de test ─────────────────────────────────────────────────
// Les pages qui font require_once 'session.php' n'exécutent que les fonctions
