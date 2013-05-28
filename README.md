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

Devtools, description
---

Troubleshooting
---

***Comment supprimer une version __N.m__ d'un module __monmodule__ publiée par erreur ?***
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
