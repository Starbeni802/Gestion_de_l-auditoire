<?php

declare(strict_types=1);

require_once __DIR__ . '/fonctions.php';

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generer') {
        $planningGenere = generer_planning();
        if (sauvegarder_planning($planningGenere) && sauvegarder_planning($planningGenere, DATA_DIR . '/planning.txt')) {
            $message = 'Planning généré et sauvegardé avec succès.';
            $typeMessage = 'success';
        } else {
            $message = 'Erreur lors de la sauvegarde du planning.';
            $typeMessage = 'error';
        }
    }

    if ($action === 'vider') {
        if (sauvegarder_planning([]) && sauvegarder_planning([], DATA_DIR . '/planning.txt')) {
            $message = 'Planning vidé.';
            $typeMessage = 'success';
        } else {
            $message = 'Impossible de vider le planning.';
            $typeMessage = 'error';
        }
    }
}

$planning = charger_planning();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Planning</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Planning des cours</h1>
        <div class="nav">
            <a href="index.php">Accueil</a>
            <a href="connexion.php">Connexion</a>
            <a href="admin.php">Espace Admin</a>
            <a href="planning.php">Planning</a>
            <a href="reservation.php">Réservation</a>
        </div>
    </div>

    <div class="card">
        <?php if ($message !== ''): ?>
            <div class="alert <?php echo $typeMessage === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="inline">
            <input type="hidden" name="action" value="generer">
            <button type="submit" class="btn btn-success">Générer automatiquement</button>
        </form>

        <form method="post" class="inline" onsubmit="return confirm('Confirmer la suppression du planning ?');">
            <input type="hidden" name="action" value="vider">
            <button type="submit" class="btn btn-danger">Vider planning</button>
        </form>

        <p><small>Total des affectations: <?php echo count($planning); ?></small></p>
    </div>

    <div class="card table-wrap">
        <?php echo afficher_planning_html($planning); ?>
    </div>
</div>
</body>
</html>
