<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('etudiant');

$stmt = $pdo->prepare("SELECT id FROM etudiants WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$etudiant    = $stmt->fetch();
$etudiant_id = $etudiant['id'];

$absences = $pdo->prepare("
    SELECT a.*, m.nom AS module_nom
    FROM absences a
    JOIN modules m ON a.module_id = m.id
    WHERE a.etudiant_id = ?
    ORDER BY a.date_absence DESC
");
$absences->execute([$etudiant_id]);
$absences = $absences->fetchAll();

$nb_j  = count(array_filter($absences, fn($a) => $a['type']==='justifiee'));
$nb_nj = count(array_filter($absences, fn($a) => $a['type']==='non_justifiee'));
$nb_au = count(array_filter($absences, fn($a) => $a['type']==='autorisee'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Absences — GestiNotes</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#f0f2f5; display:flex; min-height:100vh; }
        .sidebar { width:240px; background:#4527A0; min-height:100vh;
                   display:flex; flex-direction:column; position:fixed; top:0; left:0; }
        .sidebar-logo { padding:28px 20px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-logo span { font-size:28px; }
        .sidebar-logo h2 { color:white; font-size:18px; margin-top:8px; }
        .sidebar-logo p  { color:rgba(255,255,255,0.6); font-size:11px; margin-top:4px; }
        .sidebar nav { padding:20px 0; flex:1; }
        .sidebar nav a { display:flex; align-items:center; gap:12px; padding:13px 24px;
                         color:rgba(255,255,255,0.75); text-decoration:none; font-size:14px; transition:all 0.2s; }
        .sidebar nav a:hover, .sidebar nav a.active { background:rgba(255,255,255,0.12);
                         color:white; border-left:3px solid #CE93D8; padding-left:21px; }
        .sidebar nav a .icon { font-size:18px; }
        .sidebar-footer { padding:16px 24px; border-top:1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px;
                            display:flex; align-items:center; gap:8px; }
        .sidebar-footer a:hover { color:white; }
        .main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
        .topbar { background:white; padding:16px 32px; display:flex; justify-content:space-between;
                  align-items:center; box-shadow:0 1px 4px rgba(0,0,0,0.08); }
        .topbar h1 { font-size:20px; color:#4527A0; font-weight:bold; }
        .topbar .user-info { display:flex; align-items:center; gap:10px; font-size:14px; color:#555; }
        .topbar .avatar { width:36px; height:36px; background:#4527A0; border-radius:50%;
                          display:flex; align-items:center; justify-content:center;
                          color:white; font-weight:bold; font-size:14px; }
        .content { padding:32px; }
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:10px; padding:18px;
                     text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .nb { font-size:28px; font-weight:bold; margin-bottom:4px; }
        .stat-card p  { font-size:12px; color:#888; }
        .nb-total { color:#4527A0; }
        .nb-j     { color:#2E7D32; }
        .nb-nj    { color:#C62828; }
        .nb-au    { color:#1565C0; }
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#4527A0; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #EDE7F6; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#4527A0; color:white; padding:12px 16px; text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F3E5F5; }
        tbody td { padding:12px 16px; font-size:14px; border-bottom:1px solid #eee; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-justifiee     { background:#E8F5E9; color:#1B5E20; }
        .badge-non_justifiee { background:#FFEBEE; color:#C62828; }
        .badge-autorisee     { background:#E3F2FD; color:#1565C0; }
        .empty { text-align:center; color:#aaa; padding:60px; font-size:14px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Étudiant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Mon tableau de bord</a>
        <a href="notes.php"><span class="icon">📝</span> Mes notes</a>
        <a href="absences.php" class="active"><span class="icon">📋</span> Mes absences</a>
        <a href="releve.php"><span class="icon">📄</span> Mon relevé</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h1>Mes Absences</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>
    <div class="content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="nb nb-total"><?= count($absences) ?></div>
                <p>Total absences</p>
            </div>
            <div class="stat-card">
                <div class="nb nb-j"><?= $nb_j ?></div>
                <p>✅ Justifiées</p>
            </div>
            <div class="stat-card">
                <div class="nb nb-nj"><?= $nb_nj ?></div>
                <p>❌ Non justifiées</p>
            </div>
            <div class="stat-card">
                <div class="nb nb-au"><?= $nb_au ?></div>
                <p>🔵 Autorisées</p>
            </div>
        </div>
        <div class="card">
            <h2>📋 Historique de mes absences</h2>
            <?php if (empty($absences)): ?>
                <p class="empty">🎉 Aucune absence enregistrée. Bravo !</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Date</th><th>Module</th><th>Type</th><th>Justification</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                        <td><?= htmlspecialchars($a['module_nom']) ?></td>
                        <td>
                            <span class="badge badge-<?= $a['type'] ?>">
                                <?= $a['type']==='justifiee'?'✅ Justifiée':($a['type']==='autorisee'?'🔵 Autorisée':'❌ Non justifiée') ?>
                            </span>
                        </td>
                        <td style="color:#666; font-style:italic; font-size:13px;">
                            <?= $a['justification'] ? htmlspecialchars($a['justification']) : '—' ?>
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