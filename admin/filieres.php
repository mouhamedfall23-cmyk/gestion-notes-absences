<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('admin');

$success = '';
$error   = '';

// ── AJOUTER ──────────────────────────────────────────
if (isset($_POST['ajouter'])) {
    $nom  = trim($_POST['nom']);
    $code = strtoupper(trim($_POST['code']));

    if ($nom && $code) {
        try {
            $stmt = $pdo->prepare("INSERT INTO filieres (nom, code) VALUES (?, ?)");
            $stmt->execute([$nom, $code]);
            $success = "Filière ajoutée avec succès !";
        } catch (PDOException $e) {
            $error = "Ce code existe déjà. Choisissez un autre code.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// ── SUPPRIMER ─────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $id = (int) $_GET['supprimer'];
    $pdo->prepare("DELETE FROM filieres WHERE id = ?")->execute([$id]);
    $success = "Filière supprimée.";
}

// ── LISTE ─────────────────────────────────────────────
$filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Filières — GestiNotes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { width: 240px; background: #1A237E; min-height: 100vh; position: fixed; top: 0; left: 0; display: flex; flex-direction: column; }
        .sidebar-logo { padding: 28px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo span { font-size: 28px; }
        .sidebar-logo h2 { color: white; font-size: 18px; margin-top: 8px; }
        .sidebar-logo p { color: rgba(255,255,255,0.6); font-size: 11px; margin-top: 4px; }
        .sidebar nav { padding: 20px 0; flex: 1; }
        .sidebar nav a { display: flex; align-items: center; gap: 12px; padding: 13px 24px; color: rgba(255,255,255,0.75); text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,0.12); color: white; border-left: 3px solid #42A5F5; padding-left: 21px; }
        .sidebar nav a .icon { font-size: 18px; }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .sidebar-footer a:hover { color: white; }

        /* MAIN */
        .main { margin-left: 240px; flex: 1; }
        .topbar { background: white; padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .topbar h1 { font-size: 20px; color: #1A237E; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #555; }
        .avatar { width: 36px; height: 36px; background: #1565C0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; }

        .content { padding: 32px; }

        /* FORMULAIRE */
        .card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 28px; }
        .card h2 { font-size: 16px; color: #1A237E; margin-bottom: 20px; font-weight: bold; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; }
        label { display: block; font-size: 13px; font-weight: bold; color: #444; margin-bottom: 6px; }
        input[type=text] { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        input[type=text]:focus { outline: none; border-color: #1565C0; }
        .btn { padding: 10px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .btn-primary { background: #1565C0; color: white; }
        .btn-primary:hover { background: #0d47a1; }
        .btn-danger { background: #ffebee; color: #c62828; font-size: 13px; padding: 6px 14px; border-radius: 6px; text-decoration: none; }
        .btn-danger:hover { background: #ffcdd2; }

        /* ALERTES */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border-left: 4px solid #4CAF50; }
        .alert-error   { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }

        /* TABLEAU */
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #1A237E; color: white; padding: 12px 16px; text-align: left; font-size: 13px; }
        thead th:first-child { border-radius: 8px 0 0 0; }
        thead th:last-child  { border-radius: 0 8px 0 0; }
        tbody tr { border-bottom: 1px solid #f0f0f0; }
        tbody tr:hover { background: #f8fbff; }
        tbody td { padding: 12px 16px; font-size: 14px; color: #333; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; background: #E3F2FD; color: #1565C0; }
        .empty { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <span>🎓</span>
        <h2>GestiNotes</h2>
        <p>Administrateur</p>
    </div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="utilisateurs.php"><span class="icon">👥</span> Utilisateurs</a>
        <a href="filieres.php" class="active"><span class="icon">🏫</span> Filières</a>
        <a href="modules.php"><span class="icon">📚</span> Modules</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">🚪 Se déconnecter</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>🏫 Gestion des Filières</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>

    <div class="content">

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= $error ?></div>
        <?php endif; ?>

        <!-- Formulaire ajout -->
        <div class="card">
            <h2>➕ Ajouter une filière</h2>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Nom de la filière</label>
                        <input type="text" name="nom" placeholder="Ex: Informatique Développement" required>
                    </div>
                    <div>
                        <label>Code</label>
                        <input type="text" name="code" placeholder="Ex: IDA" required>
                    </div>
                    <div>
                        <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des filières -->
        <div class="card">
            <h2>📋 Liste des filières (<?= count($filieres) ?>)</h2>
            <?php if (empty($filieres)): ?>
                <p class="empty">Aucune filière enregistrée. Ajoutez-en une ci-dessus.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filieres as $f): ?>
                    <tr>
                        <td><?= $f['id'] ?></td>
                        <td><?= htmlspecialchars($f['nom']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($f['code']) ?></span></td>
                        <td>
                            <a href="?supprimer=<?= $f['id'] ?>"
                               class="btn-danger"
                               onclick="return confirm('Supprimer cette filière ?')">
                               🗑 Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>