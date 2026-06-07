<?php
// ============================================
//  CONNEXION PDO — db.php
// ============================================

function getConnexion() {

    $host     = 'localhost';       // Serveur WAMP
    $dbname   = 'espace_etudiant'; // Ta base de données
    $user     = 'root';            // Utilisateur par défaut WAMP
    $password = '';                // Mot de passe vide par défaut WAMP
    $charset  = 'utf8';            // Encodage pour les accents

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        return $pdo;

    } catch (PDOException $e) {
        die(json_encode(['erreur' => 'Connexion échouée : ' . $e->getMessage()]));
    }
}
?>