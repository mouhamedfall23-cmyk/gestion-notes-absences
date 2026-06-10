<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('enseignant');

$modules = $pdo->query("SELECT m.*, f.nom AS filiere_nom FROM modules m JOIN filieres f ON m.filiere_id = f.id ORDER BY m.nom")->fetchAll();
$module_sel = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

$resultats = [];
if ($module_sel) {
    $stmt = $pdo->prepare("
        SELECT u.nom, u.prenom, e.ine, e.niveau,
               n.note_test, n.note_ds, n.note_examen, n.moyenne
        FROM etudiants e
        JOIN utilisateurs u ON e.utilisateur_id = u.id
        LEFT JOIN notes n ON n.etudiant_id = e.id AND n.module_id = ?
        ORDER BY u.nom
    ");
    $stmt->execute([$module_sel]);
    $resultats = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats — GestiNotes</title>
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
        .module-selector { background:white; border-radius:12px; padding:24px;
                           box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px; }
        .module-selector h2 { font-size:16px; color:#1B5E20; margin-bottom:16px; }
        .modules-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; }
        .module-btn { padding:14px 16px; border-radius:8px; border:2px solid #ddd;
                      background:white; text-decoration:none; display:block; transition:all 0.2s; }
        .module-btn:hover { border-color:#2E7D32; }
        .module-btn.selected { border-color:#2E7D32; background:#E8F5E9; }
        .module-btn .mnom { font-weight:bold; font-size:14px; color:#333; }
        .module-btn .minfo { font-size:12px; color:#888; margin-top:3px; }
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#1B5E20; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #E8F5E9; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1B5E20; color:white; padding:12px 16px; text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F1F8E9; }
        tbody td { padding:12px 16px; font-size:13px; border-bottom:1px solid #eee; text-align:center; }
        tbody td:first-child { text-align:left; }
        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
        .moy-bien     { background:#E8F5E9; color:#1B5E20; }
        .moy-passable { background:#FFF9C4; color:#F57F17; }
        .moy-echec    { background:#FFEBEE; color:#C62828; }
        .empty { text-align:center; color:#aaa; padding:40px; font-size:14px; }
        .stats-bar { display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-mini { background:#f5f5f5; border-radius:8px; padding:12px 20px; text-align:center; }
        .stat-mini strong { display:block; font-size:22px; color:#1B5E20; }
        .stat-mini span { font-size:12px; color:#888; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Enseignant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="etudiants.php"><span class="icon">👨‍🎓</span> Mes étudiants</a>
        <a href="notes.php"><span class="icon">📝</span> Saisir les notes</a>
        <a href="absences.php"><span class="icon">📋</span> Absences</a>
        <a href="resultats.php" class="active"><span class="icon">📊</span> Résultats</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Résultats & Moyennes</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>

    <div class="content">

        <div class="module-selector">
            <h2>📚 Choisir un module</h2>
            <div class="modules-grid">
                <?php foreach ($modules as $m): ?>
                <a href="?module_id=<?= $m['id'] ?>"
                   class="module-btn <?= $module_sel===$m['id']?'selected':'' ?>">
                    <div class="mnom"><?= htmlspecialchars($m['nom']) ?></div>
                    <div class="minfo">S<?= $m['semestre'] ?> · Coef <?= $m['coefficient'] ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($module_sel && !empty($resultats)): ?>
        <?php
            $avec_notes = array_filter($resultats, fn($r) => $r['moyenne'] !== null);
            $nb_admis   = count(array_filter($avec_notes, fn($r) => $r['moyenne'] >= 10));
            $moy_classe = count($avec_notes) ? round(array_sum(array_column(array_values($avec_notes), 'moyenne')) / count($avec_notes), 2) : 0;
        ?>
        <div class="card">
            <h2>📊 Résultats du module</h2>
            <div class="stats-bar">
                <div class="stat-mini">
                    <strong><?= count($resultats) ?></strong>
                    <span>Étudiants</span>
                </div>
                <div class="stat-mini">
                    <strong><?= $nb_admis ?></strong>
                    <span>Admis (≥10)</span>
                </div>
                <div class="stat-mini">
                    <strong><?= count($resultats) - $nb_admis ?></strong>
                    <span>Recalés</span>
                </div>
                <div class="stat-mini">
                    <strong><?= $moy_classe ?>/20</strong>
                    <span>Moyenne classe</span>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>INE</th>
                        <th>Test</th>
                        <th>DS</th>
                        <th>Examen</th>
                        <th>Moyenne</th>
                        <th>Mention</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></strong></td>
                        <td style="color:#888;font-size:12px"><?= $r['ine'] ?></td>
                        <td><?= $r['note_test']   ?? '—' ?></td>
                        <td><?= $r['note_ds']     ?? '—' ?></td>
                        <td><?= $r['note_examen'] ?? '—' ?></td>
                        <td>
                            <?php if ($r['moyenne'] !== null): ?>
                            <span class="badge <?= $r['moyenne']>=14?'moy-bien':($r['moyenne']>=10?'moy-passable':'moy-echec') ?>">
                                <?= number_format($r['moyenne'],2) ?>/20
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['moyenne'] !== null):
                                $m = $r['moyenne'];
                                echo $m>=16?'🏆 Très bien':($m>=14?'👍 Bien':($m>=12?'🙂 Assez bien':($m>=10?'✅ Passable':'❌ Ajourné')));
                            else: echo '—'; endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card"><p class="empty">👆 Sélectionnez un module pour voir les résultats.</p></div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>