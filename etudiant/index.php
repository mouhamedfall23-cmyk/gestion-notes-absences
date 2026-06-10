<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('etudiant');

// Récupérer le profil étudiant
$stmt = $pdo->prepare("
    SELECT e.*, f.nom AS filiere_nom
    FROM etudiants e
    JOIN filieres f ON e.filiere_id = f.id
    WHERE e.utilisateur_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$etudiant = $stmt->fetch();
$etudiant_id = $etudiant['id'];

// Stats
$nb_notes    = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE etudiant_id = ?");
$nb_notes->execute([$etudiant_id]);
$nb_notes    = $nb_notes->fetchColumn();

$nb_absences = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id = ?");
$nb_absences->execute([$etudiant_id]);
$nb_absences = $nb_absences->fetchColumn();

$nb_absences_nj = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id = ? AND type = 'non_justifiee'");
$nb_absences_nj->execute([$etudiant_id]);
$nb_absences_nj = $nb_absences_nj->fetchColumn();

// Moyenne générale
$stmt = $pdo->prepare("SELECT AVG(moyenne) FROM notes WHERE etudiant_id = ? AND moyenne IS NOT NULL");
$stmt->execute([$etudiant_id]);
$moy_generale = round($stmt->fetchColumn(), 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace — GestiNotes</title>
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

        /* Carte profil */
        .profil-card { background:linear-gradient(135deg,#4527A0,#7B1FA2);
                       border-radius:12px; padding:28px 32px; color:white;
                       display:flex; align-items:center; gap:24px; margin-bottom:28px;
                       box-shadow:0 4px 16px rgba(69,39,160,0.3); }
        .profil-avatar { width:70px; height:70px; background:rgba(255,255,255,0.2);
                         border-radius:50%; display:flex; align-items:center;
                         justify-content:center; font-size:28px; font-weight:bold; flex-shrink:0; }
        .profil-info h2 { font-size:22px; margin-bottom:4px; }
        .profil-info p  { opacity:0.8; font-size:14px; margin-bottom:2px; }
        .profil-badge { display:inline-block; background:rgba(255,255,255,0.2);
                        padding:3px 12px; border-radius:20px; font-size:12px; margin-top:6px; }

        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:28px; }
        .stat-card { background:white; border-radius:12px; padding:22px;
                     display:flex; align-items:center; gap:14px;
                     box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .stat-icon { width:48px; height:48px; border-radius:10px;
                     display:flex; align-items:center; justify-content:center; font-size:22px; }
        .stat-card:nth-child(1) .stat-icon { background:#EDE7F6; }
        .stat-card:nth-child(2) .stat-icon { background:#E8F5E9; }
        .stat-card:nth-child(3) .stat-icon { background:#FFEBEE; }
        .stat-card:nth-child(4) .stat-icon { background:#FFF3E0; }
        .stat-info h3 { font-size:26px; font-weight:bold; color:#4527A0; }
        .stat-info p  { font-size:12px; color:#888; margin-top:2px; }

        .section-title { font-size:16px; font-weight:bold; color:#333; margin-bottom:16px; }
        .actions-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .action-card { background:white; border-radius:10px; padding:28px 20px;
                       text-align:center; text-decoration:none; color:#333;
                       box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:all 0.2s;
                       border:2px solid transparent; }
        .action-card:hover { border-color:#4527A0; transform:translateY(-2px);
                             box-shadow:0 6px 16px rgba(69,39,160,0.15); }
        .action-card .icon { font-size:36px; margin-bottom:12px; }
        .action-card p { font-size:14px; font-weight:bold; color:#444; }
        .action-card small { font-size:12px; color:#888; margin-top:4px; display:block; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><span>🎓</span><h2>GestiNotes</h2><p>Étudiant</p></div>
    <nav>
        <a href="index.php" class="active"><span class="icon">🏠</span> Mon tableau de bord</a>
        <a href="notes.php"><span class="icon">📝</span> Mes notes</a>
        <a href="absences.php"><span class="icon">📋</span> Mes absences</a>
        <a href="releve.php"><span class="icon">📄</span> Mon relevé</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php">🚪 Se déconnecter</a></div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Mon Espace</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?></div>
            <?= htmlspecialchars($_SESSION['user_nom']) ?>
        </div>
    </div>

    <div class="content">

        <!-- Profil -->
        <div class="profil-card">
            <div class="profil-avatar">
                <?= strtoupper(substr($_SESSION['user_nom'], 0, 1)) ?>
            </div>
            <div class="profil-info">
                <h2><?= htmlspecialchars($_SESSION['user_nom']) ?></h2>
                <p>📍 <?= htmlspecialchars($etudiant['filiere_nom']) ?></p>
                <p>🎓 Niveau : <?= $etudiant['niveau'] ?></p>
                <span class="profil-badge">INE : <?= htmlspecialchars($etudiant['ine']) ?></span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-info"><h3><?= $nb_notes ?></h3><p>Notes reçues</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?= $moy_generale ?: '—' ?></h3>
                    <p>Moyenne générale</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info"><h3><?= $nb_absences_nj ?></h3><p>Absences non justifiées</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info"><h3><?= $nb_absences ?></h3><p>Total absences</p></div>
            </div>
        </div>

        <!-- Actions -->
        <p class="section-title">Mes accès rapides</p>
        <div class="actions-grid">
            <a href="notes.php" class="action-card">
                <div class="icon">📝</div>
                <p>Mes notes</p>
                <small>Par module et semestre</small>
            </a>
            <a href="absences.php" class="action-card">
                <div class="icon">📋</div>
                <p>Mes absences</p>
                <small>Justifiées et non justifiées</small>
            </a>
            <a href="releve.php" class="action-card">
                <div class="icon">📄</div>
                <p>Mon relevé de notes</p>
                <small>Télécharger en PDF</small>
            </a>
        </div>

    </div>
</div>
</body>
</html>