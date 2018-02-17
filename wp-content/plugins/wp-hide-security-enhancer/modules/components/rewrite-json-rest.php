<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_json_rest extends WPH_module_component
        {
            
            function get_component_title()
                {
                    return "JSON REST";
                }
                                                
            function get_module_settings()
                {
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_json_rest_v1',
                                                                    'label'         =>  __('Disable JSON REST V1 service',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('An API service for WordPress which is active by default.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                                                                    
                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_json_rest_v2',
                                                                    'label'         =>  __('Disable JSON REST V2 service',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('An API service for WordPress which is active by default.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                                'type'            =>  'split'
                                                                                
                                                                                );
                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'block_json_rest',
                                                                    'label'         =>  __('Block any JSON REST calls',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('Any call for JSON REST API service will be blocked.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                    
                                                                                
                    $this->module_settings[]                  =   array(
                                                                                'type'            =>  'split'
                                                                                
                                                                                );
                                                                    
                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_json_rest_wphead_link',
                                                                    'label'         =>  __('Disable output the REST API link tag into page header',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('By default a REST API link tag is being append to HTML.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                    
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_json_rest_xmlrpc_rsd',
                                                                    'label'         =>  __('Disable JSON REST WP RSD endpoint from XML-RPC responses',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('By default a WP RSD endpoint is being append to the XML respose.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_json_rest_template_redirect',
                                                                    'label'         =>  __('Disable Sends a Link header for the REST API',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('On template_redirect, disable Sends a Link header for the REST API.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  58
                                                                    
                                                                    );
                    
                                                                    
                    return $this->module_settings;   
                }
                
                
                
            function _init_disable_json_rest_v1($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    add_filter('json_enabled', '__return_false');
                    add_filter('json_jsonp_enabled', '__return_false');
                    
                }
                
                
            function _init_disable_json_rest_v2($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;

                    add_filter('rest_enabled', '__return_false');
                    add_filter('rest_jsonp_enabled', '__return_false');
                    
                }
                
                
            function _callback_saved_block_json_rest($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( 'index.php', TRUE, FALSE, 'site_path' );
                    
                    if($this->wph->server_htaccess_config   === TRUE)
                        {                                        
                            $text   =   "\nRewriteRule ^wp-json(.+) ".  $rewrite_to ."?wph-throw-404 [L]";
                        }
                    
                    if($this->wph->server_web_config   === TRUE)
                        $text   = '
                                    <rule name="wph-block_json_rest" stopProcessing="true">
                                        <match url="^wp-json"  />
                                        <action type="Rewrite" url="'.  $rewrite_to .'" />  
                                    </rule>
                                                        ';
                    
                               
                    $processing_response['rewrite'] = $text;            
                                
                    return  $processing_response; 

                    
                    
                }
            
            
            function _init_disable_json_rest_wphead_link($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;

                    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
                    
                }
            
                
            function _init_disable_json_rest_xmlrpc_rsd($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;

                    remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
                    
                }
           
           
            function _init_disable_json_rest_template_redirect($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;

                    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
                    
                }

        }
?>