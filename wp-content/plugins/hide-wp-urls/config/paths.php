<?php

$currentDir = dirname(__FILE__);

define('_HMU_NAMESPACE_', 'HMU');
define('_HMU_PLUGIN_NAME_', 'hide-wp-urls');
define('_HMU_THEME_NAME_', 'default');
define('_HMU_SUPPORT_EMAIL_', 'contact@wpplugins.tips');

/* Directories */
define('_HMU_ROOT_DIR_', realpath($currentDir . '/..'));
define('_HMU_CLASSES_DIR_', _HMU_ROOT_DIR_ . '/classes/');
define('_HMU_CONTROLLER_DIR_', _HMU_ROOT_DIR_ . '/controllers/');
define('_HMU_MODEL_DIR_', _HMU_ROOT_DIR_ . '/models/');
define('_HMU_TRANSLATIONS_DIR_', _HMU_ROOT_DIR_ . '/languages/');
define('_HMU_THEME_DIR_', _HMU_ROOT_DIR_ . '/view/');

/* URLS */
define('_HMU_URL_', plugins_url() . '/' . _HMU_PLUGIN_NAME_);
define('_HMU_THEME_URL_', _HMU_URL_ . '/view/');

$upload_dir['baseurl'] = network_site_url('/wp-content/uploads');
$upload_dir['basedir'] = ABSPATH . 'wp-content/uploads';
