<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('enseignant');

$etudiants = $pdo->query("
    SELECT e.*, u.nom, u.prenom, u.email, f.nom AS filiere_nom
    FROM etudiants e
    JOIN utilisateurs u ON e.utilisateur_id = u.id
    JOIN filieres f ON e.filiere_id = f.id
    ORDER BY u.nom, u.prenom
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étudiants — GestiNotes</title>
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
        .card { background:white; border-radius:12px; padding:24px;
                box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size:16px; color:#1B5E20; margin-bottom:20px;
                   padding-bottom:10px; border-bottom:2px solid #E8F5E9; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1B5E20; color:white; padding:12px 16px; text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F1F8E9; }
        tbody td { padding:12px 16px; font-size:14px; border-bottom:1px solid #eee; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
        .badge-niveau  { background:#E8F5E9; color:#1B5E20; }
        .badge-filiere { background:#E3F2FD; color:#1565C0; }
        .empty { text-align:center; color:#aaa; padding:40px; font-size:14px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Enseignant</p></div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="etudiants.php" class="active"><span class="icon">👨‍🎓</span> Mes étudiants</a>
        <a href="notes.php"><span class="icon">📝</span> Saisir les notes</a>
        <a href="absences.php"><span class="icon">📋</span> Absences</a>
        <a href="resultats.php"><span class="icon">📊</span> Résultats</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>
<div class="main">
    <div class="topbar">
        <h1>Liste des étudiants</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>
    <div class="content">
        <div class="card">
            <h2>👨‍🎓 Étudiants inscrits (<?= count($etudiants) ?>)</h2>
            <?php if (empty($etudiants)): ?>
                <p class="empty">Aucun étudiant inscrit pour l'instant.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Prénom Nom</th>
                        <th>INE</th>
                        <th>Email</th>
                        <th>Niveau</th>
                        <th>Filière</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($e['ine']) ?></td>
                        <td><?= htmlspecialchars($e['email']) ?></td>
                        <td><span class="badge badge-niveau"><?= $e['niveau'] ?></span></td>
                        <td><span class="badge badge-filiere"><?= htmlspecialchars($e['filiere_nom']) ?></span></td>
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