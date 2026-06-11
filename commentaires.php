<?php
// ============================================
//  COMMENTAIRES — commentaires.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

$erreur = '';
$succes = '';

// Vérifier que l'ID de la publication est passé en GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: publications.php');
    exit();
}

$id_publication = (int)$_GET['id'];

try {
    $pdo = getConnexion();

    // Récupérer la publication
    $stmt = $pdo->prepare(
        "SELECT p.*, e.nom, e.prenom 
         FROM publications p 
         JOIN etudiants e ON p.etudiant_id = e.id 
         WHERE p.id = :id"
    );
    $stmt->execute(['id' => $id_publication]);
    $publication = $stmt->fetch();

    // Vérifier que la publication existe
    if (!$publication) {
        header('Location: publications.php');
        exit();
    }

    // ============================================
    //  AJOUTER UN COMMENTAIRE
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_commentaire'])) {

        $contenu = trim($_POST['contenu']);

        if (empty($contenu)) {
            $erreur = "Le commentaire ne peut pas etre vide.";

        } else {

            // Insertion du commentaire
            $stmt = $pdo->prepare(
                "INSERT INTO commentaires 
                 (publication_id, etudiant_id, contenu) 
                 VALUES (:publication_id, :etudiant_id, :contenu)"
            );
            $stmt->execute([
                'publication_id' => $id_publication,
                'etudiant_id'    => $_SESSION['etudiant_id'],
                'contenu'        => $contenu
            ]);

            // Créer une notification pour le propriétaire
            // de la publication si ce n'est pas lui qui commente
            if ($publication['etudiant_id'] != $_SESSION['etudiant_id']) {

                $message = $_SESSION['etudiant_prenom'] . ' ' .
                           $_SESSION['etudiant_nom'] .
                           ' a commente votre publication : ' .
                           $publication['titre'];

                $stmt = $pdo->prepare(
                    "INSERT INTO notifications 
                     (etudiant_id, message) 
                     VALUES (:etudiant_id, :message)"
                );
                $stmt->execute([
                    'etudiant_id' => $publication['etudiant_id'],
                    'message'     => $message
                ]);
            }

            $succes = "Commentaire ajoute avec succes !";
        }
    }

    // ============================================
    //  SUPPRIMER UN COMMENTAIRE
    // ============================================
    if (isset($_GET['supprimer']) && !empty($_GET['supprimer'])) {

        $id_commentaire = (int)$_GET['supprimer'];

        // Verifier que c'est bien le proprietaire du commentaire
        $stmt = $pdo->prepare(
            "SELECT * FROM commentaires WHERE id = :id"
        );
        $stmt->execute(['id' => $id_commentaire]);
        $commentaire = $stmt->fetch();

        if ($commentaire && 
            $commentaire['etudiant_id'] == $_SESSION['etudiant_id']) {

            $stmt = $pdo->prepare(
                "DELETE FROM commentaires 
                 WHERE id = :id AND etudiant_id = :etudiant_id"
            );
            $stmt->execute([
                'id'          => $id_commentaire,
                'etudiant_id' => $_SESSION['etudiant_id']
            ]);
        }

        header('Location: commentaires.php?id=' . $id_publication);
        exit();
    }

    // Récupérer tous les commentaires de la publication
    $stmt = $pdo->prepare(
        "SELECT c.*, e.nom, e.prenom, e.photo 
         FROM commentaires c 
         JOIN etudiants e ON c.etudiant_id = e.id 
         WHERE c.publication_id = :publication_id 
         ORDER BY c.date_creation ASC"
    );
    $stmt->execute(['publication_id' => $id_publication]);
    $commentaires = $stmt->fetchAll();

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentaires — Espace Etudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">Espace Etudiant</div>
    <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="publications.php">Publications</a></li>
        <li><a href="profil.php">Profil</a></li>
        <li><a href="deconnexion.php">Deconnexion</a></li>
        <li>
            <button class="theme-toggle" id="toggleTheme">
                Theme sombre
            </button>
        </li>
    </ul>
</nav>

<div class="container">

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= htmlentities($erreur) ?></div>
    <?php endif; ?>
    <?php if (!empty($succes)): ?>
        <div class="alert alert-success"><?= htmlentities($succes) ?></div>
    <?php endif; ?>

    <!-- Publication -->
    <div class="card">
        <h2>Publication</h2>
        <div class="publication-meta">
            Par <?= htmlentities($publication['prenom']) ?>
                <?= htmlentities($publication['nom']) ?> —
            <?= date('d/m/Y a H:i', 
                    strtotime($publication['date_creation'])) ?>
        </div>
        <h3 style="margin-bottom:10px;">
            <?= htmlentities($publication['titre']) ?>
        </h3>
        <p><?= nl2br(htmlentities($publication['contenu'])) ?></p>
        <div style="margin-top:15px;">
            <a href="publications.php" class="btn btn-secondary btn-sm">
                Retour aux publications
            </a>
        </div>
    </div>

    <!-- Commentaires -->
    <div class="card">
        <h2>
            Commentaires
            <span style="font-size:0.85rem; color:#999;">
                (<?= count($commentaires) ?>)
            </span>
        </h2>

        <?php if (empty($commentaires)): ?>
            <div class="alert alert-info">
                Aucun commentaire pour le moment. Soyez le premier !
            </div>
        <?php else: ?>
            <?php foreach ($commentaires as $commentaire): ?>
                <div class="commentaire-card">

                    <!-- Photo + Nom -->
                    <div class="commentaire-header">
                        <?php if (!empty($commentaire['photo']) && 
                                  file_exists($commentaire['photo'])): ?>
                            <img src="<?= htmlentities($commentaire['photo']) ?>"
                                 alt="photo"
                                 class="commentaire-avatar">
                        <?php else: ?>
                            <div class="commentaire-avatar-default">
                                <?= strtoupper(substr($commentaire['prenom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <strong>
                                <?= htmlentities($commentaire['prenom']) ?>
                                <?= htmlentities($commentaire['nom']) ?>
                            </strong>
                            <span class="publication-meta" 
                                  style="display:block;">
                                <?= date('d/m/Y a H:i', 
                                        strtotime($commentaire['date_creation'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Contenu -->
                    <p class="commentaire-contenu">
                        <?= nl2br(htmlentities($commentaire['contenu'])) ?>
                    </p>

                    <!-- Supprimer si proprietaire -->
                    <?php if ($commentaire['etudiant_id'] == 
                              $_SESSION['etudiant_id']): ?>
                        <a href="commentaires.php?id=<?= $id_publication ?>&supprimer=<?= $commentaire['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ce commentaire ?')">
                            Supprimer
                        </a>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Formulaire commentaire -->
        <div style="margin-top:25px; border-top:1px solid #eee; padding-top:20px;">
            <h3 style="margin-bottom:15px; color:#1a1a2e;">
                Ajouter un commentaire
            </h3>
            <form method="POST" 
                  action="commentaires.php?id=<?= $id_publication ?>">
                <div class="form-group">
                    <textarea name="contenu" 
                              placeholder="Ecrivez votre commentaire..."
                              required></textarea>
                </div>
                <button type="submit" name="ajouter_commentaire"
                        class="btn btn-primary">
                    Publier le commentaire
                </button>
            </form>
        </div>
    </div>

</div>

<script>
const toggleBtn = document.getElementById('toggleTheme');
const themeSauvegarde = localStorage.getItem('theme');

if (themeSauvegarde === 'dark') {
    document.body.classList.add('dark');
    toggleBtn.textContent = 'Theme clair';
}

toggleBtn.addEventListener('click', function() {
    document.body.classList.toggle('dark');
    if (document.body.classList.contains('dark')) {
        localStorage.setItem('theme', 'dark');
        toggleBtn.textContent = 'Theme clair';
    } else {
        localStorage.setItem('theme', 'light');
        toggleBtn.textContent = 'Theme sombre';
    }
});
</script>

</body>
</html>