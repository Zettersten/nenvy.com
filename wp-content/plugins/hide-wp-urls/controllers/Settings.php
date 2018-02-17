<?php

class HMU_Controllers_Settings extends HMU_Classes_FrontController {

    public $tabs;
    public $logout = false;

    public function __construct() {
        parent::__construct();
        add_filter('relative_url', array(HMU_Classes_ObjController::getClass('HMU_Models_Rewrite'), 'relative_url'));
    }

    function init() {
        $this->tabs = array('hmu_settings' => 'Permalinks');

        HMU_Classes_Error::setError('If a page is not working, add this parameter <strong>?hmu_disable=' . HMU_Classes_Tools::getOption('hmu_disable') . '</strong> to access the admin page');

        if (HMU_Classes_Tools::getOption('logout') && !HMU_Classes_Tools::getOption('error')) {
            $logoutForm = '
                        <form method="POST">
                            <input type="hidden" name="action" value="hmu_logout" />
                            <input type="hidden" name="hmu_nonce" value="' . wp_create_nonce(_HMU_NONCE_ID_) . '" />
                            <input type="submit" class="btn btn-success save" value="Yes, I\'m ready" />
                        </form>
                        ';
            $abortForm = '
                        <form method="POST">
                            <input type="hidden" name="action" value="hmu_abort" />
                            <input type="hidden" name="hmu_nonce" value="' . wp_create_nonce(_HMU_NONCE_ID_) . '" />
                            <input type="submit" class="btn btn-warning save" value="No, abort" />
                        </form>
                        ';
            HMU_Classes_Error::setError('Did you copied the safe code? Ready to log out? <div class="hmu_logout">' . $logoutForm . '</div><div class="hmu_abort" style="display: inline-block; margin-left: 5px;">' . $abortForm . '</div>');
        } elseif (HMU_Classes_Tools::getOption('error')) {
            $abortForm = '
                        <form method="POST">
                            <input type="hidden" name="action" value="hmu_abort" />
                            <input type="hidden" name="hmu_nonce" value="' . wp_create_nonce(_HMU_NONCE_ID_) . '" />
                            <input type="submit" class="btn btn-warning save" value="Cancel the changes" />
                        </form>
                        ';
            HMU_Classes_Error::setError(__('Action Required. Proceed with the instrutions or cancel the changes ', _HMU_PLUGIN_NAME_) . '<div class="hmu_abort" style="display: inline-block;">' . $abortForm . '</div>');

        }

        if (!get_option('permalink_structure')) {
            HMU_Classes_Error::setError(sprintf(__('Hide my WP does not work with %s Permalinks. Change it to %s or other type in Settings > Permalinks in order to hide it', _HMU_PLUGIN_NAME_), __('Plain'), __('Post Name')));
        }

        if (HMU_Classes_Tools::isNginx()) {
            HMU_Classes_Error::setError(sprintf(__('Nginx detected! You need %sHide my WP PRO%s to work with Nginx servers.', _HMU_PLUGIN_NAME_), '<a href="http://wpplugins.tips/wordpress" target="_blank">', '</a>'));
        }

        if (is_multisite()) {
            HMU_Classes_Error::setError(sprintf(__('WP Multisite detected! You need %sHide my WP PRO%s to work with WP Multisites.', _HMU_PLUGIN_NAME_), '<a href="http://wpplugins.tips/wordpress" target="_blank">', '</a>'));
        }

        HMU_Classes_ObjController::getClass('HMU_Classes_DisplayController')->loadMedia('settings');
        HMU_Classes_ObjController::getClass('HMU_Classes_DisplayController')->loadMedia('switchery.min');

        if (HMU_Classes_Tools::$default['hmu_admin_url'] == HMU_Classes_Tools::getOption('hmu_admin_url')) {
            if (strpos(admin_url(), HMU_Classes_Tools::$default['hmu_admin_url']) === false) {
                HMU_Classes_Error::setError(sprintf(__('Your admin path is changed. To prevent future errors, disable the other plugin that changes the admin path.', _HMU_PLUGIN_NAME_)));
                define('HMU_DISABLE', true);
            }
        }

        if (HMU_Classes_Tools::getIsset('page')) {
            foreach ($this->tabs as $slug => $value) {
                if (HMU_Classes_Tools::getValue('page') == $slug) {
                    echo $this->admin_tabs($slug);
                    HMU_Classes_ObjController::getClass('HMU_Classes_Error')->hookNotices();
                    echo $this->getView(ucfirst(str_replace('hmu_', '', $slug)));
                }
            }
        }

    }

    function admin_tabs($current = null) {
        $content = '';
        $content .= '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $location => $tabname) {
            if ($current == $location) {
                $class = ' nav-tab-active';
            } else {
                $class = '';
            }
            $content .= '<a class="nav-tab' . $class . '" href="?page=' . $location . '">' . $tabname . '</a>';
        }
        $content .= '</h2>';
        return $content;
    }

    /**
     * Called when Post action is triggered
     *
     * @return void
     */
    public function action() {
        parent::action();

        switch (HMU_Classes_Tools::getValue('action')) {

            case 'hmu_settings':
                if (HMU_Classes_Tools::getValue('data') <> '') {
                    parse_str(HMU_Classes_Tools::getValue('data'), $params);
                    $this->saveValues($params);
                    exit();
                } else {
                    $this->saveValues($_POST);
                }

                if (!HMU_Classes_Tools::getOption('error') && !HMU_Classes_Tools::getOption('logout')) {
                    HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->flushChanges();
                }

                break;

            case 'hmu_logout':
                if (ADMIN_COOKIE_PATH == rtrim(wp_make_link_relative(network_site_url(HMU_Classes_Tools::getOption('hmu_admin_url'))))) {

                    HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->flushChanges();
                    HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->wp_logout();
                    die();
                } else {
                    HMU_Classes_Error::setError(__("The value for 'ADMIN_COOKIE_PATH' in wp-config.php file is not set up correctly."));


                    HMU_Classes_Tools::saveOptions('error', true);
                    HMU_Classes_Tools::saveOptions('logout', false);
                }
                break;
            case 'hmu_savedefault':
                HMU_Classes_Tools::saveOptions('logout', false);

                $options = HMU_Classes_Tools::getOptions();
                foreach ($options as $key => $value) {
                    HMU_Classes_Tools::saveOptions($key, $value, true);
                }
                break;
            case 'hmu_abort':
                HMU_Classes_Tools::$options = HMU_Classes_Tools::getOptions(true);

                HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->hmu_create_config_cache(HMU_Classes_Tools::getOption('hmu_admin_url'));
                HMU_Classes_Tools::saveOptions('hmu_admin_url', HMU_Classes_Tools::getOption('hmu_admin_url'));
                HMU_Classes_Tools::saveOptions('error', false);
                HMU_Classes_Tools::saveOptions('logout', false);
                HMU_Classes_Tools::emptyCache();

                break;

            case 'hmu_manualrewrite':
                HMU_Classes_Tools::saveOptions('error', false);

                foreach (HMU_Classes_Tools::$options as $key => $value) {
                    HMU_Classes_Tools::saveOptions($key, $value, true);
                }

                HMU_Classes_Tools::emptyCache();

                break;
        }
    }

    public function saveValues($params) {
        if (!empty($params)) {
            HMU_Classes_Tools::saveOptions('error', false);
            HMU_Classes_Tools::$default['hmu_send_email'] = $params['hmu_send_email'];

            if ($params['hmu_mode'] == 'default') {
                $params = HMU_Classes_Tools::$default;
            }

            if ($params['hmu_mode'] == 'lite') {
                $params = @array_merge(HMU_Classes_Tools::$default, HMU_Classes_Tools::$lite);
            }


            //If the admin is changed, require a logout
            $lastsafeoptions = HMU_Classes_Tools::getOptions(true);
            if ($lastsafeoptions['hmu_admin_url'] <> $params['hmu_admin_url']) {
                HMU_Classes_Tools::saveOptions('logout', true);
            }


            foreach ($params as $key => $value) {
                $value = preg_replace('/[^a-z0-9-_.]/', '', $value);
                if ($value <> '' && $key <> 'action' && $key <> 'hmu_nonce') {
                    HMU_Classes_Tools::saveOptions($key, $value);
                }
            }

            if (!HMU_Classes_Tools::getOption('error')) {
                if ($params['hmu_admin_url'] == (HMU_Classes_Tools::$default['hmu_admin_url'])) {
                    if (!HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->hmu_remove_config_cache()) {
                        HMU_Classes_Tools::saveOptions('error', true);
                    }
                } elseif (!HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->hmu_create_config_cache($params['hmu_admin_url'])) {
                    HMU_Classes_Tools::saveOptions('error', true);
                }
            }

            //check if writable htaccess file
            if (!HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->is_writeable_Htaccess()) {
                //if not writeable, call the rules to show manually changes
                if (!HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->clearRedirect()->buildRedirect()->setRewriteRules()) {
                    HMU_Classes_Tools::saveOptions('error', true);
                }
            }


        }
    }

    function hookFooter() {
        HMU_Classes_Tools::saveOptions();
    }

}
