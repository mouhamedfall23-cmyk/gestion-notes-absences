<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('enseignant');

// Récupérer l'ID enseignant lié à cet utilisateur
$stmt = $pdo->prepare("SELECT id FROM enseignants WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$enseignant = $stmt->fetch();
$enseignant_id = $enseignant ? $enseignant['id'] : 0;

// Stats
$nb_modules   = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$nb_etudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
$nb_notes     = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$nb_absences  = $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant — GestiNotes</title>
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
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:32px; }
        .stat-card { background:white; border-radius:12px; padding:24px; display:flex;
                     align-items:center; gap:16px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .stat-icon { width:52px; height:52px; border-radius:12px;
                     display:flex; align-items:center; justify-content:center; font-size:24px; }
        .stat-card:nth-child(1) .stat-icon { background:#E8F5E9; }
        .stat-card:nth-child(2) .stat-icon { background:#E3F2FD; }
        .stat-card:nth-child(3) .stat-icon { background:#FFF3E0; }
        .stat-card:nth-child(4) .stat-icon { background:#FCE4EC; }
        .stat-info h3 { font-size:28px; font-weight:bold; color:#1B5E20; }
        .stat-info p  { font-size:13px; color:#888; margin-top:2px; }
        .section-title { font-size:16px; font-weight:bold; color:#333; margin-bottom:16px; }
        .actions-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .action-card { background:white; border-radius:10px; padding:28px 20px; text-align:center;
                       text-decoration:none; color:#333; box-shadow:0 2px 8px rgba(0,0,0,0.06);
                       transition:all 0.2s; border:2px solid transparent; }
        .action-card:hover { border-color:#2E7D32; transform:translateY(-2px);
                             box-shadow:0 6px 16px rgba(46,125,50,0.15); }
        .action-card .icon { font-size:36px; margin-bottom:12px; }
        .action-card p { font-size:14px; font-weight:bold; color:#444; }
        .action-card small { font-size:12px; color:#888; margin-top:4px; display:block; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <span>🎓</span><h2>GestiNotes</h2><p>Enseignant</p>
    </div>
    <nav>
        <a href="index.php" class="active"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="etudiants.php"><span class="icon">👨‍🎓</span> Mes étudiants</a>
        <a href="notes.php"><span class="icon">📝</span> Saisir les notes</a>
        <a href="absences.php"><span class="icon">📋</span> Absences</a>
        <a href="resultats.php"><span class="icon">📊</span> Résultats</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">🚪 Se déconnecter</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Tableau de bord</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>

    <div class="content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info"><h3><?= $nb_modules ?></h3><p>Modules</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-info"><h3><?= $nb_etudiants ?></h3><p>Étudiants</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-info"><h3><?= $nb_notes ?></h3><p>Notes saisies</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info"><h3><?= $nb_absences ?></h3><p>Absences</p></div>
            </div>
        </div>

        <p class="section-title">Mes actions</p>
        <div class="actions-grid">
            <a href="etudiants.php" class="action-card">
                <div class="icon">👨‍🎓</div>
                <p>Voir les étudiants</p>
                <small>Liste par filière</small>
            </a>
            <a href="notes.php" class="action-card">
                <div class="icon">📝</div>
                <p>Saisir les notes</p>
                <small>Test · DS · Examen</small>
            </a>
            <a href="absences.php" class="action-card">
                <div class="icon">📋</div>
                <p>Enregistrer absences</p>
                <small>Par module et date</small>
            </a>
            <a href="resultats.php" class="action-card">
                <div class="icon">📊</div>
                <p>Consulter résultats</p>
                <small>Moyennes calculées</small>
            </a>
        </div>
    </div>
</div>
</body>
</html>