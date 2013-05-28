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
