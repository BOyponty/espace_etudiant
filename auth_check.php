<?php
// ============================================
//  VÉRIFICATION DE SESSION — auth_check.php
// ============================================

// Démarre la session si elle n'est pas encore démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si l'étudiant est connecté
// On utilise isset() — vu dans le cours Section II.2
if (!isset($_SESSION['etudiant_id'])) {

    // Si non connecté → on redirige vers la page de connexion
    header('Location: connexion.php');
    exit(); // Toujours mettre exit() après un header redirect
            // pour stopper l'exécution du reste du script
}
?>