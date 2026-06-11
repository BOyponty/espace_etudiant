<?php
// ============================================
//  PROFIL — profil.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

$erreur  = '';
$succes  = '';

try {
    $pdo = getConnexion();

    // Récupérer les infos de l'étudiant connecté
    $stmt = $pdo->prepare(
        "SELECT * FROM etudiants WHERE id = :id"
    );
    $stmt->execute(['id' => $_SESSION['etudiant_id']]);
    $etudiant = $stmt->fetch();

} catch (PDOException $e) {
    $erreur = "Erreur : " . $e->getMessage();
}

// ============================================
//  MODIFICATION DU PROFIL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {

    $nom    = htmlentities(trim($_POST['nom']));
    $prenom = htmlentities(trim($_POST['prenom']));
    $email  = htmlentities(trim($_POST['email']));

    // Vérification champs vides — empty() cours Section II.2
    if (empty($nom) || empty($prenom) || empty($email)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } else {

        try {
            $pdo = getConnexion();

            // Vérifier si l'email est déjà utilisé par un autre étudiant
            $stmt = $pdo->prepare(
                "SELECT id FROM etudiants 
                 WHERE email = :email AND id != :id"
            );
            $stmt->execute([
                'email' => $email,
                'id'    => $_SESSION['etudiant_id']
            ]);

            if ($stmt->fetch()) {
                $erreur = "Cet email est déjà utilisé par un autre étudiant.";

            } else {

                // Mise à jour — prepare() + execute() Section III.2
                $stmt = $pdo->prepare(
                    "UPDATE etudiants 
                     SET nom = :nom, prenom = :prenom, email = :email 
                     WHERE id = :id"
                );
                $stmt->execute([
                    'nom'    => $nom,
                    'prenom' => $prenom,
                    'email'  => $email,
                    'id'     => $_SESSION['etudiant_id']
                ]);

                // Mettre à jour la session
                $_SESSION['etudiant_nom']    = $nom;
                $_SESSION['etudiant_prenom'] = $prenom;

                $succes = "Profil mis à jour avec succès !";

                // Recharger les infos
                $etudiant['nom']    = $nom;
                $etudiant['prenom'] = $prenom;
                $etudiant['email']  = $email;
            }

        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}

// ============================================
//  MODIFICATION DU MOT DE PASSE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_mdp'])) {

    $ancien_mdp  = trim($_POST['ancien_mdp']);
    $nouveau_mdp = trim($_POST['nouveau_mdp']);
    $confirmation = trim($_POST['confirmation_mdp']);

    if (empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirmation)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif ($nouveau_mdp !== $confirmation) {
        $erreur = "Les nouveaux mots de passe ne correspondent pas.";

    } elseif (strlen($nouveau_mdp) < 6) {
        $erreur = "Le nouveau mot de passe doit contenir au moins 6 caractères.";

    } else {

        try {
            $pdo = getConnexion();

            // Récupérer le mot de passe actuel
            $stmt = $pdo->prepare(
                "SELECT mot_de_passe FROM etudiants WHERE id = :id"
            );
            $stmt->execute(['id' => $_SESSION['etudiant_id']]);
            $row = $stmt->fetch();

            // Vérifier l'ancien mot de passe — password_verify()
            // Section II du cours
            if (!password_verify($ancien_mdp, $row['mot_de_passe'])) {
                $erreur = "L'ancien mot de passe est incorrect.";

            } else {

                // Hasher le nouveau mot de passe — password_hash()
                $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

                // Mise à jour
                $stmt = $pdo->prepare(
                    "UPDATE etudiants 
                     SET mot_de_passe = :mot_de_passe 
                     WHERE id = :id"
                );
                $stmt->execute([
                    'mot_de_passe' => $hash,
                    'id'           => $_SESSION['etudiant_id']
                ]);

                $succes = "Mot de passe modifié avec succès !";
            }

        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}

// ============================================
//  SUPPRESSION DU COMPTE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_compte'])) {

    $confirmation_supp = trim($_POST['confirmation_supp']);

    if ($confirmation_supp !== 'SUPPRIMER') {
        $erreur = "Veuillez taper SUPPRIMER pour confirmer.";

    } else {

        try {
            $pdo = getConnexion();

            // Suppression — ON DELETE CASCADE supprime aussi les publications
            $stmt = $pdo->prepare(
                "DELETE FROM etudiants WHERE id = :id"
            );
            $stmt->execute(['id' => $_SESSION['etudiant_id']]);

            // Destruction complète de la session
            $_SESSION = [];
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            session_destroy();

            header('Location: connexion.php');
            exit();

        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — Espace Étudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo"> Espace Étudiant</div>
    <ul>
        <li><a href="index.php"> Dashboard</a></li>
        <li><a href="publications.php"> Publications</a></li>
        <li><a href="profil.php"> Profil</a></li>
        <li><a href="deconnexion.php"> Déconnexion</a></li>
        <li>
            <button class="theme-toggle" id="toggleTheme">
                 Thème sombre
            </button>
        </li>
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

    <!-- Infos du profil -->
    <div class="card">
        <h2>👤 Mon Profil</h2>
        <p><strong>Nom :</strong> 
            <?= htmlentities($etudiant['nom']) ?></p>
        <p style="margin-top:8px"><strong>Prénom :</strong> 
            <?= htmlentities($etudiant['prenom']) ?></p>
        <p style="margin-top:8px"><strong>Email :</strong> 
            <?= htmlentities($etudiant['email']) ?></p>
        <p style="margin-top:8px"><strong>Inscrit le :</strong> 
            <?= date('d/m/Y', strtotime($etudiant['date_creation'])) ?></p>
    </div>

    <!-- Modifier le profil -->
    <div class="card">
        <h2> Modifier mes informations</h2>
        <form method="POST" action="profil.php">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom"
                       value="<?= htmlentities($etudiant['nom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom"
                       value="<?= htmlentities($etudiant['prenom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlentities($etudiant['email']) ?>" required>
            </div>
            <button type="submit" name="modifier_profil" 
                    class="btn btn-primary">
                Enregistrer les modifications
            </button>
        </form>
    </div>

    <!-- Modifier le mot de passe -->
    <div class="card">
        <h2> Modifier mon mot de passe</h2>
        <form method="POST" action="profil.php">
            <div class="form-group">
                <label>Ancien mot de passe</label>
                <input type="password" name="ancien_mdp" 
                       placeholder="Votre mot de passe actuel" required>
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="nouveau_mdp"
                       placeholder="Minimum 6 caractères" required>
            </div>
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirmation_mdp"
                       placeholder="Répétez le nouveau mot de passe" required>
            </div>
            <button type="submit" name="modifier_mdp" 
                    class="btn btn-secondary">
                Modifier le mot de passe
            </button>
        </form>
    </div>

    <!-- Supprimer le compte -->
    <div class="card">
        <h2> Supprimer mon compte</h2>
        <div class="alert alert-danger">
             Cette action est irréversible. 
            Toutes vos publications seront supprimées.
        </div>
        <form method="POST" action="profil.php">
            <div class="form-group">
                <label>
                    Tapez <strong>SUPPRIMER</strong> pour confirmer
                </label>
                <input type="text" name="confirmation_supp"
                       placeholder="SUPPRIMER" required>
            </div>
            <button type="submit" name="supprimer_compte" 
                    class="btn btn-danger">
                Supprimer définitivement mon compte
            </button>
        </form>
    </div>

</div>

<script>
// ============================================
//  THÈME SOMBRE / CLAIR — main.js
//  Basé sur Section IV du cours : DOM + Events
// ============================================

const toggleBtn = document.getElementById('toggleTheme');

// Récupérer le thème sauvegardé
// localStorage garde le choix même après rechargement
const themeSauvegarde = localStorage.getItem('theme');

if (themeSauvegarde === 'dark') {
    document.body.classList.add('dark');
    toggleBtn.textContent = ' Thème clair';
}

// Gestion du clic — addEventListener vu Section IV.4 du cours
toggleBtn.addEventListener('click', function() {

    // classList.toggle ajoute ou enlève la classe 'dark'
    document.body.classList.toggle('dark');

    if (document.body.classList.contains('dark')) {
        // Passer en mode sombre
        localStorage.setItem('theme', 'dark');
        toggleBtn.textContent = ' Thème clair';
    } else {
        // Passer en mode clair
        localStorage.setItem('theme', 'light');
        toggleBtn.textContent = ' Thème sombre';
    }
});
</script>

</body>
</html>