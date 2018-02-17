<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_slash extends WPH_module_component
        {
            function get_component_title()
                {
                    return "URL Slash";
                }
                                        
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'add_slash',
                                                                    'label'         =>  __('URL\'s add Slash',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  array( 
                                                                                                __('Add an end slash to any links without one. Disguise the existence of files and folders, they will be all slashed.',    'wp-hide-security-enhancer'). '<br /> '. __('On certain systems this can produce a small lag measured in milliseconds.',    'wp-hide-security-enhancer')
                                                                                                ,__('More details can be found at',    'wp-hide-security-enhancer') .' <a href="https://www.wp-hide.com/documentation/rewrite-url-slash/" target="_blank">Documentation</a>'),

                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  3
                                                                    );
                                                                    
                    return $this->module_settings;   
                }
                
            
            function _init_add_slash($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return;
                        
                    //nothing to do at the moment
                }
                
            function _callback_saved_add_slash($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    if($this->wph->server_htaccess_config   === TRUE)                             
                        //\nRewriteCond %{ENV:REDIRECT_STATUS} !^$"
                        $processing_response['rewrite'] =  "\nRewriteCond %{REQUEST_URI} /+[^\.]+$"
                                                            . "\nRewriteRule ^(.+[^/])$ %{REQUEST_URI}/ [R=301,L]";
                                                            
                    if($this->wph->server_web_config   === TRUE)
                        $processing_response['rewrite'] = '
                                
                                <rule name="wph-add_slash" stopProcessing="true">  
                                    <match url="^(.+[^/])$" />  
                                    <conditions>  
                                        <add input="{REQUEST_URI}" matchType="Pattern" pattern="/+[^\.]+$"  />  
                                    </conditions>  
                                    <action type="Redirect" redirectType="Permanent" url="{R:1}/" />  
                                </rule>
                            
                                                            ';
                                    
                    return  $processing_response;   
                }
                
           
         

        }
?>