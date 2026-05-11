# Systeme de Gestion des Auditoires

## 1. Analyse des besoins

Le SGA automatise la gestion des salles, promotions, cours et options afin d'eviter les collisions de salles, les depassements de capacite et les affectations incoherentes. L'application est realisee en PHP procedural, sans base de donnees, avec stockage dans des fichiers JSON, TXT et CSV.

Fonctionnalites principales : charger les donnees, verifier les contraintes, generer un planning, sauvegarder et recharger le planning, afficher le planning hebdomadaire.

Fonctionnalites complementaires : detection de conflits, rapport d'occupation et modification manuelle controlee.

## 2. Structure des fichiers

| Fichier | Role | Champs principaux |
|---|---|---|
| `salles.json` | Liste des salles | id, designation, capacite |
| `promotions.json` | Liste des promotions | id, libelle, effectif |
| `cours.json` | Liste des cours | id, intitule, volume_horaire, type, promotion, option |
| `options.json` | Liste des options L3/L4 | id, libelle, promotion, effectif |
| `planning.json` | Planning rechargeable | jour, debut, fin, salle, cours, groupe |
| `planning.txt` | Planning lisible | jour;debut;fin;salle;code_cours;groupe |

## 3. Choix de conception

Le projet separe les responsabilites : les fonctions de lecture lisent les fichiers, les fonctions de validation controlent les contraintes et la fonction de generation construit le planning. Les salles sont triees par capacite croissante afin d'utiliser la plus petite salle suffisante.

## 4. Regles metier

| Regle | Verification | Comportement |
|---|---|---|
| Capacite | effectif <= capacite | Affectation refusee si insuffisante |
| Salle unique | pas deux cours dans la meme salle au meme moment | Creneau suivant ou salle suivante |
| Groupe unique | pas deux cours pour le meme groupe au meme moment | Affectation refusee |
| Semaine pedagogique | lundi a vendredi, 08h-12h et 13h-17h | Creneaux limites a ces plages |

## 5. Implementation PHP

Le fichier `fonctions.php` contient les fonctions principales : chargement des fichiers, verification des contraintes, generation du planning, sauvegarde, affichage HTML, detection de conflits et rapport d'occupation. Chaque fonction est precedee d'un commentaire indiquant son role, ses parametres et sa valeur de retour.

## 6. Resultats obtenus

La generation produit un planning sans conflit dans `planning.json` et `planning.txt`. Le rapport d'occupation est genere dans `rapport_occupation.txt`. Les formulaires d'administration permettent la saisie et la modification des salles, promotions et cours.

## 7. Captures d'ecran a inserer

Inserer dans le PDF final les captures suivantes : page d'accueil avec le planning hebdomadaire, page d'administration, generation du planning et rapport d'occupation.

## 8. Contenu de l'archive

L'archive contient les scripts PHP, les fichiers JSON/TXT/CSV, les rapports explicatifs et les donnees de sauvegarde. Elle doit etre renommee selon le format demande : `NOM_Prenom_SGA.zip`.
