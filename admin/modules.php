<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('admin');

$message = '';
$erreur  = '';

// ── AJOUTER ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom          = trim($_POST['nom']);
    $code         = strtoupper(trim($_POST['code']));
    $coefficient  = (float)$_POST['coefficient'];
    $semestre     = (int)$_POST['semestre'];
    $filiere_id   = (int)$_POST['filiere_id'];

    if ($nom && $code && $filiere_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO modules (nom, code, coefficient, semestre, filiere_id) VALUES (?,?,?,?,?)");
            $stmt->execute([$nom, $code, $coefficient, $semestre, $filiere_id]);
            $message = "Module ajouté avec succès !";
        } catch (PDOException $e) {
            $erreur = "Ce code module existe déjà.";
        }
    } else {
        $erreur = "Tous les champs obligatoires doivent être remplis.";
    }
}

// ── SUPPRIMER ────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM modules WHERE id = ?")->execute([(int)$_GET['supprimer']]);
    $message = "Module supprimé.";
}

// ── MODIFIER ─────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id          = (int)$_POST['id'];
    $nom         = trim($_POST['nom']);
    $code        = strtoupper(trim($_POST['code']));
    $coefficient = (float)$_POST['coefficient'];
    $semestre    = (int)$_POST['semestre'];
    $filiere_id  = (int)$_POST['filiere_id'];

    $pdo->prepare("UPDATE modules SET nom=?, code=?, coefficient=?, semestre=?, filiere_id=? WHERE id=?")
        ->execute([$nom, $code, $coefficient, $semestre, $filiere_id, $id]);
    $message = "Module modifié avec succès !";
}

// ── DONNÉES ───────────────────────────────────────────
$modules  = $pdo->query("SELECT m.*, f.nom AS filiere_nom FROM modules m 
                          LEFT JOIN filieres f ON m.filiere_id = f.id 
                          ORDER BY m.semestre, m.nom")->fetchAll();
$filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();

$module_edit = null;
if (isset($_GET['modifier'])) {
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([(int)$_GET['modifier']]);
    $module_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modules — GestiNotes</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#f0f2f5; display:flex; min-height:100vh; }
        .sidebar { width:240px; background:#1A237E; min-height:100vh;
                   display:flex; flex-direction:column; position:fixed; top:0; left:0; }
        .sidebar-logo { padding:28px 20px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-logo span { font-size:28px; }
        .sidebar-logo h2 { color:white; font-size:18px; margin-top:8px; }
        .sidebar-logo p  { color:rgba(255,255,255,0.6); font-size:11px; margin-top:4px; }
        .sidebar nav { padding:20px 0; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; padding:13px 24px;
                         color:rgba(255,255,255,0.75); text-decoration:none; font-size:14px; transition:all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background:rgba(255,255,255,0.12);
                         color:white; border-left:3px solid #42A5F5; padding-left:21px; }
        .sidebar nav a .icon { font-size:18px; }
        .sidebar-footer { padding:16px 24px; border-top:1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px;
                            display:flex; align-items:center; gap:8px; }
        .sidebar-footer a:hover { color:white; }
        .main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
        .topbar { background:white; padding:16px 32px; display:flex; justify-content:space-between;
                  align-items:center; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
        .topbar h1 { font-size:20px; color:#1A237E; font-weight:bold; }
        .topbar .user-info { display:flex; align-items:center; gap:10px; font-size:14px; color:#555; }
        .topbar .avatar { width:36px; height:36px; background:#1565C0; border-radius:50%;
                          display:flex; align-items:center; justify-content:center;
                          color:white; font-weight:bold; font-size:14px; }
        .content { padding:32px; }
        .msg-ok  { background:#E8F5E9; color:#2E7D32; padding:12px 16px; border-radius:8px;
                   margin-bottom:20px; border-left:4px solid #4CAF50; font-size:14px; }
        .msg-err { background:#FFEBEE; color:#C62828; padding:12px 16px; border-radius:8px;
                   margin-bottom:20px; border-left:4px solid #F44336; font-size:14px; }
        .layout { display:grid; grid-template-columns:360px 1fr; gap:24px; }
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#1A237E; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #E3F2FD; }
        label { display:block; font-size:13px; font-weight:bold; color:#444; margin-bottom:5px; }
        input[type=text], input[type=number], select {
            width:100%; padding:10px 14px; border:1px solid #ddd;
            border-radius:6px; font-size:14px; margin-bottom:16px; background:white; }
        input:focus, select:focus { outline:none; border-color:#1565C0; }
        .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .btn { padding:10px 20px; border:none; border-radius:6px; font-size:14px;
               font-weight:bold; cursor:pointer; }
        .btn-primary { background:#1565C0; color:white; width:100%; padding:12px; }
        .btn-primary:hover { background:#0d47a1; }
        .btn-warning { background:#FF8F00; color:white; font-size:12px; padding:6px 12px; }
        .btn-danger  { background:#C62828; color:white; font-size:12px; padding:6px 12px; }
        .btn-warning:hover { background:#E65100; }
        .btn-danger:hover  { background:#B71C1C; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1A237E; color:white; padding:12px 16px;
                   text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F8FBFF; }
        tbody td { padding:12px 16px; font-size:14px; border-bottom:1px solid #eee; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px;
                 font-size:12px; font-weight:bold; }
        .badge-blue   { background:#E3F2FD; color:#1565C0; }
        .badge-green  { background:#E8F5E9; color:#2E7D32; }
        .actions { display:flex; gap:8px; }
        .empty { text-align:center; color:#aaa; padding:40px; font-size:14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <span>🎓</span><h2>GestiNotes</h2><p>Administrateur</p>
    </div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="utilisateurs.php"><span class="icon">👥</span> Utilisateurs</a>
        <a href="filieres.php"><span class="icon">🏫</span> Filières</a>
        <a href="modules.php" class="active"><span class="icon">📚</span> Modules</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">🚪 Se déconnecter</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Gestion des Modules</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>

    <div class="content">
        <?php if ($message): ?>
            <div class="msg-ok">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($erreur): ?>
            <div class="msg-err">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (empty($filieres)): ?>
            <div class="msg-err">⚠️ Vous devez d'abord créer au moins une filière avant d'ajouter des modules.</div>
        <?php endif; ?>

        <div class="layout">
            <!-- FORMULAIRE -->
            <div class="card">
                <?php if ($module_edit): ?>
                    <h2>✏️ Modifier le module</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id" value="<?= $module_edit['id'] ?>">
                        <label>Nom du module</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($module_edit['nom']) ?>" required>
                        <label>Code</label>
                        <input type="text" name="code" value="<?= htmlspecialchars($module_edit['code']) ?>" required>
                        <div class="row2">
                            <div>
                                <label>Coefficient</label>
                                <input type="number" name="coefficient" step="0.5" min="0.5" max="10"
                                       value="<?= $module_edit['coefficient'] ?>" required>
                            </div>
                            <div>
                                <label>Semestre</label>
                                <select name="semestre">
                                    <option value="1" <?= $module_edit['semestre']==1?'selected':'' ?>>Semestre 1</option>
                                    <option value="2" <?= $module_edit['semestre']==2?'selected':'' ?>>Semestre 2</option>
                                </select>
                            </div>
                        </div>
                        <label>Filière</label>
                        <select name="filiere_id" required>
                            <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $f['id']==$module_edit['filiere_id']?'selected':'' ?>>
                                <?= htmlspecialchars($f['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    </form>
                <?php else: ?>
                    <h2>➕ Ajouter un module</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="ajouter">
                        <label>Nom du module</label>
                        <input type="text" name="nom" placeholder="Ex: Algorithmique" required>
                        <label>Code</label>
                        <input type="text" name="code" placeholder="Ex: ALGO101">
                        <div class="row2">
                            <div>
                                <label>Coefficient</label>
                                <input type="number" name="coefficient" step="0.5" min="0.5" max="10" value="1" required>
                            </div>
                            <div>
                                <label>Semestre</label>
                                <select name="semestre">
                                    <option value="1">Semestre 1</option>
                                    <option value="2">Semestre 2</option>
                                </select>
                            </div>
                        </div>
                        <label>Filière</label>
                        <select name="filiere_id" required>
                            <option value="">-- Choisir une filière --</option>
                            <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">➕ Ajouter</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TABLEAU -->
            <div class="card">
                <h2>📋 Liste des modules (<?= count($modules) ?>)</h2>
                <?php if (empty($modules)): ?>
                    <p class="empty">Aucun module pour l'instant.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code</th>
                            <th>Coef.</th>
                            <th>Semestre</th>
                            <th>Filière</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['nom']) ?></td>
                            <td><span class="badge badge-blue"><?= htmlspecialchars($m['code']) ?></span></td>
                            <td><?= $m['coefficient'] ?></td>
                            <td><span class="badge badge-green">S<?= $m['semestre'] ?></span></td>
                            <td><?= htmlspecialchars($m['filiere_nom']) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?modifier=<?= $m['id'] ?>" class="btn btn-warning">✏️</a>
                                    <a href="?supprimer=<?= $m['id'] ?>"
                                       class="btn btn-danger"
                                       onclick="return confirm('Supprimer ce module ?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>