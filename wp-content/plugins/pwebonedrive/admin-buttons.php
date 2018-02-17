<?php

/**
 * @version 2.0.0
 * @package OneDrive
 * @copyright © 2016 Perfect Web sp. z o.o., All rights reserved. https://www.perfect-web.co
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @author Piotr Moćko
 */
// No direct access
function_exists('add_action') or die;

if (defined('DOING_AJAX'))
{
    add_action('wp_ajax_pwebonedrive_store', array('PWebOneDriveButtons', 'store'));
}
else
{
    add_action('admin_init', array('PWebOneDriveButtons', 'init'));
}

class PWebOneDriveButtons
{

    /**
     * Initailize editor buttons
     */
    public static function init()
    {
        // Only add hooks when the current user has permissions AND is in Rich Text editor mode
        if (( current_user_can('edit_posts') || current_user_can('edit_pages') ) && get_user_option('rich_editing') && get_option('pweb_onedrive_client_id'))
        {
            // Live Connect JavaScript library
            wp_register_script('liveconnect', (is_ssl() ? 'https' : 'http') . '://js.live.net/v5.0/wl' . (PWEBONEDRIVE_DEBUG ? '.debug' : '') . '.js');
            wp_enqueue_script('liveconnect');

            wp_register_script('pwebonedrive', plugins_url('js/onedrive.js', __FILE__), array('jquery'), '2.0.0');
            wp_enqueue_script('pwebonedrive');

            add_action('admin_head', array('PWebOneDriveButtons', 'display_script'));

            add_filter('mce_external_plugins', array('PWebOneDriveButtons', 'add_buttons'));
            add_filter('mce_buttons', array('PWebOneDriveButtons', 'register_buttons'));
        }
    }

    /**
     * Adds script with editor buttons
     * 
     * @param array $plugin_array   Array of editor plugins
     * 
     * @return array                Array of editor plugins
     */
    public static function add_buttons($plugin_array)
    {
        $plugin_array['pwebonedrive'] = plugins_url('js/editor_plugin.js?ver=1.3.0', __FILE__);
        return $plugin_array;
    }

    /**
     * Register buttons names
     * 
     * @param array $buttons    Array of buttons
     * 
     * @return array            Array of buttons
     */
    public static function register_buttons($buttons)
    {
        array_push($buttons, 'pwebonedrivegallery', 'pwebonedrivefolder', 'pwebonedrivefile');
        return $buttons;
    }

    /**
     * Dispaly JavaScript code with translations and options for editor buttons
     */
    public static function display_script()
    {
        $i18n = array(
            'button_gallery' => __('OneDrive Gallery', 'pwebonedrive'),
            'button_folder' => __('OneDrive Folder', 'pwebonedrive'),
            'button_file' => __('OneDrive File', 'pwebonedrive'),
            'emergency_exit' => __('OneDrive emergency exit', 'pwebonedrive'),
            'gallery_folder_select_warning' => __('Perfect OneDrive Gallery: select only folder with photos!', 'pwebonedrive'),
            'gallery_file_select_warning' => __('Perfect OneDrive Gallery: select only folder with photos, not a file!', 'pwebonedrive'),
            'folder_file_select_warning' => __('Perfect OneDrive Folder: select only a folder, not a file!', 'pwebonedrive')
        );

        $options   = array(
            'client_id:"' . get_option('pweb_onedrive_client_id') . '"',
            'task_url:"' . admin_url('admin-ajax.php?action=pwebonedrive_') . '"',
            'redirect_url:"' . PWebOneDrive::build_route(array('action' => 'callback')) . '"',
            'spinner_url:"' . includes_url() . 'images/wpspin-2x.gif"'
        );
        if (PWEBONEDRIVE_DEBUG)
            $options[] = 'debug:1';

        echo '<script type="text/javascript">'
        . 'PWebOneDrive.setOptions({' . implode(',', $options) . '});'
        . 'PWebOneDrive.setI18n(' . json_encode($i18n) . ');'
        . '</script>';
    }

    /**
     * Save resource ID in database with corresponding access ID
     * 
     * string $_POST['resource_id']  Resource ID for saving
     * 
     * @global object $wpdb
     * 
     * Displays JSON output:
     * boolean  status      true on success, false on failure
     * string   message     empty on success, text on failure
     */
    public static function store()
    {
        global $wpdb;

        $result = array('status' => false, 'message' => '');

        if (isset($_POST['resource_id']) AND ( $resource_id = $_POST['resource_id']))
        {
            $sql_like = method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($resource_id) : like_escape($resource_id);
            $sql      = $wpdb->prepare('SELECT `id`, `access_id` FROM `' . $wpdb->prefix . 'onedrive_storage` WHERE `resource_id` LIKE %s', $sql_like);
            $storage  = $wpdb->get_row($sql, OBJECT);

            $user_id = LiveConnectClient::getUserIdFromResource($resource_id);
            if ($user_id)
            {
                $sql_like  = method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($user_id) : like_escape($user_id);
                $sql       = $wpdb->prepare('SELECT `id` FROM `' . $wpdb->prefix . 'onedrive_access` WHERE `user_id` LIKE %s', $sql_like);
                $access_id = (int) $wpdb->get_var($sql);

                if ($access_id)
                {
                    // create new storage
                    if ($storage === null)
                    {
                        $result['status'] = $wpdb->insert($wpdb->prefix . 'onedrive_storage', array(
                            'resource_id' => $resource_id,
                            'access_id' => $access_id
                                ), array('%s', '%d'));

                        if ($result['status'] === 0)
                        {
                            $result['status'] = false;
                        }
                    }
                    // update access id in existing storage
                    elseif ((int) $storage->access_id === 0 OR (int) $storage->access_id !== $access_id)
                    {
                        $result['status'] = $wpdb->update($wpdb->prefix . 'onedrive_storage'
                                , array('access_id' => $access_id)
                                , array('id' => (int) $storage->id)
                                , array('%d'));
                    }
                    else
                    {
                        $result['status'] = true;
                    }

                    if ($result['status'] === false)
                    {
                        $result['message'] = __('Error while saving selected resource. Try again.', 'pwebonedrive');
                    }
                }
                else
                {
                    $result['message'] = __('Access token for current OneDrive session was not saved. Logout from OneDrive and try again.', 'pwebonedrive');
                }
            }
        }

        die(json_encode($result));
    }

}
