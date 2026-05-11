<?php

declare(strict_types=1);

require_once __DIR__ . '/fonctions.php';

$messages = [];
$typeMessage = 'success';

$salles = charger_salles(DATA_DIR . '/salles.json');
$promotions = charger_promotions(DATA_DIR . '/promotions.json');
$cours = charger_cours(DATA_DIR . '/cours.json');
$options = charger_options(DATA_DIR . '/options.json');
$planning = charger_planning(DATA_DIR . '/planning.json');
$creneaux = creneaux_par_defaut();
$conflits = [];

if ($salles !== []) {
    $messages[] = 'Salles chargees avec succes: ' . count($salles);
}
if ($promotions !== []) {
    $messages[] = 'Promotions chargees avec succes: ' . count($promotions);
}
if ($cours !== []) {
    $messages[] = 'Cours charges avec succes: ' . count($cours);
}
if ($options !== []) {
    $messages[] = 'Options chargees avec succes: ' . count($options);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'generer') {
        $planning = generer_planning($salles, $promotions, $cours, $options, $creneaux);
        $jsonOk = sauvegarder_planning($planning, DATA_DIR . '/planning.json');
        $txtOk = sauvegarder_planning($planning, DATA_DIR . '/planning.txt');

        if ($jsonOk && $txtOk) {
            $messages[] = 'Planning genere et sauvegarde dans planning.json et planning.txt.';
        } else {
            $messages[] = 'Erreur lors de la sauvegarde du planning.';
            $typeMessage = 'error';
        }
    }

    if ($action === 'conflits') {
        $conflits = detecter_conflits_planning_txt(DATA_DIR . '/planning.txt');
        $messages[] = $conflits === []
            ? 'Aucun conflit detecte dans planning.txt.'
            : count($conflits) . ' conflit(s) detecte(s) dans planning.txt.';
        $typeMessage = $conflits === [] ? 'success' : 'error';
    }

    if ($action === 'rapport') {
        $planningTxt = charger_planning(DATA_DIR . '/planning.txt');
        if (generer_rapport_occupation($planningTxt, $salles, $creneaux, DATA_DIR . '/rapport_occupation.txt')) {
            $messages[] = 'Rapport d occupation genere dans rapport_occupation.txt.';
        } else {
            $messages[] = 'Erreur lors de la generation du rapport d occupation.';
            $typeMessage = 'error';
        }
    }

    if ($action === 'modifier') {
        $resultat = modifier_affectation_planning_txt(
            DATA_DIR . '/planning.txt',
            trim((string) ($_POST['code_cours'] ?? '')),
            trim((string) ($_POST['groupe'] ?? '')),
            trim((string) ($_POST['salle'] ?? '')),
            [
                'jour' => trim((string) ($_POST['jour'] ?? '')),
                'debut' => trim((string) ($_POST['debut'] ?? '')),
                'fin' => trim((string) ($_POST['fin'] ?? '')),
            ],
            $salles,
            $promotions,
            $options
        );

        $messages[] = (string) $resultat['message'];
        $typeMessage = $resultat['ok'] ? 'success' : 'error';
        $planning = charger_planning(DATA_DIR . '/planning.json');
    }
}

$planning = charger_planning(DATA_DIR . '/planning.json');
$erreurs = erreurs_chargement();
if ($erreurs !== []) {
    $typeMessage = 'error';
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Script principal</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Systeme de Gestion des Auditoires et des Horaires (SGA)</h1>
        <div class="nav">
            <a href="index.php">Accueil</a>
            <a href="admin.php">Espace Admin</a>
            <a href="planning.php">Planning</a>
            <a href="reservation.php">Reservation</a>
        </div>
    </div>

    <?php if ($messages !== [] || $erreurs !== []): ?>
        <div class="alert <?php echo $typeMessage === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php foreach (array_merge($messages, $erreurs) as $message): ?>
                <div><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Script principal</h2>
            <p>Salles: <strong><?php echo count($salles); ?></strong></p>
            <p>Promotions: <strong><?php echo count($promotions); ?></strong></p>
            <p>Cours: <strong><?php echo count($cours); ?></strong></p>
            <p>Options: <strong><?php echo count($options); ?></strong></p>
            <p>Affectations: <strong><?php echo count($planning); ?></strong></p>

            <form method="post" class="inline">
                <input type="hidden" name="action" value="generer">
                <button type="submit" class="btn btn-success">Generer et sauvegarder</button>
            </form>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="conflits">
                <button type="submit" class="btn">Detecter conflits</button>
            </form>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="rapport">
                <button type="submit" class="btn">Rapport occupation</button>
            </form>
        </div>

        <div class="card">
            <h2>Mise a jour manuelle</h2>
            <form method="post">
                <input type="hidden" name="action" value="modifier">

                <label>Code cours</label>
                <input type="text" name="code_cours" required placeholder="Ex: 1">

                <label>Groupe</label>
                <input type="text" name="groupe" required placeholder="Ex: PROMO:L1">

                <label>Salle</label>
                <select name="salle" required>
                    <?php foreach ($salles as $salle): ?>
                        <option value="<?php echo htmlspecialchars((string) $salle['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $salle['id'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Jour</label>
                <select name="jour" required>
                    <?php foreach ($jours as $jour): ?>
                        <option value="<?php echo htmlspecialchars($jour, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($jour, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Debut</label>
                <select name="debut" required>
                    <option value="08:00">08:00</option>
                    <option value="13:00">13:00</option>
                </select>

                <label>Fin</label>
                <select name="fin" required>
                    <option value="12:00">12:00</option>
                    <option value="17:00">17:00</option>
                </select>

                <button type="submit" class="btn btn-success">Modifier affectation</button>
            </form>
        </div>
    </div>

    <?php if ($conflits !== []): ?>
        <div class="card">
            <h2>Conflits detectes</h2>
            <?php foreach ($conflits as $conflit): ?>
                <p><?php echo htmlspecialchars($conflit, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card table-wrap">
        <h2>Planning hebdomadaire</h2>
        <?php echo afficher_planning_html($planning); ?>
    </div>
</div>
</body>
</html>
