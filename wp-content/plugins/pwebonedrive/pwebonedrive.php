<?php
/**
 * Plugin Name: Perfect OneDrive Gallery & File
 * Plugin URI: https://www.perfect-web.co/wordpress/microsoft-onedrive-gallery-file
 * Description: Share easily your photos and files stored on Microsoft OneDrive. You can display a gallery with your photos or a link to a file for download.
 * Version: 2.0.3
 * Text Domain: pwebonedrive
 * Author: Piotr Moćko
 * Author URI: https://www.perfect-web.co
 * License: GPLv3
 */
// No direct access
function_exists('add_action') or die;

if (version_compare($GLOBALS['wp_version'], '3.1', '>=') AND version_compare(PHP_VERSION, '5.2', '>='))
{
    if (!defined('PWEBONEDRIVE_DEBUG'))
        define('PWEBONEDRIVE_DEBUG', WP_DEBUG);

    require_once dirname(__FILE__) . '/liveconnect.php';
    require_once dirname(__FILE__) . '/site.php';

    $PWebOneDrive = new PWebOneDrive();

    require_once dirname(__FILE__) . '/admin.php';

    if (is_admin())
    {
        require_once dirname(__FILE__) . '/admin-buttons.php';

        register_activation_hook(__FILE__, array('PWebOneDrive', 'install'));
        register_uninstall_hook(__FILE__, array('PWebOneDrive', 'uninstall'));
    }
}
else
{
    function pwebonedrive_requirements_notice()
    {
        ?>
        <div class="error">
            <p><?php printf(__('Perfect OneDrive Gallery & File plugin requires WordPress %s and PHP %s', 'pwebonedrive'), '3.1+', '5.2+'); ?></p>
        </div>
        <?php
    }

    add_action('admin_notices', 'pwebonedrive_requirements_notice');
}
