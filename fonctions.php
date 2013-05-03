<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}

function dlcopy($src, $dst)
{
    $ret = false;
    if (!ini_get('allow_url_fopen') && 1 === preg_match('/(ftp|https?):\/\//i', $src)) {
        $ch = curl_init($src);
        $fp = fopen($dst, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $ret = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    } else {
        $ret = copy($src, $dst);
    }
    @chmod($dst, 0755);
    return $ret;
}

function print_r_ret($mixed = null)
{
    ob_start();
    print_r($mixed);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function print_r_array($mixed = null)
{
    $content = print_r_ret($mixed);
    $content = preg_replace("/^ *\( *$/m", "", $content);
    $content = preg_replace("/^ *\) *$/m", "", $content);
    $content = preg_replace("/\n\n+/m", "\n", $content);
    $content = preg_replace("/  /m", " ", $content);
    echo $content;
}

/**
 * Recursively delete a directory
 *
 * @param string $dir Directory name
 * @param boolean $delete_root_too Delete specified top-level directory as well
 */
function unlink_recursive ($dir, $delete_root_too = true)
{
    if (!$dh = @opendir($dir)) {
        return false;
    }
    while (false !== ($obj = readdir($dh))) {
        if ($obj == '.' || $obj == '..') {
            continue;
        }
        if (!@unlink($dir . '/' . $obj)) {
            unlink_recursive($dir . '/' . $obj, true);
        }
    }
    closedir($dh);
    if ($delete_root_too) {
        @rmdir($dir);
    }
    return true;
}

/**
 * Recursively copies a directory
 *
 * @param string $src Source directory
 * @param boolean $dst Destination directory
 */
function copy_recursive ($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755);
    @chmod($dst, 0755);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_recursive($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * get_dependencies : recupere le tableau des dependances directes d'un module, avec les numeros de versions compatibles
 * 
 * @param mixed $module 
 * @param mixed $specific_version 
 * @access public
 * @return void
 */
function get_dependencies ($module, $specific_version)
{
    $module = preg_replace('/[^a-zA-Z0-9_]/S', '', $module);
    $module_depends_file_path = realpath(dirname(__FILE__) . '/tmp/' . $module);
    if (!$module_depends_file_path) {
        global $mode_developpeur;
        if (!$mode_developpeur) {
            if (!update_module_repository($module)) {
                return false;
            }
        }
        $module_depends_file_path = realpath(dirname(__FILE__) . '/tmp/' . $module);
    }
    $config = installer_getModuleConfig($module_depends_file_path, $specific_version);
    if (false === $config) {
        return false;
    }
    if (is_array($config)) {
        if (isset($config['depends_' . $specific_version]) && is_array($config['depends_' . $specific_version]) && count($config['depends_' . $specific_version])) {
            $deps = array();
            // tri des versions par ordre decroissant
            krsort($config['depends_' . $specific_version]);
            foreach ($config['depends_' . $specific_version] as $version_module => $versions) {
                // tri des versions par ordre decroissant
                $mods = array_keys($versions);
                foreach ($mods as $mod) {
                    krsort($versions[$mod]);
                }
                $deps[$version_module] = $versions;
            }
            if (isset($deps[$specific_version])) {
                return $deps[$specific_version];
            } else {
                if (isset($_GET['debug'])) {
                    echo "<br />Probleme de dépendances avec le module <strong>$module</strong> : la version demandée ($specific_version) est inconnue. Vous pouvez identifier le module qui demande cette version dans le tableau des dépendances. <br /><br />";
                }
                return false;
            }
        }
    }
    return true;
}

/**
 * register_dependencies : recupere recursivement (et stocke dans le tableau fourni en parametre) les dependances d'un module
 * 
 * @param mixed $deps : tableau passé par référence dans lequel seront stockés les résultats
 * @param string $module 
 * @param string $specific_version 
 * @param array $allversions 
 * @param int $max_recursion_level 
 * @access public
 * @return void
 */
function register_dependencies($deps = array(), $module = 'site', $specific_version = '1', $allversions = array(), $max_recursion_level = 10)
{
    if (!$specific_version) {
        echo 'Erreur, car il faut un numero de version';
        return false;
    }
    // pour ne pas tourner en boucle sur les dependances circulaires
    $stop_here = 0;
    if (($max_recursion_level < 0) || (isset($allversions[$module][$specific_version]))) {
        $stop_here = 1;
    }
    if (!$stop_here) {
        --$max_recursion_level;
        $dependancies = get_dependencies($module, $specific_version);
        if (false === $dependancies) {
            return false;
        }
        if (is_array($dependancies)) {
            foreach ($dependancies as $dependance => $versions) {
                if (!isset($allversions[$module])) {
                    $allversions[$module] = '';
                }
                $allversions[$module][$specific_version] = '';
                // recupere recursivement la suite des dependances
                foreach ($versions as $version => $val) {
                    $res = register_dependencies($dependancies, $dependance, $version, $allversions, $max_recursion_level);
                    $dependancies = $res['deps'];
                    $allversions = $res['allversions'];
                }
            }
        } else {
            if (!isset($allversions[$module])) {
                $allversions[$module] = '';
            }
            $allversions[$module][$specific_version] = '';
        }
        if (!isset($deps[$module])) {
            $deps[$module][$specific_version] = $dependancies;
        } else {
            if (is_array($dependancies)) {
                if (!isset($deps[$module][$specific_version]) || !is_array($deps[$module][$specific_version])) {
                    $deps[$module][$specific_version] = $dependancies;
                } else {
                    $deps[$module][$specific_version] = array_merge_recursive($deps[$module][$specific_version], $dependancies);
                }
            }
        }
    }
    return array('deps' => $deps, 'allversions' => $allversions);
}

/**
 * translate_dependencies 
 * 
 * @param mixed $dependances 
 * @access public
 * @return void
 */
function translate_dependencies($arbre, $str = '')
{
    $nb_parentheses = 0;
    if (is_array($arbre)) {
        foreach ($arbre as $module => $versions) {
            if (count($versions)) {
                if ($str) {
                    $str .= ' && ';
                }
                if (count($versions) > 1) {
                    ++$nb_parentheses;
                    $str .= '(';
                }
                $i = 1;
                foreach ($versions as $version => $val) {
                    $str .= '$vrs[\'' . $module . "'] == '" . $version . "'";
                    $str = translate_dependencies($val, $str);
                    if ($i < count($versions)) {
                        if (count($versions) > 1) {
                            $str .= ' || ';
                        } else {
                            $str .= ' && ';
                        }
                    }
                    ++$i;
                }
            }
            if ($nb_parentheses) {
                --$nb_parentheses;
                $str .= ')';
            }
        }
    }
    return $str;
}

function creer_matrice_candidats($candidats, $evalstr = '', &$matrice = array(), $vrs = array())
{
    if (count($candidats)) {
        $candidats_restants = $candidats;
        foreach ($candidats as $module => $versions) {
            unset($candidats_restants[$module]);
            foreach ($versions as $version => $val) {
                $vrs[$module] = $version;
                if (count($versions) > 1) {
                    $vrs = creer_matrice_candidats($candidats_restants, $evalstr, $matrice, $vrs);
                }
            }
        }
    }
    // court-circuite la creation exhaustive de la matrice de candidats en testant directement la solution
    if (eval("return " . $evalstr . ';')) {
        $key = http_build_query($vrs);
        $matrice[$key] = $vrs;
    }
    return $vrs;
}

/**
 * reduire_jeu_solutions : renvoie un jeu de solutions dedoublonne et dont on supprime tous les modules inutiles
 * 
 * @param mixed $solutions
 * @param mixed $evaltest
 * @access public
 * @return void
 */
function reduire_jeu_solutions($solutions, $evaltest)
{
    $solutions_reduites = array();
    // reduit et dedoublonne le jeu de solutions
    foreach ($solutions as $solution) {
        $sol = reduire_solution($solution, $evaltest);
        ksort($sol);
        $cle_de_sol = serialize($sol);
        $solutions_reduites[$cle_de_sol] = $sol;
    }
    return array_values($solutions_reduites);
}

/**
 * reduire_solution : renvoie une solution reduite au maximum
 * 
 * @param mixed $vrs 
 * @param mixed $evalstr 
 * @access public
 * @return void
 */
function reduire_solution($solution, $evaltest)
{
    $modules_a_supprimer = array();
    foreach ($solution as $module => $version) {
        $vrs = $solution;
        $vrs[$module] = 'X';
        $solution_valide = eval("return " . $evaltest . ';');
        if ($solution_valide) {
            $modules_a_supprimer[] = $module;
        }
    }
    foreach ($modules_a_supprimer as $module) {
        unset($solution[$module]);
    }
    // un dernier test pour être sur !
    $vrs = $solution;
    $solution_valide = eval("return " . $evaltest . ';');
    if (!$solution_valide) {
        die('<strong>Attention, solution cassée. Ceci est un BUG, merci de prévenir QUAI13 !</strong>');
    }
    return $solution;
}

function getVersionsCandidates($module, $max_version = null, $min_version = 0, $toutes_versions = false)
{
    $module_versions_path = realpath(dirname(__FILE__) . '/tmp/' . $module . '/scripts/versions');
    // recupere les versions candidates
    $new_version_candidates = array();
    if ($handle = opendir($module_versions_path)) {
        while (false !== ($file = readdir($handle))) {
            // verifie si la version est bien du format 1.x
            if ($file != '.' && $file != '..' && ($toutes_versions || preg_replace('/\..*/', '', $file) == $max_version)) {
                if ($max_version !== null) {
                    if ((strnatcmp($file, $max_version) <= 0 && strnatcmp($min_version, $file) <= 0) || !$toutes_versions) {
                        $new_version_candidates[] = $file;
                    }
                } else {
                    $new_version_candidates[] = $file;
                }
            }
        }
        closedir($handle);
    }
    // trie en ordre naturel
    natsort($new_version_candidates);
    return array_values($new_version_candidates);
}

function installer_getModules($module_type = null)
{
    // cohérence des paramètres
    $types = array('share', 'local');
    if (isset($module_type) && !in_array($module_type, $types)) {
        return false;
    }
    $dir = '../app';
    $modules = array();
    foreach ($types as $type) {
        $modules[$type] = array();
        if (isset($module_type) && $type != $module_type) {
            continue;
        }
        if (!$dh = @opendir($dir . '/' . $type)) {
            continue;
        }
        while (false !== ($obj = readdir($dh))) {
            if (($obj == '.' || $obj == '..')
                || (substr($obj, 0, 1) == '.')
                || (!is_dir($dir . '/' . $type . '/' . $obj))) {
                continue;
            }
            $version = installer_getModuleVersion($obj, false, false);
            $modules[$type][$obj] = $version;
        }
        closedir($dh);
    }
    return $modules;
}

function installer_getPendingLocalUpgrades($module)
{
    // compare la version du module enregistree en BD aux upgrades dispo
    $module_db_version  = installer_getModuleVersion($module, false, true);
    $module_db_upgrades = installer_getModuleLocalUpgrades($module, $module_db_version);
    return $module_db_upgrades;
}

function installer_getModuleLocalUpgrades($module, $min_version = null)
{
    $dir = '../app/local/' . $module . '/upgrades';
    $upgrades = array();
    foreach (glob($dir . '/*.php') as $filepath) {
        if (is_file($filepath)) {
            $version = basename($filepath, '.php');
            if (version_compare($version, $min_version) > 0) {
                if (is_file($filepath)) {
                    $upgrades[$version] = $filepath;
                }
            }
        }
    }
    ksort($upgrades);
    return $upgrades;
}

function installer_getModuleLatestVersion($module)
{
    $versions = getVersionsCandidates($module, null, 0, true);
    return array_pop($versions);
}

/**
 * installer_getConfigFromDb : lit la config enregistrée dans la BD
 *                             (dans la table clementine_installer_modules)
 * 
 * @access public
 * @return void
 */
function installer_getConfigFromDb($type = null)
{
    global $db;
    $config = array();
    $sql = '
        SELECT `module`, `version`
          FROM `clementine_installer_modules`
    ';
    if (isset($type)) {
        $sql .= '
         WHERE `type` = "' . $type . '" 
        ';
    }
    if ($stmt = $db->query($sql)) {
        for (; $res = $stmt->fetch(); ) {
            $config[$res['module']] = $res['version'];
        }
    }
    ksort($config);
    return $config;
}

/**
 * installer_getModuleVersion : renvoie la version d'un module shared installé
 * 
 * @param mixed $module 
 * @access public
 * @return void
 */
function installer_getModuleVersion($module, $check_consistency = false, $get_from_installerdatabase = false)
{
    $module = preg_replace('/[^a-zA-Z0-9_]/S', '', $module);
    $types = array('share', 'local');
    $config = '';
    if ($check_consistency || $get_from_installerdatabase) {
        $config = installer_getConfigFromDb();
        if (count($config)) {
            if ($get_from_installerdatabase) {
                if (isset($config[$module])) {
                    return $config[$module];
                } else {
                    if (isset($_GET['debug']) && !$get_from_installerdatabase) {
                        echo '<br /><strong>Installer notice:</strong> "' . $module . '" has never been installed before<br />';
                    }
                    return false;
                }
            }
        } else {
            if (isset($_GET['debug'])) {
                // echo '<strong>Installer fatal error:</strong> missing table clementine_installer_modules. The database will be overwritten if this file does not exist';
            }
            return false;
        }
    }
    foreach ($types as $type) {
        $filepath = realpath(dirname(__FILE__) . '/../app/' . $type . '/' . $module);
        $config_ini = installer_getModuleConfig($filepath);
        if (is_array($config_ini)) {
            if ($type == 'share' && $check_consistency) {
                // si demande, on verifie que $config_ini['version'] est bien celui attendu
                if (is_array($config)) {
                    if (isset($config_ini['version']) && isset($config[$module])) {
                        if ($config_ini['version'] != $config[$module]) {
                            if (isset($_GET['debug'])) {
                                echo '<strong>Installer warning:</strong> "' . $module . '" installed sources (' . $config_ini['version'] . ') differ from the version expected by file table clementine_installer_modules (' . $config[$module] . ')';
                            }
                        }
                    } else {
                        if (isset($config_ini['version'])) {
                            if (isset($_GET['debug'])) {
                                echo '<br /><strong>Installer warning:</strong> "' . $module . '" sources installed (' . $config_ini['version'] . ') but not expected by table clementine_installer_modules<br />';
                            }
                        } elseif (isset($config[$module])) {
                            if (isset($_GET['debug'])) {
                                echo '<br /><strong>Installer warning:</strong> "' . $module . '" sources are wrong (version expected by table clementine_installer_modules is ' . $config[$module] . ')<br />';
                            }
                        } else {
                            if (isset($_GET['debug'])) {
                                echo '<br /><strong>Installer warning:</strong> "' . $module . '" not expected by table clementine_installer_modules, and module sources are wrong<br />';
                            }
                        }
                    }
                } else {
                    // pas de configuration enregistree... ?
                    echo '<br /><strong>Installer fatal error:</strong> incorrect table clementine_installer_modules<br />';
                    die();
                }
            }
            if (isset($config_ini['version'])) {
                return $config_ini['version'];
            }
        } else {
            if ($type == 'share' && $check_consistency) {
                if (is_array($config) && isset($config[$module])) {
                    if (isset($_GET['debug'])) {
                        echo '<br /><strong>Installer warning:</strong> "' . $module . '" sources not installed (version expected by table clementine_installer_modules is ' . $config[$module] . ')<br />';
                    }
                } else {
                    return false;
                }
            }
        }
    }
    return false;
}

function installer_getModuleConfig($ini_path, $specific_version = false)
{
    $fichier_ini = '/etc/module.ini';
    if (!is_file($ini_path . $fichier_ini)) {
        $fichier_ini = '/scripts/depends.ini';
    }
    if (is_file($ini_path . $fichier_ini)) {
        $config = array();
        // php < 5.3 compatibility
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $infos = parse_ini_file($ini_path . $fichier_ini, true, INI_SCANNER_RAW);
        } else {
            $infos = parse_ini_file($ini_path . $fichier_ini, true);
        }
        $version_majeure = $specific_version;
        if (!$specific_version) {
            if (isset($infos['version'])) {
                $config['version'] = $infos['version'];
                $version_majeure = (int) $config['version'];
            /*} else {*/
                /*echo '<strong>Installer warning:</strong> aucune information de version (ni fournie, ni trouvee)';*/
            }
        }
        if (isset($infos['weight'])) {
            $config['weight'] = (float) $infos['weight'];
        }
        // remise en forme des dependances
        if (isset($infos['depends_' . $version_majeure])) {
            foreach ($infos['depends_' . $version_majeure] as $modulename => &$moduleversions) {
                // si la version spécifiée est '*', on prend toutes les versions possibles
                if ($moduleversions == '*') {
                    $module_depends_file_path = realpath(dirname(__FILE__) . '/tmp/' . $modulename);
                    if (!$module_depends_file_path) {
                        global $mode_developpeur;
                        if (!$mode_developpeur) {
                            if (!update_module_repository($modulename)) {
                                return false;
                            }
                        }
                    }
                    $latest = (int) installer_getModuleLatestVersion($modulename);
                    $moduleversions = '';
                    for ($i = 1; $i < $latest; ++$i) {
                        $moduleversions .= $i . ',';
                    }
                    $moduleversions .= $latest;
                }
                $tmp = explode(',', $moduleversions);
                $tmp_array = array();
                foreach ($tmp as $val) {
                    $tmp_array[$val] = '';
                }
                $moduleversions = $tmp_array;
            }
            $depends = array();
            $depends[$version_majeure] = $infos['depends_' . $version_majeure];
            $config['depends_' . $version_majeure] = $depends;
        }
        return $config;
    }
    return false;
}

/**
 * installer_getModuleWeight : renvoie le poids d'un module installé
 * 
 * @param mixed $module 
 * @access public
 * @return void
 */
function installer_getModuleWeight($module)
{
    $module = preg_replace('/[^a-zA-Z0-9_]/S', '', $module);
    $types = array('share', 'local');
    foreach ($types as $type) {
        $filepath = realpath(dirname(__FILE__) . '/../app/' . $type . '/' . $module);
        $config_ini = installer_getModuleConfig($filepath);
        if (false === $config_ini) {
            return false;
        }
        if (is_array($config_ini) && isset($config_ini['weight'])) {
            return $config_ini['weight'];
        }
    }
    return false;
}

function isolated_include($file)
{
    global $db;
    return include($file);
}

/**
 * applyScriptUpgrades : applique toutes les mises a jour scriptees pour passer de la version $previous a la version $final
 *
 * Les mises a jour scriptees sont stockées dans /scripts/versions/$version
 * Les fichiers suivants sont susceptibles d'être appelés selon les cas :
 *      upgrade.php : upgrades depuis la version precedente (meme si elle n'a jamais ete installee)
 *          - s'il n'existe pas de version precedente, ce script servira donc de script d'installation (et non de mise a jour)
 *          - en cas de passage a une version majeure, si d'autres versions mineures de la version précédente sont publiées à postériori, la présence de fichier upgrade-to-X permet de conserver la compatibilité)
 *      upgrade-to-X.php : upgrades permettant de passer directement à la version X (sautant ainsi les upgrades intermediaires)
 *          - les upgrades-to-X ne seront appliquées que si la version X est incluse dans les mises à jour permettant d'arriver à la version $final
 *
 * @param mixed $previous 
 * @param mixed $final 
 * @access public
 * @return void
 */
function applyScriptUpgrades($module, $previous, $final)
{
    // passe la connexion a la base de donnees aux scripts inclus
    global $db;
    if (isset($_GET['debug'])) {
        if ($previous) {
            echo "Upgrading <strong>$module</strong> to $final (from $previous)<br />";
        } else {
            echo "Installing <strong>$module</strong> $final<br />";
        }
    }
    $new_version_candidates = getVersionsCandidates($module, $final, $previous, true);
    $prev = $previous;
    $direct = 0;
    $ret = 0;
    foreach ($new_version_candidates as $version) {
        if ($version == $prev) {
            continue;
        }
        if (strnatcmp($version, $direct) <= 0) {
            $prev = $direct;
            continue;
        } else {
            $direct = 0;
        }
        if (isset($_GET['debug'])) {
            echo "---- $prev => $version<br />";
        }
        $scriptupgrades = 'tmp/' . $module . '/scripts/versions/' . $version . '/upgrade.php';
        if (is_file($scriptupgrades)) {
            if (isset($_GET['debug'])) {
                echo "---- applique $scriptupgrades<br />";
            }
            $db->beginTransaction();
            $next = isolated_include($scriptupgrades);
            // applique les remplacements de fichiers si necessaire
            $mise_a_jour_fichiers = 0;
            if (!empty($next['replace'])) {
                $mise_a_jour_fichiers = 1;
                echo '<pre>';
                echo "Mise à jour du code php des modules locaux ($module $prev => $version)<br />";
                $error = 0;
                $backups = array();
                $fichiers_modifies = array();
                foreach ($next['replace'] as $path => $search_replace) {
                    // liste tous les fichiers php du dossier (récursivement)
                    $fichiers = recursive_glob($path, '*.php', 0, -1);
                    foreach ($fichiers as $fich) {
                        foreach ($search_replace as $search => $replace) {
                            $retpreg = preg_replace_infile($fich, $search, $replace);
                            if (false === $retpreg) {
                                $error = 1;
                                break 3;
                            }
                            if (!isset($backups[$fich])) {
                                $backups[$fich] = $retpreg['backup_file'];
                            }
                            if ($retpreg['nb_replacements']) {
                                if (!isset($fichiers_modifies[$fich])) {
                                    $fichiers_modifies[$fich] = 0;
                                }
                                $fichiers_modifies[$fich] += $retpreg['nb_replacements'];
                            }
                        }
                    }
                }
                if ($error) {
                    $restore_error = 0;
                    echo "<br />=> <strong>erreur</strong><br />";
                    echo "<br />Restauration des backups <br />";
                    // restaure les backups
                    foreach ($backups as $orig_fich => $backup_file) {
                        if (false === copy($backup_file, $orig_fich)) {
                            $restore_error = 1;
                        }
                    }
                    if (!$restore_error) {
                        echo "<br />=> <strong>OK</strong><br />";
                    } else {
                        echo "<br />=> <strong>erreur</strong><br />";
                    }
                    $next = false;
                }
            }
            if (!empty($next['replace'])) {
                echo "<br />=> <strong>OK (" . array_sum($fichiers_modifies) . " remplacements effectués, " . count($fichiers_modifies) . " fichiers modifiés)</strong><br />";
                if (isset($_GET['debug'])) {
                    echo "<br /><strong>Fichiers modifiés</strong> => nb_remplacements<br />";
                    echo '<pre>';
                    print_r($fichiers_modifies);
                    echo '</pre>';
                }
            }
            if (!$next) {
                echo '<br /><span class="err">Erreur lors du chargement de ' . $scriptupgrades . ' </span><br />';
            }
            if ($mise_a_jour_fichiers) {
                echo '</pre>';
            }
            if (!$next) {
                // $db->rollBack(); // deja gere par les scripts d'upgrade
                $ret = $version;
                break;
            }
            $db->commit();
        }
        // saute les upgrades intermediaires si upgrade-to-X possible
        if (version_compare($final, $version) > 0) {
            $basepath = 'tmp/' . $module . '/scripts/versions/' . $version . '/upgrade-to-';
            $upgradeto = glob($basepath . '*.php');
            // tronque les valeurs pour ignorer le ".php" final
            array_walk($upgradeto, create_function('&$val', '$val = substr($val, ' . strlen($basepath) . ', -4);'));
            usort($upgradeto, 'strnatcmp');
            // ignorer les upgrades directs vers des version au dela de $final
            array_walk($upgradeto, create_function('&$val', '$val = (strnatcmp("$val", "' . $final . '") > 0) ? 0 : $val;'));
            $tounset = array_keys($upgradeto, 0);
            foreach ($tounset as $keyunset) {
                unset($upgradeto[$keyunset]);
            }
            if (count($upgradeto)) {
                $direct = array_pop($upgradeto);
            }
            if ($direct) {
                $scriptupgrades_direct = 'tmp/' . $module . '/scripts/versions/' . $version . '/upgrade-to-' . $direct . '.php';
                if (isset($_GET['debug'])) {
                    echo "---- applique direct $scriptupgrades_direct<br />";
                }
                $db->beginTransaction();
                $next_direct = isolated_include($scriptupgrades_direct);
                // applique les remplacements de fichiers si necessaire
                $mise_a_jour_fichiers_direct = 0;
                if (!empty($next_direct['replace'])) {
                    $mise_a_jour_fichiers_direct = 1;
                    echo '<pre>';
                    echo "Mise à jour directe du code php des modules locaux ($module $prev => $version)<br />";
                    $error = 0;
                    $backups = array();
                    $fichiers_modifies = array();
                    foreach ($next_direct['replace'] as $path => $search_replace) {
                        // liste tous les fichiers php du dossier (récursivement)
                        $fichiers = recursive_glob($path, '*.php', 0, -1);
                        foreach ($fichiers as $fich) {
                            foreach ($search_replace as $search => $replace) {
                                $retpreg = preg_replace_infile($fich, $search, $replace);
                                if (false === $retpreg) {
                                    $error = 1;
                                    break 3;
                                }
                                if (!isset($backups[$fich])) {
                                    $backups[$fich] = $retpreg['backup_file'];
                                }
                                if ($retpreg['nb_replacements']) {
                                    if (!isset($fichiers_modifies[$fich])) {
                                        $fichiers_modifies[$fich] = 0;
                                    }
                                    $fichiers_modifies[$fich] += $retpreg['nb_replacements'];
                                }
                            }
                        }
                    }
                    if ($error) {
                        $restore_error = 0;
                        echo "<br />=> <strong>erreur</strong><br />";
                        echo "<br />Restauration des backups <br />";
                        // restaure les backups
                        foreach ($backups as $orig_fich => $backup_file) {
                            if (false === copy($backup_file, $orig_fich)) {
                                $restore_error = 1;
                            }
                        }
                        if (!$restore_error) {
                            echo "<br />=> <strong>OK</strong><br />";
                        } else {
                            echo "<br />=> <strong>erreur</strong><br />";
                        }
                        $next_direct = false;
                    }
                }
                if (!empty($next_direct['replace'])) {
                    echo "<br />=> <strong>OK (" . array_sum($fichiers_modifies) . " remplacements effectués, " . count($fichiers_modifies) . " fichiers modifiés)</strong><br />";
                    if (isset($_GET['debug'])) {
                        echo "<br /><strong>Fichiers modifiés</strong> => nb_remplacements<br />";
                        echo '<pre>';
                        print_r($fichiers_modifies);
                        echo '</pre>';
                    }
                }
                if (!$next_direct) {
                    echo '<br /><span class="err">Erreur lors du chargement de ' . ($scriptupgrades_direct). ' </span><br />';
                }
                if ($mise_a_jour_fichiers_direct) {
                    echo '</pre>';
                }
                if (!$next_direct) {
                    // $db->rollBack(); // deja gere par les scripts d'upgrade
                    $ret = $version;
                    break;
                }
                $db->commit();
                $prev = $version;
                continue;
            }
        }
        $prev = $version;
    }
    return $ret;
}

function update_module_repository($module)
{
    $module = preg_replace('/[^a-zA-Z0-9_]/S', '', $module);
    $path = 'tmp/' . $module;
    if (!is_dir($path)) {
        mkdir($path, 0755);
        @chmod($path, 0755);
    }
    $from = $_SERVER['SERVER_NAME'] . preg_replace('@/install/.*@', '', $_SERVER['REQUEST_URI']); // juste pour anticiper l'impact des mises à jour d'un module, point trop d'indiscrétion
    $from_version = installer_getModuleVersion($module);
    $src = __CLEMENTINE_REPOSITORY_URL__ . '/clementine-framework-module-' . $module . '-scripts/archive/master.zip';
    // recupere le fichier depends.ini par telechargement
    $dst = $path . '/scripts.zip';
    if (!dlcopy($src, $dst)) {
        echo $src;
        echo ' : erreur';
        echo "<br />";
        return false;
    } else {
        if (!unzip($dst, $path)) {
            if (isset($_GET['debug'])) {
                echo $src;
                echo ' : ok';
                echo "<br />";
            }
            rename($path . '/clementine-framework-module-' . $module . '-scripts-master', $path . '/scripts');
            unlink($dst);
        } else {
            echo $src;
            echo ' : erreur';
            echo "<br />";
            return false;
        }
    }
    return true;
}

function maj_installeur_dispo()
{
    $from = $_SERVER['SERVER_NAME'] . preg_replace('@/install/.*@', '', $_SERVER['REQUEST_URI']); // juste pour anticiper l'impact des mises à jour, point trop d'indiscrétion
    $src = str_replace('//github.com', '//raw.github.com', __CLEMENTINE_REPOSITORY_URL__) . '/clementine-framework-installer/master/install_latest.txt';
    $dst = 'install_latest.txt';
    if (!dlcopy($src, $dst)) {
        echo $src;
        echo ' : erreur';
        echo "<br />";
        return false;
    }
    if (file_exists('install_version.txt')) {
        $current = file_get_contents('install_version.txt');
        $latest = file_get_contents($dst);
        if ($latest > $current) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function write_ini_file($assoc_array, $file, $header = null, $no_quotes = 0)
{
    $res = array();
    foreach ($assoc_array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval) {
                if ($no_quotes) {
                    $res[] = "$skey=" . (is_numeric($sval) ? $sval : $sval);
                } else {
                    $res[] = "$skey=" . (is_numeric($sval) ? $sval : '"' . $sval . '"');
                }
            }
        } else {
            if ($no_quotes) {
                $res[] = "$key=" . (is_numeric($val) ? $val : $val);
            } else {
                $res[] = "$key=" . (is_numeric($val) ? $val : '"' . $val . '"');
            }
        }
    }
    // safe fwrite
    if ($fp = fopen($file, 'w')) {
        $startTime = microtime();
        $canWrite = 0;
        while ((!$canWrite) && ((microtime() - $startTime) < 1000)) {
            $canWrite = flock($fp, LOCK_EX);
            // if lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) {
                usleep(round(rand(0, 100)*1000));
            }
        }
        // file was locked so now we can store information
        if ($canWrite) {
            if ($header) {
                fwrite($fp, $header . "\n");
            }
            fwrite($fp, implode("\n", $res));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    }
    return false;
}

// fonctions recuperee de wordpress, corrigees et adaptees
function unzip($file, $to)
{
    // Unzip can use a lot of memory, but not this much hopefully
    // @ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
    $needed_dirs = array();
    $to = rtrim($to, '/') . '/';
    // Determine any parent dir's needed (of the upgrade directory)
    if (!is_dir($to)) { //Only do parents if no children exist
        $path = preg_split('![/\\\]!', rtrim($to, '/'));
        for ($i = count($path); $i >= 0; --$i) {
            if (empty($path[$i])) {
                continue;
            }
            $dir = implode('/', array_slice($path, 0, $i+1) );
            // Skip it if it looks like a Windows Drive letter.
            if (preg_match('!^[a-z]:$!i', $dir)) {
                continue;
            }
            if (!is_dir($dir)) {
                $needed_dirs[] = $dir;
            } else {
                break; // A folder exists, therefor, we dont need the check the levels below this
            }
        }
    }
    if (class_exists('ZipArchive')) {
        $result = _unzip_file_ziparchive($file, $to, $needed_dirs);
        if (!$result) {
            return $result;
        }
    }
    // Fall through to PclZip if ZipArchive is not available, or encountered an error opening the file.
    $res = _unzip_file_pclzip($file, $to, $needed_dirs);
    if ($res) {
        print_r($res);
    }
    return $res;
}

// fonctions recuperee de wordpress, corrigees et adaptees
function _unzip_file_ziparchive($file, $to, $needed_dirs = array())
{
    $z = new ZipArchive();
    // PHP4-compat - php4 classes can't contain constants
    $zopen = $z->open($file, /* ZIPARCHIVE::CHECKCONS */ 4);
    if (true !== $zopen) {
        return array('incompatible_archive', 'Incompatible Archive.');
    }
    for ($i = 0; $i < $z->numFiles; ++$i) {
        if (!$info = $z->statIndex($i)) {
            return array('stat_failed', 'Could not retrieve file from archive.');
        }
        if ('__MACOSX/' === substr($info['name'], 0, 9)) { // Skip the OS X-created __MACOSX directory
            continue;
        }
        if ('/' == substr($info['name'], -1)) { // directory
            $needed_dirs[] = $to . rtrim($info['name'], '/');
        } else {
            $needed_dirs[] = $to . rtrim(dirname($info['name']), '/');
        }
    }
    $needed_dirs = array_unique($needed_dirs);
    foreach ($needed_dirs as $dir) {
        // Check the parent folders of the folders all exist within the creation array.
        if (rtrim($to, '/') == $dir) { // Skip over the working directory, We know this exists (or will exist)
            continue;
        }
        if (strpos($dir, $to) === false) { // If the directory is not within the working directory, Skip it
            continue;
        }
        $parent_folder = dirname($dir);
        while (!empty($parent_folder) && rtrim($to, '/') != $parent_folder && !in_array($parent_folder, $needed_dirs)) {
            $needed_dirs[] = $parent_folder;
            $parent_folder = dirname($parent_folder);
        }
    }
    asort($needed_dirs);
    // Create those directories if need be:
    foreach ($needed_dirs as $_dir) {
        if (!is_dir($_dir) && !mkdir($_dir, 0755) && !is_dir($_dir)) { // Only check to see if the Dir exists upon creation failure. Less I/O this way.
            return array('mkdir_failed', 'Could not create directory.', $_dir);
        } else {
            @chmod($_dir, 0755);
        }
    }
    unset($needed_dirs);
    for ($i = 0; $i < $z->numFiles; ++$i) {
        if (!$info = $z->statIndex($i)) {
            return array('stat_failed', 'Could not retrieve file from archive.');
        }
        if ('/' == substr($info['name'], -1)) { // directory
            continue;
        }
        if ('__MACOSX/' === substr($info['name'], 0, 9)) { // Don't extract the OS X-created __MACOSX directory files
            continue;
        }
        $contents = $z->getFromIndex($i);
        if (false === $contents) {
            return array('extract_failed', 'Could not extract file from archive.', $info['name']);
        }
        if (false === file_put_contents($to . $info['name'], $contents, 0755)) {
            return array('copy_failed', 'Could not copy file.', $to . $info['filename']);
        }
    }
    $z->close();
    return 0;
}

// fonctions recuperee de wordpress, corrigees et adaptees
function _unzip_file_pclzip($file, $to, $needed_dirs = array())
{
    // See #15789 - PclZip uses string functions on binary data, If it's overloaded with Multibyte safe functions the results are incorrect.
    if (ini_get('mbstring.func_overload') && function_exists('mb_internal_encoding')) {
        $previous_encoding = mb_internal_encoding();
        mb_internal_encoding('ISO-8859-1');
    }
    require_once('class-pclzip.php');
    $archive = new PclZip($file);
    $archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
    if (isset($previous_encoding)) {
        mb_internal_encoding($previous_encoding);
    }
    // Is the archive valid?
    if (!is_array($archive_files)) {
        return array ('incompatible_archive', 'Incompatible Archive.', $archive->errorInfo(true));
    }
    if (0 == count($archive_files)) {
        return array('empty_archive', 'Empty archive.');
    }
    // Determine any children directories needed (From within the archive)
    foreach ($archive_files as $file) {
        if ('__MACOSX/' === substr($file['filename'], 0, 9)) { // Skip the OS X-created __MACOSX directory
            continue;
        }
        $needed_dirs[] = $to . rtrim( $file['folder'] ? $file['filename'] : dirname($file['filename']) , '/');
    }
    $needed_dirs = array_unique($needed_dirs);
    foreach ($needed_dirs as $dir) {
        // Check the parent folders of the folders all exist within the creation array.
        if (rtrim($to, '/') == $dir) {// Skip over the working directory, We know this exists (or will exist)
            continue;
        }
        if (strpos($dir, $to) === false) { // If the directory is not within the working directory, Skip it
            continue;
        }
        $parent_folder = dirname($dir);
        while (!empty($parent_folder) && rtrim($to, '/') != $parent_folder && !in_array($parent_folder, $needed_dirs)) {
            $needed_dirs[] = $parent_folder;
            $parent_folder = dirname($parent_folder);
        }
    }
    asort($needed_dirs);
    // Create those directories if need be:
    foreach ($needed_dirs as $_dir) {
        if (!mkdir($_dir, 0755) && ! is_dir($_dir)) { // Only check to see if the dir exists upon creation failure. Less I/O this way.
            return array('mkdir_failed', 'Could not create directory.', $_dir);
        } else {
            @chmod($_dir, 0755);
        }
    }
    unset($needed_dirs);
    // Extract the files from the zip
    foreach ($archive_files as $file) {
        if ($file['folder']) {
            continue;
        }
        if ('__MACOSX/' === substr($file['filename'], 0, 9)) { // Don't extract the OS X-created __MACOSX directory files
            continue;
        }
        if (false === file_put_contents( $to . $file['filename'], $file['content'], 0755)) {
            return array('copy_failed', 'Could not copy file.', $to . $file['filename']);
        }
    }
    return 0;
}

/**
 * recursive_glob : recherche récursive de fichiers selon un masque
 *                  (inspiré de la commande GNU "find")
 *
 * @param mixed $path
 * @param string $pattern
 * @param int $flags
 * @param mixed $depth
 * @access public
 * @return void
 */
function recursive_glob($path, $pattern = '*', $flags = 0, $depth = -1)
{
    $matches = array();
    $folders = array(rtrim($path, DIRECTORY_SEPARATOR));
    while($folder = array_shift($folders)) {
        $matches = array_merge($matches, glob($folder.DIRECTORY_SEPARATOR.$pattern, $flags));
        if($depth != 0) {
            $moreFolders = glob($folder.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
            $depth   = ($depth < -1) ? -1: $depth + count($moreFolders) - 2;
            $folders = array_merge($folders, $moreFolders);
        }
    }
    return $matches;
}

/**
 * preg_replace_infile : chercher remplacer dans un fichier, avec sauvegarde du fichier d'origine
 *                       (inspiré de la commande GNU "sed -i")
 *
 * @param mixed $filepath
 * @param mixed $pattern
 * @param mixed $replacement
 * @param mixed $backup_dir : si fourni, un backup du fichier modifié sera conservé dans ce répertoire
 *                            le nom du fichier backup reprend le $filepath fourni, encodé en base64
 * @access public
 * @return void
 */
function preg_replace_infile($filepath, $pattern, $replacement, $backup_dir = 'clementine_installer_default_backup_dir')
{
    $content = file_get_contents($filepath);
    $backup_filename = null;
    if ($backup_dir) {
        if ($backup_dir == 'clementine_installer_default_backup_dir') {
            $backup_dir = __INSTALLER_ROOT__ . DIRECTORY_SEPARATOR . 'save';
        }
        if (!empty($_SERVER['REQUEST_TIME'])) {
            $backup_dir .= DIRECTORY_SEPARATOR . date('Y-m-d.H-i-s', $_SERVER['REQUEST_TIME']);
        }
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755);
        }
        // remplate '=' par '_' pour eviter d'avoir a echapper le nom de fichier...
        // il faudra faire le remplacement inverse avant de pouvoir faire le base64_decode
        $backup_filename  = $backup_dir . DIRECTORY_SEPARATOR;
        $backup_filename .= str_replace('=', '_', base64_encode($filepath));
    }
    $count = 0;
    // changement de $request : le tableau est remplace par un opjet
    $backup_content = $content;
    $content = preg_replace($pattern, $replacement, $content, -1, $count);
    if ($count) {
        // s'il y a eu des modifs, sauvegarde le fichier avant de les enregistrer (si pas déjà fait)
        if ($backup_dir && !file_exists($backup_filename)) {
            if (false === file_put_contents($backup_filename, $backup_content)) {
                // false : indique qu'une erreur s'est produite (impossible d'ecrire un backup)
                trigger_error('<br />Impossible de sauver le fichier backup ' . $backup_filename . ' pour ' . $filepath . '<br />');
                return false;
            }
        }
        if (false === file_put_contents($filepath, $content)) {
            // false : indique qu'une erreur s'est produite (impossible d'ecrire les modifications)
            trigger_error('<br />Impossible de sauver le fichier modifié ' . $filename . '<br />');
            return false;
        }
    }
    return array(
        'nb_replacements' => $count,
        'backup_file' => $backup_filename
    );
}

?>
