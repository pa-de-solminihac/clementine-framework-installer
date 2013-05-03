<?php
require('includes.php');
require('fonctions.php');

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Installation de Clémentine</title>
    <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
</head>
<body>
    <div id="wrapper">
        <div id="main">
            <h2>Mise à jour de l'installeur</h2>
            <a href="http://clementine.quai13.com"><img src="logo.jpg" alt="logo clémentine framework" /></a>
<?php
require('repocheck.php');
?>
            <fieldset>
<?php
$maj_installeur_dispo = maj_installeur_dispo();
$maj_installeur_effectuee = 0;
if ($maj_installeur_dispo) {
?>
                <p>
                    Une mise à jour de l'installeur est disponible.
                </p>
<?php
} else {
?>
                <p>
                    Pas de mise à jour de l'installeur pour le moment.
                </p>
<?php
}
?>
            </fieldset>
<?php
if (isset($_POST['real_update_installer'])) {
    // lance l'autoupdate de l'installeur
?>
            <br />
            <fieldset>
                <legend>Mise à jour de l'installeur</legend>
                <h4>Téléchargement</h4>
<?php
    if (!is_dir('../tmp')) {
        mkdir('../tmp', 0755);
        @chmod('../tmp', 0755);
    }
    $src = __CLEMENTINE_REPOSITORY_URL__ . '/clementine-framework-installer/archive/master.zip';
    $dst = '../tmp/install.zip';
    $dlerrors = 0;
    if (!dlcopy($src, $dst)) {
        ++$dlerrors;
        echo 'Erreur de téléchargement de ' . $src;
    } else {
        echo "Téléchargement terminé";
    }
?>
                <h4>Décompression</h4>
<?php
    $unziperrors = 0;
    if (!$dlerrors) {
        $src = '../tmp/install.zip';
        $dst = '../tmp';
        if (!unzip($src, $dst)) {
            echo "Décompression terminée";
            unlink($src);
        } else {
            ++$unziperrors;
            echo "Erreur lors de la décompression... Peut-être que l'archive a été mal téléchargée ?";
        }
    }
?>
                <h4>Remplacement des fichiers</h4>
<?php
    if (!$unziperrors) {
        $src = '../tmp/clementine-framework-installer-master';
        $dst = '../install';
        @chmod($src, 0755);
        unlink_recursive($dst);
        if (!rename($src, $dst)) {
            echo "Erreur de remplacement";
        } else {
            echo "Remplacement effectué";
        }
        $maj_installeur_effectuee = 1;
    }
    /*
} elseif (isset($_POST['delete_installer'])) {
    // supprime le dossier de l'installeur
?>
            <br />
            <fieldset>
                <legend>Suppression de l'installeur</legend>
<?php
    if (unlink_recursive('../install')) {
?>
                <h4>Suppression effectuée</h4>
<?php
    } else {
?>
                <h4>Suppression incomplète</h4>
                Vous devriez supprimer manuellement le dossier de l'installeur ainsi que le zip
<?php
    }
     */
}
?>
            </fieldset>
            <br />
            <div class="boutons inverse">
<?php
if (!$maj_installeur_dispo || $maj_installeur_effectuee) {
?>
                <a class="prev" href="./">Relancer l'installeur</a>
<?php
} else {
?>
                <br />
<?php
}
if (!isset($_POST['real_update_installer'])) {
?>
                <form class="formbas" action="update_installer.php" method="post" accept-charset="utf-8">
<?php
/*
?>
                    <input type="submit" class="prev horsubmit_rel" name="delete_installer" value="Supprimer l'installeur" />
<?php
 */
?>
                    <input type="submit" class="next horsubmit_rel" name="real_update_installer" value="<?php
if (!$maj_installeur_dispo) {
    echo "Réinstaller l'installeur »";
} else {
    echo "Mettre à jour l'installeur »";
}
                    
                    ?>" />
                </form>
<?php
}
?>
            </div>
        </div>
    </div>
</body>
</html>
