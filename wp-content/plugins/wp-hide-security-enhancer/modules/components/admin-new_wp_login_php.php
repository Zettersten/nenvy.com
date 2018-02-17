<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_admin_new_wp_login_php extends WPH_module_component
        {
            function get_component_title()
                {
                    return "wp-login.php";
                }
                                    
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'new_wp_login_php',
                                                                    'label'         =>  'New wp-login.php',
                                                                    'description'   =>  array(
                                                                                                __('Map a new wp-login.php instead default. This also need to include <i>.php</i> extension.',  'wp-hide-security-enhancer') . '<br />'
                                                                                                . __('More details can be found at',    'wp-hide-security-enhancer') .' <a href="http://www.wp-hide.com/documentation/admin-change-wp-login-php/" target="_blank">Link</a>',
                                                                                                '<div class="notice-error"><div class="dashicons dashicons-warning important" alt="f534">warning</div> <span class="important">' . __('Make sure your log-in url is not already modified by another plugin or theme. In such case, you should disable other code and take advantage of these features. More details at ',  'wp-hide-security-enhancer') . '<a target="_blank" href="http://www.wp-hide.com/login-conflicts/">Login Conflicts</a></span></div>',
                                                                                                '<div class="notice-error"><div class="dashicons dashicons-warning important" alt="f534">warning</div> <span class="important">' . __('If unable to access the login / admin section anymore, use the Recovery Link which reset links to default: ',  'wp-hide-security-enhancer') . '<br /><b class="pointer">' . site_url() . '?wph-recovery='.  $this->wph->functions->get_recovery_code()  .'</b></div>' 
                                                                                                ),
                                                                    'input_type'    =>  'text',
                                                                    
                                                                    'sanitize_type' =>  array(array($this->wph->functions, 'sanitize_file_path_name'), array($this->wph->functions, 'php_extension_required')),
                                                                    'processing_order'  =>  50
                                                                    
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'block_default_wp_login_php',
                                                                    'label'         =>  'Block default wp-login.php',
                                                                    'description'   =>  __('Block default wp-login.php file from being accesible.',  'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  55
                                                                    
                                                                    );
                    
                                                                    
                    return $this->module_settings;   
                }
                
                
                
            function _init_new_wp_login_php($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                            
                    //conflict handle with other plugins
                    include_once(WPH_PATH . 'conflicts/wp-simple-firewall.php');
                    WPH_conflict_handle_wp_simple_firewall::custom_login_check();
                    
  
                    add_filter('login_url',             array($this,'login_url'), 999, 3 ); 
  
                    //add replacement
                    $url    =   trailingslashit(    site_url()  ) .  'wp-login.php';
                    $this->wph->functions->add_replacement( $url,  trailingslashit(    site_url()  ) .  $saved_field_data );

                    //add relative too
                    $this->wph->functions->add_replacement( '/wp-login.php', '/' . $saved_field_data );
                     
                }
            
            
            function login_url($login_url, $redirect, $force_reauth)
                {
                    $new_wp_login_php     =   $this->wph->functions->get_module_item_setting('new_wp_login_php');
                    
                    $login_url = site_url($new_wp_login_php, 'login');
                    
                    return $login_url;   
                }
                
            function _callback_saved_new_wp_login_php($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data))
                        return  $processing_response; 
          
                    $new_wp_login_php =   untrailingslashit ( $this->wph->functions->get_url_path( trailingslashit(    site_url()  ) .  'wp-login.php'   ) );
                                
                    $rewrite_base   =   $this->wph->functions->get_rewrite_base( $saved_field_data, FALSE, FALSE );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( $new_wp_login_php, TRUE, FALSE );
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        $processing_response['rewrite'] = "\nRewriteRule ^"    .   $rewrite_base     .   '(.*) '. $rewrite_to .'$1 [L,QSA]';
                    
                    if($this->wph->server_web_config   === TRUE)
                        $processing_response['rewrite'] = '
                            <rule name="wph-new_wp_login_php" stopProcessing="true">
                                <match url="^'.  $rewrite_base   .'(.*)"  />
                                <action type="Rewrite" url="'.  $rewrite_to .'{R:1}"  appendQueryString="true" />
                            </rule>
                                                            ';
                                
                    return  $processing_response;   
                }
                
                
            function _init_block_default_wp_login_php($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                        
  
                }
                
            function _callback_saved_block_default_wp_login_php($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return  $processing_response;
                        
                    //prevent from blocking if the new_wp_login_php is empty
                    $new_wp_login_php     =   $this->wph->functions->get_module_item_setting('new_wp_login_php');
                    if (empty(  $new_wp_login_php ))
                        return FALSE;  
                                        
                    
                    $rewrite_base   =   $this->wph->functions->get_rewrite_base( 'wp-login.php', FALSE, FALSE );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( 'index.php', TRUE, FALSE, 'site_path' );
                    
                    $text   =   '';
                    
                    if($this->wph->server_htaccess_config   === TRUE)
                        {           
                            $text   =       "RewriteCond %{ENV:REDIRECT_STATUS} ^$\n";
                            $text   .=      "RewriteRule ^" . $rewrite_base ." ".  $rewrite_to ."?wph-throw-404 [L]";
                        }
                        
                    if($this->wph->server_web_config   === TRUE)
                            $text   = '
                                        <rule name="wph-block_default_wp_login_php" stopProcessing="true">
                                            <match url="^'.  $rewrite_base   .'"  />
                                            <action type="Rewrite" url="'.  $rewrite_to .'?wph-throw-404" />  
                                        </rule>
                                                            ';
                               
                    $processing_response['rewrite'] = $text;    
                                                    
                    return  $processing_response;   
                }
                
            
                            

        }
?>