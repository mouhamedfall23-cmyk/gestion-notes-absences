<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('etudiant');

$stmt = $pdo->prepare("SELECT e.*, f.nom AS filiere_nom FROM etudiants e JOIN filieres f ON e.filiere_id = f.id WHERE e.utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$etudiant    = $stmt->fetch();
$etudiant_id = $etudiant['id'];

$notes = $pdo->prepare("
    SELECT n.*, m.nom AS module_nom, m.coefficient, m.semestre
    FROM notes n
    JOIN modules m ON n.module_id = m.id
    WHERE n.etudiant_id = ?
    ORDER BY m.semestre, m.nom
");
$notes->execute([$etudiant_id]);
$notes = $notes->fetchAll();

// Grouper par semestre
$par_semestre = [];
foreach ($notes as $n) {
    $par_semestre[$n['semestre']][] = $n;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Notes — GestiNotes</title>
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
        .semestre-block { margin-bottom:28px; }
        .semestre-title { font-size:16px; font-weight:bold; color:#4527A0; margin-bottom:14px;
                          padding:10px 16px; background:#EDE7F6; border-radius:8px;
                          border-left:4px solid #4527A0; }
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#4527A0; color:white; padding:12px 16px; text-align:center; font-size:13px; }
        thead th:first-child { text-align:left; }
        tbody tr:nth-child(even) { background:#F3E5F5; }
        tbody td { padding:12px 16px; font-size:14px; border-bottom:1px solid #eee; text-align:center; }
        tbody td:first-child { text-align:left; font-weight:bold; }
        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
        .moy-bien     { background:#E8F5E9; color:#1B5E20; }
        .moy-passable { background:#FFF9C4; color:#F57F17; }
        .moy-echec    { background:#FFEBEE; color:#C62828; }
        .empty { text-align:center; color:#aaa; padding:60px; font-size:14px; }
        .moy-semestre { text-align:right; padding:10px 16px; font-size:14px;
                        color:#4527A0; font-weight:bold; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Étudiant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Mon tableau de bord</a>
        <a href="notes.php" class="active"><span class="icon">📝</span> Mes notes</a>
        <a href="absences.php"><span class="icon">📋</span> Mes absences</a>
        <a href="releve.php"><span class="icon">📄</span> Mon relevé</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Mes Notes</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>
    <div class="content">
        <?php if (empty($notes)): ?>
            <div class="card"><p class="empty">📭 Aucune note disponible pour l'instant.</p></div>
        <?php else: ?>
            <?php foreach ($par_semestre as $sem => $sem_notes): ?>
            <?php
                $moyennes_sem = array_filter(array_column($sem_notes, 'moyenne'), fn($m) => $m !== null);
                $moy_sem = count($moyennes_sem) ? round(array_sum($moyennes_sem)/count($moyennes_sem),2) : null;
            ?>
            <div class="semestre-block">
                <div class="semestre-title">📚 Semestre <?= $sem ?></div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Coef.</th>
                                <th>Test /20</th>
                                <th>DS /20</th>
                                <th>Examen /20</th>
                                <th>Moyenne</th>
                                <th>Mention</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sem_notes as $n): ?>
                            <tr>
                                <td><?= htmlspecialchars($n['module_nom']) ?></td>
                                <td><?= $n['coefficient'] ?></td>
                                <td><?= $n['note_test']   ?? '—' ?></td>
                                <td><?= $n['note_ds']     ?? '—' ?></td>
                                <td><?= $n['note_examen'] ?? '—' ?></td>
                                <td>
                                    <?php if ($n['moyenne'] !== null): ?>
                                    <span class="badge <?= $n['moyenne']>=14?'moy-bien':($n['moyenne']>=10?'moy-passable':'moy-echec') ?>">
                                        <?= number_format($n['moyenne'],2) ?>/20
                                    </span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($n['moyenne'] !== null):
                                        $m = $n['moyenne'];
                                        echo $m>=16?'🏆 Très bien':($m>=14?'👍 Bien':($m>=12?'🙂 Assez bien':($m>=10?'✅ Passable':'❌ Ajourné')));
                                    else: echo '—'; endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($moy_sem): ?>
                    <div class="moy-semestre">
                        Moyenne du semestre <?= $sem ?> :
                        <span class="badge <?= $moy_sem>=14?'moy-bien':($moy_sem>=10?'moy-passable':'moy-echec') ?>">
                            <?= $moy_sem ?>/20
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>