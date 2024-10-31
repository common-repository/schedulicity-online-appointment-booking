<?php
/*
Plugin Name: Schedulicity - Easy Online Scheduling
Plugin URI: www.schedulicity.com
Description: Wordpress Plugin that allows you to easily integrate schedulicity with one command. Activate the plugin, and navigate to the "Settings" tab on the Wordpress dashboard. Then click Schedulicity Setup. Set your business key and select which plugin type you want. Then place the [schedule_now] shortcode on any page/post and your booking calendar will automatically appear.
Version: 2.2.1
Author: Schedulicity Inc.
Author URI: www.schedulicity.com
License: GPL2
*/
/*  Copyright 2012-2019 Jeremiah Prummer, Schedulicity Inc.  (email : jeremiah.prummer@schedulicity.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/
class Schedulicity_Plugin {

	private static $add_script;
	/**
	 * Construct.
	 */
	function __construct() {

		//add_action('admin_notices', array( $this, 'general_admin_notice'), 0 );

		add_action('admin_init', array( $this, 'schedulicityplugin_init'), 0 );
		add_action('admin_menu', array( $this, 'schedulicity_add_page'), 0 );
		add_action('plugins_loaded', array( $this, 'schedulicity_widgets'), 0 );
		
		add_action('init', array( $this, 'register_script'));
		add_action('wp_footer', array( $this, 'print_script'));

		add_action( 'admin_enqueue_scripts', array($this,'load_styles'));
		
		// Admin Notices
		if ( ! empty( $_GET['hide_sched_check_bizkey'] ) ) {
			update_option( 'sched_hide_check_bizkey', 'hide' );
		}
		$sched_hide_check_bizkey = get_option( 'sched_hide_check_bizkey' );
		$sched_bizkey = array();
		$sched_bizkey = get_option( 'user_bizkey' );
		if (is_array($sched_bizkey)) {
			$sched_bizkey = array_filter($sched_bizkey);
		}

		if (($sched_hide_check_bizkey != 'hide') && (empty($sched_bizkey))){
			add_action( 'admin_notices', array( $this, 'missing_your_bizkey' ), 0);
		}

		add_shortcode('schedule_now', array($this,'schedulicity_widgets'));
		add_shortcode('schedule_now_button', array($this,'sched_button'));
		add_shortcode('btn_left' , array($this,'sched_button'));
		add_shortcode('btn_center' , array($this,'sched_button'));
		add_shortcode('btn_right' , array($this,'sched_button'));
	}

	// Init plugin options to white list our options
	function schedulicityplugin_init(){
	
		register_setting( 'schedulicity_options', 'user_bizkey');
		
		//Deprecated Settings
		//register_setting( 'schedulicity_options', 'user_maxheight');
		//register_setting( 'schedulicity_options', 'user_minheight');
		//register_setting( 'schedulicity_options', 'widget_type');
	}

	// Add menu page
	function schedulicity_add_page() {
		add_options_page('Schedulicity Plugin Setup', 'Schedulicity Setup', 'manage_options', 'schedulicity_options_page', array( &$this, 'schedulicity_options_do_page'));
	}

	/**
	 * Load JS
	 */
	static function register_script() {
		wp_register_script('schedulicity-js', plugins_url( '/js/schedulicity.js' , __FILE__ ), array('jquery'),'2.2.1',true);
	}

	/**
	 * Load CSS
	 */
	public function load_styles() {
		
		$css = file_exists( get_stylesheet_directory() . '/schedulicity-admin.css' )
			? get_stylesheet_directory_uri() . '/schedulicity-admin.css'
			: plugins_url( '/css/schedulicity-admin.css', __FILE__ );
			
		wp_register_style( 'schedulicity-admin', $css, array(), '2.2.1', 'all' );
		wp_enqueue_style( 'schedulicity-admin' );
	}

	static function print_script() {
		if ( ! self::$add_script ) {
			return;
		} else {
			wp_print_scripts('schedulicity-js');
		}
	}

	// Draw the menu page itself
	function schedulicity_options_do_page() {
	?>
		<div class="wrap schedulicity" style="font-size: 18px">	
			<?php
			if (isset($_GET['tab'])) {
				$active_tab = $_GET['tab'];
			} else {
				$active_tab = 'standard_setup';
			}
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?page=schedulicity_options_page&tab=standard_setup" class="nav-tab <?php echo $active_tab == 'standard_setup' ? 'nav-tab-active' : ''; ?>">Setup</a>  
				<a href="?page=schedulicity_options_page&tab=advanced_setup" class="nav-tab <?php echo $active_tab == 'advanced_setup' ? 'nav-tab-active' : ''; ?>">Advanced</a> 
			</h2>
			<?php
			if ($active_tab == 'standard_setup') {
			?>
			<div style="background: #FFF;-moz-border-radius: 3px;border-radius: 3px;margin:5%;margin-top: 30px;padding: 20px;-moz-box-shadow: 0 0 5px #888;-webkit-box-shadow: 0 0 5px#888;box-shadow: 0 0 5px #888;">
				
			<div style="margin-bottom: 20px">
			<span style="float:right;">
				Like Us? <a href="https://wordpress.org/support/view/plugin-reviews/schedulicity-online-appointment-booking?filter=5#postform" style="color:green;font-weight:bold" target="_blank">Rate Us on WordPress.org!</a>
			</span>
			<img src="<?php echo plugins_url( 'schedulicitylogo.png', __FILE__ ); ?>" style="width: 250px; margin-bottom: 10px" />
			<h2>Schedulicity Plugin Setup</h2>
			</div>
			<form method="post" action="options.php">
				<?php settings_fields('schedulicity_options'); ?>
				
				<ol>
					<li style="font-size: 18px; font-weight: bold; margin-top: 10px;margin-bottom:10px">Insert Your Biz Key.</li>
						<?php $options = get_option('user_bizkey'); ?>			
						<ul style="font-size: 16px">
						Business Key: <input type="text" name="user_bizkey[bizkey]" id="bizkey_field" value="<?php echo $options['bizkey']; ?>" /><input type="submit" class="button-primary" value="<?php _e('Save Business Key') ?>" style="margin-left: 20px" /><span style="margin-left: 20px;font-size: 14px"><a href="?page=schedulicity_options_page&tab=advanced_setup#bizkey">What's my Business Key?</a></span>
						<p>
							<strong>Note:</strong> If you don’t have a Schedulicity account, <a href="https://essentials.schedulicity.com/?utm_source=wordpress&utm_channel=affiliate&utm_medium=affiliate&utm_campaign=wp_plugin_settings&CampaignNumber=wp_plugin_settings" target="_blank">sign up</a> for one now. (It’s free!).
						</p>
						</ul>
					<li style="font-size: 18px; font-weight: bold; margin-top: 10px;margin-bottom:10px">Create Your Shortcode.</li>
							<ul style="font-size: 16px">
							<p>
								Choose from any of the buttons or widgets below. For buttons, select the size, style, and alignment you like, then copy the code that appears in the Button Shortcode box at right. For widgets, choose between an embedded or overlay scheduling widget below, then copy the code that appears in the Widget Shortcode box at right.
							</p>
							<div id="shortcode-builder">	
								<div class="type-panel">
									<h2>Button Size</h2>
				                    <span data-value="lrg" class="selected"><img ng-src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-lrg-v2.png" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-lrg-v2.png"></span>
				                    <span data-value="med"><img ng-src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-med-v2.png" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-med-v2.png"></span>
				                    <span data-value="sm"><img ng-src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-sm-v2.png" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-sm-v2.png"></span>
				                    <span data-value="url">URL only</span><br>
				                </div>
								<div class="button-panel" ng-show="showButtons">
									<h2>Button Style</h2>
									<div class="buttons">
										<img class="button-img ng-scope selected" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-med-v2.png" data-id="schedule-btn-dark">
										<img class="button-img ng-scope" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-white-med-v2.png" data-id="schedule-btn-white">
										<img class="button-img ng-scope" src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-huckleberry-med-v2.png" data-id="schedule-btn-huckleberry">
									</div>
									<div class="button-alignment">
										<h2>Button Alignment</h2>
										<div class="button-alignment-selection">
											<div>
												<input type="radio" id="left" name="button-alignment" value="left">
												<label for="left">Left</label>
											</div>
											<div>
												<input type="radio" id="center" name="button-alignment" value="center" checked>
												<label for="center">Center</label>
											</div>
											<div>
												<input type="radio" id="right" name="button-alignment" value="right">
												<label for="right">Right</label>
											</div>
										</div>
									</div>
				                </div>
				                <div class="shortcode-updater" id="button-shortcode-updater">
				                	<h2>Button ShortCode</h2>
				                	<p>Copy the shortcode below and paste it into any page or post.</p>
				                	<div style="width: 90%;margin: 0px auto;text-align:center">
				                		<img src="//cdn.schedulicity.com/images/user-widget-buttons/schedule-btn-dark-med-v2.png" />
				                	</div>
				                	<input type="text" name="sched_shortcode" id="sched_shortcode" value="[schedule_now_button format='schedule-btn-dark-lrg-v2' align='center']" />
				                </div>
				                <div class="formsection primary button-panel">
					                <div id="widgetheader">
					                	<h2>Website - scheduling widget</h2>
					                	<p>Clients can easily book an appointment without ever leaving your website.</p>
					                </div>
				                	<span id="embeddedwidget" class="widgetselector">
				                		<div class="checkbox"></div>
				                		<h3>Embedded scheduling widget</h3>
				                		<p>Embed the scheduling widget on your site so that clients can book from their desktop, tablet, or mobile phone.</p>
				                	</span>
				                	<span id="overlaywidget" class="widgetselector">
				                		<div class="checkbox"></div>
				                		<h3>Overlay scheduling widget</h3>
				                		<p>Add the overlay widget to every page of your website for quick, accessible scheduling for your clients</p>
				                	</span>
					            </div>
					            <div class="shortcode-updater" id="widget-shortcode-updater">
				                	<h2>Widget ShortCode</h2>
				                	<p>Copy the below shortcode and paste it into any page or post.</p>
				                	<!--<img src="https://cdn.schedulicity.com/images/schedulenow_lt_green3_lg.png" />-->
				                	<input type="text" name="sched_shortcode" id="sched_widgetshortcode" value="[schedule_now widget='embedded']" />
				                </div>
				            </div>
				            </ul>
					<li style="font-size: 18px; font-weight: bold; margin-top: 10px;margin-bottom:10px">Start Scheduling!</li>
						<ul style="font-size: 16px">
						Once you’ve added the shortcode to a Wordpress page or post, give it a quick test to make sure it works. If you have any issues, email <a href="mailto:support@schedulicity.com">support@schedulicity.com</a> or call <strong>877-582-0494</strong>. When you're ready, send your customers to your site to start booking their appointments!
						</ul>
				</ol>
                <script type="text/javascript">
                	jQuery( document ).ready(function( $ ) {
						$('.button-img').click(function(){
							$('.button-img.selected').removeClass('selected');
							$(this).addClass('selected');
							button_show_shortcode();
						});
						$('div.button-alignment-selection input').click(function(){
							console.log('clicked');
							var alignment = $('input[name="button-alignment"]:checked').val();
							$('#button-shortcode-updater > div').css("text-align",alignment);
							button_show_shortcode();
						});
						$('div.checkbox').click(function(){
							$sibling = $(this).parent().siblings(".widgetselector").find(".checkbox");
							if($(this).hasClass('selected')){
								$(this).removeClass('selected');
								widget_show_shortcode();
							} else {
								$(this).addClass('selected');
								$sibling.removeClass('selected');
								widget_show_shortcode();
							}
						});

						$('.type-panel span').click(function(){
							$('.type-panel span.selected').removeClass('selected');
							$(this).addClass('selected');
							button_show_shortcode();
						});
						$('#sched_shortcode, #sched_widgetshortcode').click(function(){
							$(this).select();
						});
						$bizkey = '<?php echo $options["bizkey"] ?>';
						function button_show_shortcode(){
							$style = $('.button-img.selected').data('id');
							$size = $('.type-panel .selected').data('value');
							$alignment = $('input[name="button-alignment"]:checked').val();
							if($style == null){
								$style = 'schedule-btn-dark';
							}
							if($size == null){
								$size = 'med';
							}
							$url = '//cdn.schedulicity.com/images/user-widget-buttons/'+$style+'-'+$size+'-v2.png';
							$('#button-shortcode-updater #sched_shortcode').fadeTo(700, 0.5, function() {
								$('#button-shortcode-updater #sched_shortcode').css('background-color','#4534c7');
								$('#button-shortcode-updater #sched_shortcode').fadeTo(600, 1).css('background-color','#ffffff'); 
							});
							$('#button-shortcode-updater img').attr('src',$url);
							$('#button-shortcode-updater #sched_shortcode').val("[schedule_now_button format='"+$style+"-"+$size+"-v2' align='"+$alignment+"']");
							$('#button-shortcode-updater > img').show();
							if($size != 'url'){
								$('#button-shortcode-updater > img').show();
								$('#button-shortcode-updater > img').fadeTo(700, 0.5, function() { $('#button-shortcode-updater > img').fadeTo(600, 1); });
							}
							if($size == 'url'){
								$('#sched_shortcode').val('https://www.schedulicity.com/scheduling/'+$bizkey);
								$('#button-shortcode-updater > img').hide();
							}
						}

						function widget_show_shortcode(){
							$('#sched_widgetshortcode').fadeTo(700, 0.5, function() { $('#sched_widgetshortcode').fadeTo(600, 1); });
							if($('#overlaywidget div.checkbox').hasClass('selected')){
								$('#sched_widgetshortcode').val('[schedule_now widget="overlay"]');
							} else {
								$('#sched_widgetshortcode').val('[schedule_now widget="embedded"]');
							}
						}
					});
                </script>
			</form>
			</div>
			<?php
			}
			else {
			?>
			<div style="background: #FFF;-moz-border-radius: 3px;border-radius: 3px;margin:5%;margin-top: 30px;padding: 20px;-moz-box-shadow: 0 0 5px #888;-webkit-box-shadow: 0 0 5px#888;box-shadow: 0 0 5px #888;">
				<span style="float:right;">
					Like Us? <a href="https://wordpress.org/support/view/plugin-reviews/schedulicity-online-appointment-booking?filter=5#postform" style="color:green;font-weight:bold" target="_blank">Rate Us on WordPress.org!</a>
				</span>
				<img src="<?php echo plugins_url( 'schedulicitylogo.png', __FILE__ ); ?>" style="width: 250px; margin-bottom: 10px" />
				<h2>Schedulicity Advanced Setup</h2>
				<div id="bizkey">
					<h4>1. Finding Your Business Key</h4>
					<p style="margin-left: 20px; font-size: 14px">
						<ol>
							<li style="font-size:14px">Log in to your Schedulicity account.*</li>
							<li style="font-size:14px">Click the "Marketing" tab in the sidebar menu.</li>
							<li style="font-size:14px">Click the "Widgets" tile.</li>
							<li style="font-size:14px">At the top of the screen, you’ll see a row of buttons. Click "URL only". You’ll see a URL that looks something like https://www.schedulicity.com/scheduling/<strong>842735</strong>. The 6 digits after /scheduling/ are your business key.</li>
							<li style="font-size:14px">Copy and paste the 6-digit business key into the <a href="?page=schedulicity_options_page&tab=standard_setup">business key box</a>.</li>
						</ol>
						<p style="margin-left: 20px; font-size: 14px">*You’ll need an active Schedulicity account to add our plugin to your site. If you don’t have one, <a href="https://essentials.schedulicity.com/?utm_source=wordpress&utm_channel=affiliate&utm_medium=affiliate&utm_campaign=wp_plugin_settings&CampaignNumber=wp_plugin_settings" target="_blank">create one here for free</a>.</p>
					</p>
				</div>
				<div id="multipleaccounts">
					<h4>2. Use With Multiple Schedulicity Accounts</h4>
					<p style="margin-left: 20px; font-size: 14px">Using the Schedulicity plugin with multiple accounts is a snap! Just add 
					<span style="color: #4b9500">bizkey=" "</span> to the [schedule_now] or [schedule_now_button] shortcodes and place your bizkey between the quotes. 
					Examples: <span style="background: #ffef73">[schedule_now <span style="color: #4b9500">bizkey="SSTJP8"</span>]</span> or 
					<span style="background: #ffef73">[schedule_now_button align="left" <span style="color: #4b9500">bizkey="SSTJP8"</span>]</span>. With this method, you can add as many booking calendars or buttons to your site as needed.</p>
				</div>
				<div id="supportinfo">
					<h4>3. Support Issues</h4>
					<p style="margin-left: 20px; font-size: 14px">
						If you have any questions, please feel free to reach out to the Schedulicity support team at <strong><a href="mailto:support@schedulicity.com">support@schedulicity.com</a></strong> or <strong>877-582-0494</strong>.
					</p>
				</div>
			</div>	
			<?php
			}
			?>
			
		</div>
	<?php	
	}

	function sched_button($atts) {
		$user_bizkey = get_option('user_bizkey');
		$sched_bizkey = $user_bizkey['bizkey'];
		extract(shortcode_atts( array('bizkey' => $sched_bizkey, 'align' =>'center', 'style' => '', 'size' => 'lrg', 'format' => '') , $atts));
		$sched_button = '';
		$alignment = '';
		if ($align == 'right') {
			$alignment = 'right';
		}
		elseif ($align == 'left') {
			$alignment = 'left';
		}
		else {
			$alignment = 'center';
		}
		/* old button style 2015 - 2019 */
		if (!empty($style)) {
			$image_url = '';
			$oldstyle = strpos($style, 'button');
			if($oldstyle !== false){
				$style = str_replace('button','', $style);
				if ($style < 10){
					$style = sprintf('%02d', $style);
				}
				$image_url = '//d2k394ztg01v3m.cloudfront.net/images/schedulenow_'.$style.'_'.$size.'.png';
			} elseif ($oldstyle === false){
				$image_url = '//cdn.schedulicity.com/images/schedulenow_'.$style.'.png';
			}					
			$sched_button = '<div style="text-align: '.$alignment.'"><a href="https://www.schedulicity.com/scheduling/'.$bizkey.'" title="Online scheduling" target="_blank" id="schednowlink"><img src="'.$image_url.'" title="Online Scheduling" alt="Online Scheduling" border="0" /></a></div>';
		}
		/* button style 2020+ */
		elseif (!empty($format)) {
			$image_url = '';
			$image_url = '//cdn.schedulicity.com/images/user-widget-buttons/'.$format.'.png';
			$sched_button = '<div style="text-align: '.$alignment.'"><a style="display:inline-block" href="https://www.schedulicity.com/scheduling/'.$bizkey.'" title="Online scheduling" target="_blank" id="schednowlink"><img src="'.$image_url.'" title="Online Scheduling" alt="Online Scheduling" border="0" /></a></div>';
		}
		/* really old button style. Pre 2015 */
		else {
			$sched_button = '<div style="text-align: '.$alignment.'"><a href="https://www.schedulicity.com/scheduling/'.$bizkey.'" title="Online scheduling" target="_blank" id="schednowlink"><img src="https://www.schedulicity.com/Business/Images/ScheduleNow_LG.png" title="Online Scheduling" alt="Online Scheduling" border="0" /></a></div>';
		}
		
		return $sched_button;
	}

	function schedulicity_widgets($atts) {
		self::$add_script = true;
		// Retrieve Widget Type
		$widget = get_option('widget_type');
		$sched_widget = $widget['embedded'];
		// Functions in this if statement are deprecated
		if (isset($sched_widget)){
		
			if ($sched_widget==2) {
				$bizkey = get_option('user_bizkey');
				$sched_bizkey = $bizkey['bizkey'];
				extract(shortcode_atts( array('bizkey' => $sched_bizkey) , $atts));
				$content = '<script type="text/javascript" src="https://www.schedulicity.com/api/public/widget/';
				$content .= $bizkey;
				$content .= '/popup"></script>';
				return $content;
			}
			
			else {
				$bizkey = get_option('user_bizkey');
				$sched_bizkey = $bizkey['bizkey'];
				extract(shortcode_atts( array('bizkey' => $sched_bizkey) , $atts));
				$content = '<script type="text/javascript" src="https://www.schedulicity.com/api/public/widget/';
				$content .= $bizkey;
				$content .= '/embed"></script>';
				return $content;
			}
			
		}
		else {
			$bizkey = get_option('user_bizkey');
			$sched_bizkey = $bizkey['bizkey'];
			extract(shortcode_atts( array('bizkey' => $sched_bizkey, 'widget' => 'embedded') , $atts));
			if(empty($bizkey)){
				$bizkey = $sched_bizkey;
			}
			if ($widget == 'overlay') {
				$content = '<script type="text/javascript" src="https://www.schedulicity.com/api/public/widget/';
				$content .= $bizkey;
				$content .= '/popup"></script>';
			}
			else {
				$content = '<script type="text/javascript" src="https://www.schedulicity.com/api/public/widget/';
				$content .= $bizkey;
				$content .= '/embed"></script>';
			}
			return $content;
		}
		
	}
	
	function missing_your_bizkey() {
		$error = 'The Schedulicity Plugin needs a valid business key to work. Please update your business key from the <a href="#">settings page</a>';
		$message = sprintf('<div class="error"><p>%1$s. <a href="%2$s">%3$s</a></p></div>', $error, add_query_arg( 'hide_sched_check_bizkey', 'true' ), 'Hide this notice' );
		echo $message;
	}

	function general_admin_notice(){
		$admin_page = admin_url().'options-general.php?page=schedulicity_options_page';
		echo '<div data-dismissible="disable-schedulicity-bizkey-notice-forever" class="notice notice-error is-dismissible"><p>The Schedulicity Plugin needs a valid business key to work. Please update your business key from the <a href="'.$admin_page.'">settings page</a></p></div>';
	}


}
$Schedulicity_Plugin = new Schedulicity_Plugin();
?>
