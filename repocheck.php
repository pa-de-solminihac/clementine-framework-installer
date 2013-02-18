<?php
if (__CLEMENTINE_REPOSITORY_URL__ != __CLEMENTINE_DEFAULT_REPOSITORY_URL__ && __CLEMENTINE_REPOSITORY_URL__ != __CLEMENTINE_DEFAULT_REPOSITORY_SSL_URL__ ) {
?>
            <div class="warning">
                <p>
                    Vous utilisez un dépôt personnalisé :<br />
                    <a href="<?php echo __CLEMENTINE_REPOSITORY_URL__; ?>"><?php echo __CLEMENTINE_REPOSITORY_URL__; ?></a>
                </p>
                <p>
                    Le dépôt par défaut est :<br />
                    <a href="<?php echo __CLEMENTINE_DEFAULT_REPOSITORY_SSL_URL__; ?>"><?php echo __CLEMENTINE_DEFAULT_REPOSITORY_SSL_URL__; ?></a>
                </p>
            </div>
<?php
}
?>
