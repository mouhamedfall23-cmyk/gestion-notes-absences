<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si pas connecté → retour login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Vérifier le rôle
function checkRole($role) {
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ../login.php');
        exit;
    }
}
?>