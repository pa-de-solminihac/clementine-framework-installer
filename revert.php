<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
?>
                <h4>Restauration de la sauvegarde</h4>
<?php
if (is_file('save/dump.sql') && filesize('save/dump.sql')) {
    if (!ini_get('safe_mode') && function_exists('exec') && is_callable('exec') && __CLEMENTINE_INSTALLER_PATH_TO_MYSQL__ && is_file(__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__)) {
        $retour = 0;
        $commande  = __CLEMENTINE_INSTALLER_PATH_TO_MYSQL__;
        $commande .= ' --defaults-file=' . realpath(dirname(__FILE__)) . '/tmp/.my.cnf';
        if (isset($_GET['debug'])) {
            $commande .= ' --show-warnings';
        }
        $commande .= ' -u ' . escapeshellcmd($site_config['clementine_db']['user']);
        $commande .= ' ' . escapeshellcmd($site_config['clementine_db']['name']);
        $commande .= ' < ' . realpath(dirname(__FILE__)) . '/save/dump.sql';
        $commande .= ' > ' . realpath(dirname(__FILE__)) . '/save/log';
        $commande .= ' 2>&1';
        $tab_retours = array ();
        exec($commande, $tab_retours, $retour);
        if (!$retour) {
            echo '<p class="ok">Base de données restaurée</p>';
        } else {
            echo '<p class="err">Échec de la restauration de la base de données</p>';
            echo '<pre>';
            echo $commande . "\n";
            print_r($tab_retours);
            echo '</pre>';
        }
    } else {
?>
                <p>
                    Pour une restauration manuelle, la sauvegarde est disponible dans
                    <pre><?php echo realpath(dirname(__FILE__)) . '/save/dump.sql'; ?></pre> 
                </p>
<?php
    }
}
?>
            </fieldset>
            <p class="err">
                L'installation a échoué !
            </p>
            <br />
            <div class="boutons">
                <a class="prev" href="./">Relancer l'installeur</a>
                <a class="next" href="../">Aller au site</a>
            </div>
        </div>
    </div>
</body>
</html>
