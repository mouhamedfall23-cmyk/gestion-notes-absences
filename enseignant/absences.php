<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('enseignant');

$message = '';
$erreur  = '';

// ── ENREGISTRER UNE ABSENCE ──────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $etudiant_id   = (int)$_POST['etudiant_id'];
    $module_id     = (int)$_POST['module_id'];
    $date_absence  = $_POST['date_absence'];
    $type          = $_POST['type'];
    $justification = trim($_POST['justification']);

    if ($etudiant_id && $module_id && $date_absence && $type) {
        $stmt = $pdo->prepare("INSERT INTO absences (etudiant_id, module_id, date_absence, type, justification) VALUES (?,?,?,?,?)");
        $stmt->execute([$etudiant_id, $module_id, $date_absence, $type, $justification ?: null]);
        $message = "Absence enregistrée !";
    } else {
        $erreur = "Tous les champs sont obligatoires.";
    }
}

// ── SUPPRIMER ────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM absences WHERE id = ?")->execute([(int)$_GET['supprimer']]);
    $message = "Absence supprimée.";
}

// ── DONNÉES ───────────────────────────────────────────
$etudiants = $pdo->query("
    SELECT e.id, u.nom, u.prenom, e.ine
    FROM etudiants e
    JOIN utilisateurs u ON e.utilisateur_id = u.id
    ORDER BY u.nom
")->fetchAll();

$modules = $pdo->query("SELECT * FROM modules ORDER BY nom")->fetchAll();

$absences = $pdo->query("
    SELECT a.*, u.nom, u.prenom, e.ine, m.nom AS module_nom
    FROM absences a
    JOIN etudiants e ON a.etudiant_id = e.id
    JOIN utilisateurs u ON e.utilisateur_id = u.id
    JOIN modules m ON a.module_id = m.id
    ORDER BY a.date_absence DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Absences — GestiNotes</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#f0f2f5; display:flex; min-height:100vh; }
        .sidebar { width:240px; background:#1B5E20; min-height:100vh;
                   display:flex; flex-direction:column; position:fixed; top:0; left:0; }
        .sidebar-logo { padding:28px 20px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-logo span { font-size:28px; }
        .sidebar-logo h2 { color:white; font-size:18px; margin-top:8px; }
        .sidebar-logo p  { color:rgba(255,255,255,0.6); font-size:11px; margin-top:4px; }
        .sidebar nav { padding:20px 0; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; padding:13px 24px;
                         color:rgba(255,255,255,0.75); text-decoration:none; font-size:14px; transition:all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background:rgba(255,255,255,0.12);
                         color:white; border-left:3px solid #A5D6A7; padding-left:21px; }
        .sidebar nav a .icon { font-size:18px; }
        .sidebar-footer { padding:16px 24px; border-top:1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px;
                            display:flex; align-items:center; gap:8px; }
        .sidebar-footer a:hover { color:white; }
        .main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
        .topbar { background:white; padding:16px 32px; display:flex; justify-content:space-between;
                  align-items:center; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
        .topbar h1 { font-size:20px; color:#1B5E20; font-weight:bold; }
        .topbar .user-info { display:flex; align-items:center; gap:10px; font-size:14px; color:#555; }
        .topbar .avatar { width:36px; height:36px; background:#2E7D32; border-radius:50%;
                          display:flex; align-items:center; justify-content:center;
                          color:white; font-weight:bold; font-size:14px; }
        .content { padding:32px; }
        .msg-ok  { background:#E8F5E9; color:#2E7D32; padding:12px 16px; border-radius:8px;
                   margin-bottom:20px; border-left:4px solid #4CAF50; font-size:14px; }
        .msg-err { background:#FFEBEE; color:#C62828; padding:12px 16px; border-radius:8px;
                   margin-bottom:20px; border-left:4px solid #F44336; font-size:14px; }
        .layout { display:grid; grid-template-columns:340px 1fr; gap:24px; }
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#1B5E20; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #E8F5E9; }
        label { display:block; font-size:13px; font-weight:bold; color:#444; margin-bottom:5px; }
        select, input[type=date], textarea {
            width:100%; padding:10px 14px; border:1px solid #ddd;
            border-radius:6px; font-size:14px; margin-bottom:14px; background:white; }
        select:focus, input:focus, textarea:focus { outline:none; border-color:#2E7D32; }
        textarea { resize:vertical; height:70px; }
        .btn-primary { background:#2E7D32; color:white; border:none; width:100%;
                       padding:12px; border-radius:6px; font-size:14px;
                       font-weight:bold; cursor:pointer; }
        .btn-primary:hover { background:#1B5E20; }
        .btn-danger { background:#C62828; color:white; border:none; font-size:12px;
                      padding:5px 10px; border-radius:6px; cursor:pointer; }
        .btn-danger:hover { background:#B71C1C; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1B5E20; color:white; padding:11px 14px;
                   text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F1F8E9; }
        tbody td { padding:10px 14px; font-size:13px; border-bottom:1px solid #eee; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px;
                 font-size:11px; font-weight:bold; }
        .badge-justifiee    { background:#E8F5E9; color:#1B5E20; }
        .badge-non_justifiee{ background:#FFEBEE; color:#C62828; }
        .badge-autorisee    { background:#E3F2FD; color:#1565C0; }
        .empty { text-align:center; color:#aaa; padding:40px; font-size:14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Enseignant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="etudiants.php"><span class="icon">👨‍🎓</span> Mes étudiants</a>
        <a href="notes.php"><span class="icon">📝</span> Saisir les notes</a>
        <a href="absences.php" class="active"><span class="icon">📋</span> Absences</a>
        <a href="resultats.php"><span class="icon">📊</span> Résultats</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Gestion des Absences</h1>
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

        <div class="layout">

            <!-- FORMULAIRE -->
            <div class="card">
                <h2>➕ Enregistrer une absence</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="ajouter">

                    <label>Étudiant *</label>
                    <select name="etudiant_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($etudiants as $e): ?>
                        <option value="<?= $e['id'] ?>">
                            <?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?>
                            (<?= $e['ine'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Module *</label>
                    <select name="module_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($modules as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Date *</label>
                    <input type="date" name="date_absence"
                           value="<?= date('Y-m-d') ?>" required>

                    <label>Type *</label>
                    <select name="type" required>
                        <option value="non_justifiee">❌ Non justifiée</option>
                        <option value="justifiee">✅ Justifiée</option>
                        <option value="autorisee">🔵 Autorisée</option>
                    </select>

                    <label>Justification (optionnel)</label>
                    <textarea name="justification" placeholder="Motif de l'absence..."></textarea>

                    <button type="submit" class="btn-primary">➕ Enregistrer</button>
                </form>
            </div>

            <!-- LISTE -->
            <div class="card">
                <h2>📋 Historique des absences (<?= count($absences) ?>)</h2>
                <?php if (empty($absences)): ?>
                    <p class="empty">Aucune absence enregistrée.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Module</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absences as $a): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong>
                                <br><small style="color:#888"><?= $a['ine'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['module_nom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $a['type'] ?>">
                                    <?= $a['type'] === 'justifiee' ? '✅ Justifiée' :
                                       ($a['type'] === 'autorisee' ? '🔵 Autorisée' : '❌ Non justifiée') ?>
                                </span>
                                <?php if ($a['justification']): ?>
                                <br><small style="color:#666; font-style:italic">
                                    <?= htmlspecialchars($a['justification']) ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?supprimer=<?= $a['id'] ?>"
                                   class="btn-danger"
                                   onclick="return confirm('Supprimer cette absence ?')">
                                   🗑️
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
</div>
</body>
</html>