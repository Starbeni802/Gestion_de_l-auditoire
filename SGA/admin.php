<?php

declare(strict_types=1);

require_once __DIR__ . '/fonctions.php';

$entitesAutorisees = ['salles', 'promotions', 'cours'];
$entite = $_GET['entity'] ?? 'salles';
if (!in_array($entite, $entitesAutorisees, true)) {
    $entite = 'salles';
}

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entitePost = $_POST['entity'] ?? 'salles';
    if (!in_array($entitePost, $entitesAutorisees, true)) {
        $entitePost = 'salles';
    }

    $action = $_POST['action'] ?? '';

    if ($entitePost === 'salles') {
        $donnees = charger_salles();

        if ($action === 'add') {
            $donnees[] = [
                'id' => prochaine_id($donnees),
                'designation' => trim((string) ($_POST['designation'] ?? '')),
                'capacite' => (int) ($_POST['capacite'] ?? 0),
            ];
            $ok = sauvegarder_salles($donnees);
            $message = $ok ? 'Salle ajoutée.' : 'Erreur lors de l\'ajout de la salle.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'update') {
            $id = (string) ($_POST['id'] ?? '');
            foreach ($donnees as &$ligne) {
                if ((string) ($ligne['id'] ?? '') === $id) {
                    $ligne['designation'] = trim((string) ($_POST['designation'] ?? ''));
                    $ligne['capacite'] = (int) ($_POST['capacite'] ?? 0);
                }
            }
            unset($ligne);

            $ok = sauvegarder_salles($donnees);
            $message = $ok ? 'Salle modifiée.' : 'Erreur lors de la modification de la salle.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            $donnees = array_values(array_filter($donnees, static fn(array $ligne): bool => (string) ($ligne['id'] ?? '') !== $id));
            $ok = sauvegarder_salles($donnees);
            $message = $ok ? 'Salle supprimée.' : 'Erreur lors de la suppression de la salle.';
            $typeMessage = $ok ? 'success' : 'error';
        }
    }

    if ($entitePost === 'promotions') {
        $donnees = charger_promotions();

        if ($action === 'add') {
            $donnees[] = [
                'id' => prochaine_id($donnees),
                'libelle' => trim((string) ($_POST['libelle'] ?? '')),
                'effectif' => (int) ($_POST['effectif'] ?? 0),
            ];
            $ok = sauvegarder_promotions($donnees);
            $message = $ok ? 'Promotion ajoutée.' : 'Erreur lors de l\'ajout de la promotion.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'update') {
            $id = (string) ($_POST['id'] ?? '');
            foreach ($donnees as &$ligne) {
                if ((string) ($ligne['id'] ?? '') === $id) {
                    $ligne['libelle'] = trim((string) ($_POST['libelle'] ?? ''));
                    $ligne['effectif'] = (int) ($_POST['effectif'] ?? 0);
                }
            }
            unset($ligne);

            $ok = sauvegarder_promotions($donnees);
            $message = $ok ? 'Promotion modifiée.' : 'Erreur lors de la modification de la promotion.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            $donnees = array_values(array_filter($donnees, static fn(array $ligne): bool => (string) ($ligne['id'] ?? '') !== $id));
            $ok = sauvegarder_promotions($donnees);
            $message = $ok ? 'Promotion supprimée.' : 'Erreur lors de la suppression de la promotion.';
            $typeMessage = $ok ? 'success' : 'error';
        }
    }

    if ($entitePost === 'cours') {
        $donnees = charger_cours();

        if ($action === 'add') {
            $donnees[] = [
                'id' => prochaine_id($donnees),
                'intitule' => trim((string) ($_POST['intitule'] ?? '')),
                'volume_horaire' => (int) ($_POST['volume_horaire'] ?? 2),
                'type' => (string) ($_POST['type'] ?? 'tronc commun'),
                'promotion' => (string) ($_POST['promotion'] ?? ''),
            ];
            $ok = sauvegarder_cours($donnees);
            $message = $ok ? 'Cours ajouté.' : 'Erreur lors de l\'ajout du cours.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'update') {
            $id = (string) ($_POST['id'] ?? '');
            foreach ($donnees as &$ligne) {
                if ((string) ($ligne['id'] ?? '') === $id) {
                    $ligne['intitule'] = trim((string) ($_POST['intitule'] ?? ''));
                    $ligne['volume_horaire'] = (int) ($_POST['volume_horaire'] ?? 2);
                    $ligne['type'] = (string) ($_POST['type'] ?? 'tronc commun');
                    $ligne['promotion'] = (string) ($_POST['promotion'] ?? '');
                }
            }
            unset($ligne);

            $ok = sauvegarder_cours($donnees);
            $message = $ok ? 'Cours modifié.' : 'Erreur lors de la modification du cours.';
            $typeMessage = $ok ? 'success' : 'error';
        }

        if ($action === 'delete') {
            $id = (string) ($_POST['id'] ?? '');
            $donnees = array_values(array_filter($donnees, static fn(array $ligne): bool => (string) ($ligne['id'] ?? '') !== $id));
            $ok = sauvegarder_cours($donnees);
            $message = $ok ? 'Cours supprimé.' : 'Erreur lors de la suppression du cours.';
            $typeMessage = $ok ? 'success' : 'error';
        }
    }

    sauvegarder_salles_csv(charger_salles());
    sauvegarder_promotions_csv(charger_promotions());
    sauvegarder_cours_csv(charger_cours());

    $entite = $entitePost;
}

$salles = charger_salles();
$promotions = charger_promotions();
$cours = charger_cours();

$editId = $_GET['edit'] ?? '';
$ligneEdition = null;

if ($editId !== '') {
    if ($entite === 'salles') {
        $ligneEdition = trouver_par_id($salles, (string) $editId);
    }
    if ($entite === 'promotions') {
        $ligneEdition = trouver_par_id($promotions, (string) $editId);
    }
    if ($entite === 'cours') {
        $ligneEdition = trouver_par_id($cours, (string) $editId);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Admin</title>
    <link rel="stylesheet" href="style.css?v=20260508">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Espace Admin</h1>
        <div class="nav">
            <a href="index.php">Accueil</a>
            <a href="connexion.php">Connexion</a>
            <a href="admin.php?entity=salles">Salles</a>
            <a href="admin.php?entity=promotions">Promotions</a>
            <a href="admin.php?entity=cours">Cours</a>
            <a href="planning.php">Planning</a>
            <a href="reservation.php">Réservation</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert <?php echo $typeMessage === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2><?php echo ucfirst($entite); ?></h2>

            <?php if ($entite === 'salles'): ?>
                <form method="post">
                    <input type="hidden" name="entity" value="salles">
                    <input type="hidden" name="action" value="<?php echo $ligneEdition ? 'update' : 'add'; ?>">
                    <?php if ($ligneEdition): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligneEdition['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>

                    <label>Désignation</label>
                    <input type="text" name="designation" required value="<?php echo htmlspecialchars((string) ($ligneEdition['designation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label>Capacité</label>
                    <input type="number" name="capacite" required min="1" value="<?php echo (int) ($ligneEdition['capacite'] ?? 30); ?>">

                    <button type="submit" class="btn btn-success"><?php echo $ligneEdition ? 'Mettre à jour' : 'Ajouter'; ?></button>
                    <?php if ($ligneEdition): ?>
                        <a class="btn" href="admin.php?entity=salles">Annuler</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if ($entite === 'promotions'): ?>
                <form method="post">
                    <input type="hidden" name="entity" value="promotions">
                    <input type="hidden" name="action" value="<?php echo $ligneEdition ? 'update' : 'add'; ?>">
                    <?php if ($ligneEdition): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligneEdition['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>

                    <label>Libellé</label>
                    <input type="text" name="libelle" required value="<?php echo htmlspecialchars((string) ($ligneEdition['libelle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label>Effectif</label>
                    <input type="number" name="effectif" required min="1" value="<?php echo (int) ($ligneEdition['effectif'] ?? 50); ?>">

                    <button type="submit" class="btn btn-success"><?php echo $ligneEdition ? 'Mettre à jour' : 'Ajouter'; ?></button>
                    <?php if ($ligneEdition): ?>
                        <a class="btn" href="admin.php?entity=promotions">Annuler</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if ($entite === 'cours'): ?>
                <form method="post">
                    <input type="hidden" name="entity" value="cours">
                    <input type="hidden" name="action" value="<?php echo $ligneEdition ? 'update' : 'add'; ?>">
                    <?php if ($ligneEdition): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligneEdition['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>

                    <label>Intitulé</label>
                    <input type="text" name="intitule" required value="<?php echo htmlspecialchars((string) ($ligneEdition['intitule'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label>Volume horaire</label>
                    <input type="number" name="volume_horaire" required min="1" value="<?php echo (int) ($ligneEdition['volume_horaire'] ?? 2); ?>">

                    <label>Type</label>
                    <select name="type" required>
                        <?php $typeActuel = (string) ($ligneEdition['type'] ?? 'tronc commun'); ?>
                        <option value="tronc commun" <?php echo $typeActuel === 'tronc commun' ? 'selected' : ''; ?>>Tronc commun</option>
                        <option value="option" <?php echo $typeActuel === 'option' ? 'selected' : ''; ?>>Option</option>
                    </select>

                    <label>Promotion</label>
                    <select name="promotion" required>
                        <?php $promoActuelle = (string) ($ligneEdition['promotion'] ?? ''); ?>
                        <?php foreach ($promotions as $promo): ?>
                            <option value="<?php echo htmlspecialchars((string) $promo['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $promo['id'] === $promoActuelle ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $promo['libelle'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-success"><?php echo $ligneEdition ? 'Mettre à jour' : 'Ajouter'; ?></button>
                    <?php if ($ligneEdition): ?>
                        <a class="btn" href="admin.php?entity=cours">Annuler</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <div class="card table-wrap">
            <h2>Liste</h2>
            <?php if ($entite === 'salles'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Désignation</th>
                            <th>Capacité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($salles as $ligne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ligne['designation'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $ligne['capacite']; ?></td>
                            <td>
                                <a class="btn" href="admin.php?entity=salles&edit=<?php echo urlencode((string) $ligne['id']); ?>">Modifier</a>
                                <form method="post" class="inline" onsubmit="return confirm('Supprimer cette salle ?');">
                                    <input type="hidden" name="entity" value="salles">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($entite === 'promotions'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Libellé</th>
                            <th>Effectif</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($promotions as $ligne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ligne['libelle'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $ligne['effectif']; ?></td>
                            <td>
                                <a class="btn" href="admin.php?entity=promotions&edit=<?php echo urlencode((string) $ligne['id']); ?>">Modifier</a>
                                <form method="post" class="inline" onsubmit="return confirm('Supprimer cette promotion ?');">
                                    <input type="hidden" name="entity" value="promotions">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($entite === 'cours'): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Intitulé</th>
                            <th>Volume</th>
                            <th>Type</th>
                            <th>Promotion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cours as $ligne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) $ligne['intitule'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $ligne['volume_horaire']; ?>h</td>
                            <td><?php echo htmlspecialchars((string) $ligne['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(libelle_groupe('PROMO:' . (string) $ligne['promotion'], $promotions, []), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a class="btn" href="admin.php?entity=cours&edit=<?php echo urlencode((string) $ligne['id']); ?>">Modifier</a>
                                <form method="post" class="inline" onsubmit="return confirm('Supprimer ce cours ?');">
                                    <input type="hidden" name="entity" value="cours">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $ligne['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
