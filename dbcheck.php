<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php', true, 302);
    die();
}
// teste la connexion à la base de données si possible
global $db;
$dberrors = array();
$is_db_set = (isset($site_config['clementine_db']) && isset($site_config['clementine_db']['host']) && isset($site_config['clementine_db']['name']) && isset($site_config['clementine_db']['user']) && isset($site_config['clementine_db']['pass']) && $site_config['clementine_db']['host'] && $site_config['clementine_db']['name'] && $site_config['clementine_db']['user']);
$dsn_host_port = 'mysql:host=' . $site_config['clementine_db']['host'];
if (!empty($site_config['clementine_db']['port'])) {
    $dsn_host_port .= ';port=' . $site_config['clementine_db']['port'];
}
if ($is_db_set) {
    try {
        $db = new PDO($dsn_host_port, $site_config['clementine_db']['user'], $site_config['clementine_db']['pass']);
    } catch (PDOException $e) {
        $db = null;
        $config_file_path = substr(realpath($site_config_filepath), strlen($_SERVER['DOCUMENT_ROOT']));
        if (!$config_file_path) {
            $config_file_path = '/app/local/site/etc/config.ini';
        }
        $dberrors[] = 'Check credentials in <strong>' . $config_file_path . '</strong>';
    }
    // teste si la base existe
    if ($db) {
        try {
            $db = null;
            $db = new PDO($dsn_host_port . ';dbname=' . $site_config['clementine_db']['name'], $site_config['clementine_db']['user'], $site_config['clementine_db']['pass']);
            // migre si nécessaire app/share/modules_versions.ini vers la base de données (table clementine_installer_modules)
            $upgraded = require 'dbcheck-upgrade.php';
            if (!$upgraded) {
                $db = null;
                $dberrors[] = 'Installer upgrade failed (.ini to database)';
            }
        } catch (PDOException $e) {
            $dberrors[] = 'Database does not exists';
        }
    }
    if ($db) {
        // active l'affichage des erreurs
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        // force l'encodage
        $db->query('SET NAMES ' . __SQL_ENCODING__);
        $db->query('SET CHARACTER SET ' . __SQL_ENCODING__);
    }
} else {
    // db module required but db not set
    $db = null;
    $dberrors[] = 'DB required : please provide database credentials';
}
