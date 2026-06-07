<?php
// ============================================
//  INSCRIPTION — inscription.php
// ============================================

session_start();

// Si déjà connecté → rediriger vers le dashboard
if (isset($_SESSION['etudiant_id'])) {
    header('Location: index.php');
    exit();
}

require 'db.php';

$erreur  = '';
$succes  = '';

// Traitement du formulaire — Section II.2 du cours
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération des données — isset() vu dans le cours
    if (isset($_POST['nom'], $_POST['prenom'], $_POST['email'], 
              $_POST['mot_de_passe'], $_POST['confirmation'])) {

        // Nettoyage des données — htmlentities() pour sécuriser
        $nom          = htmlentities(trim($_POST['nom']));
        $prenom       = htmlentities(trim($_POST['prenom']));
        $email        = htmlentities(trim($_POST['email']));
        $mot_de_passe = trim($_POST['mot_de_passe']);
        $confirmation = trim($_POST['confirmation']);

        // Vérification des champs vides — empty() vu dans le cours
        if (empty($nom) || empty($prenom) || empty($email) || 
            empty($mot_de_passe) || empty($confirmation)) {
            $erreur = "Tous les champs sont obligatoires.";

        // Vérification format email
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'adresse email n'est pas valide.";

        // Vérification mots de passe identiques
        } elseif ($mot_de_passe !== $confirmation) {
            $erreur = "Les mots de passe ne correspondent pas.";

        // Vérification longueur mot de passe
        } elseif (strlen($mot_de_passe) < 6) {
            $erreur = "Le mot de passe doit contenir au moins 6 caractères.";

        } else {

            try {
                $pdo = getConnexion();

                // Vérifier si l'email existe déjà — prepare() + execute()
                // Section III.3 du cours : Requêtes Préparées
                $stmt = $pdo->prepare(
                    "SELECT id FROM etudiants WHERE email = :email"
                );
                $stmt->execute(['email' => $email]);

                if ($stmt->fetch()) {
                    $erreur = "Cet email est déjà utilisé.";

                } else {

                    // Hashage du mot de passe — password_hash()
                    // Section II du cours : Sécurité des mots de passe
                    $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                    // Insertion en base — prepare() + execute()
                    $stmt = $pdo->prepare(
                        "INSERT INTO etudiants (nom, prenom, email, mot_de_passe) 
                         VALUES (:nom, :prenom, :email, :mot_de_passe)"
                    );
                    $stmt->execute([
                        'nom'          => $nom,
                        'prenom'       => $prenom,
                        'email'        => $email,
                        'mot_de_passe' => $hash
                    ]);

                    $succes = "Inscription réussie ! Vous pouvez vous connecter.";
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
    <title>Inscription — Espace Étudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="card">
        <h1>🎓 Espace Étudiant</h1>
        <p class="subtitle">Créez votre compte</p>

        <!-- Affichage des messages -->
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger"><?= $erreur ?></div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="alert alert-success"><?= $succes ?></div>
        <?php endif; ?>

        <!-- Formulaire d'inscription -->
        <form method="POST" action="inscription.php">

            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" 
                       placeholder="Votre nom" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" 
                       placeholder="Votre prénom" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       placeholder="votre@email.com" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" 
                       placeholder="Minimum 6 caractères" required>
            </div>

            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation" 
                       placeholder="Répétez le mot de passe" required>
            </div>

            <button type="submit" class="btn btn-primary" 
                    style="width:100%">S'inscrire</button>
        </form>

        <div class="auth-link">
            Déjà un compte ? <a href="connexion.php">Se connecter</a>
        </div>
    </div>
</div>

</body>
</html>