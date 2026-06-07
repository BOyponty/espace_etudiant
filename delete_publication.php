<?php
// ============================================
//  SUPPRIMER UNE PUBLICATION — delete_publication.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

// Vérifier que l'ID est bien passé en GET
// isset() — cours Section II.2
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: publications.php');
    exit();
}

$id_publication = (int)$_GET['id']; // Cast en entier pour la sécurité

try {
    $pdo = getConnexion();

    // Récupérer la publication pour vérification
    $stmt = $pdo->prepare(
        "SELECT * FROM publications WHERE id = :id"
    );
    $stmt->execute(['id' => $id_publication]);
    $publication = $stmt->fetch();

    // Vérifier que la publication existe
    if (!$publication) {
        header('Location: publications.php');
        exit();
    }

    // ============================================
    //  SÉCURITÉ : Vérifier que c'est bien
    //  le propriétaire qui supprime
    // ============================================
    if ($publication['etudiant_id'] != $_SESSION['etudiant_id']) {
        header('Location: publications.php');
        exit();
    }

    // ============================================
    //  SUPPRESSION
    // ============================================
    // Double vérification id + etudiant_id dans la requête
    $stmt = $pdo->prepare(
        "DELETE FROM publications 
         WHERE id = :id AND etudiant_id = :etudiant_id"
    );
    $stmt->execute([
        'id'          => $id_publication,
        'etudiant_id' => $_SESSION['etudiant_id']
    ]);

    // Redirection après suppression
    header('Location: publications.php');
    exit();

} catch (PDOException $e) {
    // Gestion erreur PDO — Section III.3 du cours
    die("Erreur : " . $e->getMessage());
}
?>