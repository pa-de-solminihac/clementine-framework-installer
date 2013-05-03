<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
?>
                <h4>Remplacement des fichiers</h4>
<?php

if (isset($_GET['debug'])) {
    echo 'Remplacements';
    echo '<pre>';
}
$dst = '../app/share';
if (!is_dir($dst)) {
    mkdir($dst, 0755);
    @chmod($dst, 0755);
}
$replaceerrors = 0;
foreach ($versions_choisies as $module => $new_version) {
    $src = 'tmp';
    $previous_version = installer_getModuleVersion($module);
    // on ne remplace les sources que si la version monte (ou si on a demande une reinstall)
    if (version_compare($new_version, $previous_version) > 0 || (isset($_GET['reinstall']) && $_GET['reinstall'] == 1)) {
        if (isset($_GET['debug'])) {
            echo "Remplacement du module $module<br />";
        }
        unlink_recursive($dst . '/' . $module);
        if ($mode_developpeur) {
            if (!copy_recursive($src . '/' . $module . '/src/' . $module, $dst . '/' . $module)) {
                ++$replaceerrors;
            }
        } else {
            $renamed = rename($src . '/' . $module . '/src/clementine-framework-module-' . $module . '-' . $new_version, $dst . '/' . $module);
            if (!$renamed) {
                ++$replaceerrors;
            }
        }
    }
}
if (isset($_GET['debug'])) {
    echo '</pre>';
}
if (!$replaceerrors) {
    echo '<span>';
    echo 'Remplacements terminés. ';
    echo '</span>';
}

?>
                <h4>Enregistrement des versions installées</h4>
<?php
// on enregistre les versions installées dans la table clementine_installer_modules
global $db;
$db->beginTransaction();
$sql = "
    DELETE FROM `clementine_installer_modules` WHERE `type` = 'share' ";
$stmt = $db->query($sql);
$sql = "
    INSERT INTO `clementine_installer_modules` (
        `module`, `version`, `type`
    ) VALUES (
        :module, :version, 'share'
    ) ";
$stmt = $db->prepare($sql);
foreach ($versions_choisies as $module => $version) {
    if (!$stmt->execute(array(':module' => $module, ':version' => $version))) {
        $db->rollBack();
        echo '<span>';
        echo 'Enregistrement impossible. ';
        echo '</span>';
    }
}
$db->commit();
echo '<span>';
echo 'Enregistrement terminé. ';
echo '</span>';
?>
                <h4>Nettoyage</h4>
<?php

$current_share_modules = array();
$required_share_modules = array_keys($premiere_solution);
// liste les dossiers contenus dans ../app/share
if (!$dh = @opendir($dst)) {
    return false;
}
while (false !== ($obj = readdir($dh))) {
    if ($obj == '.' || $obj == '..' || (isset($obj[0]) && $obj[0] == '.')) {
        continue;
    }
    if (is_dir($dst . '/' . $obj)) {
        $current_share_modules[] = $obj;
    }
}
closedir($dh);
if (isset($_GET['debug'])) {
    echo 'Suppression des modules obsolètes';
    echo '<pre>';
}
$cleanerrors = 0;
foreach ($current_share_modules as $module) {
    if (!in_array($module, $required_share_modules)) {
        echo 'Module obsolete : ' , $module , '<br />';
        if (!unlink_recursive($dst . '/' . $module)) {
            ++$cleanerrors;
        }
    }
}
if (isset($_GET['debug'])) {
    echo '</pre>';
}
if (!__CLEMENTINE_INSTALLER_NODOWNLOAD__ && !unlink_recursive('tmp')) {
    ++$cleanerrors;
}
if (!$cleanerrors) {
    echo '<span>';
    echo 'Nettoyage terminé';
    echo '</span>';
}

?>
            </fieldset>
            <p>
                L'installation est terminée.
            </p>
            <br />
            <div class="boutons">
                <a class="prev" href="./">Relancer l'installeur</a>
<?php
    if (!((isset($_GET['reinstall']) && $_GET['reinstall'] == 1))) {
?>
                <a class="next" href="../">Aller au site</a>
<?php
}
?>
            </div>
        </div>
    </div>
</body>
</html>
