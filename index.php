<?php
require('includes.php');
require('fonctions.php');
require('dbcheck.php');

// verifications
$verif_ok = 0;
$baseconfig_ok = 0;
$errors = array();
// PHP version
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    $errors['php5'] = 'PHP 5 required';
}
// safe_mode disabled
if (ini_get('safe_mode')) {
    $errors['safe_mode'] = 'PHP safe_mode is deprecated, an MUST be disabled for this installer to run properly';
}
// file management
$errfile = 0;
if (!is_dir('tmp')) {
    mkdir('tmp', 0755);
    @chmod('tmp', 0755);
}
$path = 'test_' . md5(uniqid());
if (!is_dir('tmp')) {
    ++$errfile;
} else {
    touch('tmp/' . $path);
    if (!is_file('tmp/' . $path)) {
        ++$errfile;
    } else {
        if (!unlink('tmp/' . $path)) {
            ++$errfile;
        }
    }
}
if ($errfile) {
    $errors['chmod'] = 'Could not manage files properly';
}
if (!$db) {
    $errors['db_set'] = 'Missing database credentials';
}
$config = installer_getModuleConfig('../app/local/site');
if (is_array($config) && isset($config['version']) && isset($config['weight']) && isset($config['depends_' . (int) $config['version']]) && isset($config['depends_' . (int) $config['version']][(int) $config['version']])) {
    $baseconfig_ok = 1;
}
if ($db && !count($errors) && $baseconfig_ok) {
    header('Location: update.php' , true, 302);
    die();
}

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
            <h2>Bienvenue dans l'installeur</h2>
            <a href="http://clementine.quai13.com"><img src="logo.jpg" alt="logo clémentine framework" /></a>
            <fieldset>
                <legend>Vérifications</legend>
<dl>
<?php
echo '<dt>PHP version</dt>';
if (isset($errors['php5'])) {
    echo "<dd class='err'><p>PHP 5 est nécessaire. </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>PHP safe_mode</dt>';
if (isset($errors['safe_mode'])) {
    echo "<dd class='err'><p>Vous devez désactiver le safe_mode (qui est de toute facon OBSOLÈTE ;)). </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>File management</dt>';
if (isset($errors['chmod'])) {
    echo "<dd class='err'><p>Des problèmes de droits sur les fichier empêcheront l'installeur de fonctionner correctement. </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>Database credentials</dt>';
if (!$db) {
    if ($is_db_set) {
        if (count($dberrors)) {
            foreach ($dberrors as $key => $err) {
                echo "<dd class='err'><p>" . $err . "</p></dd>";
            }
        } else {
            echo "<dd class='err'><p>Les informations de connexion à la base de donnees sont incorrectes. </p></dd>";
        }
    } else {
        echo "<dd class='err'><p>Les informations de connexion à la base de donnees manquent. </p></dd>";
    }
} else {
    echo '<dd class="ok">ok</dd>';
}
?>
</dl>
<div class="spacer"></div>
<?php

// code php du fichier module d'exemple
$exemple_module_ini = <<<INI

version=1.0
weight=0.9

; dependances de la version 1.* du module site
[depends_1]
core=1
cron=1
fonctions=1

INI;

if (!$baseconfig_ok) {
    $errors[] = 'Base Config';
}

if (!count($errors)) {
?>
                <p>
                    <span class="ok">Les vérifications sont bien passées. </span>
                </p>

            </fieldset>
            <br />

            <form action="" method="post" accept-charset="utf-8">
                <div class="boutons">
                    <a class="prev" href="../">Retour au site</a>
                    <input class="next" type="submit" value="Installer ou mettre à jour" />
                </div>
            </form>
<?php
} else {
    if (!$baseconfig_ok) {
?>
            <h3 id="exemple">Création du module <em>site</em></h3>
            <p>
            Avant de procéder à l'installation, vous devez créer le fichier : <br /><strong>app/local/site/etc/module.ini</strong>
            </p>
            <p>
            Vous pouvez vous inspirer du modèle ci-dessous :
            </p>
            <div style="border: 1px solid #000000">
                <p style="border-bottom: 1px dotted #000000; margin: 0; padding: 0.2em; text-indent: 1em;">
                    <strong>Exemple de fichier app/local/site/etc/module.ini</strong>
                </p>
                <pre style="background-color: #CCCCCC; margin: 10px; padding: 0.5em; border: 1px outset #000000"><?php echo $exemple_module_ini; ?></pre>
            </div>
            <p>
            Ce fichier est présent dans chaque module, et contient des informations importantes comme sa <em>version</em>, son <em>poids</em>, et les <em>autres modules</em> dont il dépend. 
            </p>
            <p>
            Ces modules seront alors installés automatiquement.
            </p>
<?php 
    }
?>
            </fieldset>
            <br />

            <div class="boutons" action="update_installer2.php" method="post" accept-charset="utf-8">
                <a class="prev" href="../">Retour au site</a>
                <a class="disabled" href="">Revérifier&hellip;</a>
            </div>
<?php
}
?>
            <form class="formbas" action="update_installer.php" method="post" accept-charset="utf-8">
                <input type="submit" class="prev horsubmit" name="update_installer" value="Mise à jour de l'installeur" />
            </form>
        </div>
    </div>
</body>
</html>
