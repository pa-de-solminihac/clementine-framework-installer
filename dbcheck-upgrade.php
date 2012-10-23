<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
global $db;
// migre si nécessaire app/share/modules_versions.ini vers la base de données (table clementine_installer_modules)
$upgraded_now = 0;
// teste l'existence du fichier .ini
$config_file = dirname(__FILE__) . '/../app/share/modules_versions.ini';
$config_is_ini = 0;
$config_db = 0;
$config_db_vide = 0;
$config_ini = array();
if (is_file($config_file) && filesize($config_file) && $config_ini = parse_ini_file($config_file)) {
    $config_is_ini = 1;
}
// crée la table clementine_installer_modules si nécessaire
$sql = 'SHOW CREATE TABLE `clementine_installer_modules`';
if ($stmt = $db->query($sql)) {
    $config_db = 1;
    $config_db_vide = 1;
    $sql = 'SELECT COUNT(*) AS nb FROM `clementine_installer_modules`';
    $stmt_cnt = $db->query($sql);
    if (($cnt = $stmt_cnt->fetchAll()) && $cnt[0]['nb']) {
        $config_db_vide = 0;
    }
} else {
    // crée la table
    $db->beginTransaction();
    $sql = "
        CREATE TABLE `clementine_installer_modules` (
            `module` VARCHAR(255) NOT NULL,
            `version` VARCHAR(32) NOT NULL,
            PRIMARY KEY (`module`)
        )
        ENGINE = InnoDB
        DEFAULT CHARACTER SET = utf8; ";
    if (!$db->query($sql)) {
        $db->rollBack();
        return false;
    }
    $db->commit();
    $config_db = 1;
    $config_db_vide = 1;
}
// si la table existe, qu'elle est vide, et que le fichier ini existe aussi on migre le contenu du fichier dans la table
if ($config_db && $config_db_vide && count($config_ini)) {
    // remplit la table
    $db->beginTransaction();
    $sql = "
        INSERT INTO `clementine_installer_modules` (
            `module`, `version`
        ) VALUES (
            :module, :version
        ) ";
    $stmt = $db->prepare($sql);
    foreach ($config_ini as $module => $version) {
        if (!$stmt->execute(array(':module' => $module, ':version' => $version))) {
            $db->rollBack();
            return false;
        }
    }
    $db->commit();
    $upgraded_now = 1;
}
// puis on supprime le fichier si la table est remplie
if ($config_db) {
    $sql = 'SELECT COUNT(*) AS nb FROM `clementine_installer_modules`';
    $stmt_cnt = $db->query($sql);
    if (($cnt = $stmt_cnt->fetchAll()) && $cnt[0]['nb']) {
        if ($upgraded_now) {
            if (unlink($config_file)) {
                // tout s'est bien passé
                return true;
            } else {
                // annule la migration
                $sql = "DROP TABLE `clementine_installer_modules`";
                return false;
            }
        }
    }
}
// cree la colonne type (local ou share) si nécessaire 
$sql = 'SHOW COLUMNS FROM `clementine_installer_modules` LIKE "type" ';
$column_exists = false;
if ($stmt = $db->query($sql)) {
    $column_exists = $stmt->rowCount();
}
if (!$column_exists) {
    $sql = 'ALTER TABLE `clementine_installer_modules` ADD `type` VARCHAR(16) NOT NULL DEFAULT "share" AFTER `version` ';
    $db->query($sql);
}

if ($config_db && !$config_db_vide) {
    // pas besoin de faire la MAJ, tout est ok
    return true;
} else {
    // pas eu besoin de MAJ
    return true;
}
?>
