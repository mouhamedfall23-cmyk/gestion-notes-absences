<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
checkRole('admin');

$message = '';
$erreur  = '';

// ── AJOUTER ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom      = trim($_POST['nom']);
    $prenom   = trim($_POST['prenom']);
    $email    = trim($_POST['email']);
    $mdp      = $_POST['mot_de_passe'];
    $role     = $_POST['role'];
    $filiere  = isset($_POST['filiere_id']) ? (int)$_POST['filiere_id'] : null;
    $niveau   = isset($_POST['niveau'])     ? $_POST['niveau']          : null;
    $ine      = isset($_POST['ine'])        ? trim($_POST['ine'])        : null;

    if ($nom && $prenom && $email && $mdp && $role) {
        try {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            $pdo->beginTransaction();

            // Créer l'utilisateur
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?,?,?,?,?)");
            $stmt->execute([$nom, $prenom, $email, $hash, $role]);
            $user_id = $pdo->lastInsertId();

            // Créer le profil selon le rôle
            if ($role === 'etudiant') {
                $stmt2 = $pdo->prepare("INSERT INTO etudiants (ine, niveau, utilisateur_id, filiere_id) VALUES (?,?,?,?)");
                $stmt2->execute([$ine, $niveau, $user_id, $filiere]);
            } elseif ($role === 'enseignant') {
                $stmt2 = $pdo->prepare("INSERT INTO enseignants (utilisateur_id) VALUES (?)");
                $stmt2->execute([$user_id]);
            }

            $pdo->commit();
            $message = "Utilisateur créé avec succès !";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Cet email existe déjà ou une erreur s'est produite.";
        }
    } else {
        $erreur = "Tous les champs obligatoires doivent être remplis.";
    }
}

// ── SUPPRIMER ────────────────────────────────────────
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    if ($id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
        $message = "Utilisateur supprimé.";
    } else {
        $erreur = "Vous ne pouvez pas supprimer votre propre compte.";
    }
}

// ── FILTRE ───────────────────────────────────────────
$filtre = isset($_GET['role']) ? $_GET['role'] : '';
if ($filtre) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE role = ? ORDER BY nom");
    $stmt->execute([$filtre]);
} else {
    $stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY role, nom");
}
$utilisateurs = $stmt->fetchAll();
$filieres     = $pdo->query("SELECT * FROM filieres ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Utilisateurs — GestiNotes</title>
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
        .main  { margin-left:240px; flex:1; display:flex; flex-direction:column; }
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
        input[type=text], input[type=email], input[type=password], select {
            width:100%; padding:10px 14px; border:1px solid #ddd;
            border-radius:6px; font-size:14px; margin-bottom:14px; background:white; }
        input:focus, select:focus { outline:none; border-color:#1565C0; }
        .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .btn { padding:10px 20px; border:none; border-radius:6px; font-size:14px;
               font-weight:bold; cursor:pointer; text-decoration:none; display:inline-block; }
        .btn-primary { background:#1565C0; color:white; width:100%; padding:12px; text-align:center; }
        .btn-primary:hover { background:#0d47a1; }
        .btn-danger  { background:#C62828; color:white; font-size:12px; padding:6px 12px; }
        .btn-danger:hover  { background:#B71C1C; }

        /* Filtres */
        .filtres { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
        .filtre-btn { padding:7px 16px; border-radius:20px; border:2px solid #ddd;
                      background:white; font-size:13px; cursor:pointer; text-decoration:none; color:#555; }
        .filtre-btn:hover, .filtre-btn.active { border-color:#1565C0; color:#1565C0;
                                                background:#E3F2FD; font-weight:bold; }
        /* Tableau */
        table { width:100%; border-collapse:collapse; }
        thead th { background:#1A237E; color:white; padding:12px 16px;
                   text-align:left; font-size:13px; }
        tbody tr:nth-child(even) { background:#F8FBFF; }
        tbody td { padding:11px 16px; font-size:13px; border-bottom:1px solid #eee; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px;
                 font-size:11px; font-weight:bold; }
        .badge-admin      { background:#EDE7F6; color:#4527A0; }
        .badge-enseignant { background:#E8F5E9; color:#1B5E20; }
        .badge-etudiant   { background:#E3F2FD; color:#0D47A1; }

        /* Champs conditionnels */
        #champs_etudiant { display:none; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <span>🎓</span><h2>GestiNotes</h2><p>Administrateur</p>
    </div>
    <nav>
        <a href="index.php"><span class="icon">🏠</span> Tableau de bord</a>
        <a href="utilisateurs.php" class="active"><span class="icon">👥</span> Utilisateurs</a>
        <a href="filieres.php"><span class="icon">🏫</span> Filières</a>
        <a href="modules.php"><span class="icon">📚</span> Modules</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">🚪 Se déconnecter</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Gestion des Utilisateurs</h1>
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

            <!-- FORMULAIRE AJOUT -->
            <div class="card">
                <h2>➕ Nouvel utilisateur</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="ajouter">

                    <div class="row2">
                        <div>
                            <label>Prénom *</label>
                            <input type="text" name="prenom" placeholder="Fatou" required>
                        </div>
                        <div>
                            <label>Nom *</label>
                            <input type="text" name="nom" placeholder="Diallo" required>
                        </div>
                    </div>

                    <label>Email *</label>
                    <input type="email" name="email" placeholder="fatou@unchk.sn" required>

                    <label>Mot de passe *</label>
                    <input type="password" name="mot_de_passe" placeholder="minimum 6 caractères" required>

                    <label>Rôle *</label>
                    <select name="role" id="select_role" onchange="afficherChamps(this.value)" required>
                        <option value="">-- Choisir un rôle --</option>
                        <option value="admin">👤 Administrateur</option>
                        <option value="enseignant">👨‍🏫 Enseignant</option>
                        <option value="etudiant">👨‍🎓 Étudiant</option>
                    </select>

                    <!-- Champs supplémentaires si étudiant -->
                    <div id="champs_etudiant">
                        <label>INE (Identifiant National) *</label>
                        <input type="text" name="ine" placeholder="Ex: IDA2024001">

                        <label>Niveau *</label>
                        <select name="niveau">
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3" selected>L3</option>
                            <option value="M1">M1</option>
                            <option value="M2">M2</option>
                        </select>

                        <label>Filière *</label>
                        <select name="filiere_id">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">➕ Créer le compte</button>
                </form>
            </div>

            <!-- LISTE -->
            <div class="card">
                <h2>👥 Liste des utilisateurs (<?= count($utilisateurs) ?>)</h2>

                <!-- Filtres -->
                <div class="filtres">
                    <a href="utilisateurs.php" class="filtre-btn <?= !$filtre ? 'active' : '' ?>">Tous</a>
                    <a href="?role=admin"       class="filtre-btn <?= $filtre==='admin'      ? 'active' : '' ?>">👤 Admins</a>
                    <a href="?role=enseignant"  class="filtre-btn <?= $filtre==='enseignant' ? 'active' : '' ?>">👨‍🏫 Enseignants</a>
                    <a href="?role=etudiant"    class="filtre-btn <?= $filtre==='etudiant'   ? 'active' : '' ?>">👨‍🎓 Étudiants</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Prénom Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= $u['role'] ?>">
                                    <?= $u['role'] === 'admin' ? '👤 Admin' :
                                       ($u['role'] === 'enseignant' ? '👨‍🏫 Enseignant' : '👨‍🎓 Étudiant') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="?supprimer=<?= $u['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Supprimer <?= htmlspecialchars($u['prenom']) ?> ?')">
                                   🗑️ Supprimer
                                </a>
                                <?php else: ?>
                                <span style="color:#aaa; font-size:12px;">Compte actif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function afficherChamps(role) {
    document.getElementById('champs_etudiant').style.display =
        role === 'etudiant' ? 'block' : 'none';
}
</script>
</body>
</html>