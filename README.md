Clementine Framework installer
==============================

Principe de fonctionnement
---

Fonctionnalités
---

Gestion des dépendances
---

Publier un nouveau module
---

Publier une nouvelle version d'un module, mises à jour scriptées
---

On publier les migrations de la base de données dans des fichiers de la forme suivante :
`app/local/site/upgrades/YYYY-mm-dd.php`

```php
<?php
/**
 * Script non interactif de mise à jour
 */

// deja appele par l'installer
// $db->beginTransaction();

$requetes = array (
    'UPDATE ...',
    'UPDATE 2 ...',
);
// execute les requetes une par une et rollback au moindre plantage
foreach ($requetes as $sql) {
    if (!$db->prepare($sql)->execute()) {
        $db->rollBack();
        return false;
    }
}

// deja appele par l'installer
// $db->commit();

return true;
```

On lance ensuite l'installeur, qui appliquera les fichiers de mise à jour dans l'ordre alphabétique (donc chronologique selon le nom de fichiers adopté ici).

Mettre à jour le dépot
---
```bash
# se connecter en ssh au depot
cd devtools
git pull
./update_repository.sh
```

Devtools, description
---
* update_repository.sh
* create_package.sh
* create_installer.sh
* create_release.sh


Troubleshooting
---

*Comment supprimer une version __N.m__ d'un module __monmodule__ publiée par erreur ?*
```bash
cd modules/monmodule/trunk
# corriger le numero de version dans etc/module.ini
git tag -d N.m
git push origin :refs/tags/N.m
cd ../repository/scripts
git rm -r versions/N.m
git commit -a versions/N.m
git push
git tag -d N.m
git push origin :refs/tags/N.m
```
Puis il faut mettre à jour le dépot pour qu'il prenne en compte la modif.
