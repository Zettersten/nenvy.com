<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_rewrite_new_upload_path extends WPH_module_component
        {
            
            function get_component_title()
                {
                    return "Uploads";
                }
                                    
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'new_upload_path',
                                                                    'label'         =>  __('New Uploads Path',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('The default uploads path is set to',    'wp-hide-security-enhancer') . ' <strong>'. str_replace(get_bloginfo('wpurl'), '' ,$this->wph->default_variables['upload_url'])  .'</strong>
                                                                                         '. __('More details can be found at',    'wp-hide-security-enhancer') .' <a href="http://www.wp-hide.com/documentation/rewrite-uploads/" target="_blank">Link</a>',
                                                                    
                                                                    'value_description' =>  __('e.g. my_uploads',    'wp-hide-security-enhancer'),
                                                                    'input_type'    =>  'text',
                                                                    
                                                                    'sanitize_type' =>  array(array($this->wph->functions, 'sanitize_file_path_name')),
                                                                    'processing_order'  =>  40
                                                                    );
                                                                    
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'block_upload_url',
                                                                    'label'         =>  __('Block uploads URL',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('Block upload files from being accesible through default urls.',    'wp-hide-security-enhancer') . ' <br />'.__('If set to Yes, all new images inserted into posts will use the new Upload Url, as old url become blocked. Using the No, new images inserted will use old url, which however are being updated on front side. This may be helpful on plugin disable, so image urls can be accessible as before.',    'wp-hide-security-enhancer').'<br />'. __('Apply only if',    'wp-hide-security-enhancer') .' <b>New Upload Path</b> '.__('is not empty.',    'wp-hide-security-enhancer'),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower'),
                                                                    'processing_order'  =>  45
                                                                    
                                                                    );
                                                                    
                    return $this->module_settings;   
                }
                
                
                
            function _init_new_upload_path($saved_field_data)
                {
                    if(empty($saved_field_data))
                        return FALSE;
                    
                    //Preserver uploads urls
                    $preserve_upload_url    =   TRUE;
                    //only within admin
                    if( ! is_admin() )
                        $preserve_upload_url    =   FALSE;
                    //only if block_upload_url is set to no
                    if($preserve_upload_url &&  $this->wph->functions->get_module_item_setting('block_upload_url')   !=  'no')
                        $preserve_upload_url    =   FALSE;
                    if($preserve_upload_url &&  defined('DOING_AJAX')   &&  constant('DOING_AJAX')  === TRUE)
                        {
                            if(isset($_POST['action'])   &&  !in_array(sanitize_text_field($_POST['action']), array('query-attachments', 'upload-attachment', 'send-attachment-to-editor', 'set-post-thumbnail')))
                                $preserve_upload_url    =   FALSE;
                        }
                    
                    if( $preserve_upload_url    === TRUE )
                        {
                            //preserve the links
                            $this->wph->functions->add_replacement( $this->wph->default_variables['upload_url'], 'WPH-preserved-upload-url', 'high');
                            
                            //restore the original url
                            $this->wph->functions->add_replacement( 'WPH-preserved-upload-url', $this->wph->default_variables['upload_url'], 'low');
                            
                            return;
                        }


                    add_filter('upload_dir',            array( $this, 'upload_dir' ), 999);
                    
                    //add default plugin path replacement
                    $new_upload_path        =   $this->wph->functions->untrailingslashit_all(    $this->wph->functions->get_module_item_setting('new_upload_path')  );
                    $new_url                =   trailingslashit(    site_url()  )   . $new_upload_path;
                    $this->wph->functions->add_replacement( $this->wph->default_variables['upload_url'], $new_url);
                    
                }
                
            function _callback_saved_new_upload_path($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    //check if the field is noe empty
                    if(empty($saved_field_data))
                        return  $processing_response; 
                    
                    $wp_upload_dir  =   wp_upload_dir(); 
                                        
                    $uploads_path =   $this->wph->functions->get_url_path(   $wp_upload_dir['baseurl']   );
                    
                    $path           =   '';
                    if(!empty($this->wph->default_variables['wordpress_directory']))
                        $path           =   trailingslashit($this->wph->default_variables['wordpress_directory']);
                    $path           .=  trailingslashit(   $saved_field_data   );
                    
                    $rewrite_base   =   $this->wph->functions->get_rewrite_base( $saved_field_data, FALSE );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( $uploads_path );                    
                               
                    if($this->wph->server_htaccess_config   === TRUE)
                        $processing_response['rewrite'] = "\nRewriteRule ^"    .   $rewrite_base   .   '(.+) '. $rewrite_to .'$1 [L,QSA]';
                        
                    if($this->wph->server_web_config   === TRUE)
                        $processing_response['rewrite'] = '
                            <rule name="wph-new_upload_path" stopProcessing="true">
                                <match url="^'.  $rewrite_base   .'(.+)"  />
                                <action type="Rewrite" url="'.  $rewrite_to .'{R:1}"  appendQueryString="true" />
                            </rule>
                                                            ';
                                
                    return  $processing_response;   
                }
                
            
            function upload_dir($data)
                {
              
                    $new_upload_path        =   $this->wph->functions->untrailingslashit_all(    $this->wph->functions->get_module_item_setting('new_upload_path')  );
                    
                    $new_url                =   trailingslashit(    site_url()  )   . $new_upload_path;
                    
                    //$data['baseurl']        =   $new_url;
                    
                    //add replacement
                    if(! ($this->wph->functions->replacement_exists( $this->wph->default_variables['upload_url'] )))
                        {
                            //prevent media images from being replaced on admin, as when plugin disable the links will not work anymore
                            $block_upload_url   =   $this->wph->functions->get_module_item_setting('block_upload_url');
                            if(!is_admin() ||  (is_admin() && !empty($block_upload_url) &&  $block_upload_url   !=  'no'))
                                {
                                    $this->wph->functions->add_replacement($this->wph->default_variables['upload_url'], $new_url);
                                }
                        }
                       
                    return $data;   
                }
                            
            function _callback_saved_block_upload_url($saved_field_data)
                {
                    $processing_response    =   array();
                    
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                    
                    //prevent from blocking if the wp-include is not modified
                    $new_upload_path     =   $this->wph->functions->get_module_item_setting('new_upload_path');
                    if (empty(  $new_upload_path ))
                        return FALSE;
                    
                    $wp_upload_dir  =   wp_upload_dir();
                    
                    $default_upload_url    =   untrailingslashit   (  $wp_upload_dir['baseurl']  );
                    $default_upload_url    =   str_replace(    site_url(), "", $default_upload_url);
                    $default_upload_url    =   ltrim(rtrim($default_upload_url, "/"),  "/");
                                
                    $rewrite_base   =   $this->wph->functions->get_rewrite_base( $default_upload_url, FALSE );
                    $rewrite_to     =   $this->wph->functions->get_rewrite_to_base( 'index.php', TRUE, FALSE, 'site_path' );
                    
                    $text   =   '';
                    
                    if($this->wph->server_htaccess_config   === TRUE)
                        {                                        
                            $text   =   "RewriteCond %{ENV:REDIRECT_STATUS} ^$\n";
                            $text   .=   "RewriteRule ^".   $rewrite_base   ."(.+) ".  $rewrite_to ."?wph-throw-404 [L]";
                        }
                        
                    if($this->wph->server_web_config   === TRUE)
                            $text   = '
                                        <rule name="wph-block_upload_url" stopProcessing="true">
                                            <match url="^'.  $rewrite_base   .'(.+)"  />
                                            <action type="Rewrite" url="'.  $rewrite_to .'?wph-throw-404" />  
                                        </rule>
                                                            ';
                               
                    $processing_response['rewrite'] = $text;            
                                
                    return  $processing_response;     
                    
                    
                }


        }
?>