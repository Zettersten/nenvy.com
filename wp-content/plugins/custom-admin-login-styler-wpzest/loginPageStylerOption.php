<?php
ob_start();
add_action( 'admin_menu', 'lps_menu');

function lps_menu(){
	add_menu_page( 'Custom Admin Login', 'Custom Admin Login','administrator', 'lps_option', 'lps_settings_page', '', 65);
	add_action( 'admin_init', 'lps_register_settings' );
/* Vars */
	$page_hook_id = lps_option_setings_page_id();
	/* Do stuff in settings page, such as adding scripts, etc. */

	/* Load the JavaScript needed for the settings screen. */
	add_action( 'admin_enqueue_scripts', 'lps_option_enqueue_scripts' );
	add_action( "admin_footer-{$page_hook_id}", 'lps_option_footer_scripts' );

}

/**
 * Utility: Page Hook
 * The Settings Page Hook, it's the same with global $hook_suffix.
 * @since 0.1.0
 */
function lps_option_setings_page_id(){
	return 'toplevel_page_lps_option';
}

/**
 * Load Script Needed For Meta Box
 * @since 0.1.0
 */
function lps_option_enqueue_scripts( $hook_suffix ){
	$page_hook_id = lps_option_setings_page_id();
	if ( $hook_suffix == $page_hook_id ){
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );
        wp_enqueue_style('lps-admin-custom-css', WP_PLUGIN_URL.'/custom-admin-login-styler-wpzest/css/custom.css');
	}
}

/**
 * Footer Script Needed for Meta Box:
 * - Meta Box Toggle.
 * - Spinner for Saving Option.
 * - Reset Settings Confirmation
 * @since 0.1.0
 */
function lps_option_footer_scripts(){
	$page_hook_id = lps_option_setings_page_id();
?>
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function($) {
		// toggle
		$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		postboxes.add_postbox_toggles( '<?php echo $page_hook_id; ?>' );
	});
	//]]>
</script>
<?php
}

function lps_register_settings(){
	register_setting('lps-settings-group', 'lps_login_nav_color');
	register_setting('lps-settings-group', 'lps_login_nav_hover_color');
	register_setting('lps-settings-group', 'lps_login_nav_link_hide' );
	register_setting('lps-settings-group', 'lps_login_logo_hide' );
	register_setting('lps-settings-group', 'lps_login_logo_msg_hide');
	register_setting('lps-settings-group', 'lps_login_on_off');
	register_setting('lps-settings-group', 'lps_login_blog_link_hide');
	register_setting('lps-settings-group', 'lps_login_button_border_radius');
	register_setting('lps-settings-group', 'lps_login_logo_link');
	register_setting('lps-settings-group', 'lps_login_logo_tittle');
	register_setting('lps-settings-group', 'lps_login_logo');
	register_setting('lps-settings-group', 'lps_login_logo_width');
	register_setting('lps-settings-group', 'lps_login_logo_height');
	register_setting('lps-settings-group', 'lps_login_button_color');
	register_setting('lps-settings-group', 'lps_login_button_border_color');
	register_setting('lps-settings-group', 'lps_login_button_color_hover');
	register_setting('lps-settings-group', 'lps_login_button_text_color');
	register_setting('lps-settings-group', 'lps_login_button_text_color_hover');
	register_setting('lps-settings-group', 'lps_login_button_border_color_hover');
	register_setting('lps-settings-group', 'lps_login_nav_size');
	register_setting('lps-settings-group', 'captcha_site_key');
	register_setting('lps-settings-group', 'captcha_secret_key');
	
	
}

add_action( 'admin_enqueue_scripts', 'wp_enqueue_color_picker' );
function wp_enqueue_color_picker( ) {
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'thickbox' );
    wp_enqueue_script( 'thickbox' );
    wp_enqueue_script( 'media-upload' ); 
    wp_enqueue_script( 'wp-color-picker-script', WP_PLUGIN_URL .'/custom-admin-login-styler-wpzest/loginPageStyler.js', array( 'wp-color-picker' ), false, true );
    wp_enqueue_script( 'wp-color-picker-script', WP_PLUGIN_URL .'/custom-admin-login-styler-wpzest/loginPageStyler.js', array( 'wp-color-picker' ), false, true );
}


 function lps_settings_page(){
	  /* global vars */
	global $hook_suffix;

	/* utility hook */
	do_action( 'lps_option_settings_page_init' );

	/* enable add_meta_boxes function in this page. */
	do_action( 'add_meta_boxes', $hook_suffix );
	 ?>
 
 
<h1><center>Admin Settings | WPZest</center></h1>
<?php settings_errors(); ?>
				<form action="options.php" method="post" id="disable-comments">
					<?php settings_fields('lps-settings-group');?>

		<div class="fx-settings-meta-box-wrap">

			<form id="fx-smb-form" method="post" action="options.php">
				<div id="poststuff" style="padding-left: 7%;">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						
						<div id="postbox-container-1" class="postbox-container" style="width:50% !important" >

							<?php do_meta_boxes( $hook_suffix, 'side', null ); ?>
							<!-- #side-sortables -->

						</div><!-- #postbox-container-1 -->
						<div id="postbox-container-2" class="postbox-container"style="width:50% !important" >

							<?php do_meta_boxes( $hook_suffix, 'normal', null ); ?>
							<!-- #normal-sortables -->

						</div><!-- #postbox-container-2 -->

					</div><!-- #post-body -->

					<br class="clear">

				</div><!-- #poststuff -->

		</div><!-- .fx-settings-meta-box-wrap -->
	<div class="tabs tabs-style-topline">
							<nav>
                                <ul>
                                    <li><a href="#section-topline-1">
										<h1><?php echo '<img src="' . plugins_url( 'images/switch.png', __FILE__ ) . '" > '; ?></h1>
										<span>Login Settings</span></a>
									</li>
                                    <li><a href="#section-topline-2">
										<h1><?php echo '<img src="' . plugins_url( 'images/re-captcha.png', __FILE__ ) . '" > '; ?></h1>
										<span>reCaptcha Settings</span></a>
									</li>
                                    <li><a href="#section-topline-3">
										<h1><?php echo '<img src="' . plugins_url( 'images/logo-settings.png', __FILE__ ) . '" > '; ?></h1>
										<span>Logo Settings</span></a>
									</li>
                                    <li><a href="#section-topline-4">
										<h1><?php echo '<img src="' . plugins_url( 'images/login-setting.png', __FILE__ ) . '" > '; ?></h1>
										<span>Login Background Settings</span></a>
									</li>
                                    <li><a href="#section-topline-5">
										<h1><?php echo '<img src="' . plugins_url( 'images/lost-password.png', __FILE__ ) . '" > '; ?></h1>
										<span>Navigation Link Settings</span></a>
									</li>
                                    <li><a href="#section-topline-6">
										<h1><?php echo '<img src="' . plugins_url( 'images/custom-message.png', __FILE__ ) . '" > '; ?></h1>
										<span>Custom Message</span></a>
									</li>
                                    <li><a href="#section-topline-7">
										<h1><?php echo '<img src="' . plugins_url( 'images/form-setting.png', __FILE__ ) . '" > '; ?></h1>
										<span>Form Settings</span></a>
									</li>

                                    <li><a href="#section-topline-8">
										<h1><?php echo '<img src="' . plugins_url( 'images/button-settings.png', __FILE__ ) . '" > '; ?></h1>
										<span>Button Settings</span></a>
									</li>
                                    <li><a href="#section-topline-9">
										<h1><?php echo '<img src="' . plugins_url( 'images/logout-redirect.png', __FILE__ ) . '" > '; ?></h1>
										<span>Logout Redirect</span></a>
									</li>
                                                                        
                                </ul>
                            </nav>
							
<div class="content-wrap">
 
 
<section id="section-topline-1">
	<ul class="indent">
		<table class="form-table">

		    <tr valign='top'>
        <th scope='row'><?php _e('Enable Plugin :');?></th>
        <td>
            <div class="onoffswitch">
                     <input type="checkbox" name="lps_login_on_off" class="onoffswitch-checkbox"  id="myonoffswitch" value='1'<?php checked(1, get_option('lps_login_on_off')); ?> />
                     <label class="onoffswitch-label" for="myonoffswitch">
                     <span class="onoffswitch-inner"></span>
                     <span class="onoffswitch-switch"></span>
                     </label>
                    </div>


        </td>
      </tr>
        
      
      <tr valign='top'>
        <th scope='row'><?php _e('Hide Login Logo');?></th>
        <td>
            <div class="onoffswitch">
                     <input type="checkbox" name="lps_login_logo_hide" class="onoffswitch-checkbox"  id="myonoffswitch2" value='1'<?php checked(1, get_option('lps_login_logo_hide')); ?> />
                     <label class="onoffswitch-label" for="myonoffswitch2">
                     <span class="onoffswitch-inner"></span>
                     <span class="onoffswitch-switch"></span>
                     </label>
                    </div>


        </td>
      </tr>


      <tr valign='top'>
        <th scope='row'><?php _e('Hide Login Error Msg');?></th>
        <td>
            <div class="onoffswitch">
                     <input type="checkbox" name="lps_login_logo_msg_hide" class="onoffswitch-checkbox"  id="myonoffswitch3" value='1'<?php checked(1, get_option('lps_login_logo_msg_hide')); ?> />
                     <label class="onoffswitch-label" for="myonoffswitch3">
                     <span class="onoffswitch-inner"></span>
                     <span class="onoffswitch-switch"></span>
                     </label>
                    </div>


        </td>
      </tr>



      <tr valign='top'>
        <th scope='row'><?php _e('Hide Lost Password Link');?></th>
        <td>
            <div class="onoffswitch">
                     <input type="checkbox" name="lps_login_nav_link_hide" class="onoffswitch-checkbox"  id="myonoffswitch4" value='1'<?php checked(1, get_option('lps_login_nav_link_hide')); ?> />
                     <label class="onoffswitch-label" for="myonoffswitch4">
                     <span class="onoffswitch-inner"></span>
                     <span class="onoffswitch-switch"></span>
                     </label>
                    </div>

        </td>
      </tr>


      <tr valign='top'>
        <th scope='row'><?php _e('Hide Back to Blog Link');?></th>
        <td>
            <div class="onoffswitch">
                     <input type="checkbox" name="lps_login_blog_link_hide" class="onoffswitch-checkbox"  id="myonoffswitch5" value='1'<?php checked(1, get_option('lps_login_blog_link_hide')); ?> />
                     <label class="onoffswitch-label" for="myonoffswitch5">
                     <span class="onoffswitch-inner"></span>
                     <span class="onoffswitch-switch"></span>
                     </label>
                    </div>
        </td>
      </tr>

            
             </table>
			 
			   <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>




<section id="section-topline-2">
	<ul class="indent">
		<table class="form-table">
		<tr valign='top'>
				<th scope='row'><?php _e('Captcha site key');?></th>
				<td><label for='aptcha_site_key'>
					<input type='text' class='' id='captcha_site_key' name='captcha_site_key' value='<?php echo get_option('captcha_site_key'); ?>'/> </br></br>
					</label>
			    </td>
			</tr>
			
				<tr valign='top'>
				<th scope='row'><?php _e('Captcha secret key');?></th>
				<td><label for='captcha_secret_key'>
					<input type='text' class='' id='captcha_secret_key' name='captcha_secret_key' value='<?php echo get_option('captcha_secret_key'); ?>'/> </br></br>
					 </label>
			    </td>
			</tr>
		
			  </table>
			  		  <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
		<br><br>
		<table>
			<tr valign='top'>
				<th>
				<td>
					<?php
					echo __( '<p style="color:#000 !important; font-size:16px; letter-spacing:0.5px; line-height:30px;">You need to <a style="color:#006799; text-decoration:none;" href="https://www.google.com/recaptcha/admin" rel="external">Register Your Domain</a> and get keys to make this plugin work.Enter the key details below</p>' );
					echo __( '<p style="color:#000 !important; font-size:16px;letter-spacing:0.5px; line-height:30px;">1) Under Guides click on "Create an API key".<br>
								 2) Fill the "Register a new site" form.<br>
								 3) You should then be taken to the setup screen. You will only need the Site key and Secret key information.<br>
								 4) Copy the Site key and Secret key paste it into Custom Admin Login plugin.</p>');
					?>
			</td>
			</th>
			
			</tr>
		</table>
	</ul>
</section>



<section id="section-topline-3">
<ul class="indent">
		<table class="form-table">

			<tr valign="top">
			  <th scope="row"><?php _e('Logo Link'); ?></th>
			  <td><label for="lps_login_logo_link">
				  <input type="text" id="lps_login_logo_link"  name="lps_login_logo_link" size="40" value="<?php echo get_option( 'lps_login_logo_link' ); ?>"/>
				  <p class="description"><?php _e( 'Enter site url eg: www.google.com ,It will redirect user when logo is clicked'); ?></p>
				  </label>
			 </td>
		    </tr>			


		    <tr valign="top">
			  <th scope="row"><?php _e('Logo Title'); ?></th>
			  <td><label for="lps_login_logo_tittle">
				  <input type="text" id="lps_login_logo_tittle"  name="lps_login_logo_tittle" size="40" value="<?php echo get_option( 'lps_login_logo_tittle' ); ?>" />
				  <p class="description"><?php _e( 'Enter Tittle for logo eg:Powered by abcd. '); ?></p>
				  </label>
			 </td>
		    </tr>


		    <tr valign="top">
			  <th scope="row"><?php _e('Logo Image'); ?></th>
			  <td><label for="lps_login_logo">
				  <input id="image_location" type="text" name="lps_login_logo" value="<?php echo get_option('lps_login_logo'); ?>" size="30" />
                    <input class="onetarek-upload-button button" type="button" value="Upload Image" />					
				 </label>
			 </td>
		    </tr>


		    <tr valign="top">
			  <th scope="row"><?php _e('Logo Width'); ?></th>
			  <td><label for="lps_login_logo_width">
				  <input type='range'  id='lps_login_logo_width' name='lps_login_logo_width' min='0' max='350' value='<?php echo get_option('lps_login_logo_width'); ?>' oninput="this.form.amountInputW.value=this.value" /> <input type="number"  name="amountInputW" min="0" max="350" value='<?php echo get_option('lps_login_logo_width'); ?>' size='4' oninput="this.form.lps_login_logo_width.value=this.value"/>				  
				 </label>
			 </td>
		    </tr>


		    <tr valign="top">
			  <th scope="row"><?php _e('Logo Height'); ?></th>
			  <td><label for="lps_login_logo_height">
				  <input type='range'  id='lps_login_logo_height' name='lps_login_logo_height' min='0' max='200' value='<?php echo get_option('lps_login_logo_height'); ?>' oninput="this.form.amountInputH.value=this.value" /> <input type="number"  name="amountInputH" min="0" max="200" value='<?php echo get_option('lps_login_logo_height'); ?>' size='4' oninput="this.form.lps_login_logo_height.value=this.value" />				  
				 </label>
			 </td>
		    </tr>

</table>
					  
					  
					  <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>




<section id="section-topline-4">
<ul class="indent">
			<div class="overlay-yo">
			<a href="http://www.wpzest.com/wordpress-plugins/pro-custom-admin-login/">Buy Premium Version Now</a>
			</div>
		<table class="form-table">


		    <tr valign="top">
			  <th scope="row"><?php _e( 'Background Color' ); ?></th>
			  <td><label for="">
				  <input type="text" class="color_picker" id=""  name="" value="" />
				  <p class="description"><?php _e( 'Change background color'); ?></p>
				  </label>
			 </td>
		    </tr>


		    <tr valign="top">
			  <th scope="row"><?php _e('Background Image'); ?></th>
			  <td><label for="">
					<input id="" type="text" name="" value="" />
                    <input class="onetarek-upload-button button" type="button" value="Upload Image" />
					<p class='description'><?php _e('Upload or Select  Background Image'); ?></p>
				</label>
				</td>
		    </tr>


            <tr valign="top">
			  <th scope="row"><?php _e('Body Background Image Repeat'); ?></th>
			  <td><label for="">
				  <select name=''>
					     <option >No Repeat</option>
                         <option >Repeat X</option>
                         <option >Repeat Y</option>
				  </select>
				  <p class="description"><?php _e('Backgroun image repeat');?></p>
		          </label>
			 </td>
		    </tr>
</table>
					 <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>
<section id="section-topline-5">
<ul class="indent">

		<table class="form-table">


            <tr valign='top'>
				<th scope='row'><?php _e('Navigation Link Size');?></th>
				<td><label for='lps_login_nav_size'>
					<input type='range'  id='lps_login_nav_size' name='lps_login_nav_size' min='13' max='20' value='<?php echo get_option('lps_login_nav_size'); ?>' oninput="this.form.amountInput8.value=this.value" /> <input type="number"  name="amountInput8" min="13" max="20" value='<?php echo get_option('lps_login_nav_size'); ?>' size='4' oninput="this.form.lps_login_nav_size.value=this.value" />
					<p class="description"><?php _e( 'Slide to select Navigation Link Size .'); ?></p>
				</label>
				</td>
			</tr>

		   <tr valign='top'>
				<th scope='row'><?php _e('Navigation Links Color'); ?></th>
				<td><label for='lps_login_nav_color'>
					<input type='text' class='color_picker' id='lps_login_nav_color' name='lps_login_nav_color' value='<?php echo get_option('lps_login_nav_color' ); ?>'/>
				</label>
				</td>
			</tr>

			
			<tr valign='top'>
				<th scope='row'><?php _e('Navigation Hover Links Color'); ?></th>
				<td><label for='lps_login_nav_hover_color'>
					<input type='text' class='color_picker' id='lps_login_nav_hover_color' name='lps_login_nav_hover_color' value='<?php echo get_option('lps_login_nav_hover_color' ); ; ?>' />
				</label>
				</td>
			</tr>


</table>
<p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>

<section id="section-topline-6">
<ul class="indent">
		<table class="form-table">
		<div class="overlay-yo">
			<a href="http://www.wpzest.com/wordpress-plugins/pro-custom-admin-login/">Buy Premium Version Now</a>
			</div>
			<tr valign='top'>
				<th scope='row'><?php _e('Header Message');?></th>
				<td><label for=''>
					<input type='text' class='' id='' name='' value=''  />
				</label>
				</td>
			</tr>
             
		      <tr valign="top">
		        <th scope="row"><?php _e( 'Header Text Color' ); ?></th>
		        <td><label for="">
		          <input type='text' class='color_picker' id='' name='' value=''/>
		          <p class="description"><?php _e( 'Change Header massage text color'); ?></p>
		          </label>
		       </td>
		      </tr>

			  <tr valign='top'>
			    <th scope='row'><?php _e('Header Font Size');?></th>
			    <td><label for='lps_msg_font_size'>
			      <input type='range'  id='' name='' min='10' max='30' value='' oninput="" /> <input type=""  name="" min="10" max="30" value='' size='4' oninput="" />
			      <p class="description"><?php _e( 'Slide to select Header Font Size .'); ?></p>
			    </label>
			    </td>
			 </tr>

			 <tr valign="top">
        <th scope="row"><?php _e('Font Family'); ?></th>
        
        <td>
          <label for="">
          <select id="" class="standard-dropdown" name="" value="">
              <optgroup label="Default Fonts">
                <option value="Arial" <?php selected($RPP_Font_Style1, 'Arial' ); ?>>Arial</option>
                <option value="Arial Black" <?php selected($RPP_Font_Style1, 'Arial Black' ); ?>>Arial Black</option>
                
            </select>
          <p class="description"><?php _e('Select Header font-family ,  ');?></p>
              </label>
       </td>
        </tr>

			<tr valign='top'>
				<th scope='row'><?php _e('Forget Password Message');?></th>
				<td><label for=''>
					<input type='text' class='' id='' name='' value=''  />
				</label>
				</td>
			</tr>

			<tr valign="top">
		        <th scope="row"><?php _e( 'Forget password Text Color' ); ?></th>
		        <td><label for="">
		          <input type='text' class='color_picker' id='' name='' value=''/>
		          <p class="description"><?php _e( 'Change Forget Password text color'); ?></p>
		          </label>
		       </td>
		      </tr>

		      <tr valign="top">
		        <th scope="row"><?php _e( 'Forget password Text Hover Color' ); ?></th>
		        <td><label for="">
		          <input type='text' class='color_picker' id='' name='' value=''/>
		          <p class="description"><?php _e( 'Change Forget Password text hover color'); ?></p>
		          </label>
		       </td>
		      </tr>

			  <tr valign='top'>
			    <th scope='row'><?php _e('Forget Password Font Size');?></th>
			    <td><label for=''>
			      <input type='range'  id='' name='' min='10' max='30' value='' oninput="" /> <input type="number"  name="" min="10" max="30" value='' size='4' oninput="" />
			      <p class="description"><?php _e( 'Slide to select Forget Password Font Size .'); ?></p>
			    </label>
			    </td>
			 </tr>

			 <tr valign="top">
			  <th scope="row"><?php _e('Forget Password Font Family'); ?></th>
			  <td>
			  	<label for="">
				  <select id="" class="standard-dropdown" name="" value="">
              <optgroup label="Default Fonts">
                <option value="Arial" <?php selected($RPP_Font_Style, 'Arial' ); ?>>Arial</option>
               </optgroup>
            </select>
				  <p class="description"><?php _e('Select Lost Password font-family ,  ');?></p>
		          </label>
			 </td>
		    </tr>						
           </table>
					 
					 <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>
<section id="section-topline-7">
<ul class="indent">
<div class="overlay-yo">
			<a href="http://www.wpzest.com/wordpress-plugins/pro-custom-admin-login/">Buy Premium Version Now</a>
			</div>
		<table class="form-table">


		    <tr valign='top'>
				<th scope='row'><?php _e('Change Login Form Position');?></th>
				<td><label for=''>
				<select name="">
					<option value=''>Middle-Center</option>

				</select>
				</label>
				</td>
			</tr>

            
            <tr valign="top">
			  <th scope="row"><?php _e('Login Form Background Image'); ?></th>
			  <td><label for="">
					<input id="" type="text" />
                    <input class="onetarek-upload-button button" type="button" value="Upload Image" />
				</label>
				</td>
		    </tr>

			
			<tr>
				<th scope='row'><?php _e('Login Form Color');?></th>
				<td><label for=''>
					<input type='text' />
					<p class='description'><?php _e('Change Form color') ;?></p>
				</label>
				</td>
			</tr>


			<tr>
				<th scope='row'><?php _e('Login Form Color with Opacity');?></th>
				<td><label for=''>
					<input type='text' />
					<p class='description'> <?php _e( 'Add RGBA color value eg: 255 , 255 , 255 ,0.5 last value in decimal is the Opacity .'); ?></p>
				</label>
				</td>
			</tr>


			<tr valign='top'>
				<th scope='row'><?php _e('Label Color');?></th>
				<td><label for=''>
					<input type='text' /> 
					<p class='description'> <?php _e( 'Change form label(Username /Password) color'); ?></p>
				    </label>
			    </td>
			</tr>

			<tr valign='top'>
				<th scope='row'><?php _e('Login Form Label Size');?></th>
				<td><label for=''>
					<input type='' /> <input type=""  />
					<p class='description'> <?php _e( 'Change form label size '); ?></p>
				    </label>
			    </td>
			</tr>


			<tr valign="top">
			  <th scope="row"><?php _e('Login Form Label Fonts'); ?></th>
			  <td>
			  	<label for="">
				  <select name=''>
					     <option value=''>Arial</option>

				  </select>
				  <p class="description"><?php _e('Select login form label(Username/password) font-family ,  ');?></p>
		          </label>
			 </td>
		    </tr>


			<tr valign='top'>
				<th scope='row'><?php _e('Login Form  Remember Me Label Size');?></th>
				<td><label for=''>
					<input type=''  min='12' max='25'  /> <input type=""  min="12" max="25" size='4'/> 
					</label>
			    </td>
			</tr>


			<tr valign="top">
			  <th scope="row"><?php _e('Login Form Border Style'); ?></th>
			  <td>
			  	<label for="">
			  	  <input type=''  min='0' max='10' /> <input type=""  min="0" max="10" size='4' /> 	
			  	  <p class="description"><?php _e('Slide to change border width');?></p>
			  	</label>

			  	<label for="">
				  <select name=''>
					     <option value=''>None</option>
				  </select>
				  <p class="description"><?php _e('Select login form border style');?></p>
		          </label>
			 </td>
		    </tr>


		    <tr valign='top'>
				<th scope='row'><?php _e('Login Form Border Color');?></th>
				<td><label for=''>
					<input type='' />
					<p class="description"><?php _e('Change login form  border color .'); ?></p>
				</label>
				</td>
			</tr>



			<tr valign='top'>
				<th scope='row'><?php _e('Login Form Border Radius');?></th>
				<td><label for=''>
					 <input type='' min='0' max='10' /> <input type=""  min="0" max="10" size='4' />
					<p class="description"><?php _e('Slide to select Login form border radius'); ?></p>
				</label>
				</td>
			</tr>


			<tr valign="top">
			  <th scope="row"><?php _e('Login Form Input Field Border Style'); ?></th>
			  <td>
			  	<label for="">
			  	  <input type='range'  /> <input type=""  />	
			  	  <p class="description"><?php _e('Slide to select Login form input-field border width');?></p>
			  	</label>

			  	<label for="">
				  <select name=''>
					     <option value=''>None</option>
				  </select>
				  <p class="description"><?php _e('Select login form input field border style ');?></p>
		          </label>
			 </td>
		    </tr>


		    <tr valign='top'>
				<th scope='row'><?php _e('Login Form Input Field Border Color');?></th>
				<td><label for=''>
					<input type=''/>
					<p class="description"><?php _e('Change login form input field border color . '); ?></p>
				</label>
				</td>
			</tr>



			<tr valign='top'>
				<th scope='row'><?php _e('Login Form Input Field Border Radius');?></th>
				<td><label for=''>
					<input type=''  id='' value='' /> <input type=""  name="" min="0" max="10"  />
					<p class="description"><?php _e( 'Slide to select Login form input-field border radius . '); ?></p>
				</label>
				</td>
			</tr>


			<tr>
				<th scope='row'><?php _e('Login Form Input Field Color with Opacity');?></th>
				<td><label for=''>
					<input type='' id='' name='' value=''/>
					<p class='description'><?php _e( 'Add RGBA color value eg: 255 , 255 , 255 ,0.5 last value in decimal is the Opacity .'); ?></p>
				</label>
				</td>
			</tr>


</table>
<p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>
<section id="section-topline-8">
<ul class="indent">
			
		<table class="form-table">

			<tr valign='top'>
				<th scope='row'><?php _e('Login Button Border Radius');?></th>
				<td><label for='lps_login_button_border_radius'>
					<input type='range'  id='lps_login_button_border_radius' name='lps_login_button_border_radius' min='0' max='10' value='<?php echo get_option('lps_login_button_border_radius') ?>' oninput="this.form.amountInput6.value=this.value"  /> <input type="number"  name="amountInput6" min="0" max="10" value='<?php echo get_option('lps_login_button_border_radius') ?>' size='4' oninput="this.form.lps_login_button_border_radius.value=this.value" />
					<p class="description"><?php _e('Add login button border radius..'); ?></p>
				</label>
				</td>
			</tr>


 
            <tr valign='top'>
				<th scope='row'><?php _e('Login Button Color');?></th>
				<td><label for='lps_login_button_color'>
					<input type='text' class='color_picker' id='lps_login_button_color' name='lps_login_button_color' value='<?php echo get_option('lps_login_button_color'); ?>' /> 
					<p class='description'><?php _e( 'Change login button color'); ?></p></br>
					<input type='text' class='color_picker' id='lps_login_button_border_color' name='lps_login_button_border_color' value='<?php echo get_option('lps_login_button_border_color'); ?>' /><p class='description'><?php _e('Login Button Border Color');?></p></br>
					<input type='text' class='color_picker' id='lps_login_button_text_color' name='lps_login_button_text_color' value='<?php echo get_option('lps_login_button_text_color'); ?>' />
					<p class='description'><?php _e('Login Button Text Color');?></p>
				    </label>
			    </td>
			</tr>


			<tr valign='top'>
				<th scope='row'><?php _e('Login Button Color Hover');?></th>
				<td><label for='lps_login_button_color_hover'>
					<input type='text' class='color_picker' id='lps_login_button_color_hover' name='lps_login_button_color_hover' value='<?php echo get_option('lps_login_button_color_hover'); ?>'/> </br></br>
					<p class='description'><?php _e( 'Change login button color'); ?></p></br>
					<input type='text' class='color_picker' id='lps_login_button_border_color_hover' name='lps_login_button_border_color_hover' value='<?php echo get_option('lps_login_button_border_color_hover'); ?>'  /></br></br>
					<p class='description'><?php _e('Login Button Border Color');?></p></br>
					<input type='text' class='color_picker' id='lps_login_button_text_color_hover' name='lps_login_button_text_color_hover' value='<?php echo get_option('lps_login_button_text_color_hover'); ?>'  />
				    <p class='description'><?php _e('Login Button Text Color');?></p>
					</label>
			    </td>
			</tr>			

</table>
					  <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>
<section id="section-topline-9">
<ul class="indent">
<div class="overlay-yo">
			<a href="http://www.wpzest.com/wordpress-plugins/pro-custom-admin-login/">Buy Premium Version Now</a>
			</div>
		<table class="form-table">         
	<tr valign="top">
		   <th scope="row"><?php _e( 'Logout Page URL') ?></th>
				<td><label for="">
               <h4 style="color:#222 !important;">http://www.<input class="form-control" id="loginPageURL" placeholder="Ex.wpzest.com" type="text" 
                    name="" value=""></h4><p class="description">Example: wpzest.com</p>
                <p class="description">Logout Page URL is the page that the user will be taken to when they logout.</p>
                
				
				
				</label>
				</td>
			</tr>
			
			
		           </table>		   
		   
		   
		   <p class="submit">
			   <center>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /> </center>
		</p>
	</ul>
</section>
</div><!-- /content -->

</div><!-- /tabs -->
<script>
   /**
 * cbpFWTabs.js v1.0.0
 */
;( function( window ) {
	
	'use strict';

	function extend( a, b ) {
		for( var key in b ) { 
			if( b.hasOwnProperty( key ) ) {
				a[key] = b[key];
			}
		}
		return a;
	}

	function CBPFWTabs( el, options ) {
		this.el = el;
		this.options = extend( {}, this.options );
  		extend( this.options, options );
  		this._init();
	}

	CBPFWTabs.prototype.options = {
		start : 0
	};

	CBPFWTabs.prototype._init = function() {
		// tabs elems
		this.tabs = [].slice.call( this.el.querySelectorAll( 'nav > ul > li' ) );
		// content items
		this.items = [].slice.call( this.el.querySelectorAll( '.content-wrap > section' ) );
		// current index
		this.current = -1;
		// show current content item
		this._show();
		// init events
		this._initEvents();
	};

	CBPFWTabs.prototype._initEvents = function() {
		var self = this;
		this.tabs.forEach( function( tab, idx ) {
			tab.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				self._show( idx );
			} );
		} );
	};

	CBPFWTabs.prototype._show = function( idx ) {
		if( this.current >= 0 ) {
			this.tabs[ this.current ].className = this.items[ this.current ].className = '';
		}
		// change current
		this.current = idx != undefined ? idx : this.options.start >= 0 && this.options.start < this.items.length ? this.options.start : 0;
		this.tabs[ this.current ].className = 'tab-current';
		this.items[ this.current ].className = 'content-current';
	};

	// add to global namespace
	window.CBPFWTabs = CBPFWTabs;

})( window );
   </script>
	<script>
        (function() {

            [].slice.call( document.querySelectorAll( '.tabs' ) ).forEach( function( el ) {
                new CBPFWTabs( el );
            });

        })();
    </script>
    <!-- end tabs js -->
   
</form>
</div>

<?php }; 

/* === EXAMPLE BASIC META BOX === */

/* Add Meta Box */
add_action( 'add_meta_boxes', 'lps_option_basic_add_meta_box' );

/**
 * Basic Meta Box
 * @since 0.1.0
 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
 */
function lps_option_basic_add_meta_box(){

	$page_hook_id = lps_option_setings_page_id();

	add_meta_box(
		'basic',                  /* Meta Box ID */
		'Get PRO VERSION',               /* Title */
		'lps_option_basic_meta_box',  /* Function Callback */
		$page_hook_id,               /* Screen: Our Settings Page */
		'normal',                 /* Context */
		'default'                 /* Priority */
	);
}

/**
 * Submit Meta Box Callback
 * @since 0.1.0
 */
function lps_option_basic_meta_box(){
?>
Use this coupon code <b style="font-size: 14px; !important">'50PERCENTOFF'</b> to get <b style="font-size: 14px; !important">PRO VERSION</b> of this plugin.<br>Hurry offer valid for limited first 300 users.&nbsp;&nbsp;<a href="http://www.wpzest.com/wordpress-plugins/pro-custom-admin-login/" target="_blank">Click Here</a>
<?php
}

/* Add Meta Box Other */
add_action( 'add_meta_boxes', 'lps_option_basic_add_two_meta_box' );

/**
 * Basic Meta Box
 * @since 0.1.0
 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
 */
function lps_option_basic_add_two_meta_box(){

	$page_hook_id = lps_option_setings_page_id();

	add_meta_box(
		'submitdiv',               /* Meta Box ID */
		'Disable Comments',               /* Title */
		'lps_option_basic_other_meta_box',  /* Function Callback */
		$page_hook_id,               /* Screen: Our Settings Page */
		'side',                 /* Context */
		'default'                 /* Priority */
	);
}

/**
 * Submit Meta Box Callback
 * @since 0.1.0
 */
function lps_option_basic_other_meta_box(){
?>
To Disable Comments on your website use our <b style="font-size: 14px; !important">"Advance Disable Comments"</b> plugin.<br><a href="https://wordpress.org/plugins/disable-comments-wpz/" target="_blank">Click Here</a>
<?php
}
?>
