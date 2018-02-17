

    var WPH = {
            
            setting_reset : function () {
                
                var agree   =   confirm(wph_vars.reset_confirmation);
                if (!agree)
                    return false;
                    
                jQuery('#reset_settings_form').submit();
                
            }
            
    }