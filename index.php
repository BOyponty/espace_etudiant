<?php
// ============================================
//  TABLEAU DE BORD — index.php
// ============================================

session_start();

// Protection de la page — auth_check
// Si non connecté → redirige vers connexion
require __DIR__ . '/auth_check.php';
require __DIR__ . '/db.php';

try {
    $pdo = getConnexion();

    // Récupérer les infos de l'étudiant connecté
    $stmt = $pdo->prepare(
        "SELECT * FROM etudiants WHERE id = :id"
    );
    $stmt->execute(['id' => $_SESSION['etudiant_id']]);
    $etudiant = $stmt->fetch();

    // Compter ses publications
    $stmt2 = $pdo->prepare(
        "SELECT COUNT(*) as total FROM publications 
         WHERE etudiant_id = :id"
    );
    $stmt2->execute(['id' => $_SESSION['etudiant_id']]);
    $stats = $stmt2->fetch();

    // Compter total étudiants
    $stmt3 = $pdo->query("SELECT COUNT(*) as total FROM etudiants");
    $totalEtudiants = $stmt3->fetch();

    // Récupérer les 3 dernières publications
    $stmt4 = $pdo->prepare(
        "SELECT p.*, e.nom, e.prenom 
         FROM publications p 
         JOIN etudiants e ON p.etudiant_id = e.id 
         ORDER BY p.date_creation DESC 
         LIMIT 3"
    );
    $stmt4->execute();
    $dernieres = $stmt4->fetchAll();

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — Espace Étudiant</title>
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

    <!-- Message de bienvenue -->
    <div class="card">
        <h2>
            👋 Bienvenue, 
            <?= htmlentities($_SESSION['etudiant_prenom']) ?> 
            <?= htmlentities($_SESSION['etudiant_nom']) ?> !
        </h2>
        <p style="color:#666; margin-top:8px;">
            Vous êtes connecté à votre espace étudiant.
        </p>
    </div>

    <!-- Statistiques -->
    <div class="dashboard-grid">

        <div class="stat-card">
            <div class="number">
                <?= $stats['total'] ?>
            </div>
            <div class="label">Mes Publications</div>
        </div>

        <div class="stat-card">
            <div class="number">
                <?= $totalEtudiants['total'] ?>
            </div>
            <div class="label">Étudiants inscrits</div>
        </div>

        <div class="stat-card">
            <div class="number">
                <?= date('d/m/Y') ?>
            </div>
            <div class="label">Date du jour</div>
        </div>

    </div>

    <!-- Dernières publications -->
    <div class="card">
        <h2>📰 Dernières Publications</h2>

        <?php if (empty($dernieres)): ?>
            <div class="alert alert-info">
                Aucune publication pour le moment.
            </div>
        <?php else: ?>
            <?php foreach ($dernieres as $pub): ?>
                <div class="publication-card">

                    <div class="publication-meta">
                        👤 <?= htmlentities($pub['prenom']) ?> 
                           <?= htmlentities($pub['nom']) ?> — 
                        🕒 <?= date('d/m/Y à H:i', 
                                strtotime($pub['date_creation'])) ?>
                    </div>

                    <h3><?= htmlentities($pub['titre']) ?></h3>

                    <p>
                        <?= htmlentities(
                            substr($pub['contenu'], 0, 150)
                        ) ?>...
                    </p>

                </div>
            <?php endforeach; ?>

            <a href="publications.php" class="btn btn-secondary">
                Voir toutes les publications
            </a>
        <?php endif; ?>
    </div>

    <!-- Accès rapide -->
    <div class="card">
        <h2>⚡ Accès Rapide</h2>
        <div style="display:flex; gap:15px; flex-wrap:wrap;">
            <a href="publications.php" class="btn btn-primary">
                📝 Nouvelle publication
            </a>
            <a href="profil.php" class="btn btn-secondary">
                👤 Mon profil
            </a>
        </div>
    </div>

</div>

</body>
</html>