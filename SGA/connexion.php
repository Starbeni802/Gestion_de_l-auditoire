<?php

declare(strict_types=1);

session_start();

$message = '';
$typeMessage = '';

function charger_etudiants(string $chemin): array
{
    if (!file_exists($chemin)) {
        return [];
    }

    $contenu = file_get_contents($chemin);
    if ($contenu === false || trim($contenu) === '') {
        return [];
    }

    $donnees = json_decode($contenu, true);
    return is_array($donnees) ? $donnees : [];
}

function verifier_identite_etudiant(array $etudiants, string $nom, string $motDePasse): ?array
{
    foreach ($etudiants as $etudiant) {
        if (
            strcasecmp((string) ($etudiant['nom'] ?? ''), $nom) === 0
            && (string) ($etudiant['mot_de_passe'] ?? '') === $motDePasse
        ) {
            return $etudiant;
        }
    }

    return null;
}

$etudiants = charger_etudiants(__DIR__ . '/data/etudiants.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $motDePasse = trim((string) ($_POST['mot_de_passe'] ?? ''));

    if ($nom === '' || $motDePasse === '') {
        $message = 'Veuillez saisir le nom et le mot de passe.';
        $typeMessage = 'error';
    } else {
        $etudiant = verifier_identite_etudiant($etudiants, $nom, $motDePasse);

        if ($etudiant === null) {
            $message = 'Nom ou mot de passe incorrect.';
            $typeMessage = 'error';
        } else {
            $_SESSION['auth_etape_1'] = true;
            $_SESSION['etudiant_nom'] = (string) $etudiant['nom'];
            header('Location: connexion_biometrique.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Connexion etudiant</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body class="auth-modern-page">
<main class="auth-modern-shell">
    <section class="auth-hero-panel">
        <div class="upc-logo">UPC</div>
        <div>
            <p class="auth-eyebrow">Universite Protestante au Congo</p>
            <h1>Connexion au SGA</h1>
            <p class="auth-lead">Accedez a votre espace de gestion des auditoires avec une interface securisee et moderne.</p>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="auth-form-card">
            <div class="upc-logo mobile-logo">UPC</div>
            <p class="auth-eyebrow">Espace etudiant</p>
            <h2>Connexion au SGA</h2>
            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $typeMessage === 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <label>Identite</label>
                <input type="text" name="nom" required placeholder="Votre identifiant">

                <label>Mot de passe</label>
                <input type="password" name="mot_de_passe" required placeholder="Votre mot de passe">

                <button type="submit" class="auth-primary-btn">Continuer</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
