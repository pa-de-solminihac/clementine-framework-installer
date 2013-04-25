<?php
require('dbcheck.php');
if (CLEMENTINE_INSTALLER_DISABLE || !$db) {
    header('Location: index.php' , true, 302);
    die();
}
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Installation de Clémentine</title>
    <link rel="stylesheet" href="style.css" type="text/css" media="screen" />
<script type="text/javascript" charset="utf-8">
function toggle (E1) {
    var elt = document.getElementById(E1);
    var E1_legend = document.getElementById(E1 + '_legend');
    if (E1_legend.className=='deplie' || E1_legend.className=='replie') {
        elt.style.display = (elt.style.display == 'block' ? 'none' : 'block');
        E1_legend.className = (elt.style.display == 'block' ? 'replie' : 'deplie');
    }
}
</script>
</head>
<body>
    <div id="wrapper">
        <div id="main">
            <h2>Installation ou mise à jour<br />
                <a href="../" target="_blank">voir le site &rarr;</a></h2>
            <a href="http://clementine.quai13.com"><img src="logo.jpg" alt="logo clémentine framework" /></a>
<?php
require('repocheck.php');
?>
            <fieldset>
                <legend id="apercu_legend" class="<?php
if (isset($_GET['confirm']) && !isset($_GET['debug'])) {
    echo 'deplie';
}
                ?>" onclick="toggle('apercu');">Aperçu des modifications</legend>
                <div id="apercu" style="display: <?php 
if (isset($_GET['confirm']) && !isset($_GET['debug'])) {
    echo 'none';
}
                ?>">


<?php

echo '<h4>Connexion à la base de données</h4>';

if ($db) {
    echo '<span>La connexion à la base de données fonctionne. </span>';

    // sauvegarde la base de données si possible
    if (!ini_get('safe_mode') && function_exists('exec') && is_callable('exec') && __CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__ && is_file(__CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__)) {
        touch('tmp/.my.cnf');
        @chmod('tmp/.my.cnf', 0600);
        $_my_cnf = array('client' => array('password' => $site_config['clementine_db']['pass']));
        write_ini_file($_my_cnf, 'tmp/.my.cnf', null, 1);
        $retour = 0;
        $commande  = __CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__;
        $commande .= ' --defaults-file=' . realpath(dirname(__FILE__)) . '/tmp/.my.cnf';
        $commande .= ' -u ' . escapeshellcmd($site_config['clementine_db']['user']); // pas de flag -R car il demande trop de privileges
        $commande .= ' -B ' . escapeshellcmd($site_config['clementine_db']['name']); // flag -B pour drop et re-create database
        $commande .= ' --result-file=' . realpath(dirname(__FILE__)) . '/save/dump.sql';
        $tab_retours = array ();
        exec($commande, $tab_retours, $retour);
        if (!$retour) {
            echo '<span class="ok">(sauvegarde effectuée)</span>';
        } else {
            echo '<span class="warn">(échec de la sauvegarde)</span>';
            if (isset($_GET['debug'])) {
                echo '<pre>';
                echo $commande . "\n";
                print_r($tab_retours);
                echo '</pre>';
            }
        }
    } else {
        echo '<p class="warn"><span>Exécutable mysqldump non trouvé</span></p>';
    }
    if (!(!ini_get('safe_mode') && function_exists('exec') && is_callable('exec') && __CLEMENTINE_INSTALLER_PATH_TO_MYSQL__ && is_file(__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__))) {
        echo '<p class="warn"><span>Exécutable mysql non trouvé, la restauration ne sera pas automatique</span></p>';
    }

}

if (count($dberrors)) {
    foreach ($dberrors as $key => $errmsg) {
?>
<p class="err">
<?php
        echo $errmsg;
?>
</p>
<?php 
    }
}
?>

                <h4>Résolution des dépendances</h4>
<?php
// crée l'arbre des dépendances, et enregistre les modules et versions possibles
if (isset($_GET['debug'])) {
    echo "Récupération des méta données";
    echo "<pre>";
}
if (!is_dir('tmp')) {
    header('Location: index.php' , true, 302);
    die();
}

// verifie si les modules installes sont bien ceux enregistrés en BD et si les versions concordent
$config_from_db = installer_getConfigFromDb('share');
$config_from_fs = installer_getModules();
$incoherences = 0;
foreach ($config_from_db as $module => $version) {
    $found = installer_getModuleVersion($module, true, false);
    if (!$version || $version != $found) {
        ++$incoherences;
    }
}
foreach ($config_from_fs['share'] as $module => $version) {
    $found = installer_getModuleVersion($module, true, false);
    if (!$version || $version != $found) {
        ++$incoherences;
    }
}

if ($incoherences) {
    if (!(isset($_GET['reinstall']) && $_GET['reinstall'] == 1)) {
        echo '<span class="warn">Il y a une différence entre les versions attendues et installées... <br />vous devriez <a href="update.php?reinstall=1';
        if (isset($_GET['debug'])) {
            echo  '&debug';
        }
        echo '">réinstaller</a> plutôt que mettre à jour dans un premier temps</span><br /><br />';
    }
}

// verifie s'il y a des mises a jour de db à appliquer (pour les modules locaux uniquement, ca ne concerne pas les modules share)
$local_module_upgrades = array();
foreach ($config_from_fs['local'] as $module => $version) {
    $ups = installer_getPendingLocalUpgrades($module);
    if ($ups) {
        foreach ($ups as $upsdate => $upsfile) {
            if (!isset($local_module_upgrades[$upsdate])) {
                $local_module_upgrades[$upsdate] = array();
            }
            if (!isset($local_module_upgrades[$upsdate][$module])) {
                $local_module_upgrades[$upsdate][$module] = array();
            }
            $local_module_upgrades[$upsdate][$module] = $upsfile;
        }
    }
}
ksort($local_module_upgrades);
if (isset($_GET['debug']) && count($local_module_upgrades)) {
    echo '<strong>Local module upgrades</strong>';
    echo '<pre>';
    print_r($local_module_upgrades);
    echo '</pre>';
}
if (count($local_module_upgrades)) {
    echo '<span class="warn">Des mises à jour de base de données des modules locaux doivent être appliquées avant de continuer.</span><br /><br />';
}

// si reinstall, on fixe les versions choisies à celles attendues par la BD
if (isset($_GET['reinstall']) && $_GET['reinstall'] == 1 || count($local_module_upgrades)) {
    $versions_choisies = $config_from_db;
    $premiere_solution = $config_from_db;
    if (count($versions_choisies)) {
        echo '<span>';
        echo 'Pas de résolution de dépendances pour une réinstallation ou des mises à jour de la base de données. ';
        echo '</span>';
?>
                <h4>Versions des modules</h4>
<table>
    <tr>
        <th>Module</th>
        <th>Infos</th> <!-- à installer, installé, maj dispo, install non terminée, plus utilisé, aucune version dispo -->
        <th></th>
    </tr>
<?php
        foreach ($versions_choisies as $module => $new_version) {
            if ($module != 'site') {
                if (!isset($current_local_modules[$module])) {
                    echo '<tr>';
                    $previous_version = installer_getModuleVersion($module, true, true);
                    $latest = installer_getModuleLatestVersion($module);
                    $msg_latest = '';
                    $max_new_version = $versions_choisies[$module];
                    if (strnatcmp($latest, $max_new_version) > 0 && strnatcmp($latest, $previous_version) > 0) {
                        $msg_latest = '<span class="warn">' . $latest . ' plus récent' . '</span>';
                    }
                    if (version_compare($max_new_version, $previous_version) > 0) {
                        if ($previous_version) {
                            echo "<td><span>$module</span></td>";
                            echo '<td class="versions_module"><span>' . $previous_version . ' installé</span> <span class="ok">' . $max_new_version . ' disponible</span> ' . $msg_latest . '</td>';
                        } else {
                            echo "<td><span>$module</span></td>";
                            echo '<td class="versions_module"><span class="warn">non installé</span> <span class="ok">' . $max_new_version . ' disponible</span> ' . $msg_latest . '</td>';
                        }
                    } else {
                        echo "<td><span>$module</span></td>";
                        echo '<td class="versions_module"><span class="warn">' . $previous_version . ' à réinstaller</span> ' . $msg_latest . '</td>';
                    }
                    echo '<td></td>';
                    echo "</tr>\n";
                }
            }
        }
?>
</table>
<?php
    } else {
        echo '<span>';
        echo 'Pas de configuration enregistrée en base de données. Réinstallation impossible. ';
        echo '</span>';
        die();
    }
} else {
    $res = register_dependencies();
    if (isset($_GET['debug'])) {
        echo "</pre>";
    }
    if (!empty($res['allversions'])) {
        if (count($res)) {
            echo '<span>';
            echo 'Récupération des méta données terminée. ';
            echo '</span>';
        }
        $dependances = $res['deps'];
        $allversions = $res['allversions'];

        if (isset($_GET['debug'])) {
            echo '<p>';
            echo "Dépendances";
            echo '<pre>';
            print_r_array($dependances['site'][1]); // par convention (over configuration) le module du site est dans app/local/site et il est en version 1
            echo '</pre>';
            echo '</p>';
        }

        // traduit l'arbre des dependances en conditions evaluables par PHP
        $evalstr = translate_dependencies($dependances);

        // cree la table de vérité de ce systeme d'equations et renvoie la première solution valide rencontrée par la même occasion
        $solution = array();
        creer_matrice_candidats($allversions, $evalstr, $solution);

        // reduit le jeu de solutions : supprime les modules inutiles et dédoublonne
        $solution_reduite = reduire_jeu_solutions($solution, $evalstr);
        $premiere_solution = array_shift($solution_reduite);
        if (count($premiere_solution)) {
            echo '<br />';
            echo '<span>';
            echo count($solution) . ' solutions trouvées. ';
            echo '</span>';
            if (isset($_GET['debug'])) {
                echo '<p>Liste des solutions possibles : </p>';
                echo '<pre>';
                print_r($solution);
                echo '</pre>';
            }
        }
    }

?>
                <h4>Versions des modules</h4>
<?php

    // Choix de la premiere solution
    if (isset($premiere_solution) && is_array($premiere_solution) && count($premiere_solution)) {
?>
<table>
    <tr>
        <th>Module</th>
        <th>Infos</th> <!-- à installer, installé, maj dispo, install non terminée, plus utilisé, aucune version dispo -->
        <th></th>
    </tr>
<?php
        $versions_choisies = array();
        foreach ($premiere_solution as $module => $new_version) {
            if ($module != 'site') {
                if (!isset($current_local_modules[$module])) {
                    echo '<tr>';
                    $previous_version = installer_getModuleVersion($module, true);
                    // recupere les versions candidates
                    $new_version_candidates = getVersionsCandidates($module, $new_version);
                    $msg_latest = '';
                    if (count($new_version_candidates)) {
                        $max_new_version = $new_version_candidates[count($new_version_candidates) - 1];
                        $latest = installer_getModuleLatestVersion($module);
                        if (strnatcmp($latest, $max_new_version) > 0 && strnatcmp($latest, $previous_version) > 0) {
                            $msg_latest = '<span class="warn">' . $latest . ' plus récent' . '</span>';
                        }
                        if (version_compare($max_new_version, $previous_version) > 0) {
                            $versions_choisies[$module] = $max_new_version;
                            if ($previous_version) {
                                echo "<td><span>$module</span></td>";
                                echo '<td class="versions_module"><span>' . $previous_version . ' installé</span> <span class="ok">' . $max_new_version . ' disponible</span> ' . $msg_latest . '</td>';
                            } else {
                                echo "<td><span>$module</span></td>";
                                echo '<td class="versions_module"><span class="warn">non installé</span> <span class="ok">' . $max_new_version . ' disponible</span> ' . $msg_latest . '</td>';
                            }
                        } else {
                            $versions_choisies[$module] = $previous_version;
                            echo "<td><span>$module</span></td>";
                            echo '<td class="versions_module"><span>' . $previous_version . ' installé</span> ' . $msg_latest . '</td>';
                        }
                    } else {
                        echo "<td><span>$module</span></td>";
                        echo '<td class="versions_module"><span class="err">aucune version disponible</span> ' . $msg_latest . '</td>';
                    }
                    echo '<td></td>';
                    echo "</tr>\n";
                }
            }
        }
?>
</table>
<?php
    } else {
        if (isset($premiere_solution)) {
            echo 'Pas de solution trouvée dans l\'arbre des dépendances. ';
            if (!isset($_GET['debug'])) {
                echo 'Il faut chercher pourquoi en <a href="?debug" target="">activant le debug</a> !';
            }
            die();
        } else {
            echo 'Problème de téléchargement rencontré. ';
            if (!isset($_GET['debug'])) {
                echo '<br /><br /><div class="boutons">
                    <a class="prev" href="update.php" target="">Relancer l\'installeur</a>
                </div>';
            }
            die();
        }
    }
}

if (count($local_module_upgrades)) {
    ++$incoherences;
?>
                <h4>Mises à jour de modules locaux</h4>
<table>
    <tr>
        <th>Version</th>
        <th>Module</th>
        <th></th>
    </tr>
<?php
    foreach ($local_module_upgrades as $version => $modules) {
        foreach ($modules as $module => $filepath) {
?>
    <tr>
        <td><?php echo '<span>' . $version . '</span>'; ?></td>
        <td><?php echo '<span>' . $module . '</span>'; ?></td>
        <td><?php echo '<span class="warn">mise à jour à appliquer </span><br />'; ?></td>
    </tr>
<?php
        }
    }
?>
</table>
<?php
}

$overrides = array();
foreach ($versions_choisies as $module => $version) {
    $overrides[$module] = installer_getModuleWeight($module);
}
asort($overrides);

// appelle l'etape suivante
require('download.php');
?>
