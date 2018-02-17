<?php
ob_start();
add_action('login_head','lps_login_logo_hide' );
add_action('login_head','lps_login_nav_link_hide' );
add_action('login_head','lps_login_logo_msg_hide');
add_action('login_head','lps_login_blog_link_hide');
add_action('login_head','lps_login_nav_color' );
add_action('login_head','lps_login_nav_hover_color' );
add_action('login_head','lps_login_button_border_radius' );
add_action('login_head','lps_login_logo' );
add_action('login_head','lps_login_logo_width');
add_action('login_head','lps_login_logo_height');
add_action('login_head','lps_login_button_color' );
add_action('login_head','lps_login_button_color_hover');
add_action('login_head','lps_login_nav_size');
add_filter( 'wp_logout', 'lps_logoutPage', 10, 2 );
add_filter('login_headerurl', 'lps_login_logo_link' );
add_filter('login_headertitle','lps_login_logo_tittle' );
add_filter('plugin_action_links', 'lps_action_links' ,10,2);
?>