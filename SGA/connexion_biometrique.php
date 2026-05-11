<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['auth_etape_1'])) {
    header('Location: connexion.php');
    exit;
}

$message = '';
$typeMessage = '';
$connecte = false;

function charger_etudiants_biometrique(string $chemin): array
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

function retrouver_etudiant_session(array $etudiants, string $nom): ?array
{
    foreach ($etudiants as $etudiant) {
        if (strcasecmp((string) ($etudiant['nom'] ?? ''), $nom) === 0) {
            return $etudiant;
        }
    }

    return null;
}

$nomEtudiant = (string) ($_SESSION['etudiant_nom'] ?? '');
$etudiants = charger_etudiants_biometrique(__DIR__ . '/data/etudiants.json');
$etudiant = retrouver_etudiant_session($etudiants, $nomEtudiant);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empreinte = (string) ($_POST['empreinte'] ?? '');
    $pin = trim((string) ($_POST['pin'] ?? ''));

    if ($empreinte !== 'validee') {
        $message = 'Veuillez valider l empreinte digitale.';
        $typeMessage = 'error';
    } elseif ($etudiant === null || (string) ($etudiant['pin'] ?? '') !== $pin) {
        $message = 'Code PIN incorrect.';
        $typeMessage = 'error';
    } else {
        $_SESSION['connecte'] = true;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Empreinte digitale</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body class="auth-modern-page">
<main class="auth-modern-shell">
    <section class="auth-hero-panel hello-hero-panel">
        <div class="upc-logo">UPC</div>
        <div>
            <p class="auth-eyebrow">Verification securisee</p>
            <h1>Empreinte digitale</h1>
            <p class="auth-lead">Confirmez votre identite avec une verification securisee avant d'ouvrir le SGA.</p>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="auth-form-card hello-card">
            <div class="upc-logo mobile-logo">UPC</div>
            <p class="auth-eyebrow">Deuxieme etape</p>
            <h2>Verification</h2>
            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $typeMessage === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!$connecte): ?>
                <form method="post">
                    <label class="windows-hello-option">
                        <input type="checkbox" name="empreinte" value="validee" required>
                        <div class="fingerprint-icon" aria-hidden="true"></div>
                        <span>Utiliser l'empreinte digitale</span>
                    </label>

                    <label>Code PIN de securite</label>
                    <input type="password" name="pin" inputmode="numeric" required placeholder="Votre code PIN">

                    <button type="submit" class="auth-primary-btn">Valider</button>
                </form>
            <?php else: ?>
                <p><a class="auth-primary-link" href="index.php">Ouvrir le SGA</a></p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
