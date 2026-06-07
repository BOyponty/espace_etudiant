<?php
// ============================================
//  DÉCONNEXION — deconnexion.php
// ============================================

session_start();

// Étape 1 : Vider toutes les variables de session
$_SESSION = [];

// Étape 2 : Détruire le cookie de session
// (supprime le cookie PHPSESSID du navigateur)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Étape 3 : Détruire complètement la session
session_destroy();

// Étape 4 : Rediriger vers la page de connexion
header('Location: connexion.php');
exit();
?>