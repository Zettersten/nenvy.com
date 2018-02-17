<?php

/*
  Copyright (c) 2016, WPPlugins.
  The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

  Plugin Name: Hide WP Admin and Login
  Plugin URI:
  Author: WPPlugins - Hide Admin and Login
  Description: You can choose to hide the Wordpress URLs without physically changing them and increases your Wordpress security against hackers and spammers. <br /> <a href="http://wpplugins.tips/wordpress" target="_blank"><strong>Unlock all features</strong></a>
  Version: 1.1.013
  Author URI: http://wpplugins.tips
 */
define('HMU_VERSION', '1.1.013');

/* Call config files */
require(dirname(__FILE__) . '/debug/index.php');
require(dirname(__FILE__) . '/config/config.php');

/* important to check the PHP version */
if (PHP_VERSION_ID >= 5100) {
    if (!class_exists('HMWP_Classes_ObjController') && !class_exists('HMW_Classes_ObjController')) {
        /* inport main classes */
        require_once(_HMU_CLASSES_DIR_ . 'ObjController.php');
        HMU_Classes_ObjController::getClass('HMU_Classes_FrontController');

        if (is_admin() || is_network_admin()) {
            /* Main class call for admin */
            add_action('init', array(HMU_Classes_ObjController::getClass('HMU_Classes_FrontController'), 'runAdmin'));

            register_activation_hook(__FILE__, array(HMU_Classes_ObjController::getClass('HMU_Classes_Tools'), 'hmu_activate'));
            register_deactivation_hook(__FILE__, array(HMU_Classes_ObjController::getClass('HMU_Classes_Tools'), 'hmu_deactivate'));
        } else {
            add_action('init', array(HMU_Classes_ObjController::getClass('HMU_Classes_FrontController'), 'runFrontend'));
        }
    }
} else {
    /* Main class call */
    add_action('admin_notices', 'hmu_showError');
}

/**
 * Called in Notice Hook
 */
function hmu_showError() {
    echo '<div class="update-nag"><span style="color:red; font-weight:bold;">' . __('For Hide My URLs to work, the PHP version has to be equal or greater then 5.1', _HMU_PLUGIN_NAME_) . '</span></div>';
}


