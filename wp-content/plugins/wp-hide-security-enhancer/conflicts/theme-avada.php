<?php


    class WPH_conflict_theme_avada
        {
                        
            static function init()
                {
                    add_action('plugins_loaded',        array('WPH_conflict_theme_avada', 'run') , -1);    
                }                        
            
            static function is_theme_active()
                {
                    
                    $theme  =   wp_get_theme();
                    
                    if( ! $theme instanceof WP_Theme )
                        return FALSE;
                    
                    if (isset( $theme->template ) && strtolower( $theme->template ) == 'avada')
                        return TRUE;
                    
                    return FALSE;
                    
                }
            
            static public function run()
                {   
                    if( !   self::is_theme_active())
                        return FALSE;
                    
                    global $wph;
                                        
                    add_filter ('fusion_dynamic_css_final', array('WPH_conflict_theme_avada', 'url_replacement'), 999);
                    
                    //flush avada cache when settings changes
                    add_action('wph/settings_changed',  'avada_reset_all_cache');
                               
                }
                 
            static function url_replacement( $css )
                {
                    
                    global $wph;
                    
                    $replacement_list   =   $wph->functions->get_replacement_list();
                                            
                    //replace the urls
                    $css =   $wph->functions->content_urls_replacement( $css,  $replacement_list );    
                    
                    return $css;    
                }
                            
        }


?>