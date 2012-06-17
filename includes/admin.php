<?php defined('ABSPATH') or die("No direct access allowed");
/**
 * @author Vinny Alves (vinny@usestrict.net)
 * @copyright 2012
 *
 * Manage eShop third-party shipping options 
 */
class USC_eShop_Shipping_Extension_Admin extends USC_eShop_Shipping_Extension
{
	protected $notices;
	private $modules;
	private $admin_css = 'USC_eShop_Shipping_Admin.css';
	
	function __construct()
	{
		parent::__construct();
		
		# Don't do anything else if it's an Ajax request. We're only interested in the 
		# parent __construct()
		if (isset($_REQUEST['action']) && 
			$_REQUEST['action'] === $USC_eShop_Shipping_Extension->domain . '-get-rates')
		{
			return;
		}
		
		
		add_action('admin_menu', array(&$this, 'add_options_page'));
		add_action('admin_init', array(&$this, 'register_options'));
		
		// Add CSS file
		wp_enqueue_style( $this->domain . '-admin-style', ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL . '/' . $this->admin_css,
						  null,  ESHOP_SHIPPING_EXTENSION_VERSION , 'all' );
		
		// Initialize any Shipping modules
		$this->initialize_modules();
	}
	
	/**
	 * Method: add_options_page
	 * Description: Creates Settings options
	 */
	function add_options_page()
	{
		add_options_page('eShop Shipping Extension', 'eShop Shipping Extension', 'manage_options',
				$this->options_name, array(&$this,'show_admin'));
	}
	
	/**
	 * Method: initialize_modules()
	 * Description: Creates shipping module objects from classes located in third-party.
	 * @returns nothing - sets values into $this->modules 
	 */
	function initialize_modules()
	{
		$mod_path = ESHOP_SHIPPING_EXTENSION_THIRD_PARTY;

		foreach (glob($mod_path . '/*.php') as $filename)
		{
			require_once($filename);
			$class_name = basename($filename, '.php');
			
			$this->modules[$class_name] = new $class_name();
		}
	}
	
	
	/**
	 * Method: register_options
	 * Description: registers the module's options
	 */
	function register_options()
	{
		register_setting($this->options_name,$this->options_name, array(&$this,'validate_input'));
	}
	
	
	/**
	 * Method: validate_input
	 * Description: Validates input
	 * @param array $input
	 * @returns array $clean
	 */
	function validate_input($input)
	{
		$clean = array();
		
		// Accepted third-party values
		$third_party = array_keys($this->modules);
		$third_party[] = 'none';
		
		if (! in_array($input['third_party'], $third_party))
		{
			add_settings_error('third_party','third_party',__('Invalid option selected!'), 'error');
		}
		
		if (!isset($input['from_zip']) || trim($input['from_zip']) === '' )
		{
			add_settings_error('from_zip','from_zip',__('Zip/Postal Code is required!'), 'error');
		}
		
		foreach($this->modules as $module)
		{
			if (method_exists($module,'validate_input'))
			{
				$input = $module->validate_input($input);
			}
		}
		
		return $input;
	}
	
	
	/**
	 * Method: show_admin
	 * Description: Shows the content in the admin screen.
	 */
	function show_admin()
	{
		if (! $this->eshop_is_installed)
		{
			$this->set_notice(__("eShop is not installed. Please install it in order to use this!",$this->domain), true);
		}
		elseif (! $this->eshop_is_active)
		{
			$this->set_notice(__("eShop is not active. Don't forget to activate it!",$this->domain));
		}
	
		$this->render_template();
	}
	
	/**
	 * Method: render_template
	 */
	function render_template()
	{
		$opts       = $this->get_options();
		$eshop_opts = $this->get_eshop_options();
		$css_file   = ESHOP_SHIPPING_EXTENSION_INCLUDES . '/css/' . $this->css_filename;
		
		if (isset($opts['css']))
		{
			file_put_contents($css_file,$opts['css']);
			$css_contents = $opts['css'];
		}
		else
		{
			$css_contents = file_get_contents(ESHOP_SHIPPING_EXTENSION_INCLUDES . '/css/' . $this->css_filename);
		}
		
		if ((int)$eshop_opts['shipping'] !== 4)
		{
			$this->set_notice(__("Please remember to change eShop shipping mode to <em>\"Weight &amp; Zone\"</em> in order to use third-party services.",$this->domain), true);
		}
		
		if (count($this->notices) > 0)
		{
			foreach ($this->notices as $n)
			{
				echo $n;
			}
		}		
		?>
		<?php settings_errors();?>
		<div class="wrap">
			<script type="text/javascript">
				jQuery(document).ready(function(){

					jQuery("h3.toggle_child").click(function(){
						jQuery(this).next().slideToggle();
					});

					jQuery("#third_party_select").change(function(){
						var val = jQuery(this).val();
						  

						if (val == 'none')
						{
							jQuery("div.modules").slideUp();
						}
						else
						{
							mod_div = jQuery("div."+val);
							jQuery("div."+val).not(mod_div).hide();
							if (mod_div.is(':hidden'))
							{
								mod_div.slideDown();
							}
						}
						
					});
					
				});
			</script>
			
		</div>
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2><?php _e('eShop Shipping Extension Settings', $this->domain); ?> v <?php echo ESHOP_SHIPPING_EXTENSION_VERSION; ?></h2>
		
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="side-info-column" class="inner-sidebar">
				<div class="meta-box-sortables">
					<div id="about" class="postbox">
						<h3 id="about-sidebar"><?php _e('About the Author:', $this->domain); ?></h3>
						<div class="inside">
						
							<p><?php _e('This plugin is brought to you by <a href="http://usestrict.net" target="_new">UseStrict Consulting</a>, ' . 
									        'where you can find information on Perl, PHP, and Web Technologies in general.',$this->domain); ?>
							</p>
								
							<p><?php _e('You can reach us on Twitter <a href="http://twitter.com/vinnyusestrict" target="_new">@vinnyusestrict</a> and ' . 
										'<a href="http://www.facebook.com/vinny.alves" target="_new">Facebook</a>',$this->domain); ?></p>
											
							<p><strong><?php _e('Donate', $this->domain); ?>:</strong><br />
							<?php _e('Writing and maintaining a plugin takes time and coffee - a lot of it. If you enjoy this plugin, ' . 
									 'please consider making a donation towards my next all-nighter ;-). <strong>Thank You!</strong>', $this->domain ); ?>
							</p>
							<?php _e('<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
											<input type="hidden" name="cmd" value="_s-xclick">
											<input type="hidden" name="hosted_button_id" value="VLQU2MMXKB6S2">
											<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
											<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
											</form>',$this->domain);?>
						
						</div>
					</div>
				</div>
			</div> <!--  end of sidebar -->
			
			<form method="post" action="options.php">
			<?php settings_fields($this->options_name); ?>
			
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<div class="meta-box-sortables">
							<div class="postbox">
								<h3><?php _e('General Settings',$this->domain); ?></h3>
								<div class="inside">
									<p><?php _e('eShop Shipping Extension overrides the default options that ships with eShop. It provides a way to ' . 
											 'interact directly with available third-party services such as Canada Post and get real-time shipping rates. ' .
											 'Use the options below to set up your services.',$this->domain);?></p>
									<hr />
									<p><strong><?php _e('Zip/Postal Code of origin?',$this->domain); ?></strong><br />
									<input type="text" id="from_zip" name="<?php echo $this->options_name?>[from_zip]" 
										      value="<?php echo $opts['from_zip']; ?>" /> </p>
									
									<p><strong><?php _e('Select which interface (if any) you want to use:',$this->domain); ?></strong><br />
									<select id="third_party_select" name="<?php echo $this->options_name; ?>[third_party]">
										<option value="none" <?php selected($opts['third_party'],'none');?>><?php _e('None',$this->domain); ?></option>
										<?php foreach ($this->modules as $k => $v) :?>
										<option value="<?php echo $k; ?>" <?php selected($opts['third_party'],$k);?>><?php echo $v->module_name; ?></option>
										<?php endforeach;?>
									</select> <i><?php _e('"None" means eShop\'s default settings.',$this->domain); ?></i></p>
									<p><strong><?php _e('Shipping Details CSS Styles:',$this->domain); ?></strong><br />
									
									<textarea id="general_css" name="<?php echo $this->options_name; ?>[css]"><?php echo $css_contents;?></textarea>
									</p>
								
								</div>
								
							</div>
						</div>
						
						<?php $mod_count = count($this->modules); 
						foreach ($this->modules as $k => $v) : ?>
						<div class="meta-box-sortables">
							<div class="postbox">
								<h3 class="toggle_child"><?php echo $v->module_name; ?></h3>
								<div class="inside modules <?php echo get_class($v); echo $mod_count > 1 ? ' hidden' : ' '; ?>">
									<?php echo $v->intro_paragraph();?>
									<hr />
									<?php echo $v->form_html(); ?>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save Preferences',$this->domain); ?>" />
					</p>
				</div>
			
			</form>
			
		</div> 	
		
		<?php 
		
	}
	
}


/* End of file admin.php */
/* Location: eshop-shipping-extension/includes/admin.php */