<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_general_headers extends WPH_module_component
        {
            function get_component_title()
                {
                    return "Headers";
                }
                                    
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'remove_x_powered_by',
                                                                    'label'         =>  'Remove X-Powered-By Header',
                                                                    'description'   =>  __('Remove X-Powered-By Header if being set.', 'wp-hide-security-enhancer') . ' ' .
                                                                                        __('More details at ', 'wp-hide-security-enhancer') . '<a target="_blank" href="http://www.wp-hide.com/documentation/request-headers/">Request Headers</a>',
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  70
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'remove_x_pingback',
                                                                    'label'         =>  'Remove X-Pingback Header',
                                                                    'description'   =>  __('Remove X-Pingback Header if being set.', 'wp-hide-security-enhancer') . ' ' .
                                                                                        __('More details at ', 'wp-hide-security-enhancer') . '<a target="_blank" href="http://www.wp-hide.com/documentation/request-headers/">Request Headers</a>',
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  70
                                                                    );
                                                                    
                    return $this->module_settings;   
                }
                
                
                
            function _init_remove_x_powered_by($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                        
                    
                }
                
            function _callback_saved_remove_x_powered_by($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE; 
                    
                    if($this->wph->server_htaccess_config   === TRUE)                               
                        $processing_response['rewrite'] = '
                            <FilesMatch "">
                                <IfModule mod_headers.c>
                                    Header unset X-Powered-By
                                </IfModule>
                            </FilesMatch>';
                            
                    if($this->wph->server_web_config   === TRUE)
                        {
                            //this goes after </rules> section
                            //to be implemented at a later versoin 
                            /*
                            $processing_response['rewrite'] = '
                                    <outboundRules>
                                      <rule name="wph-bcdscsdh">  
                                            <match serverVariable="RESPONSE_X-POWERED-BY" pattern=".*" ignoreCase="true" />
                                            <action type="Rewrite" value="" />  
                                        </rule>
                                   </outboundRules>
                                    ';
                            */
                            
                            $processing_response['rewrite'] =   '';
                        }
                                
                    return  $processing_response;   
                }
                
                
            function _init_remove_x_pingback($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                        
                    
                }
                
            function _callback_saved_remove_x_pingback($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE; 
                    
                    if($this->wph->server_htaccess_config   === TRUE)                               
                        $processing_response['rewrite'] = '
                            <FilesMatch "">
                                <IfModule mod_headers.c>
                                    Header unset X-Pingback
                                </IfModule>
                            </FilesMatch>';
                            
                    if($this->wph->server_web_config   === TRUE)
                        {
                            
                            $processing_response['rewrite'] =   '';
                        }
                                
                    return  $processing_response;   
                }


        }
?>