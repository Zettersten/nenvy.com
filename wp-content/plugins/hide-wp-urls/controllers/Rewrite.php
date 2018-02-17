<?php

class HMU_Controllers_Rewrite extends HMU_Classes_FrontController {

    public function __construct() {
        parent::__construct();
        add_filter('query_vars', array($this->model, 'addParams'));

        $this->model->buildRedirect();
        add_action('generate_rewrite_rules', array($this->model, 'setRewriteRules'));

        if (get_option('permalink_structure')) {

            //change the admin url $params['hmu_mode'] == 'default'
            add_filter('admin_url', array($this->model, 'admin_url'), 1, 1);
            add_filter('network_admin_url', array($this->model, 'network_admin_url'), 1, 1);
            add_filter('site_url', array($this->model, 'site_url'), 1, 2);
            add_filter('login_redirect', array($this->model, 'sanitize_redirect'), 1, 1);
            add_action('wp_logout', array($this->model, 'wp_logout'), 1, 1);
            //add_filter( 'logout_url', array($this->model, 'logout_url'), 1, 1);
            if (HMU_Classes_Tools::getIsset('hmu_disable')) {
                if (HMU_Classes_Tools::getValue('hmu_disable') == HMU_Classes_Tools::getOption('hmu_disable')) {
                    return;
                }
            }
            if (HMU_Classes_Tools::getOption('error') || HMU_Classes_Tools::getOption('logout')) {
                return;
            }

            $this->hideUrl();
        }
    }

    /**
     * Check Hidden pages
     */
    function hideUrl() {

        if (!HMU_Classes_Tools::getOption('error') && !is_user_logged_in()) {

            if (HMU_Classes_Tools::getIsset('hmu_disable')) {
                if (HMU_Classes_Tools::getValue('hmu_disable') == HMU_Classes_Tools::getOption('hmu_disable')) {
                    return;
                }
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $url = $_SERVER['REQUEST_URI'];
                //redirect if no final slash is added
                if ($url == wp_make_link_relative(get_bloginfo('url')) . '/' . HMU_Classes_Tools::getOption('hmu_admin_url')) {
                    wp_redirect(admin_url());
                    exit();
                }

                $url = trailingslashit($url);

                if (HMU_Classes_Tools::$default['hmu_admin_url'] <> HMU_Classes_Tools::getOption('hmu_admin_url')) {
                    if (strpos($url, '/wp-admin/') !== false && HMU_Classes_Tools::getOption('hmu_hide_admin')) {
                        HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->getNotFound();
                    }

                    if (strpos($url, '/admin/') !== false && HMU_Classes_Tools::getOption('hmu_admin_url') <> 'admin' && HMU_Classes_Tools::getOption('hmu_hide_admin')) {
                        HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->getNotFound();
                    }

                } else {
                    if (strpos($url, '/wp-admin/') !== false && strpos($url, admin_url('admin-ajax.php', 'relative')) === false && HMU_Classes_Tools::getOption('hmu_hide_admin')) {
                        HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->getNotFound();
                    }
                }

                if (HMU_Classes_Tools::$default['hmu_login_url'] <> HMU_Classes_Tools::getOption('hmu_login_url')) {
                    if ((strpos($url, '/wp-login/') !== false || strpos($url, '/wp-login.php') !== false) && HMU_Classes_Tools::getOption('hmu_hide_login')) {
                        HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->getNotFound();
                    }
                    if (strpos($url, '/login/') !== false && HMU_Classes_Tools::getOption('hmu_login_url') <> 'login' && HMU_Classes_Tools::getOption('hmu_hide_login')) {
                        HMU_Classes_ObjController::getClass('HMU_Models_Rewrite')->getNotFound();
                    }
                }
            }
        }
    }


}
