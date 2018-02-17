<?php

class HMU_Controllers_Menu extends HMU_Classes_FrontController {

    public function __construct() {
        parent::__construct();
        //add_filter('rewrite_rules_array', array(HMU_Classes_ObjController::getClass('HMU_Models_Rewrite'), 'rewrite_rules'), 999, 1);
    }

    /**
     * Hook the Admin load
     */
    public function hookInit() {

        /* add the plugin menu in admin */
        if (current_user_can('manage_options')) {
            //check if activated
            if (get_transient('hmu_activate') == 1) {
                // Delete the redirect transient
                delete_transient('hmu_activate');

                wp_safe_redirect(admin_url('admin.php?page=hmu_settings'));
                exit();
            }

            //Check if there are expected upgrades
            HMU_Classes_Tools::checkUpgrade();

            //Show notifications for the admin
            HMU_Classes_ObjController::getClass('HMU_Controllers_Notice');
        }
    }

    /**
     * Creates the Setting menu in Wordpress
     */
    public function hookMenu() {
        /* add the plugin menu in admin */
        $this->model->addSubmenu(array('options-general.php',
            __('Hide My URLs Settings', _HMU_PLUGIN_NAME_),
            __('Hide My WP', _HMU_PLUGIN_NAME_),
            'manage_options',
            'hmu_settings',
            array(HMU_Classes_ObjController::getClass('HMU_Controllers_Settings'), 'init')
        ));

    }


}