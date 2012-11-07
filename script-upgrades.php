<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
?>
<h4>Mises à jour scriptées</h4>
<?php
if (isset($_GET['debug'])) {
    echo 'Mises à jour scriptées';
    echo '<pre>';
}
$upgradeerrors = 0;
if (isset($_GET['reinstall']) && $_GET['reinstall'] == 1) {
    echo "<span>Mises à jour pour les modules locaux</span><br />";
    if (count($local_module_upgrades)) {
?>
                    <h4>Mises à jour de modules locaux</h4>
<?php
        global $db;
        foreach ($local_module_upgrades as $version => $modules) {
            foreach ($modules as $module => $filepath) {
                if (is_file($filepath)) {
                    $db->beginTransaction();
                    if (!include ($filepath)) {
                        // $db->rollBack(); // deja gere par les scripts d'upgrade
                        echo '<br /><span class="err">erreur lors de la mise à jour de ' . $module . ' (' . $version . ') </span><br />';
                        ++$upgradeerrors;
                        break 2;
                    }
                    // met à jour la version du module en base de données
                    $sql = "
                        INSERT INTO `clementine_installer_modules` (
                            `module`, `version`, `type`
                        ) VALUES (
                            :module, :version, 'local'
                        ) ON DUPLICATE KEY UPDATE `version` = :version ";
                    $stmt = $db->prepare($sql);
                    if (!$stmt->execute(array(':module' => $module, ':version' => $version))) {
                        $db->rollBack();
                    }
                    $db->commit();
                    // restaure l'encodage
                    $db->query('SET NAMES ' . __SQL_ENCODING__);
                    $db->query('SET CHARACTER SET ' . __SQL_ENCODING__);
                    echo '<span class="ok">mise à jour de ' . $module . ' (' . $version . ') appliquée </span><br />';
                }

            }
        }
    }
} else {
    foreach ($versions_choisies as $module => $new_version) {
        if (!isset($current_local_modules[$module])) {
            $previous_version = installer_getModuleVersion($module, false, true);
            if (version_compare($new_version, $previous_version) > 0) {
                if ($intermediaire = applyScriptUpgrades($module, $previous_version, $new_version)) {
                    ++$upgradeerrors;
                    echo '<br /><span class="err">Problème rencontré lors de la mise à jour du module ' . $module . ' depuis la version ' . $intermediaire . ' vers la version ' . $new_version . '... on ne continue pas les mises a jour !</span><br />';
                    break;
                }
            }
        }
    }
}
if (isset($_GET['debug'])) {
    echo '</pre>';
}
if (!$upgradeerrors) {
    if (!(isset($_GET['reinstall']) && $_GET['reinstall'] == 1)) {
        echo '<span>';
        echo 'Mises à jour scriptées terminées. ';
        echo '</span>';
    }
    // appelle l'etape suivante
    require('replace.php');
} else {
    // annule la mise à jour : restaure le backup de la BD
    require('revert.php');
}
?>
