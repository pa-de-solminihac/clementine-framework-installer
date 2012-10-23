<?php
if (CLEMENTINE_INSTALLER_DISABLE) {
    header('Location: index.php' , true, 302);
    die();
}
define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQL__', '/usr/bin/mysql');
define('__CLEMENTINE_INSTALLER_PATH_TO_MYSQLDUMP__', '/usr/bin/mysqldump');
// define('__CLEMENTINE_REPOSITORY_URL__', 'http://pa.quai13.com/clementine');
define('__CLEMENTINE_REPOSITORY_URL__', 'http://clementine.quai13.com');
define('__CLEMENTINE_INSTALLER_NODOWNLOAD__', '0');
?>
