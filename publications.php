<?php
// ============================================
//  PUBLICATIONS — publications.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

$erreur = '';
$succes = '';

try {
    $pdo = getConnexion();

    // ============================================
    //  CRÉER UNE PUBLICATION
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_pub'])) {
$titre   = trim($_POST['titre']);
$contenu = trim($_POST['contenu']);
        // Vérification champs vides — empty() cours Section II.2
        if (empty($titre) || empty($contenu)) {
            $erreur = "Le titre et le contenu sont obligatoires.";

        } else {

            // Insertion — prepare() + execute() Section III.2
            $stmt = $pdo->prepare(
                "INSERT INTO publications (etudiant_id, titre, contenu) 
                 VALUES (:etudiant_id, :titre, :contenu)"
            );
            $stmt->execute([
                'etudiant_id' => $_SESSION['etudiant_id'],
                'titre'       => $titre,
                'contenu'     => $contenu
            ]);

            $succes = "Publication créée avec succès !";
        }
    }

    // ============================================
    //  RECHERCHE — Bonus cours
    // ============================================
    $recherche = '';
    if (isset($_GET['recherche']) && !empty(trim($_GET['recherche']))) {
        $recherche = htmlentities(trim($_GET['recherche']));
    }

    // ============================================
    //  PAGINATION
    // ============================================
    $par_page    = 5; // Nombre de publications par page
    $page_actuelle = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset      = ($page_actuelle - 1) * $par_page;

    // Compter le total des publications
    if (!empty($recherche)) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total FROM publications 
             WHERE titre LIKE :recherche 
             OR contenu LIKE :recherche"
        );
        $stmt->execute(['recherche' => '%' . $recherche . '%']);
    } else {
        $stmt = $pdo->query(
            "SELECT COUNT(*) as total FROM publications"
        );
    }
    $total_pubs  = $stmt->fetch()['total'];
    $total_pages = ceil($total_pubs / $par_page);

    // Récupérer les publications avec pagination
    if (!empty($recherche)) {
        $stmt = $pdo->prepare(
            "SELECT p.*, e.nom, e.prenom 
             FROM publications p 
             JOIN etudiants e ON p.etudiant_id = e.id 
             WHERE p.titre LIKE :recherche 
             OR p.contenu LIKE :recherche
             ORDER BY p.date_creation DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':recherche', '%' . $recherche . '%');
        $stmt->bindValue(':limit',  $par_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            "SELECT p.*, e.nom, e.prenom 
             FROM publications p 
             JOIN etudiants e ON p.etudiant_id = e.id 
             ORDER BY p.date_creation DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $par_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
        $stmt->execute();
    }
    $publications = $stmt->fetchAll();

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publications — Espace Étudiant</title>
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

    <!-- Affichage messages -->
    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= $erreur ?></div>
    <?php endif; ?>
    <?php if (!empty($succes)): ?>
        <div class="alert alert-success"><?= $succes ?></div>
    <?php endif; ?>

    <!-- Formulaire création publication -->
    <div class="card">
        <h2>✏️ Nouvelle Publication</h2>
        <form method="POST" action="publications.php">
            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="titre"
                       placeholder="Titre de votre publication" required>
            </div>
            <div class="form-group">
                <label>Contenu</label>
                <textarea name="contenu"
                          placeholder="Écrivez votre publication ici..."
                          required></textarea>
            </div>
            <button type="submit" name="creer_pub" 
                    class="btn btn-primary">
                Publier
            </button>
        </form>
    </div>

    <!-- Barre de recherche -->
    <div class="card">
        <h2>🔍 Rechercher une publication</h2>
        <form method="GET" action="publications.php">
            <div style="display:flex; gap:10px;">
                <input type="text" name="recherche"
                       class="form-group"
                       style="flex:1; padding:12px 15px; border:1px solid #ddd;
                              border-radius:8px; font-size:0.95rem;"
                       placeholder="Rechercher par titre ou contenu..."
                       value="<?= htmlentities($recherche) ?>">
                <button type="submit" class="btn btn-secondary">
                    Rechercher
                </button>
                <?php if (!empty($recherche)): ?>
                    <a href="publications.php" class="btn btn-warning">
                        Réinitialiser
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Liste des publications -->
    <div class="card">
        <h2>📰 Toutes les Publications 
            <span style="font-size:0.85rem; color:#999;">
                (<?= $total_pubs ?> au total)
            </span>
        </h2>

        <?php if (empty($publications)): ?>
            <div class="alert alert-info">
                <?= !empty($recherche) 
                    ? "Aucune publication trouvée pour \"$recherche\"." 
                    : "Aucune publication pour le moment." ?>
            </div>
        <?php else: ?>

            <?php foreach ($publications as $pub): ?>
                <div class="publication-card">

                    <!-- Auteur et date -->
                    <div class="publication-meta">
                        👤 <?= htmlentities($pub['prenom']) ?> 
                           <?= htmlentities($pub['nom']) ?> —
                        🕒 <?= date('d/m/Y à H:i', 
                                strtotime($pub['date_creation'])) ?>
                    </div>

                    <!-- Titre -->
                    <h3><?= htmlentities($pub['titre']) ?></h3>

                    <!-- Contenu -->
                    <p><?= nl2br(htmlentities($pub['contenu'])) ?></p>

                    <!-- Actions uniquement pour le propriétaire -->
                    <?php if ($pub['etudiant_id'] == $_SESSION['etudiant_id']): ?>
                        <div class="publication-actions">
                            <a href="edit_publication.php?id=<?= $pub['id'] ?>"
                               class="btn btn-warning btn-sm">
                                ✏️ Modifier
                            </a>
                            <a href="delete_publication.php?id=<?= $pub['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Confirmer la suppression ?')">
                                🗑️ Supprimer
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:center; 
                            gap:8px; margin-top:20px; flex-wrap:wrap;">

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="publications.php?page=<?= $i ?>
                                 <?= !empty($recherche) 
                                     ? '&recherche=' . urlencode($recherche) 
                                     : '' ?>"
                           class="btn btn-sm 
                                  <?= $i == $page_actuelle 
                                      ? 'btn-primary' 
                                      : 'btn-secondary' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

</div>

</body>
</html>