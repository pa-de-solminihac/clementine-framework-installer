<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
$dlerrors = 0;
$unziperrors = 0;
// appelle l'etape suivante
if (isset($_GET['confirm'])) {
?>
                <h4>Téléchargement des modules</h4>
<?php
    if (strlen(__CLEMENTINE_REPOSITORY_URL__) && !$mode_developpeur) {
        // Téléchargement
        if (isset($_GET['debug'])) {
            echo "Téléchargement";
            echo "<pre>";
        }
        foreach ($versions_choisies as $module => $version) {
            if (!isset($current_local_modules[$module]) /* || $overrides[$module] != 'local' */) {
                $src = __CLEMENTINE_REPOSITORY_URL__ . '/clementine-framework-module-' . $module . '/archive/' . $version . '.zip';
                $dst = 'tmp/' . $module . '/' . $module . '-' . $version . '.zip';
                // cree le dossier destination s'il n'existe pas
                $path = 'tmp/' . $module;
                if (!is_dir($path)) {
                    mkdir($path, 0755);
                    @chmod($path, 0755);
                }
                if (!dlcopy($src, $dst)) {
                    ++$dlerrors;
                    echo $src;
                    echo ' : erreur';
                    echo "<br />";
                } else {
                    if (isset($_GET['debug'])) {
                        echo $src;
                        echo ' : ok';
                        echo "<br />";
                    }
                }
            }
        }
        if (isset($_GET['debug'])) {
            echo "</pre>";
        }

        // Extraction
        if (!$dlerrors) {
            if (isset($_GET['debug'])) {
                echo "Décompression";
                echo "<pre>";
            }
            foreach ($versions_choisies as $module => $version) {
                if (!isset($current_local_modules[$module]) /* || $overrides[$module] != 'local' */) {
                    $src = 'tmp/' . $module . '/' . $module . '-' . $version . '.zip';
                    $dst = 'tmp/' . $module . '/src';
                    // nettoie le dossier destination avant unzip
                    if (!is_dir($dst)) {
                        mkdir($dst, 0755);
                        @chmod($dst, 0755);
                    } else {
                        unlink_recursive($dst . '/' . $module);
                    }
                    if (!unzip($src, $dst)) {
                        if (isset($_GET['debug'])) {
                            echo 'Unzip ' . $src . ' to ' . $dst;
                            echo ' : ok';
                            echo "<br />";
                        }
                        unlink($src);
                    } else {
                        ++$unziperrors;
                        echo 'Unzip ' . $src;
                        echo ' : erreur';
                        echo "<br />";
                    }
                }
            }
            if (isset($_GET['debug'])) {
                echo "</pre>";
            }
        }

        if ($dlerrors || $unziperrors) {
            if ($dlerrors) {
                echo '<p>Certains téléchargements ont échoué. </p>';
            }
            if ($unziperrors) {
                echo '<p>La décompression des archives à échoué. </p>';
            }
        } else {
            echo "<span>";
            echo 'Téléchargement et décompression terminés. ';
            echo "</span>";
        }
    } else {
        echo "<span>";
        echo 'Mode développeur (pas de téléchargement)';
        echo "</span>";
    }
}

// teste la connexion à la base de données si possible
global $db;
$dberrors = array();
$modules_list = array_keys($versions_choisies);


?>
                </div>
            </fieldset>
<?php

// appelle l'etape suivante
if (!($dlerrors || $unziperrors) && isset($_GET['confirm'])) {
?>
            <br />
            <fieldset>
                <legend>Application des modifications</legend>
<?php
    require('script-upgrades.php');
} else {
    // nettoie le dossier tmp (sauf si mode developpeur)
    if (strlen(__CLEMENTINE_REPOSITORY_URL__) && !__CLEMENTINE_INSTALLER_NODOWNLOAD__) {
        unlink_recursive('tmp', false);
    }
?>
            <br />
            <div class="boutons">
                <a class="prev" href="./">Relancer l'installeur</a>
<?php
    if (!count($dberrors) && !$dlerrors && !$unziperrors) {
        if ($incoherences) {
?>
                <a class="next" href="update.php?reinstall=1&confirm<?php
            if (isset($_GET['debug'])) {
                echo '&debug';
            }
?>"><?php
            if (isset($_GET['reinstall']) && $_GET['reinstall'] == 1) {
                echo 'Appliquer ces modifications';
            } else {
                echo 'Réinstaller les modules share et mettre à jour les modules locaux';
            }
?></a>
<?php
        }
        if (!$incoherences) {
?>
                <a class="next" href="update.php?confirm<?php
            if (isset($_GET['debug'])) {
                echo  '&debug';
            }
?>">Installer les dernières version des modules share</a>
<?php
        }
    }
?>
            </div>
<?php
/*
?>
            <form class="formbas" action="update_installer.php" method="post" accept-charset="utf-8">
                <input type="submit" class="prev horsubmit" name="update_installer" value="Mise à jour de l'installeur" />
            </form>
<?php
*/
?>
        </div>
    </div>
</body>
</html>
<?php
}
?>
