<?php


    class WPH_conflict_handle_wp_fastest_cache
        {
                        
            static function init()
                {
                    add_action('plugins_loaded',        array('WPH_conflict_handle_wp_fastest_cache', 'wpcache') , -1);    
                }                        
            
            static function is_plugin_active()
                {
                    
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    
                    if(is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ))
                        return TRUE;
                        else
                        return FALSE;
                }
            
            static public function wpcache()
                {   
                    if( !   self::is_plugin_active())
                        return FALSE;
                    
                    global $wph;
                                        
                    //add_action('plugins_loaded', array('WPH_conflict_handle_wp_fastest_cache', 'plugins_loaded'));
                               
                }
                
            static function plugins_loaded()
                {
                    ob_start(array('WPH_conflict_handle_wp_fastest_cache', "callback"));
                }
            
            
            static function callback($content)
                {
                    
                    global $wph;
                    
                    $replacement_list   =   $wph->functions->get_replacement_list();
                                            
                    //replace the urls
                    $content =   $wph->functions->content_urls_replacement($content,  $replacement_list );    
                    
                    return $content;    
                }
                            
        }


?>