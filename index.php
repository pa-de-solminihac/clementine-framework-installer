<?php
require('includes.php');
require('fonctions.php');
require('dbcheck.php');

// verifications
$verif_ok = 0;
$baseconfig_ok = 0;
$errors = array();
// PHP version
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    $errors['php5'] = 'PHP 5 required';
}
// safe_mode disabled
if (ini_get('safe_mode')) {
    $errors['safe_mode'] = 'PHP safe_mode is deprecated and MUST be disabled for this installer to run properly';
}
// openssl PHP extension
if (!extension_loaded('openssl')) {
    $errors['openssl'] = 'openssl PHP extension required';
}
// file management
$errfile = 0;
if (!is_dir('tmp')) {
    mkdir('tmp', 0755);
    @chmod('tmp', 0755);
}
$path = 'test_' . md5(uniqid());
if (!is_dir('tmp')) {
    ++$errfile;
} else {
    touch('tmp/' . $path);
    if (!is_file('tmp/' . $path)) {
        ++$errfile;
    } else {
        if (!unlink('tmp/' . $path)) {
            ++$errfile;
        }
    }
}
if ($errfile) {
    $errors['chmod'] = 'Could not manage files properly';
}
// site's module.ini
if (!is_file('../app/local/site/etc/module.ini')) {
    $errors['module_ini'] = 'Missing module.ini file';
}
// site's config.ini
if (!is_file('../app/local/site/etc/config.ini')) {
    $errors['config_ini'] = 'Missing config.ini file';
}
if (!$db) {
    $errors['db_set'] = 'Missing database credentials';
}
$config = installer_getModuleConfig('../app/local/site');
if (is_array($config) 
    && isset($config['version']) 
    && isset($config['weight']) 
    && isset($config['depends_' . (int) $config['version']]) 
    && isset($config['depends_' . (int) $config['version']][(int) $config['version']])
    && !isset($errors['config_ini'])
) {
    $baseconfig_ok = 1;
}
if ($db && !count($errors) && $baseconfig_ok) {
    header('Location: update.php' , true, 302);
    die();
}

// code php des fichiers ini d'exemple
$exemple_module_ini = <<<INI

version=1.0
weight=0.9

; dependances de la version 1.* du module site
; module core version 3
; module db version 1
[depends_1]
core=3
db=1


INI;

$exemple_config_ini = <<<INI

[clementine_global]
site_name=New site
email_prod=your@email.com
email_dev=your@email.com

[clementine_installer]
enabled=1
allowed_ip=

[clementine_db]
host=localhost
name=dbname
user=root
pass=


INI;

if (!$baseconfig_ok) {
    $errors[] = 'Base Config';
}

?>
<!DOCTYPE html>
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
            <h2>Bienvenue dans l'installeur</h2>
            <a href="http://clementine.quai13.com"><img src="logo.jpg" alt="logo clémentine framework" /></a>
<?php
require('repocheck.php');
if (!count($errors)) {
?>
                <p>
                    <span class="ok">Les vérifications sont bien passées. </span>
                </p>

            </fieldset>
            <br />

            <form action="" method="post" accept-charset="utf-8">
                <div class="boutons">
                    <a class="prev" href="../">Retour au site</a>
                    <input class="next" type="submit" value="Installer ou mettre à jour" />
                </div>
            </form>
<?php
} else {
?>
            <fieldset>
                <legend id="presentation_legend" class="<?php echo $baseconfig_ok ? 'deplie' : 'replie'; ?>" onclick="toggle('presentation');">Présentation</legend>
                <div id="presentation" style="display: <?php echo $baseconfig_ok ? 'none' : 'block'; ?>">
                    <p>
                        Clémentine est un framework basé sur des modules. <br />
                        <br />
                        On distingue <strong>2 types de modules</strong> :<br />
<pre>
/app/<em>share</em> : modules partagés, importés par l'installeur
/app/<em>local</em> : modules locaux, qui contiendront les fichiers du site
</pre>
                    </p>
                    <p class="remarque">
                       Remarque<br />
                       On ne modifiera jamais les fichiers d'un module <em>share</em> : on les surchargera dans les modules locaux.
                    </p>
                        <br />
                    <h3 id="exemple">Créer un nouveau site</h3>
                    <p>
                        Pour contenir les fichiers de notre site, on va créer un nouveau module dans /app/<em>local</em>/<strong>site</strong>. 
                        C'est très simple, on va juste créer 2 fichiers :
<pre>
<em>module.ini</em> : présente le module au framework
<em>config.ini</em> : configuration du module
</pre>
                    </p>
                    <h4>Fichier module.ini</h4>
                    <p>
                        Notre module <em>site</em> a besoin d'au moins 2 modules partagé : <br />
<pre>
<em>core</em> : le coeur de Clémentine.
<em>db</em>   : le module d'accès à la base de données.
</pre>
                    </p>
                    <p>
                        On va présenter au framework notre module <em>site</em>, qui dépend de <em>core</em> et <em>db</em> : 
                    </p>
                    <div style="border: 1px solid #000000">
                        <p style="border-bottom: 1px dotted #000000; margin: 0; padding: 0.2em; text-indent: 1em;">
                            Fichier <strong>/app/local/site/etc/module.ini</strong>
                        </p>
                        <pre style="background-color: #CCCCCC; margin: 10px; padding: 0.5em; border: 1px outset #000000"><?php echo htmlentities($exemple_module_ini); ?></pre>
                    </div>
                    <h4>Fichier config.ini</h4>
                    <p>
                        Par ailleurs, le site a besoin d'un minimum de configuration, pour se connecter à la base de donnés par exemple :
                    </p>
                    <div style="border: 1px solid #000000">
                        <p style="border-bottom: 1px dotted #000000; margin: 0; padding: 0.2em; text-indent: 1em;">
                            Fichier <strong>/app/local/site/etc/config.ini</strong>
                        </p>
                        <pre style="background-color: #CCCCCC; margin: 10px; padding: 0.5em; border: 1px outset #000000"><?php echo htmlentities($exemple_config_ini); ?></pre>
                    </div>
                    <p>
                        <br />
                        <strong class="directive">Créez <em>config.ini</em> et <em>module.ini</em> et <a href="">rafraîchissez la page</a></strong>
                    </p>
                    
                </div>
            </fieldset>
            <br />
            <fieldset>
                <legend>Vérifications</legend>
<dl>
<?php
echo '<dt>PHP version</dt>';
if (isset($errors['php5'])) {
    echo "<dd class='err'><p>PHP 5 est nécessaire. </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>PHP safe_mode</dt>';
if (isset($errors['safe_mode'])) {
    echo "<dd class='err'><p>Vous devez désactiver le safe_mode (qui est de toute facon OBSOLÈTE ;)). </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>openssl PHP extension</dt>';
if (isset($errors['openssl'])) {
    echo "<dd class='err'><p>Vous devez activer l'extension PHP openssl. </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>File management</dt>';
if (isset($errors['chmod'])) {
    echo "<dd class='err'><p>Des problèmes de droits sur les fichier empêcheront l'installeur de fonctionner correctement. </p></dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>Site module : module.ini</dt>';
if (isset($errors['module_ini'])) {
    echo "<dd class='err'>Fichier module.ini manquant.</dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>Site module : config.ini</dt>';
if (isset($errors['config_ini'])) {
    echo "<dd class='err'>Fichier config.ini manquant.</dd>";
} else {
    echo '<dd class="ok">ok</dd>';
}
echo '<dt>Database credentials</dt>';
if (!$db) {
    if ($is_db_set) {
        if (count($dberrors)) {
            foreach ($dberrors as $key => $err) {
                echo "<dd class='err'>" . $err . "</dd>";
            }
        } else {
            echo "<dd class='err'><p>Les informations de connexion à la base de donnees sont incorrectes. </p></dd>";
        }
    } else {
        echo "<dd class='err'><p>Les informations de connexion à la base de donnees manquent. </p></dd>";
    }
} else {
    echo '<dd class="ok">ok</dd>';
}
?>
</dl>
<div class="spacer"></div>
            </fieldset>
            <br />

            <div class="boutons" action="update_installer2.php" method="post" accept-charset="utf-8">
                <a class="prev" href="../">Retour au site</a>
                <a class="disabled" href="">Revérifier&hellip;</a>
            </div>
<?php
}
?>
            <form class="formbas" action="update_installer.php" method="post" accept-charset="utf-8">
                <input type="submit" class="prev horsubmit" name="update_installer" value="Mise à jour de l'installeur" />
            </form>
        </div>
    </div>
</body>
</html>
