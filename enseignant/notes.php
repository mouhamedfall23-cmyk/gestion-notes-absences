<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('enseignant');

$message = '';
$erreur  = '';

// ── ENREGISTRER UNE NOTE ─────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'saisir') {
    $etudiant_id  = (int)$_POST['etudiant_id'];
    $module_id    = (int)$_POST['module_id'];
    $note_test    = $_POST['note_test']   !== '' ? (float)$_POST['note_test']   : null;
    $note_ds      = $_POST['note_ds']     !== '' ? (float)$_POST['note_ds']     : null;
    $note_examen  = $_POST['note_examen'] !== '' ? (float)$_POST['note_examen'] : null;

    // Calcul moyenne : test×20% + ds×30% + examen×50%
    $moyenne = null;
    if ($note_test !== null && $note_ds !== null && $note_examen !== null) {
        $moyenne = round($note_test * 0.20 + $note_ds * 0.30 + $note_examen * 0.50, 2);
    }

    try {
        // INSERT ou UPDATE si la note existe déjà
        $stmt = $pdo->prepare("
            INSERT INTO notes (etudiant_id, module_id, note_test, note_ds, note_examen, moyenne)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                note_test = VALUES(note_test),
                note_ds   = VALUES(note_ds),
                note_examen = VALUES(note_examen),
                moyenne   = VALUES(moyenne)
        ");
        $stmt->execute([$etudiant_id, $module_id, $note_test, $note_ds, $note_examen, $moyenne]);
        $message = "Note enregistrée avec succès !";
    } catch (PDOException $e) {
        $erreur = "Erreur lors de l'enregistrement.";
    }
}

// ── DONNÉES ───────────────────────────────────────────
$modules   = $pdo->query("SELECT m.*, f.nom AS filiere_nom FROM modules m JOIN filieres f ON m.filiere_id = f.id ORDER BY m.nom")->fetchAll();
$etudiants = $pdo->query("SELECT e.*, u.nom, u.prenom, f.nom AS filiere_nom FROM etudiants e JOIN utilisateurs u ON e.utilisateur_id = u.id JOIN filieres f ON e.filiere_id = f.id ORDER BY u.nom")->fetchAll();

// Filtre module sélectionné
$module_sel = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

// Notes déjà saisies pour ce module
$notes_existantes = [];
if ($module_sel) {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE module_id = ?");
    $stmt->execute([$module_sel]);
    foreach ($stmt->fetchAll() as $n) {
        $notes_existantes[$n['etudiant_id']] = $n;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notes — GestiNotes</title>
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
        .main  { margin-left:240px; flex:1; display:flex; flex-direction:column; }
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

        /* Sélecteur module */
        .module-selector { background:white; border-radius:12px; padding:24px;
                           box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px; }
        .module-selector h2 { font-size:16px; color:#1B5E20; margin-bottom:16px; }
        .modules-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:12px; }
        .module-btn { padding:14px 16px; border-radius:8px; border:2px solid #ddd;
                      background:white; cursor:pointer; text-align:left; text-decoration:none;
                      display:block; transition:all 0.2s; }
        .module-btn:hover { border-color:#2E7D32; }
        .module-btn.selected { border-color:#2E7D32; background:#E8F5E9; }
        .module-btn .mnom { font-weight:bold; font-size:14px; color:#333; }
        .module-btn .minfo { font-size:12px; color:#888; margin-top:3px; }

        /* Tableau de saisie */
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#1B5E20; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #E8F5E9; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1B5E20; color:white; padding:12px 16px; text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F1F8E9; }
        tbody td { padding:10px 16px; border-bottom:1px solid #eee; vertical-align:middle; }
        input[type=number] { width:80px; padding:7px 10px; border:1px solid #ddd;
                             border-radius:6px; font-size:14px; text-align:center; }
        input[type=number]:focus { outline:none; border-color:#2E7D32; }
        .btn-save { background:#2E7D32; color:white; border:none; padding:8px 16px;
                    border-radius:6px; font-size:13px; font-weight:bold; cursor:pointer; }
        .btn-save:hover { background:#1B5E20; }
        .moyenne-badge { display:inline-block; padding:4px 12px; border-radius:20px;
                         font-size:13px; font-weight:bold; }
        .moy-bien    { background:#E8F5E9; color:#1B5E20; }
        .moy-passable{ background:#FFF9C4; color:#F57F17; }
        .moy-echec   { background:#FFEBEE; color:#C62828; }
        .empty { text-align:center; color:#aaa; padding:40px; font-size:14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Enseignant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="etudiants.php"><span class="icon">👨‍🎓</span> Mes étudiants</a>
        <a href="notes.php" class="active"><span class="icon">📝</span> Saisir les notes</a>
        <a href="absences.php"><span class="icon">📋</span> Absences</a>
        <a href="resultats.php"><span class="icon">📊</span> Résultats</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Saisie des Notes</h1>
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

        <!-- ÉTAPE 1 : Choisir un module -->
        <div class="module-selector">
            <h2>📚 Étape 1 — Choisir un module</h2>
            <div class="modules-grid">
                <?php foreach ($modules as $m): ?>
                <a href="?module_id=<?= $m['id'] ?>"
                   class="module-btn <?= $module_sel === $m['id'] ? 'selected' : '' ?>">
                    <div class="mnom"><?= htmlspecialchars($m['nom']) ?></div>
                    <div class="minfo"><?= $m['filiere_nom'] ?> · S<?= $m['semestre'] ?> · Coef <?= $m['coefficient'] ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ÉTAPE 2 : Saisir les notes -->
        <?php if ($module_sel && !empty($etudiants)): ?>
        <?php $module_info = array_filter($modules, fn($m) => $m['id'] === $module_sel);
              $module_info = reset($module_info); ?>
        <div class="card">
            <h2>📝 Étape 2 — Notes pour : <?= htmlspecialchars($module_info['nom']) ?>
                <small style="font-weight:normal; color:#888; font-size:13px;">
                    (Formule : Test×20% + DS×30% + Examen×50%)
                </small>
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>INE</th>
                        <th>Test /20</th>
                        <th>DS /20</th>
                        <th>Examen /20</th>
                        <th>Moyenne</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($etudiants as $e): ?>
                <?php $note = $notes_existantes[$e['id']] ?? null; ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="action" value="saisir">
                        <input type="hidden" name="etudiant_id" value="<?= $e['id'] ?>">
                        <input type="hidden" name="module_id" value="<?= $module_sel ?>">
                        <td><strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong></td>
                        <td style="color:#888; font-size:12px;"><?= htmlspecialchars($e['ine']) ?></td>
                        <td>
                            <input type="number" name="note_test" min="0" max="20" step="0.25"
                                   value="<?= $note ? $note['note_test'] : '' ?>" placeholder="—">
                        </td>
                        <td>
                            <input type="number" name="note_ds" min="0" max="20" step="0.25"
                                   value="<?= $note ? $note['note_ds'] : '' ?>" placeholder="—">
                        </td>
                        <td>
                            <input type="number" name="note_examen" min="0" max="20" step="0.25"
                                   value="<?= $note ? $note['note_examen'] : '' ?>" placeholder="—">
                        </td>
                        <td>
                            <?php if ($note && $note['moyenne'] !== null): ?>
                            <?php $m = $note['moyenne']; ?>
                            <span class="moyenne-badge <?= $m>=14?'moy-bien':($m>=10?'moy-passable':'moy-echec') ?>">
                                <?= number_format($m, 2) ?>/20
                            </span>
                            <?php else: ?>
                            <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="submit" class="btn-save">💾 Enregistrer</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($module_sel): ?>
            <div class="card"><p class="empty">Aucun étudiant inscrit.</p></div>
        <?php else: ?>
            <div class="card"><p class="empty">👆 Sélectionnez un module ci-dessus pour saisir les notes.</p></div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>