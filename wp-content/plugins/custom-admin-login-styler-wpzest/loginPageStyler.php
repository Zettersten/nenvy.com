<?php
/* 
 *Plugin Name: Custom Admin Login | WPZest 
 *Plugin URI: http://www.wpzest.com/plugins/
 *Description: This plugin allows you to customize the appearance of the WordPress Login Screen as you like to see.
 *Version: 1.2.0
 *Author: WPZest
 *Author URI: http://www.wpzest.com
 *License: GPLv2
 */
ob_start();

function lps_login_nav_color(){

	echo '<style> .login #login a{ color : '.get_option('lps_login_nav_color' ).';}</style>';
}

function lps_login_nav_hover_color(){

	echo '<style> .login #login a:hover{ color : '.get_option('lps_login_nav_hover_color' ).';}</style>';
}

function lps_login_nav_link_hide(){

	if (get_option('lps_login_nav_link_hide') == 1)
	{ 

		echo '<style> .login #nav {display:none;}</style>';
	}
	else
	{
	    echo '<style> .login #nav {display:block;}</style>';
	}
}


function lps_login_logo_hide(){

	if (get_option('lps_login_logo_hide') == 1)
	{ 

		echo '<style> .login h1 a {display:none !important;}</style>';
	}
	else
	{
	    echo '<style> .login h1 a {display:block;}</style>';
	}
}

function lps_login_logo_msg_hide(){

	if(get_option('lps_login_logo_msg_hide')== 1)
	{
		echo '<style> #login_error,.login .message{display:none;}</style>';
	}
	else
	{
	   
		echo '<style> #login_error,.login .message{display:block;}</style>';
	}
	
}

function lps_login_blog_link_hide(){

	if(get_option('lps_login_blog_link_hide') == 1)
	{
		echo '<style> .login #backtoblog{ display:none;}</style>';
	}
	else
	{
		echo '<style> .login #backtoblog{ display:block;}</style>';
	}
}

function lps_login_button_border_radius(){

	echo '<style> .login .button-primary{ border-radius:'.get_option('lps_login_button_border_radius').'px; } </style>';
}

function lps_login_logo_link(){

	return  get_option('lps_login_logo_link');
}


function lps_login_logo_tittle(){

	return get_option('lps_login_logo_tittle');
}


function lps_login_logo(){
	if(get_option('lps_login_logo') != '')
	{	
	   echo '<style> .login h1 a{ display:block; background-size:'.get_option('lps_login_logo_width').'px,'.get_option('lps_login_logo_height').'px;  background-image:url('.get_option('lps_login_logo').');} </style>';
    }
    
}

function lps_login_logo_width(){

	if(get_option('lps_login_logo_width')!= '')
    {
	   echo '<style> .login h1 a{ width:'.get_option('lps_login_logo_width').'px;}</style>';
    }
}

function lps_login_logo_height(){
    if(get_option('lps_login_logo_height')!= '')
    {
	   echo '<style> .login h1 a{ height:'.get_option('lps_login_logo_height').'px;}</style>';
    }
}


function lps_login_button_color(){

	echo '<style> .login .button-primary{background-color:'.get_option('lps_login_button_color').'!important;

    border-color:'.get_option('lps_login_button_border_color').'; border:1px solid '.get_option('lps_login_button_border_color').';

    color:'.get_option('lps_login_button_text_color').';
 

    }</style>';
}


function lps_login_button_color_hover(){

	echo '<style> .login .button-primary:hover {background-color:'.get_option('lps_login_button_color_hover').'!important;

    border-color:'.get_option('lps_login_button_border_color_hover').'; border:1px solid '.get_option('lps_login_button_border_color_hover').';

    color:'.get_option('lps_login_button_text_color_hover').';

    }</style>';
}

function lps_login_nav_size(){

	echo '<style> .login #nav,
.login #backtoblog{font-size:'.get_option('lps_login_nav_size').'px;}</style>';
}
function lps_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/loginPageStyler.php' ) ) {
		$links[] = '<a href="' . get_bloginfo('url') . '/wp-admin/admin.php?page=lps_option">Settings</a>';;
	}
	return $links;
}
require('loginPageStylerOption.php'); 

if (get_option('lps_login_on_off')==1) {
	require 'lpsFiltersAndActions.php';	   
  
add_action( "wp_enqueue_scripts", "frontend_recaptcha_script" );

    function frontend_recaptcha_script()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            wp_register_script( "recaptcha", "https://www.google.com/recaptcha/api.js" );
            wp_enqueue_script( "recaptcha" );
			
        }   
    }

    add_action( "comment_form", "display_comment_recaptcha" );

    function display_comment_recaptcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey=""></div>
                <input name="submit" type="submit" value="Submit Comment">
            <?php   
        }
        
    }

    add_filter( "preprocess_comment", "verify_comment_captcha" );

    function verify_comment_captcha( $commentdata )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" .$_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $commentdata;
            }
            else
            {
                echo __( "Bots are not allowed to submit comments." );
                return null;
            }
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                echo __( "Bots are not allowed to submit comments. If you are not a bot then please enable JavaScript in browser." );
                return null;    
            }   
            else
            {
                return $commentdata;
            }
        }
    }

    add_action( "login_enqueue_scripts", "login_recaptcha_script" );

    function login_recaptcha_script()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            wp_register_script( "recaptcha_login", "https://www.google.com/recaptcha/api.js" );
            wp_enqueue_script( "recaptcha_login" );
        }
    }

    add_action( "login_form", "display_login_captcha" );

    function display_login_captcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key' ); ?>"></div>
            <?php
        }   
    }

    add_filter( "wp_authenticate_user", "verify_login_captcha", 10, 2 );

    function verify_login_captcha( $user, $password )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $user;
            }
            else
            {
                return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
            } 
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
            }
            else
            {
                return $user;
            }
        }   
    }

    add_action( "register_form", "display_register_captcha" );


    function display_register_captcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey="<?php echo get_option( 'captcha_site_key' ); ?>"></div>
            <?php   
        }       
    }

    add_filter( "registration_errors", "verify_registration_captcha", 10, 3 );

    function verify_registration_captcha( $errors, $sanitized_user_login, $user_email )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
           $recaptcha_secret = get_option( 'captcha_secret_key' );
		   print_r($recaptcha_secret);
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $errors;
            }
            else
            {
                $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
            }
        }
        else
        {   
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
            }
            else
            {
                return $errors;
            }
            
        }   

        return $errors;
    }


    add_action( "lostpassword_form", "display_login_captcha" );
    add_action( "lostpassword_post", "verify_lostpassword_captcha" );

    function verify_lostpassword_captcha()
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
           echo $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return;
            }
            else
            {
                wp_die( __( "<strong>ERROR</strong>: You are a bot" ) );
            }
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                wp_die( __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) ); 
            }
            else
            {
                return;
            }
            
        }   

        return $errors; 
    } 


	
}
?>