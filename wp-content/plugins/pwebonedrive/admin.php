<?php
/**
 * @version 2.0.3
 * @package OneDrive
 * @copyright © 2016 Perfect Web sp. z o.o., All rights reserved. https://www.perfect-web.co
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @author Piotr Moćko
 */
// No direct access
function_exists('add_action') or die;

add_filter('generate_rewrite_rules', array('PWebOneDriveAdmin', 'build_rewrite_rules'));
add_action('admin_init', array('PWebOneDriveAdmin', 'flush_rewrite_rules'));
add_action('admin_init', array('PWebOneDriveAdmin', 'init'));
add_action('admin_menu', array('PWebOneDriveAdmin', 'admin_menu'));

class PWebOneDriveAdmin
{

    /**
     * Initailize administartion interface
     */
    public static function init()
    {
        add_filter('plugin_action_links', array('PWebOneDriveAdmin', 'plugin_action_links'), 10, 2);
    }

    /**
     * Check if exist rewrite rules for OneDrive and if no, then flush current and create new
     */
    public static function flush_rewrite_rules()
    {
        $rules = get_option('rewrite_rules', array());

        if (!isset($rules['pwebonedrive/callback']))
        {
            flush_rewrite_rules();
        }
    }

    /**
     * Create rewirte rules for OneDrive
     * 
     * @param WP_Rewrite $wp_rewrite
     */
    public static function build_rewrite_rules($wp_rewrite)
    {
        $wp_rewrite->add_external_rule('pwebonedrive/(.+)/([0-9]+)/(.+)', 'wp-admin/admin-ajax.php?action=pwebonedrive_$1&aid=$2&filename=$3');
        $wp_rewrite->add_external_rule('pwebonedrive/(.+)/([0-9]+)', 'wp-admin/admin-ajax.php?action=pwebonedrive_$1&aid=$2');
        $wp_rewrite->add_external_rule('pwebonedrive/(.+)', 'wp-admin/admin-ajax.php?action=pwebonedrive_$1');

        $wp_rewrite->rules['pwebonedrive/callback'] = 'wp-admin/admin-ajax.php?action=pwebonedrive_callback';
    }

    /**
     * Add menu entry with plug-in settings page
     */
    public static function admin_menu()
    {
        add_submenu_page('plugins.php'
                , __('Perfect OneDrive Gallery & File', 'pwebonedrive')
                , __('Perfect OneDrive', 'pwebonedrive')
                , 'manage_options'
                , 'pwebonedrive-config'
                , array('PWebOneDriveAdmin', 'display_configuration_page'));
    }

    /**
     * Add action links on plugins list
     * 
     * @param array $links  Array of action links
     * @param string $file  Plugin name
     * 
     * @return array        Array of action links
     */
    public static function plugin_action_links($links, $file)
    {
        if ($file == plugin_basename(dirname(__FILE__) . '/pwebonedrive.php'))
        {
            $links[] = '<a href="' . admin_url('admin.php?page=pwebonedrive-config') . '">' . __('Settings') . '</a>';
        }

        return $links;
    }

    /**
     * Get plug-in version
     * 
     * @return string   version
     */
    public static function get_version()
    {
        $data = get_plugin_data(dirname(__FILE__) . '/pwebonedrive.php');
        return $data['Version'];
    }

    /**
     * Display the page content for the settings submenu
     * 
     * @global string $wp_version
     */
    public static function display_configuration_page()
    {
        global $wp_version;

        //must check that the user has the required capability 
        if (!current_user_can('manage_options'))
        {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $errors = array();

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        if (isset($_POST['submitConfig']))
        {
            if (isset($_POST['client_id']) AND $_POST['client_id'])
            {
                update_option('pweb_onedrive_client_id', trim($_POST['client_id']));
            }
            else
            {
                $errors[] = __('Missing Client ID.', 'pwebonedrive');
            }

            if (isset($_POST['client_secret']) AND $_POST['client_secret'])
            {
                update_option('pweb_onedrive_client_secret', trim($_POST['client_secret']));
            }
            else
            {
                $errors[] = __('Missing Client secret.', 'pwebonedrive');
            }

            if (!count($errors))
            {
                ?>
                <div class="updated"><p><strong><?php _e('Settings saved.', 'pwebonedrive'); ?></strong></p></div>
                <?php
            }
        }

        $client_id = get_option('pweb_onedrive_client_id');
        if ((empty($client_id) OR strpos($client_id, '-') !== false OR strlen($client_id) > 16) AND !is_ssl())
        {
            $errors[] = __('OneDrive API requires website with a vliad SSL certificate. Make sure that you can connect with your website through HTTPS protocol.', 'pwebonedrive');
        }

        if (count($errors))
        {
            ?>
            <div class="error"><p><strong><?php echo implode('<br>', $errors); ?></strong></p></div>
            <?php
        }
        ?>
        <div class="wrap">

            <div style="float:right;padding:9px 0 4px">
                <?php echo __('Version') . ' ' . self::get_version(); ?>
            </div>

            <h2>
                <?php _e('Perfect OneDrive Gallery & File Settings', 'pwebonedrive'); ?>

                <a class="add-new-h2" href="https://www.perfect-web.co/wordpress/microsoft-onedrive-gallery-file/documentation" target="_blank">
                    <?php _e('Documentation'); ?></a>

                <a class="add-new-h2" href="https://www.perfect-web.co/wordpress/microsoft-onedrive-gallery-file" target="_blank">
                    <?php _e('Buy Support'); ?></a>
            </h2>

            <?php if (version_compare($wp_version, '3.1', '<')) : ?>
                <div class="error"><p><strong><?php _e('This plugin is compatible with WordPress 3.1 or higher.', 'pwebonedrive'); ?></strong></p></div>
            <?php endif; ?>

            <div id="wp_updates"></div>

            <p><?php _e('Share easily your photos and files stored on Microsoft OneDrive. You can display a gallery with your photos or a link to a file for download.', 'pwebonedrive'); ?></p>

            <form name="config" method="post" action="<?php echo admin_url('plugins.php?page=pwebonedrive-config'); ?>">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <a href="http://onedrive.live.com" target="_blank"><img src="<?php echo plugins_url(null, __FILE__); ?>/images/onedrive-logo.png" alt="OneDrive"></a>
                            </th>
                            <td>
                                <p>
                                    <?php _e('Register your site in', 'pwebonedrive'); ?>
                                    <a target="_blank" href="https://apps.dev.microsoft.com/"><?php _e('Windows Live application management', 'pwebonedrive'); ?></a>.<br>
                                    <?php _e('Remember to set', 'pwebonedrive'); ?> <strong><?php _e('Redirect URL\'s', 'pwebonedrive'); ?></strong>
                                    <?php _e('and', 'pwebonedrive'); ?> <strong><?php _e('Mobile client app: No', 'pwebonedrive'); ?></strong><br>
                                    <?php _e('and if available', 'pwebonedrive'); ?> <strong><?php _e('Enhanced redirection security: Enabled', 'pwebonedrive'); ?></strong> <?php _e('(for applications created before June 2014)', 'pwebonedrive'); ?><br>
                                    <?php _e('Read how to', 'pwebonedrive'); ?> <a target="_blank" href="http://msdn.microsoft.com/library/cc287659.aspx"><?php _e('get your Client ID', 'pwebonedrive'); ?></a>.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Redirect URL\'s', 'pwebonedrive'); ?></label></th>
                            <td>
                                <input name="redirect_uri_mod_rewrite_permalinks" type="text" size="30" readonly="readonly" value="<?php echo esc_attr(PWebOneDrive::build_route(array('action' => 'callback'), null, 2)); ?>" class="large-text widefat pweb-make-selection" style="margin-bottom:5px"><br>
                                <input name="redirect_uri_permalinks" type="text" size="30" readonly="readonly" value="<?php echo esc_attr(PWebOneDrive::build_route(array('action' => 'callback'), null, 1)); ?>" class="large-text widefat pweb-make-selection" style="margin-bottom:5px"><br>
                                <input name="redirect_uri" type="text" size="30" readonly="readonly" value="<?php echo esc_attr(PWebOneDrive::build_route(array('action' => 'callback'), null, 0)); ?>" class="large-text widefat pweb-make-selection">
                                <script type="text/javascript">
                                    jQuery(document).ready(function ($) {
                                        $("input.pweb-make-selection")
                                                .on("click", function (e) {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    $(this).select();
                                                })
                                                .on("keydown", function (e) {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    $(this).select();
                                                });
                                    });
                                </script>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="pweb-client_id"><?php _e('Client ID', 'pwebonedrive'); ?></label></th>
                            <td>
                                <input id="pweb-client_id" name="client_id" type="text" size="15" value="<?php echo esc_attr(get_option('pweb_onedrive_client_id')); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="pweb-client_secret"><?php _e('Client secret', 'pwebonedrive'); ?></label></th>
                            <td>
                                <input id="pweb-client_secret" name="client_secret" type="password" size="15" value="<?php echo esc_attr(get_option('pweb_onedrive_client_secret')); ?>" class="regular-text">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submitConfig" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>">
                </p>
            </form>

            <p>
                <img src="<?php echo plugins_url(null, __FILE__); ?>/images/MSFT_logo.png" alt="Microsoft" >
            </p>
            <p>
                <em>Inspired by <a href="http://www.microsoft.com/openness/default.aspx" target="_blank"><strong>Microsoft</strong></a>.
                    Copyright &copy; 2016 <strong>Perfect Web</strong> sp. z o.o. All rights reserved. Distributed under GPL by
                    <a href="https://www.perfect-web.co/wordpress" target="_blank"><strong>Perfect-Web.co</strong></a>.<br>
                    All other trademarks and copyrights are property of their respective owners.</em>
            </p>

            <script type="text/javascript">
                // Updates feed
                (function () {
                    var pw = document.createElement("script");
                    pw.type = "text/javascript";
                    pw.async = true;
                    pw.src = "//www.perfect-web.co/index.php?option=com_pwebshop&view=updates&format=raw&extension=wp_onedrive&version=<?php echo pweb_onedrive_get_version(); ?>&wpversion=<?php echo $wp_version; ?>&uid=<?php echo md5(home_url()); ?>";
                    var s = document.getElementsByTagName("script")[0];
                    s.parentNode.insertBefore(pw, s);
                })();
            </script>
        </div>

        <?php
    }

}
