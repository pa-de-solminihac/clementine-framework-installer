<?php
// recupere la configuration du site : installeur actif, infos de connexion à la BD du site...
if (is_file('../app/local/site/etc/config.ini')) {
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
        $site_config = parse_ini_file('../app/local/site/etc/config.ini', true, INI_SCANNER_RAW);
    } else {
        $site_config = parse_ini_file('../app/local/site/etc/config.ini', true);
    }
}
if (isset($site_config['clementine_installer']) && isset($site_config['clementine_installer']['enabled']) && $site_config['clementine_installer']['enabled']) {
    if (!isset($site_config['clementine_installer']['allowed_ip']) 
        || (isset($site_config['clementine_installer']['allowed_ip']) && (!$site_config['clementine_installer']['allowed_ip']
                                                                          || (in_array($_SERVER['REMOTE_ADDR'], explode(',', $site_config['clementine_installer']['allowed_ip'])))))) {
        define('CLEMENTINE_INSTALLER_DISABLE', '0');
    } else {
        define('CLEMENTINE_INSTALLER_DISABLE', '1');
    }
} else {
    if (!isset($site_config['clementine_installer'])) {
        define('CLEMENTINE_INSTALLER_DISABLE', '0');
    } else {
        define('CLEMENTINE_INSTALLER_DISABLE', '1');
    }
}
if (CLEMENTINE_INSTALLER_DISABLE) {
    echo '<p><strong>The installer is disabled.</strong></p> <p>You can enable it in the <em>[clementine_installer]</em> section of the <em>app/local/site/etc/config.ini</em> file.</p>';
    die();
}
// pour éviter les surprises
session_start();
session_destroy();
if (ini_get('apc.enabled')) {
    apc_clear_cache();
    apc_clear_cache('user');
}

// optional config file for config overriding
if (is_file('config.php')) {
    include('config.php');
}
define('__CLEMENTINE_DEFAULT_REPOSITORY_SSL_URL__', 'http://clementine.quai13.com/repository');
define('__CLEMENTINE_DEFAULT_REPOSITORY_URL__', 'http://clementine.quai13.com/repository');
if (!defined('__CLEMENTINE_REPOSITORY_URL__')) {
    if (isset($site_config['clementine_installer']) && isset($site_config['clementine_installer']['repository_url'])) {
        define('__CLEMENTINE_REPOSITORY_URL__', $site_config['clementine_installer']['repository_url']);
    } else {
        define('__CLEMENTINE_REPOSITORY_URL__', __CLEMENTINE_DEFAULT_REPOSITORY_SSL_URL__);
    }
}
if (!defined('__CLEMENTINE_INSTALLER_NODOWNLOAD__')) {
    define('__CLEMENTINE_INSTALLER_NODOWNLOAD__', '0');
}
if (!defined('__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__')) {
    if (isset($site_config['clementine_installer']) && isset($site_config['clementine_installer']['path_to_mysql'])) {
        define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__', $site_config['clementine_installer']['path_to_mysql']);
    } else {
        define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__', '/usr/bin/mysql');
    }
}
if (!defined('__CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__')) {
    if (isset($site_config['clementine_installer']) && isset($site_config['clementine_installer']['path_to_mysql'])) {
        define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__', $site_config['clementine_installer']['path_to_mysqldump']);
    } else {
        define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__', '/usr/bin/mysqldump');
    }
}
// si mode développeur, téléchargement seulement si tmp vide
global $mode_developpeur;
$mode_developpeur = 0;
if (__CLEMENTINE_INSTALLER_NODOWNLOAD__) {
    if (is_dir('tmp')) {
        $directory = dir('tmp');
        while ((false !== ($item = $directory->read())) && (!isset($directory_not_empty))) {
            if ($item != '.' && $item != '..') {
                // dossier non vide, on active le mode developpeur
                $mode_developpeur = 1;
                break;
            }
        }
        $directory->close();
    }
}
// toujours afficher les erreurs, car il n'est pas cense y en avoir !
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');
if (!ini_get('safe_mode')) {
    ini_set('max_execution_time', 0);
    set_time_limit(0);
}
// gestion de l'encodage
if (isset($site_config['clementine_global']['php_encoding'])) {
    define('__PHP_ENCODING__', $site_config['clementine_global']['php_encoding']);
} else {
    define('__PHP_ENCODING__', 'UTF-8');
}
if (isset($site_config['clementine_global']['html_encoding'])) {
    define('__HTML_ENCODING__', $site_config['clementine_global']['html_encoding']);
} else {
    define('__HTML_ENCODING__', 'utf-8');
}
if (isset($site_config['clementine_global']['sql_encoding'])) {
    define('__SQL_ENCODING__', $site_config['clementine_global']['sql_encoding']);
} else {
    define('__SQL_ENCODING__', 'utf8');
}
mb_internal_encoding(__PHP_ENCODING__);
// force l'encodage du site mais n'envoie les headers que si possible (sinon PHPUnit n'aime pas...)
if (!headers_sent()) {
    header('Content-type: text/html; charset="' . __HTML_ENCODING__ . '"');
}
// détermine la racine de l'install
define('__INSTALLER_ROOT__', realpath(dirname(__FILE__)));
?>
