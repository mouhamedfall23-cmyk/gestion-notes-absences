<?php
/**
 * logout.php — Page de déconnexion
 * Projet : Gestion des Notes et Absences des Étudiants
 * Auteur  : Étudiant 6
 */

session_start();

// ── Détruire complètement la session ───────────────────────────────────────

// 1. Vider le tableau de session
$_SESSION = [];

// 2. Supprimer le cookie de session côté navigateur
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Détruire la session côté serveur
session_destroy();

// ── Redirection vers la page de connexion ──────────────────────────────────
header('Location: login.php?deconnecte=1');
exit();
