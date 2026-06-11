<?php
// ============================================
//  PROFIL — profil.php
// ============================================

session_start();
require 'auth_check.php';
require 'db.php';

$erreur = '';
$succes = '';

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
//  UPLOAD PHOTO DE PROFIL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {

    // Vérifier qu'un fichier a bien été envoyé
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

        $fichier    = $_FILES['photo'];
        $nom        = $fichier['name'];
        $taille     = $fichier['size'];
        $tmp        = $fichier['tmp_name'];
        $extension  = strtolower(pathinfo($nom, PATHINFO_EXTENSION));

        // Extensions autorisées
        $extensions_autorisees = ['jpg', 'jpeg', 'png', 'gif'];

        // Taille max : 2 Mo
        $taille_max = 2 * 1024 * 1024;

        if (!in_array($extension, $extensions_autorisees)) {
            $erreur = "Format non autorisé. Utilisez JPG, PNG ou GIF.";

        } elseif ($taille > $taille_max) {
            $erreur = "L'image ne doit pas dépasser 2 Mo.";

        } else {

            // Générer un nom unique pour éviter les conflits
            $nouveau_nom = 'photo_' . $_SESSION['etudiant_id'] 
                         . '_' . time() . '.' . $extension;

            $destination = 'uploads/' . $nouveau_nom;

            // Déplacer le fichier dans le dossier uploads/
            if (move_uploaded_file($tmp, $destination)) {

                try {
                    $pdo = getConnexion();

                    // Supprimer l'ancienne photo si elle existe
                    if (!empty($etudiant['photo']) && 
                        file_exists($etudiant['photo'])) {
                        unlink($etudiant['photo']);
                    }

                    // Mettre à jour en base de données
                    $stmt = $pdo->prepare(
                        "UPDATE etudiants SET photo = :photo 
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        'photo' => $destination,
                        'id'    => $_SESSION['etudiant_id']
                    ]);

                    // Mettre à jour la variable locale
                    $etudiant['photo'] = $destination;
                    $succes = "Photo de profil mise à jour !";

                } catch (PDOException $e) {
                    $erreur = "Erreur : " . $e->getMessage();
                }

            } else {
                $erreur = "Erreur lors de l'upload. Réessayez.";
            }
        }

    } else {
        $erreur = "Veuillez sélectionner une photo.";
    }
}

// ============================================
//  MODIFICATION DU PROFIL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {

    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email  = trim($_POST['email']);

    if (empty($nom) || empty($prenom) || empty($email)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } else {

        try {
            $pdo = getConnexion();

            $stmt = $pdo->prepare(
                "SELECT id FROM etudiants 
                 WHERE email = :email AND id != :id"
            );
            $stmt->execute([
                'email' => $email,
                'id'    => $_SESSION['etudiant_id']
            ]);

            if ($stmt->fetch()) {
                $erreur = "Cet email est déjà utilisé.";

            } else {

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

                $_SESSION['etudiant_nom']    = $nom;
                $_SESSION['etudiant_prenom'] = $prenom;

                $etudiant['nom']    = $nom;
                $etudiant['prenom'] = $prenom;
                $etudiant['email']  = $email;

                $succes = "Profil mis à jour avec succès !";
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

    $ancien_mdp   = trim($_POST['ancien_mdp']);
    $nouveau_mdp  = trim($_POST['nouveau_mdp']);
    $confirmation = trim($_POST['confirmation_mdp']);

    if (empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirmation)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif ($nouveau_mdp !== $confirmation) {
        $erreur = "Les nouveaux mots de passe ne correspondent pas.";

    } elseif (strlen($nouveau_mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";

    } else {

        try {
            $pdo = getConnexion();

            $stmt = $pdo->prepare(
                "SELECT mot_de_passe FROM etudiants WHERE id = :id"
            );
            $stmt->execute(['id' => $_SESSION['etudiant_id']]);
            $row = $stmt->fetch();

            if (!password_verify($ancien_mdp, $row['mot_de_passe'])) {
                $erreur = "L'ancien mot de passe est incorrect.";

            } else {

                $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

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

            // Supprimer la photo si elle existe
            if (!empty($etudiant['photo']) && 
                file_exists($etudiant['photo'])) {
                unlink($etudiant['photo']);
            }

            $stmt = $pdo->prepare(
                "DELETE FROM etudiants WHERE id = :id"
            );
            $stmt->execute(['id' => $_SESSION['etudiant_id']]);

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

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger"><?= htmlentities($erreur) ?></div>
    <?php endif; ?>
    <?php if (!empty($succes)): ?>
        <div class="alert alert-success"><?= htmlentities($succes) ?></div>
    <?php endif; ?>

    <!-- Photo de profil -->
    <div class="card" style="text-align:center;">
        <h2> Photo de Profil</h2>

        <!-- Affichage de la photo -->
        <?php if (!empty($etudiant['photo']) && 
                  file_exists($etudiant['photo'])): ?>
            <img src="<?= htmlentities($etudiant['photo']) ?>"
                 alt="Photo de profil"
                 style="width:150px; height:150px; border-radius:50%;
                        object-fit:cover; border:4px solid #e94560;
                        margin-bottom:20px;">
        <?php else: ?>
            <div style="width:150px; height:150px; border-radius:50%;
                        background-color:#1a1a2e; display:flex;
                        align-items:center; justify-content:center;
                        margin:0 auto 20px; font-size:3rem;">
                
            </div>
        <?php endif; ?>

        <!-- Formulaire upload -->
        <form method="POST" action="profil.php" 
              enctype="multipart/form-data">
            <div class="form-group">
                <input type="file" name="photo" 
                       accept=".jpg,.jpeg,.png,.gif"
                       style="margin-bottom:10px;">
                <small style="color:#999; display:block;">
                    Formats acceptés : JPG, PNG, GIF — Max 2 Mo
                </small>
            </div>
            <button type="submit" name="upload_photo" 
                    class="btn btn-primary">
                 Mettre à jour la photo
            </button>
        </form>
    </div>

    <!-- Infos du profil -->
    <div class="card">
        <h2> Mon Profil</h2>
        <p><strong>Nom :</strong> 
            <?= htmlentities($etudiant['nom']) ?></p>
        <p style="margin-top:8px"><strong>Prénom :</strong> 
            <?= htmlentities($etudiant['prenom']) ?></p>
        <p style="margin-top:8px"><strong>Email :</strong> 
            <?= htmlentities($etudiant['email']) ?></p>
        <p style="margin-top:8px"><strong>Rôle :</strong> 
            <?= htmlentities($etudiant['role']) ?></p>
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
                       value="<?= htmlentities($etudiant['nom']) ?>" 
                       required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom"
                       value="<?= htmlentities($etudiant['prenom']) ?>" 
                       required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlentities($etudiant['email']) ?>" 
                       required>
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
                       placeholder="Répétez le nouveau mot de passe" 
                       required>
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
const toggleBtn = document.getElementById('toggleTheme');
const themeSauvegarde = localStorage.getItem('theme');

if (themeSauvegarde === 'dark') {
    document.body.classList.add('dark');
    toggleBtn.textContent = ' Thème clair';
}

toggleBtn.addEventListener('click', function() {
    document.body.classList.toggle('dark');
    if (document.body.classList.contains('dark')) {
        localStorage.setItem('theme', 'dark');
        toggleBtn.textContent = ' Thème clair';
    } else {
        localStorage.setItem('theme', 'light');
        toggleBtn.textContent = ' Thème sombre';
    }
});
</script>

</body>
</html>