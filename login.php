<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp   = $_POST['mot_de_passe'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin')      header('Location: admin/index.php');
        elseif ($user['role'] === 'enseignant') header('Location: enseignant/index.php');
        else                                header('Location: etudiant/index.php');
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — GestiNotes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1565C0; 
               display: flex; justify-content: center; align-items: center; 
               min-height: 100vh; }
        .card { background: white; padding: 40px; border-radius: 12px; 
                width: 380px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        h2 { color: #1565C0; text-align: center; margin-bottom: 8px; }
        p.subtitle { text-align: center; color: #888; font-size: 13px; 
                     margin-bottom: 28px; }
        label { display: block; font-size: 13px; font-weight: bold; 
                color: #333; margin-bottom: 5px; }
        input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; 
                border-radius: 6px; font-size: 14px; margin-bottom: 18px; }
        input:focus { outline: none; border-color: #1565C0; }
        button { width: 100%; padding: 12px; background: #1565C0; 
                 color: white; border: none; border-radius: 6px; 
                 font-size: 15px; font-weight: bold; cursor: pointer; }
        button:hover { background: #0d47a1; }
        .error { background: #ffebee; color: #c62828; padding: 10px; 
                 border-radius: 6px; font-size: 13px; margin-bottom: 16px; 
                 text-align: center; }
        .logo { text-align: center; font-size: 36px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">🎓</div>
    <h2>GestiNotes</h2>
    <p class="subtitle">UNCHK — Gestion des Notes & Absences</p>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Adresse email</label>
        <input type="email" name="email" placeholder="votre@email.sn" required>
        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" placeholder="••••••••" required>
        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>