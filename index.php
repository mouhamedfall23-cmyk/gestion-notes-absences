<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
} else {
    if ($_SESSION['user_role'] === 'admin')
        header('Location: admin/index.php');
    elseif ($_SESSION['user_role'] === 'enseignant')
        header('Location: enseignant/index.php');
    else
        header('Location: etudiant/index.php');
}
exit;
?>