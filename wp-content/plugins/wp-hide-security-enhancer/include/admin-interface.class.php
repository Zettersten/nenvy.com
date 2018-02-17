<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_interface
        {
            var $screen_slug;
            var $tab_slug;
            
            var $module;
            var $module_settings;
            var $interface_data;
            
            var $wph;
            var $functions;
                   
            function __construct()
                {
                    global $wph;
                    $this->wph          =   &$wph;
                    
                    $this->functions    =   new WPH_functions();
                    
                }
            
            function __destruct()
                {
                
                }
                   
            function _render($interface_name)
                {
                    
                    $this->screen_slug  =   sanitize_text_field($_GET['page']);
                    $this->tab_slug     =   isset($_GET['component'])   ?   sanitize_text_field($_GET['component'])  :   FALSE;
     
                    //identify the module by slug
                    $this->module   =   $this->functions->get_module_by_slug($this->screen_slug);
                    
                    if(empty($this->tab_slug)   &&  $this->module->use_tabs  === TRUE)
                        {
                            //get the first component
                            foreach($this->module->components   as  $module_component)
                                {
                                    if( ! $module_component->title)
                                        continue;
                                    
                                    $this->tab_slug =   $module_component->id;
                                    break;
                                }  
                            
                        }
                   
                    $this->_load_interface_data();
   
                    $this->_generate_interface_html();
                    
                }
            
            function _load_interface_data()
                {
                    $this->module_settings  =   $this->functions->filter_settings(   $this->module->get_module_settings($this->tab_slug ));
                        
                    $this->interface_data   =   $this->module->get_interface_data();                      
                }
                  
            function _generate_interface_html()
                {
                    
                    ?>
                        <div id="wph" class="wrap">
                            <h1><?php echo $this->interface_data['title'] ?></h1>
                         
                            <?php
                                
                                echo $this->functions->get_ad_banner();
                                
                                if($this->module->use_tabs  === TRUE)
                                    $this->_generate_interface_tabs();
                            
                            ?>
                                                     
                            <div id="poststuff">
                                
                                <?php if(!empty($this->interface_data['handle_title'])) { ?>
                                <div class="postbox">
                                    <h3 class="handle"><?php echo $this->interface_data['handle_title'] ?></h3>
                                </div>
                                <?php } ?>
                                
                                    <div class="inside">
                                           
                                        <form method="post" action="">
                                        <?php wp_nonce_field( 'wph/interface_fields', 'wph-interface-nonce' ); ?>
                                            
                                            <?php
                                            
                                                $outputed_module    =   FALSE;
                                                
                                                foreach($this->module_settings  as  $module_setting)
                                                    {
                                                                                       
                                                        $this->_generate_module_html($module_setting, $outputed_module);    
                                                        
                                                        
                                                        $outputed_module    =   TRUE;
                                                    }
                                            
                                            
                                            ?>
                                                
                                                <table class="wph_input widefat">
                                                                <tbody>
                                                <tr class="submit">
                                                    <td class="label">&nbsp;</td>
                                                    <td class="label">
                                                        <input type="submit" value="Save" class="button-primary alignright"> 
                                                    </td>    
                                                </tr>
                                                    
                                                    </tbody>
                                                </table>
                                        </form> 
                                    </div>
                              
                            </div>
                        </div>
                  
                <?php   
                    
                }
                
                
            function _generate_module_html($module_setting, $outputed_module)
                {
                    
                    if(isset($module_setting['type'])   &&  $module_setting['type']    ==  'split'  &&  $outputed_module    === TRUE)
                        {
                            ?>
                            <p>&nbsp;</p>
                            <?php
                            
                            return;
                        }
                        else if (isset($module_setting['type'])   &&  $module_setting['type']    ==  'split'  &&  $outputed_module    !== TRUE)
                        {
                        
                            return;
                        }   
                        
                    if($module_setting['visible']   === FALSE)
                        return;
                    
                    ?>
                        <div class="postbox">
                        <table class="wph_input widefat">
                            <tbody>
                        
                                <tr>
                                    <td class="label">
                                        <label for=""><?php echo $module_setting['label'] ?></label>
                                        <?php
                                            
                                            if(is_array($module_setting['description']))
                                                {
                                                    foreach($module_setting['description']  as  $description)
                                                        {
                                                            ?>
                                                                <div class="description"><?php echo nl2br($description) ?></div>
                                                            <?php
                                                        }    
                                                }
                                                else
                                                {
                                                    ?>
                                                        <p class="description"><?php echo nl2br($module_setting['description']) ?></p>
                                                    <?php 
                                                } ?>
                                    </td>
                                </tr>
                                <tr> 
                                    <td class="data">
                                        <?php if(!empty($module_setting['value_description'])) { ?><p class="description"><?php echo $module_setting['value_description'] ?></p><?php } ?>
                                        <?php
                                        
                                            $option_name    =   $module_setting['id'];
                                            $value          =   $this->wph->get_setting_value(  $option_name, $module_setting['default_value']);
                                            
                                            switch($module_setting['input_type'])
                                                {
                                                    case 'text' :
                                                                    $class          =   'text';
                                                                    
                                                                    ?><input name="<?php echo $module_setting['id'] ?>" class="<?php echo $class ?>" value="<?php echo esc_html($value) ?>" placeholder="<?php echo esc_html($module_setting['placeholder']) ?>" type="text"><?php
                                                                    
                                                                    break;
                                                                    
                                                    case 'radio' :
                                                                    $class          =   'radio';
                                                                                                                                                                    
                                                                    ?>
                                                                    <fieldset>
                                                                        <?php  
                                                                        
                                                                            foreach($module_setting['options']  as  $option_value  =>  $option_title)
                                                                                {
                                                                                    ?><label><input type="radio" class="<?php echo $class ?>" <?php checked($value, $option_value)  ?> value="<?php echo $option_value ?>" name="<?php echo $module_setting['id'] ?>"> <span><?php echo esc_html($option_title) ?></span></label><?php
                                                                                }
                                                                        
                                                                        ?>
                                                                    </fieldset>
                                                                    <?php
                                                                    
                                                                    break;    
                                                }
                                                
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>   
                    
                    <?php   
                    
                }
                
                
            function _generate_interface_tabs()
                {
                    
                    ?> 
                    <h2 class="nav-tab-wrapper">
                        <?php
                            
                            //output all module components as tabs
                            foreach($this->module->components   as  $module_component)
                                {
                                    if( ! $module_component->title)
                                        continue;
                                    
                                    $class  =   '';
                                    if($module_component->id    ==  $this->tab_slug)
                                        $class  =   'nav-tab-active';
                                    
                                    ?>   
                                    <a href="<?php echo esc_url(admin_url( 'admin.php?page=' . $this->screen_slug . '&component=' . $module_component->id)); ?>" class="nav-tab <?php echo $class ?>"><?php echo $module_component->title ?></a>
                                    <?php                                    
                                }
                        
                        ?>
                        <a href="javascript:void(0)" class="button-secondary cancel alignright" onClick="WPH.setting_reset()" id="reset_settings" type="button">Reset All Settings</a>
                    </h2>
                    <form id="reset_settings_form" action="<?php echo esc_url(admin_url( 'admin.php?page=wp-hide')) ?>" method="post">
                        <input type="hidden" name="reset-settings" value="true" />
                        <?php wp_nonce_field( 'wp-hide-reset-settings', '_wpnonce' ); ?>
                    </form>
                    
                    <?php
                    
                }
        } 


?>