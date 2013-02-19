<?php
require('includes.php');
if (!isset($site_config['clementine_db']['user'])) {
    header('Location: index.php' , true, 302);
    die();
}
require('fonctions.php');

if (maj_installeur_dispo()) {
    header('Location: update_installer.php' , true, 302);
    die();
} 

// liste les dossiers contenus dans ../app/local
$dst = '../app/local';
$current_local_modules = array();
if (!$dh = @opendir($dst)) {
    return false;
}
while (false !== ($obj = readdir($dh))) {
    if ($obj == '.' || $obj == '..' || (isset($obj[0]) && $obj[0] == '.')) {
        continue;
    }
    if (is_dir($dst . '/' . $obj)) {
        $current_local_modules[$obj] = 'local';
    }
}
closedir($dh);

// recupere les fichiers tmp/$module/module.ini (telecharge pour les modules "share", copie pour les modules "local")
// on telecharge meme si on est a l'etape "confirmation"
global $mode_developpeur;
if (strlen(__CLEMENTINE_REPOSITORY_URL__) && !$mode_developpeur) {
    if (!is_dir('tmp')) {
        mkdir('tmp', 0755);
        @chmod('tmp', 0755);
    }
    unlink_recursive('tmp', false);
    foreach ($current_local_modules as $module => $type) {
        $module = preg_replace('/[^a-zA-Z0-9_]/S', '', $module);
        $path = 'tmp/' . $module;
        if (!is_dir($path)) {
            mkdir($path, 0755);
            @chmod($path, 0755);
        }
        $src = '../app/local/' . $module . '/etc/module.ini';
        // recupere le fichier depends.ini par copie
        if (!is_dir($path . '/etc')) {
            mkdir($path . '/etc', 0755);
            @chmod($path . '/etc', 0755);
        }
        if (!dlcopy($src, $path . '/etc/module.ini')) {
            echo $src;
            echo ' : erreur';
            echo "<br />";
        }
    }
}

// appelle l'etape suivante
require('depends.php');
?>
