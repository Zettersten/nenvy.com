<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width" />
    <link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_uri(); ?>" />
    <link href="<?php echo get_template_directory_uri(); ?>/stylesheets/screen.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo get_template_directory_uri(); ?>/stylesheets/bootstrap.css" rel="stylesheet" type="text/css" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

    <div id="wrapper" class="hfeed">
        <header id="header" role="banner">
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container">
                    <a class="navbar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_html( get_bloginfo( 'name' ) ); ?>" rel="home">
                        <img src="http://media.nenvy.com/blog/2018/02/5a7d6aa75eb87-5a7d6aa75ebd4Nenvy-Logo-BW-Medium.png-300x131.png" height="72" alt="">
                    </a>
                    <?php wp_nav_menu( array( 'theme_location' => 'main-menu', 'menu_class' => 'navbar-nav', 'container' => 'ul' ) ); ?>
                </div>
            </nav>
        </header>
        
        <div id="container">