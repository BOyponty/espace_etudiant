<?php
// ============================================
//  MODIFIER UNE PUBLICATION — edit_publication.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

$erreur = '';
$succes = '';

// Vérifier que l'ID est bien passé en GET
// isset() — cours Section II.2
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: publications.php');
    exit();
}

$id_publication = (int)$_GET['id']; // Cast en entier pour la sécurité

try {
    $pdo = getConnexion();

    // Récupérer la publication
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
    //  le propriétaire qui modifie
    // ============================================
    if ($publication['etudiant_id'] != $_SESSION['etudiant_id']) {
        header('Location: publications.php');
        exit();
    }

    // ============================================
    //  TRAITEMENT DU FORMULAIRE
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_pub'])) {
$titre   = trim($_POST['titre']);
$contenu = trim($_POST['contenu']);

        // Vérification champs vides — empty() cours Section II.2
        if (empty($titre) || empty($contenu)) {
            $erreur = "Le titre et le contenu sont obligatoires.";

        } else {

            // Mise à jour — prepare() + execute() Section III.2
            $stmt = $pdo->prepare(
                "UPDATE publications 
                 SET titre = :titre, contenu = :contenu 
                 WHERE id = :id AND etudiant_id = :etudiant_id"
            );
            $stmt->execute([
                'titre'       => $titre,
                'contenu'     => $contenu,
                'id'          => $id_publication,
                'etudiant_id' => $_SESSION['etudiant_id']
            ]);

            // Redirection après succès
            header('Location: publications.php');
            exit();
        }
    }

} catch (PDOException $e) {
    // Gestion erreur PDO — Section III.3 du cours
    $erreur = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la publication — Espace Étudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">🎓 Espace Étudiant</div>
    <ul>
        <li><a href="index.php">🏠 Dashboard</a></li>
        <li><a href="publications.php">📝 Publications</a></li>
        <li><a href="profil.php">👤 Profil</a></li>
        <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
    </ul>
</nav>

<div class="container">

    <!-- Affichage erreur -->
    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= $erreur ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>✏️ Modifier la Publication</h2>

        <form method="POST" 
              action="edit_publication.php?id=<?= $id_publication ?>">

            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="titre"
                       value="<?= htmlentities($publication['titre']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Contenu</label>
                <textarea name="contenu" required>
<?= htmlentities($publication['contenu']) ?></textarea>
            </div>

            <div style="display:flex; gap:15px;">
                <button type="submit" name="modifier_pub"
                        class="btn btn-primary">
                    💾 Enregistrer les modifications
                </button>
                <a href="publications.php" class="btn btn-secondary">
                    ❌ Annuler
                </a>
            </div>

        </form>
    </div>

</div>

</body>
</html>