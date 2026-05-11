<?php

declare(strict_types=1);

const DATA_DIR = __DIR__ . '/data';

/**
 * Ajoute un message d'erreur de chargement dans une liste globale.
 * Parametre: $message contient l'erreur a memoriser.
 * Retour: aucun.
 */
function ajouter_erreur_chargement(string $message): void
{
    $GLOBALS['erreurs_chargement'][] = $message;
}

/**
 * Retourne toutes les erreurs produites pendant la lecture des fichiers.
 * Parametre: aucun.
 * Retour: tableau de messages d'erreur.
 */
function erreurs_chargement(): array
{
    return $GLOBALS['erreurs_chargement'] ?? [];
}

/**
 * Lit un fichier JSON et le transforme en tableau PHP.
 * Parametres: $chemin est le fichier a lire, $defaut est la valeur retournee en cas d'erreur.
 * Retour: tableau associatif ou tableau par defaut.
 */
function lire_json(string $chemin, array $defaut = []): array
{
    if (!file_exists($chemin)) {
        ajouter_erreur_chargement("Fichier introuvable: $chemin");
        return $defaut;
    }

    $contenu = file_get_contents($chemin);
    if ($contenu === false || trim($contenu) === '') {
        ajouter_erreur_chargement("Fichier vide ou illisible: $chemin");
        return $defaut;
    }

    $donnees = json_decode($contenu, true);
    if (!is_array($donnees)) {
        ajouter_erreur_chargement("JSON malforme: $chemin");
        return $defaut;
    }

    return $donnees;
}

/**
 * Charge une table JSON en verifiant les champs obligatoires.
 * Parametres: $chemin est le fichier, $champsObligatoires liste les champs et leurs types attendus.
 * Retour: tableau des lignes valides.
 */
function charger_table_json(string $chemin, array $champsObligatoires): array
{
    $lignes = lire_json($chemin, []);
    $donneesValides = [];

    foreach ($lignes as $numero => $ligne) {
        if (!is_array($ligne)) {
            ajouter_erreur_chargement("Ligne malformee dans $chemin a l'index $numero");
            continue;
        }

        $ligneValide = true;
        foreach ($champsObligatoires as $champ => $type) {
            if (!array_key_exists($champ, $ligne) || $ligne[$champ] === '') {
                ajouter_erreur_chargement("Valeur manquante pour '$champ' dans $chemin a l'index $numero");
                $ligneValide = false;
                break;
            }

            if ($type === 'int' && filter_var($ligne[$champ], FILTER_VALIDATE_INT) === false) {
                ajouter_erreur_chargement("Valeur numerique invalide pour '$champ' dans $chemin a l'index $numero");
                $ligneValide = false;
                break;
            }
        }

        if ($ligneValide) {
            $donneesValides[] = $ligne;
        }
    }

    return $donneesValides;
}

/**
 * Ecrit un tableau PHP dans un fichier JSON lisible.
 * Parametres: $chemin est le fichier cible, $donnees contient les donnees a sauvegarder.
 * Retour: true si l'ecriture reussit, false sinon.
 */
function ecrire_json(string $chemin, array $donnees): bool
{
    $json = json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    return file_put_contents($chemin, $json) !== false;
}

/**
 * Charge les salles depuis salles.json ou depuis un chemin donne.
 * Parametre: $chemin_fichier est optionnel et remplace le chemin par defaut.
 * Retour: tableau des salles.
 */
function charger_salles(?string $chemin_fichier = null): array
{
    return charger_table_json($chemin_fichier ?? DATA_DIR . '/salles.json', [
        'id' => 'string',
        'designation' => 'string',
        'capacite' => 'int',
    ]);
}

/**
 * Charge les promotions depuis promotions.json ou depuis un chemin donne.
 * Parametre: $chemin_fichier est optionnel et remplace le chemin par defaut.
 * Retour: tableau des promotions.
 */
function charger_promotions(?string $chemin_fichier = null): array
{
    return charger_table_json($chemin_fichier ?? DATA_DIR . '/promotions.json', [
        'id' => 'string',
        'libelle' => 'string',
        'effectif' => 'int',
    ]);
}

/**
 * Charge les cours depuis cours.json ou depuis un chemin donne.
 * Parametre: $chemin_fichier est optionnel et remplace le chemin par defaut.
 * Retour: tableau des cours.
 */
function charger_cours(?string $chemin_fichier = null): array
{
    return charger_table_json($chemin_fichier ?? DATA_DIR . '/cours.json', [
        'id' => 'string',
        'intitule' => 'string',
        'volume_horaire' => 'int',
        'type' => 'string',
        'promotion' => 'string',
    ]);
}

/**
 * Charge les options depuis options.json ou depuis un chemin donne.
 * Parametre: $chemin_fichier est optionnel et remplace le chemin par defaut.
 * Retour: tableau des options.
 */
function charger_options(?string $chemin_fichier = null): array
{
    return charger_table_json($chemin_fichier ?? DATA_DIR . '/options.json', [
        'id' => 'string',
        'libelle' => 'string',
        'promotion' => 'string',
        'effectif' => 'int',
    ]);
}

/**
 * Recharge le planning depuis planning.json ou planning.txt.
 * Parametre: $chemin_fichier est optionnel et remplace le chemin par defaut.
 * Retour: tableau des affectations du planning.
 */
function charger_planning(?string $chemin_fichier = null): array
{
    $chemin = $chemin_fichier ?? DATA_DIR . '/planning.json';

    if (strtolower(pathinfo($chemin, PATHINFO_EXTENSION)) === 'txt') {
        return charger_planning_txt($chemin);
    }

    return charger_table_json($chemin, [
        'jour' => 'string',
        'debut' => 'string',
        'fin' => 'string',
        'salle' => 'string',
        'cours' => 'string',
        'groupe' => 'string',
    ]);
}

/**
 * Lit le fichier planning.txt au format jour;debut;fin;salle;code_cours;groupe.
 * Parametre: $chemin_fichier indique le fichier texte a lire.
 * Retour: tableau des affectations valides.
 */
function charger_planning_txt(string $chemin_fichier): array
{
    if (!file_exists($chemin_fichier)) {
        ajouter_erreur_chargement("Fichier introuvable: $chemin_fichier");
        return [];
    }

    $lignes = file($chemin_fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lignes === false) {
        ajouter_erreur_chargement("Fichier illisible: $chemin_fichier");
        return [];
    }

    $planning = [];
    foreach ($lignes as $numero => $ligne) {
        $champs = str_getcsv($ligne, ';');
        if (count($champs) !== 6) {
            ajouter_erreur_chargement("Ligne planning.txt malformee a la ligne " . ($numero + 1));
            continue;
        }

        $planning[] = [
            'jour' => trim($champs[0]),
            'debut' => trim($champs[1]),
            'fin' => trim($champs[2]),
            'salle' => trim($champs[3]),
            'code_cours' => trim($champs[4]),
            'cours' => trim($champs[4]),
            'groupe' => trim($champs[5]),
        ];
    }

    return $planning;
}

/**
 * Sauvegarde le planning en JSON ou en TXT selon l'extension du fichier cible.
 * Parametres: $planning contient les affectations, $chemin_fichier est optionnel.
 * Retour: true si la sauvegarde reussit, false sinon.
 */
function sauvegarder_planning(array $planning, ?string $chemin_fichier = null): bool
{
    $chemin = $chemin_fichier ?? DATA_DIR . '/planning.json';

    if (strtolower(pathinfo($chemin, PATHINFO_EXTENSION)) === 'txt') {
        $lignes = [];
        foreach ($planning as $affectation) {
            $lignes[] = implode(';', [
                (string) ($affectation['jour'] ?? ''),
                (string) ($affectation['debut'] ?? ''),
                (string) ($affectation['fin'] ?? ''),
                (string) ($affectation['salle'] ?? ''),
                (string) ($affectation['code_cours'] ?? $affectation['cours'] ?? ''),
                (string) ($affectation['groupe'] ?? ''),
            ]);
        }

        return file_put_contents($chemin, implode(PHP_EOL, $lignes)) !== false;
    }

    return ecrire_json($chemin, $planning);
}

/**
 * Sauvegarde la liste des salles dans salles.json.
 * Parametre: $salles contient les salles a enregistrer.
 * Retour: true si la sauvegarde reussit, false sinon.
 */
function sauvegarder_salles(array $salles): bool
{
    return ecrire_json(DATA_DIR . '/salles.json', array_values($salles));
}

/**
 * Sauvegarde la liste des promotions dans promotions.json.
 * Parametre: $promotions contient les promotions a enregistrer.
 * Retour: true si la sauvegarde reussit, false sinon.
 */
function sauvegarder_promotions(array $promotions): bool
{
    return ecrire_json(DATA_DIR . '/promotions.json', array_values($promotions));
}

/**
 * Sauvegarde la liste des cours dans cours.json.
 * Parametre: $cours contient les cours a enregistrer.
 * Retour: true si la sauvegarde reussit, false sinon.
 */
function sauvegarder_cours(array $cours): bool
{
    return ecrire_json(DATA_DIR . '/cours.json', array_values($cours));
}

/**
 * Ecrit un tableau de donnees dans un fichier CSV avec separateur point-virgule.
 * Parametres: $chemin est le fichier cible, $donnees les lignes, $colonnes les champs a exporter.
 * Retour: true si l'ecriture reussit, false sinon.
 */
function ecrire_csv(string $chemin, array $donnees, array $colonnes): bool
{
    $fichier = fopen($chemin, 'w');
    if ($fichier === false) {
        return false;
    }

    fputcsv($fichier, $colonnes, ';');
    foreach ($donnees as $ligne) {
        $valeurs = [];
        foreach ($colonnes as $colonne) {
            $valeurs[] = (string) ($ligne[$colonne] ?? '');
        }
        fputcsv($fichier, $valeurs, ';');
    }

    return fclose($fichier);
}

/**
 * Exporte les salles dans data/salles.csv.
 * Parametre: $salles contient les salles a exporter.
 * Retour: true si l'export reussit, false sinon.
 */
function sauvegarder_salles_csv(array $salles): bool
{
    return ecrire_csv(DATA_DIR . '/salles.csv', $salles, ['id', 'designation', 'capacite']);
}

/**
 * Exporte les promotions dans data/promotions.csv.
 * Parametre: $promotions contient les promotions a exporter.
 * Retour: true si l'export reussit, false sinon.
 */
function sauvegarder_promotions_csv(array $promotions): bool
{
    return ecrire_csv(DATA_DIR . '/promotions.csv', $promotions, ['id', 'libelle', 'effectif']);
}

/**
 * Exporte les cours dans data/cours.csv.
 * Parametre: $cours contient les cours a exporter.
 * Retour: true si l'export reussit, false sinon.
 */
function sauvegarder_cours_csv(array $cours): bool
{
    return ecrire_csv(DATA_DIR . '/cours.csv', $cours, ['id', 'intitule', 'volume_horaire', 'type', 'promotion', 'option']);
}

/**
 * Verifie si deux plages horaires se chevauchent.
 * Parametres: heures de debut et de fin des deux plages.
 * Retour: true si les plages se chevauchent, false sinon.
 */
function creneaux_se_chevauchent(string $debutA, string $finA, string $debutB, string $finB): bool
{
    return ($debutA < $finB) && ($debutB < $finA);
}

/**
 * Convertit un creneau en tableau standard jour/debut/fin.
 * Parametre: $creneau peut etre un tableau ou une chaine.
 * Retour: tableau normalise contenant jour, debut et fin.
 */
function normaliser_creneau($creneau): array
{
    if (is_array($creneau)) {
        return [
            'jour' => (string) ($creneau['jour'] ?? ''),
            'debut' => (string) ($creneau['debut'] ?? ''),
            'fin' => (string) ($creneau['fin'] ?? ''),
        ];
    }

    $parties = explode('|', (string) $creneau);
    if (count($parties) === 2) {
        $heures = explode('-', $parties[1], 2);
        $debut = $heures[0] ?? '';
        $fin = $heures[1] ?? '';
        return ['jour' => $parties[0], 'debut' => $debut, 'fin' => $fin];
    }

    return ['jour' => '', 'debut' => '', 'fin' => ''];
}

/**
 * Verifie qu'une salle est libre sur un creneau donne.
 * Parametres: $planning actuel, $id_salle, $creneau a tester.
 * Retour: true si la salle est disponible, false sinon.
 */
function salle_disponible(array $planning, string $id_salle, $creneau, ?string $fin = null, ?string $salle = null): bool
{
    if ($salle !== null) {
        $creneau = ['jour' => $id_salle, 'debut' => (string) $creneau, 'fin' => (string) $fin];
        $id_salle = $salle;
    }

    $creneau = normaliser_creneau($creneau);

    foreach ($planning as $ligne) {
        if (($ligne['jour'] ?? '') !== $creneau['jour']) {
            continue;
        }

        if ((string) ($ligne['salle'] ?? '') !== $id_salle) {
            continue;
        }

        if (creneaux_se_chevauchent($creneau['debut'], $creneau['fin'], (string) $ligne['debut'], (string) $ligne['fin'])) {
            return false;
        }
    }

    return true;
}

/**
 * Verifie que la capacite d'une salle peut accueillir un effectif.
 * Parametres: $salles disponibles, $id_salle cible, $effectif du groupe.
 * Retour: true si la capacite suffit, false sinon.
 */
function capacite_suffisante(array $salles, string $id_salle, int $effectif): bool
{
    foreach ($salles as $item) {
        if ((string) ($item['id'] ?? '') === $id_salle) {
            return (int) ($item['capacite'] ?? 0) >= $effectif;
        }
    }

    return false;
}

/**
 * Verifie qu'un groupe n'a pas deja cours sur le meme creneau.
 * Parametres: $planning actuel, $id_groupe, $creneau a tester.
 * Retour: true si le groupe est libre, false sinon.
 */
function creneau_libre_groupe(array $planning, string $id_groupe, $creneau, ?string $fin = null, ?string $groupe = null): bool
{
    if ($groupe !== null) {
        $creneau = ['jour' => $id_groupe, 'debut' => (string) $creneau, 'fin' => (string) $fin];
        $id_groupe = $groupe;
    }

    $creneau = normaliser_creneau($creneau);

    foreach ($planning as $ligne) {
        if (($ligne['jour'] ?? '') !== $creneau['jour']) {
            continue;
        }

        if ((string) ($ligne['groupe'] ?? '') !== $id_groupe) {
            continue;
        }

        if (creneaux_se_chevauchent($creneau['debut'], $creneau['fin'], (string) $ligne['debut'], (string) $ligne['fin'])) {
            return false;
        }
    }

    return true;
}

/**
 * Detecte les conflits dans le fichier planning.txt.
 * Parametre: $chemin_fichier indique le planning texte a analyser.
 * Retour: tableau de descriptions explicites des conflits.
 */
function detecter_conflits_planning_txt(string $chemin_fichier): array
{
    $planning = charger_planning_txt($chemin_fichier);
    $conflits = [];

    for ($i = 0; $i < count($planning); $i++) {
        for ($j = $i + 1; $j < count($planning); $j++) {
            $a = $planning[$i];
            $b = $planning[$j];

            if (($a['jour'] ?? '') !== ($b['jour'] ?? '')) {
                continue;
            }

            $chevauchement = creneaux_se_chevauchent(
                (string) ($a['debut'] ?? ''),
                (string) ($a['fin'] ?? ''),
                (string) ($b['debut'] ?? ''),
                (string) ($b['fin'] ?? '')
            );

            if (!$chevauchement) {
                continue;
            }

            if ((string) ($a['salle'] ?? '') === (string) ($b['salle'] ?? '')) {
                $conflits[] = sprintf(
                    "Conflit salle: %s est utilisee simultanement le %s de %s a %s par %s/%s et %s/%s.",
                    (string) $a['salle'],
                    (string) $a['jour'],
                    max((string) $a['debut'], (string) $b['debut']),
                    min((string) $a['fin'], (string) $b['fin']),
                    (string) ($a['code_cours'] ?? $a['cours']),
                    (string) $a['groupe'],
                    (string) ($b['code_cours'] ?? $b['cours']),
                    (string) $b['groupe']
                );
            }

            if ((string) ($a['groupe'] ?? '') === (string) ($b['groupe'] ?? '')) {
                $conflits[] = sprintf(
                    "Conflit groupe: %s a deux cours simultanes le %s de %s a %s dans %s et %s.",
                    (string) $a['groupe'],
                    (string) $a['jour'],
                    max((string) $a['debut'], (string) $b['debut']),
                    min((string) $a['fin'], (string) $b['fin']),
                    (string) $a['salle'],
                    (string) $b['salle']
                );
            }
        }
    }

    return $conflits;
}

/**
 * Genere un rapport d'occupation des salles.
 * Parametres: $planning, $salles, $creneaux_disponibles et $chemin_fichier cible.
 * Retour: true si le rapport est ecrit, false sinon.
 */
function generer_rapport_occupation(array $planning, array $salles, array $creneaux_disponibles, string $chemin_fichier): bool
{
    $totalCreneaux = count($creneaux_disponibles);
    $lignes = ["salle;creneaux_occupes;creneaux_libres;taux_occupation"];

    foreach ($salles as $salle) {
        $idSalle = (string) ($salle['id'] ?? '');
        $occupes = [];

        foreach ($planning as $affectation) {
            if ((string) ($affectation['salle'] ?? '') !== $idSalle) {
                continue;
            }

            $cle = (string) ($affectation['jour'] ?? '') . '|' . (string) ($affectation['debut'] ?? '') . '-' . (string) ($affectation['fin'] ?? '');
            $occupes[$cle] = true;
        }

        $nbOccupes = count($occupes);
        $nbLibres = max(0, $totalCreneaux - $nbOccupes);
        $taux = $totalCreneaux > 0 ? round(($nbOccupes / $totalCreneaux) * 100, 2) : 0;
        $lignes[] = $idSalle . ';' . $nbOccupes . ';' . $nbLibres . ';' . $taux . '%';
    }

    return file_put_contents($chemin_fichier, implode(PHP_EOL, $lignes)) !== false;
}

/**
 * Modifie une affectation dans planning.txt apres verification des contraintes.
 * Parametres: fichier, cours, groupe, nouvelle salle, nouveau creneau et donnees de reference.
 * Retour: tableau avec ok et message.
 */
function modifier_affectation_planning_txt(
    string $chemin_fichier,
    string $code_cours,
    string $groupe,
    string $nouvelle_salle,
    array $nouveau_creneau,
    array $salles,
    array $promotions,
    array $options
): array {
    $planning = charger_planning_txt($chemin_fichier);
    $index = null;

    foreach ($planning as $position => $affectation) {
        $code = (string) ($affectation['code_cours'] ?? $affectation['cours'] ?? '');
        if ($code === $code_cours && (string) ($affectation['groupe'] ?? '') === $groupe) {
            $index = $position;
            break;
        }
    }

    if ($index === null) {
        return ['ok' => false, 'message' => 'Affectation introuvable.'];
    }

    $effectif = obtenir_effectif_groupe($groupe, $promotions, $options);
    if (!capacite_suffisante($salles, $nouvelle_salle, $effectif)) {
        return ['ok' => false, 'message' => 'Capacite insuffisante pour le groupe choisi.'];
    }

    $planningSansLigne = $planning;
    unset($planningSansLigne[$index]);
    $planningSansLigne = array_values($planningSansLigne);

    if (!salle_disponible($planningSansLigne, $nouvelle_salle, $nouveau_creneau)) {
        return ['ok' => false, 'message' => 'La salle est deja occupee sur ce creneau.'];
    }

    if (!creneau_libre_groupe($planningSansLigne, $groupe, $nouveau_creneau)) {
        return ['ok' => false, 'message' => 'Le groupe a deja un cours sur ce creneau.'];
    }

    $planning[$index]['jour'] = (string) ($nouveau_creneau['jour'] ?? '');
    $planning[$index]['debut'] = (string) ($nouveau_creneau['debut'] ?? '');
    $planning[$index]['fin'] = (string) ($nouveau_creneau['fin'] ?? '');
    $planning[$index]['salle'] = $nouvelle_salle;

    if (!sauvegarder_planning($planning, $chemin_fichier)) {
        return ['ok' => false, 'message' => 'Erreur lors de la sauvegarde du planning modifie.'];
    }

    sauvegarder_planning($planning, DATA_DIR . '/planning.json');
    return ['ok' => true, 'message' => 'Affectation modifiee avec succes.'];
}

/**
 * Recherche une ligne par son identifiant dans un tableau.
 * Parametres: $donnees a parcourir, $id recherche.
 * Retour: ligne trouvee ou null.
 */
function trouver_par_id(array $donnees, string $id): ?array
{
    foreach ($donnees as $ligne) {
        if ((string) ($ligne['id'] ?? '') === $id) {
            return $ligne;
        }
    }

    return null;
}

/**
 * Calcule le prochain identifiant numerique pour une liste.
 * Parametre: $donnees existantes.
 * Retour: prochain entier disponible.
 */
function prochaine_id(array $donnees): int
{
    $max = 0;
    foreach ($donnees as $ligne) {
        $val = (int) ($ligne['id'] ?? 0);
        if ($val > $max) {
            $max = $val;
        }
    }

    return $max + 1;
}

/**
 * Determine les groupes concernes par un cours.
 * Parametres: cours, promotions et options.
 * Retour: tableau des groupes avec code, libelle et effectif.
 */
function construire_groupes_pour_cours(array $coursItem, array $promotions, array $options): array
{
    $groupes = [];
    $promotionId = (string) ($coursItem['promotion'] ?? '');
    $type = strtolower((string) ($coursItem['type'] ?? 'tronc commun'));

    if ($type === 'option') {
        $optionId = (string) ($coursItem['option'] ?? '');
        foreach ($options as $option) {
            if ((string) ($option['promotion'] ?? '') !== $promotionId) {
                continue;
            }

            if ($optionId !== '' && (string) ($option['id'] ?? '') !== $optionId) {
                continue;
            }

            $groupes[] = [
                'code' => 'OPTION:' . (string) $option['id'],
                'libelle' => (string) $option['libelle'],
                'effectif' => (int) ($option['effectif'] ?? 0),
            ];
        }
    } else {
        $promotion = trouver_par_id($promotions, $promotionId);
        if ($promotion !== null) {
            $groupes[] = [
                'code' => 'PROMO:' . (string) $promotion['id'],
                'libelle' => (string) $promotion['libelle'],
                'effectif' => (int) ($promotion['effectif'] ?? 0),
            ];
        }
    }

    return $groupes;
}

/**
 * Retrouve l'effectif correspondant a un code groupe.
 * Parametres: $codeGroupe, $promotions et $options.
 * Retour: effectif du groupe ou 0 si introuvable.
 */
function obtenir_effectif_groupe(string $codeGroupe, array $promotions, array $options): int
{
    if (str_starts_with($codeGroupe, 'PROMO:')) {
        $id = substr($codeGroupe, 6);
        $promo = trouver_par_id($promotions, $id);
        return (int) ($promo['effectif'] ?? 0);
    }

    if (str_starts_with($codeGroupe, 'OPTION:')) {
        $id = substr($codeGroupe, 7);
        $option = trouver_par_id($options, $id);
        return (int) ($option['effectif'] ?? 0);
    }

    return 0;
}

/**
 * Retourne le libelle lisible d'un groupe.
 * Parametres: $codeGroupe, $promotions et $options.
 * Retour: libelle du groupe ou code initial si introuvable.
 */
function libelle_groupe(string $codeGroupe, array $promotions, array $options): string
{
    if (str_starts_with($codeGroupe, 'PROMO:')) {
        $id = substr($codeGroupe, 6);
        $promo = trouver_par_id($promotions, $id);
        return (string) ($promo['libelle'] ?? $codeGroupe);
    }

    if (str_starts_with($codeGroupe, 'OPTION:')) {
        $id = substr($codeGroupe, 7);
        $option = trouver_par_id($options, $id);
        return (string) ($option['libelle'] ?? $codeGroupe);
    }

    return $codeGroupe;
}

/**
 * Construit les creneaux pedagogiques standards de la semaine.
 * Parametre: aucun.
 * Retour: tableau des creneaux du lundi au vendredi.
 */
function creneaux_par_defaut(): array
{
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    $plages = [
        ['debut' => '08:00', 'fin' => '12:00'],
        ['debut' => '13:00', 'fin' => '17:00'],
    ];

    $creneaux = [];
    foreach ($jours as $jour) {
        foreach ($plages as $plage) {
            $creneaux[] = [
                'jour' => $jour,
                'debut' => $plage['debut'],
                'fin' => $plage['fin'],
            ];
        }
    }

    return $creneaux;
}

/**
 * Genere automatiquement un planning hebdomadaire sans collision.
 * Parametres: salles, promotions, cours, options et creneaux disponibles.
 * Retour: tableau des affectations generees.
 */
function generer_planning(
    ?array $salles = null,
    ?array $promotions = null,
    ?array $cours = null,
    ?array $options = null,
    ?array $creneaux_disponibles = null
): array
{
    $salles = $salles ?? charger_salles();
    $promotions = $promotions ?? charger_promotions();
    $cours = $cours ?? charger_cours();
    $options = $options ?? charger_options();
    $creneaux_disponibles = $creneaux_disponibles ?? creneaux_par_defaut();

    usort($salles, static function (array $a, array $b): int {
        return ((int) ($a['capacite'] ?? 0)) <=> ((int) ($b['capacite'] ?? 0));
    });

    $planning = [];

    foreach ($cours as $coursItem) {
        $intitule = (string) ($coursItem['intitule'] ?? 'Cours');
        $codeCours = (string) ($coursItem['id'] ?? $intitule);
        $volumeHoraire = max(1, (int) ($coursItem['volume_horaire'] ?? 4));
        $blocs = (int) ceil($volumeHoraire / 4);
        $groupes = construire_groupes_pour_cours($coursItem, $promotions, $options);

        foreach ($groupes as $groupe) {
            $blocsPlaces = 0;

            foreach ($creneaux_disponibles as $creneau) {
                if ($blocsPlaces >= $blocs) {
                    break;
                }

                $creneau = normaliser_creneau($creneau);

                foreach ($salles as $salle) {
                    $salleId = (string) ($salle['id'] ?? '');

                    if (!capacite_suffisante($salles, $salleId, (int) $groupe['effectif'])) {
                        continue;
                    }

                    if (!salle_disponible($planning, $salleId, $creneau)) {
                        continue;
                    }

                    if (!creneau_libre_groupe($planning, (string) $groupe['code'], $creneau)) {
                        continue;
                    }

                    $planning[] = [
                        'jour' => $creneau['jour'],
                        'debut' => $creneau['debut'],
                        'fin' => $creneau['fin'],
                        'salle' => $salleId,
                        'code_cours' => $codeCours,
                        'cours' => $intitule,
                        'groupe' => (string) $groupe['code'],
                    ];

                    $blocsPlaces++;
                    break;
                }
            }
        }
    }

    return $planning;
}

/**
 * Transforme un planning en tableau HTML hebdomadaire.
 * Parametre: $planning contient les affectations a afficher.
 * Retour: chaine HTML du tableau.
 */
function afficher_planning_html(array $planning): string
{
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    $creneaux = [
        '08:00-12:00',
        '13:00-17:00',
    ];

    $index = [];
    foreach ($planning as $ligne) {
        $cle = (string) ($ligne['jour'] ?? '') . '|' . (string) ($ligne['debut'] ?? '') . '-' . (string) ($ligne['fin'] ?? '');
        if (!isset($index[$cle])) {
            $index[$cle] = [];
        }
        $index[$cle][] = $ligne;
    }

    ob_start();
    echo '<table class="planning-table">';
    echo '<thead><tr><th>Créneau</th>';
    foreach ($jours as $jour) {
        echo '<th>' . htmlspecialchars($jour, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($creneaux as $creneau) {
        echo '<tr>';
        echo '<td class="cell-slot">' . htmlspecialchars($creneau, ENT_QUOTES, 'UTF-8') . '</td>';

        foreach ($jours as $jour) {
            $cle = $jour . '|' . $creneau;
            echo '<td>';

            if (!empty($index[$cle])) {
                foreach ($index[$cle] as $item) {
                    echo '<div class="event">';
                    echo '<strong>' . htmlspecialchars((string) $item['cours'], ENT_QUOTES, 'UTF-8') . '</strong><br>';
                    echo 'Groupe: ' . htmlspecialchars((string) $item['groupe'], ENT_QUOTES, 'UTF-8') . '<br>';
                    echo 'Salle: ' . htmlspecialchars((string) $item['salle'], ENT_QUOTES, 'UTF-8');
                    echo '</div>';
                }
            } else {
                echo '<span class="empty">-</span>';
            }

            echo '</td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';

    return (string) ob_get_clean();
}
