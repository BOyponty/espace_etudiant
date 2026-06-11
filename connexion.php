<?php
// ============================================
//  CONNEXION — connexion.php
// ============================================

session_start();

// Si déjà connecté → rediriger vers le dashboard
if (isset($_SESSION['etudiant_id'])) {
    header('Location: index.php');
    exit();
}

require 'db.php';

$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification existence des données — isset() cours Section II.2
    if (isset($_POST['email'], $_POST['mot_de_passe'])) {

        // Nettoyage des données
        $email        = htmlentities(trim($_POST['email']));
        $mot_de_passe = trim($_POST['mot_de_passe']);

        // Vérification champs vides — empty() cours Section II.2
        if (empty($email) || empty($mot_de_passe)) {
            $erreur = "Tous les champs sont obligatoires.";

        } else {

            try {
                $pdo = getConnexion();

                // Recherche de l'étudiant par email
                // Requête préparée — Section III.3 du cours
                $stmt = $pdo->prepare(
                    "SELECT * FROM etudiants WHERE email = :email"
                );
                $stmt->execute(['email' => $email]);
                $etudiant = $stmt->fetch();

                // Vérification mot de passe — password_verify()
                // Section II du cours : Sécurité des mots de passe
                if ($etudiant && password_verify($mot_de_passe, $etudiant['mot_de_passe'])) {

                    // Création de la session
                    $_SESSION['etudiant_id']  = $etudiant['id'];
                    $_SESSION['etudiant_nom'] = $etudiant['nom'];
                    $_SESSION['etudiant_prenom'] = $etudiant['prenom'];

                    // Redirection vers le dashboard
                    header('Location: index.php');
                    exit();

                } else {
                    $erreur = "Email ou mot de passe incorrect.";
                }

            } catch (PDOException $e) {
                // Gestion erreur PDO — Section III.3 du cours
                $erreur = "Erreur base de données : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Espace Étudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="card">
        <h1> Espace Étudiant</h1>
        <p class="subtitle">Connectez-vous à votre compte</p>

        <!-- Affichage erreur -->
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger"><?= $erreur ?></div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form method="POST" action="connexion.php">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="votre@email.com" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe"
                       placeholder="Votre mot de passe" required>
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width:100%">Se connecter</button>
        </form>

        <div class="auth-link">
            Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
        </div>
    </div>
</div>

</body>
</html>