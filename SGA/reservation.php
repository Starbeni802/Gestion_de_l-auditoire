<?php

declare(strict_types=1);

require_once __DIR__ . '/fonctions.php';

$salles = charger_salles();
$promotions = charger_promotions();
$cours = charger_cours();
$options = charger_options();
$planning = charger_planning();

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jour = trim((string) ($_POST['jour'] ?? ''));
    $debut = trim((string) ($_POST['debut'] ?? ''));
    $fin = trim((string) ($_POST['fin'] ?? ''));
    $salle = trim((string) ($_POST['salle'] ?? ''));
    $coursId = trim((string) ($_POST['cours'] ?? ''));
    $groupe = trim((string) ($_POST['groupe'] ?? ''));

    $coursLigne = trouver_par_id($cours, $coursId);
    $intituleCours = $coursLigne ? (string) $coursLigne['intitule'] : 'Cours manuel';

    if ($jour === '' || $debut === '' || $fin === '' || $salle === '' || $groupe === '') {
        $message = 'Tous les champs sont obligatoires.';
        $typeMessage = 'error';
    } elseif ($debut >= $fin) {
        $message = 'Le créneau est invalide.';
        $typeMessage = 'error';
    } else {
        $effectif = obtenir_effectif_groupe($groupe, $promotions, $options);

        if ($effectif <= 0) {
            $message = 'Groupe invalide.';
            $typeMessage = 'error';
        } elseif (!capacite_suffisante($salles, $salle, $effectif)) {
            $message = 'Capacité insuffisante pour ce groupe.';
            $typeMessage = 'error';
        } elseif (!salle_disponible($planning, $jour, $debut, $fin, $salle)) {
            $message = 'La salle est déjà occupée sur ce créneau.';
            $typeMessage = 'error';
        } elseif (!creneau_libre_groupe($planning, $jour, $debut, $fin, $groupe)) {
            $message = 'Le groupe a déjà un cours sur ce créneau.';
            $typeMessage = 'error';
        } else {
            $planning[] = [
                'jour' => $jour,
                'debut' => $debut,
                'fin' => $fin,
                'salle' => $salle,
                'cours' => $intituleCours,
                'groupe' => $groupe,
            ];

            if (sauvegarder_planning($planning)) {
                $message = 'Réservation enregistrée.';
                $typeMessage = 'success';
                $planning = charger_planning();
            } else {
                $message = 'Erreur lors de la sauvegarde.';
                $typeMessage = 'error';
            }
        }
    }
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heuresDebut = ['08:00', '13:00'];
$heuresFin = ['12:00', '17:00'];

$groupes = [];
foreach ($promotions as $promo) {
    $groupes[] = [
        'code' => 'PROMO:' . (string) $promo['id'],
        'libelle' => (string) $promo['libelle'] . ' (' . (int) $promo['effectif'] . ')',
    ];
}
foreach ($options as $opt) {
    $groupes[] = [
        'code' => 'OPTION:' . (string) $opt['id'],
        'libelle' => (string) $opt['libelle'] . ' (' . (int) $opt['effectif'] . ')',
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Réservation</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Réservation manuelle des salles</h1>
        <div class="nav">
            <a href="index.php">Accueil</a>
            <a href="connexion.php">Connexion</a>
            <a href="admin.php">Espace Admin</a>
            <a href="planning.php">Planning</a>
            <a href="reservation.php">Réservation</a>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Nouvelle affectation</h2>
            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $typeMessage === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <label>Jour</label>
                <select name="jour" required>
                    <?php foreach ($jours as $jour): ?>
                        <option value="<?php echo htmlspecialchars($jour, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($jour, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Heure début</label>
                <select name="debut" required>
                    <?php foreach ($heuresDebut as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Heure fin</label>
                <select name="fin" required>
                    <?php foreach ($heuresFin as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Salle</label>
                <select name="salle" required>
                    <?php foreach ($salles as $salle): ?>
                        <option value="<?php echo htmlspecialchars((string) $salle['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $salle['designation'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $salle['capacite']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Cours</label>
                <select name="cours" required>
                    <?php foreach ($cours as $ligne): ?>
                        <option value="<?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $ligne['intitule'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Groupe</label>
                <select name="groupe" required>
                    <?php foreach ($groupes as $grp): ?>
                        <option value="<?php echo htmlspecialchars($grp['code'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($grp['libelle'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-success">Réserver</button>
            </form>
        </div>

        <div class="card table-wrap">
            <h2>Planning actuel</h2>
            <?php echo afficher_planning_html($planning); ?>
        </div>
    </div>
</div>
</body>
</html>
