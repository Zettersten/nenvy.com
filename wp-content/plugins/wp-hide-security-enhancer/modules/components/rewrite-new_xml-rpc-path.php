<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_new_xml_rpc_path extends WPH_module_component
        {
            
            function get_component_title()
                {
                    return "XML-RPC";
                }
                                                
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'new_xml_rpc_path',
                                                                    'label'         =>  __('New XML-RPC Path',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('The default XML-RPC path is set to xmlrpc.php. If not used you can leave empty and block the service using the following area.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'value_description' =>  __('e.g. my-xml-rpc.php',    'wp-hide-security-enhancer'),
                                                                    'input_type'    =>  'text',
                                                                    
                                                                    'sanitize_type' =>  array(array($this->wph->functions, 'sanitize_file_path_name'), array($this->wph->functions, 'php_extension_required')),
                                                                    'processing_order'  =>  50
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'block_xml_rpc',
                                                                    'label'         =>  __('Block default xmlrpc.php',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('XML-RPC gives others the ability to talk to your WordPress site. If not used you should disable. Keep in mind that some plugins like Jetpack use this API.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  55
                                                                    
                                                                    );
                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_xml_rpc_auth',
                                                                    'label'         =>  __('Disable XML-RPC authentication',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('Filter whether XML-RPC methods requiring authentication, such as for publishing purposes, are enabled.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  55
                                                                    
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'remove_xml_rpc_tag',
                                                                    'label'         =>  __('Remove pingback',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('Remove pingback link tag from theme.',    'wp-hide-security-enhancer'),
                                                                    
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
                
                
                
            function _init_new_xml_rpc_path($saved_field_data)
                {
                    if(empty($saved_field_data))
                        return FALSE;
                    
                    //add default plugin path replacement
                    $old_url    =   trailingslashit(    site_url()  )   . 'xmlrpc.php';
                    $new_url    =   trailingslashit(    site_url()  )   . $saved_field_data;
                    $this->wph->functions->add_replacement( $old_url ,  $new_url );
                }
                
            function _callback_saved_new_xml_rpc_path($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    //check if the field is noe empty
                    if(empty($saved_field_data))
                        return  $processing_response; 
                    
                    $file_path   =   $this->wph->functions->get_url_path( trailingslashit(site_url()) . 'xmlrpc.php'    );
                    
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( $file_path, TRUE, FALSE );
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        $processing_response['rewrite'] = "\nRewriteRule ^"    .   $saved_field_data  .   ' '. $rewrite_to .' [L,QSA]';
                    
                    if($this->wph->server_web_config   === TRUE)
                        $processing_response['rewrite'] = '
                            <rule name="wph-new_xml_rpc_path" stopProcessing="true">
                                <match url="^'.  $saved_field_data   .'"  />
                                <action type="Rewrite" url="'.  $rewrite_to .'"  appendQueryString="true" />
                            </rule>
                                                            ';
                                
                    return  $processing_response;   
                }
                
   
            function _callback_saved_block_xml_rpc($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( 'index.php', TRUE, FALSE, 'site_path' );
                    
                    $text   =   '';
                    
                    if($this->wph->server_htaccess_config   === TRUE)
                        {                                        
                            $text   =   "RewriteCond %{ENV:REDIRECT_STATUS} ^$\n";
                            $text   .=   "RewriteRule ^xmlrpc.php ".  $rewrite_to ."?wph-throw-404 [L]";
                        }
                    
                    if($this->wph->server_web_config   === TRUE)
                        $text   = '
                                    <rule name="wph-block_xml_rpc" stopProcessing="true">
                                        <match url="^xmlrpc.php"  />
                                        <action type="Rewrite" url="'.  $rewrite_to .'?wph-throw-404" />  
                                    </rule>
                                                        ';
                    
                               
                    $processing_response['rewrite'] = $text;            
                                
                    return  $processing_response;     
                    
                    
                }
                
            function _init_disable_xml_rpc_auth($saved_field_data)
                {
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    
                    add_filter( 'xmlrpc_enabled', '__return_false' ); 
                    
                }
            
                
            function _init_remove_xml_rpc_tag($saved_field_data)
                {
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    
                    add_filter('wp-hide/ob_start_callback', array($this, 'remove_xml_rpc_tag'));
                    
                }
                
                
            function remove_xml_rpc_tag( $buffer )
                {
                    
                    $result   = preg_match_all('/(<link([^>]+)rel=("|\')pingback("|\')([^>]+)?\/?>)/im', $buffer, $founds);
    
                    if(!isset($founds[0])   ||  count($founds[0])    <   1)
                        return $buffer;
    
                    if(count($founds[0]) > 0)
                        {
                            foreach ($founds[0]  as  $found)
                                {
                                    if(empty($found))
                                        continue;

                                    $buffer =   str_replace($found, "", $buffer);
                                    
                                }
                            
                            
                        }
                    
                    return $buffer;
     
                }


        }
?>