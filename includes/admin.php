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
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'), 10, 1);
		add_action('save_post', array(&$this,'save_product_package_class'),20,2);
		
		
		
		// Initialize any Shipping modules
		$this->initialize_modules();
	}
	
	
	/**
	 * Method: enqueue_scripts
	 * Description: enqueues any JS scripts required in the admin screen
	 */
	function enqueue_scripts($hook)
	{
		global $post;
		
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) 
		{
			$eshop_post_types = apply_filters('eshop_post_types',array('post','page'));
			
			if (in_array($post->post_type, $eshop_post_types))
			{
				$opts = $this->get_options();
				
				// Force global form if no package_class_elements have been created				
				if (! isset($opts['package_class_elements']) || ! is_array($opts['package_class_elements']))
				{
					$opts['package_class'] = 'global';
				}
				
				// Stopped returning if it's global, because we want to keep any saved data 
				// when switching between package_class modes.
// 				if ($opts['package_class'] === 'global') return;
				
				$prod_meta = maybe_unserialize(get_post_meta($post->ID, '_eshop_product', true)); 
				
				$prod_opt_array = array();
				if (is_array($prod_meta))
				{
					foreach ($prod_meta['products'] as $key => $val)
					{
						$prod_opt_array[$key] = $val['sel_package_class'];
					}
				}
				
				$pc_elements = array();
				if ($opts['package_class_elements'] && is_array($opts['package_class_elements']))
				{
					foreach ($opts['package_class_elements'] as $key => $val)
					{
						 $pc_elements[$key] = $val['name'];
					}
				}
				
				wp_enqueue_script( 'usc_package_classes',  ESHOP_SHIPPING_EXTENSION_MODULES_URL . '/../usc_package_classes.js', array( 'jquery' ),  ESHOP_SHIPPING_EXTENSION_VERSION);
				// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
				wp_localize_script( 'usc_package_classes', 'eShopShippingModule_packages', 
									array('package_class'      => $opts['package_class'],
										  'pc_elements'        => $pc_elements,
										  'sel_prod_level'     => (isset($prod_meta['sel_package_class']) ? $prod_meta['sel_package_class'] : ''),
										  'sel_prod_opt_level' => $prod_opt_array,
										  'lang'               => array('select' => __('Select',$this->domain),
										  								'package_class' => __('Package Class', $this->domain),
																  )
									)
								);
			}
		}
	}
	
	/**
	 * Method: save_product_package_class
	 * Description: Attaches the selected package_class_element onto the product 
	 */
	function save_product_package_class($post_ID)
	{
		$opts = $this->get_options();

		// Commented this out because we want to always run it so we don't lose data
// 		if ($opts['package_class'] == 'global') return;
		
		$prod_meta = maybe_unserialize(get_post_meta($post_ID, '_eshop_product', true));

		if (! $prod_meta) return; // WP saves everything twice, once with a weird post_id, then lastly with the correct one.
		
		if ($opts['package_class'] == 'product')
		{
			if (! $_POST['eshop_product_package_class'])
			{
				delete_post_meta( $post_ID, '_eshop_stock');
				add_filter('redirect_post_location','eshop_error');
			}
		}
		else
		{
			foreach ($prod_meta['products'] as $key => $val)
			{
				if (! $prod_meta['products'][$key]['option']) continue;
				
				$prod_meta['products'][$key]['sel_package_class'] = $_POST['eshop_opt_package_class_'.$key];
				
				// If option description is set, then package class is mandatory!
				if (isset($opts['package_class']) && 
					$opts['package_class'] != 'global' && 
					! $prod_meta['products'][$key]['sel_package_class'])
				{
					delete_post_meta( $post_ID, '_eshop_stock');
					add_filter('redirect_post_location','eshop_error');
				}
			}
		}
		
		// Always save the package class (it may be hidden or not)
		$pack_class_name = $_POST['eshop_product_package_class'];
		$prod_meta['sel_package_class']	= $pack_class_name;
		
		update_post_meta($post_ID,'_eshop_product',$prod_meta);
	}
	
	/**
	 * Method: add_admin_css
	 * Description: Adds Admin CSS only for this options page
	 */
	function add_admin_css()
	{
		// Add CSS file
		wp_enqueue_style( $this->domain . '-admin-style', ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL . '/' . $this->admin_css,
				null,  ESHOP_SHIPPING_EXTENSION_VERSION , 'all' );
		
	}
	
	
	/**
	 * Method: add_options_page
	 * Description: Creates Settings options, adds action to enqueue admin style
	 */
	function add_options_page()
	{
		$this->plugin_page = add_options_page('eShop Shipping Extension', 'eShop Shipping Extension', 'manage_options',
								$this->options_name, array(&$this,'show_admin'));

		// Add action to insert css
		add_action('admin_head-' . $this->plugin_page, array(&$this,'add_admin_css'));
	}
	
	/**
	 * Method: initialize_modules()
	 * Description: Creates shipping module objects from classes located in third-party.
	 * @returns nothing - sets values into $this->modules 
	 */
	function initialize_modules()
	{
		$mod_path = ESHOP_SHIPPING_EXTENSION_MODULES;

		foreach (glob($mod_path . '/*/*.php') as $filename)
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
		// Accepted third-party values
		$third_party = array_keys($this->modules);
		
		$this->do_recursive($input,'trim');
		
		if ($input['third_party'] !== 'USC_eShop_UPS') 
		{
			if (!isset($input['from_zip']) || $input['from_zip'] === '' )
			{
				add_settings_error('from_zip','from_zip',__('Zip/Postal Code is required!'), 'error');
			}
		}
		
		// Massage Package Classes
		# Iterate through all fields to get the right order
		# since people can mess up rel numbers by adding/deleting rows
		if (! isset($_FILES['usc_upload_pack_class_csv']) || $_FILES['usc_upload_pack_class_csv']['error'])
		{
			$count = 1;
			$package_classes = array();
			foreach ($input['package_class_elements'] as $p)
			{
				// Skip empty rows
				if (!$p['name'] && ! $p['width'] && !$p['height'] && !$p['length'] && !$p['girth'])
				{
					continue;
				}
				
				$has_error = false;
				foreach(array_keys($p) as $key)
				{
					switch($key)
					{
						case 'name':
							$p[$key] = htmlspecialchars_decode($p[$key]);
							
							! $p[$key] && add_settings_error("package_class_elements",
															 "package_class_elements",sprintf(__('Package Class Row %d: "Name" is required!'),$count), 'error')
							&& $has_error = true;
							break;
						case 'length':
							if (!$p[$key])
							{
								add_settings_error("package_class_elements",
								"package_class_elements",sprintf(__('Package Class Row %d: "Length" is required and must be greater than 0!'),$count), 'error')
								&& $has_error = true;
							}
							else
							{
								! is_numeric($p[$key]) && add_settings_error("package_class_elements",
														  "package_class_elements",sprintf(__('Package Class Row %d: "Length" must be a number!'),$count), 'error')
								&& $has_error = true;
							}
							break; 
						case 'width':
							if (!$p[$key])
							{
								add_settings_error("package_class_elements",
										"package_class_elements",sprintf(__('Package Class Row %d: "Width" is required and must be greater than 0!'),$count), 'error')
								&& $has_error = true;
							}
							else
							{
								! is_numeric($p[$key]) && add_settings_error("package_class_elements",
										"package_class_elements",sprintf(__('Package Class Row %d: "Width" must be a number!'),$count), 'error')
								&& $has_error = true;
							}
							break;
						case 'height':
							if (!$p[$key])
							{
								add_settings_error("package_class_elements",
										"package_class_elements",sprintf(__('Package Class Row %d: "Height" is required and must be greater than 0!'),$count), 'error')
								&& $has_error = true;
							}
							else
							{
								! is_numeric($p[$key]) && add_settings_error("package_class_elements",
										"package_class_elements",sprintf(__('Package Class Row %d: "Height" must be a number!'),$count), 'error')
								&& $has_error = true;
							}
							break;
						case 'girth':
							$p[$key] && ! is_numeric($p[$key]) && ($has_error = 1) && 
								add_settings_error("package_class_elements",
								"package_class_elements",sprintf(__('Package Class Row %d: "Girth", if set, must be a number!'),$count), 'error')
								&& $has_error = true;
							break;
						default:
							break;
					}
					
				}
	
				if ($has_error === false)
				{
					if ( $p['length'] < $p['height'] || 
						 $p['length'] < $p['width']  ||
					    ($p['girth'] && $p['length'] < $p['girth']) )
					{
						add_settings_error("package_class_elements",
								"package_class_elements",sprintf(__('Package Class Row %d: "Length", must be the largest dimension!'),$count), 'error');
						$has_error = true;
					}
				}
	
				$package_classes[$count++] = $p;
			}

			$input['package_class_elements'] = $package_classes;
		}
		
		
		// Run module-specific validation
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
		
		// Set package_class to global if not defined
		$opts['package_class'] = ! isset($opts['package_class']) ? 'global' : $opts['package_class'];
		
		// Set dummy/blank package_class_elements if not defined
		if (! isset($opts['package_class_elements'])) 
		{
			$dummy = array('name'   => '',
						   'width'  => '',
						   'length' => '',
						   'height' => '',
						   'girth'  => '');
			
			$opts['package_class_elements'][] = $dummy;
			$opts['package_class_elements'][] = $dummy;
			$opts['package_class_elements'][] = $dummy;
		}
		
		// Ensure a minumum of 3 rows
		$size = count($opts['package_class_elements']);
		if ($size < 3)
		{
			while ($size < 3)
			{
				$opts['package_class_elements'][] = $dummy;
				$size++;
			}
		}
		
		
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
		
		
		$this->admin_notices();
		?>
		
		<div class="wrap">
			<script type="text/javascript">
				jQuery(document).ready(function($){

					// Shows the selected div - used by onchange and onload
					show_selected_div = function(){

						var val = [], hash = {};
						
						$("input.third_party_chkbx:checked").each(function(){
							val.push($(this).val());
							hash[$(this).val()] = 1;
						});

						if (! val.length) {
							
							$("div.modules").slideUp();
						}
						else {
							$.each(val,function(i,val){

								mod_div = $("div."+val);
	
								if (mod_div.is(':hidden')){
									mod_div.slideDown();
								}

							});
						}

						// Hide any non-active divs
						$("div.modules").each(function(){
							var classname = $(this).attr('class'), matches = 0;
							$.each(hash,function(key,val){
								var re = new RegExp(key);
								if (classname.match(re))
								{
									matches = 1;
								}
							});

							if (! matches)
							{
								$(this).hide();
							}
						});
						
					};

					// On click...
					$("h3.toggle_child").click(function(){
						$(this).next().slideToggle();
					});

					// On click for checkboxes...
					$(".third_party_chkbx").click(function(){
						show_selected_div();
					});

					// On load...
					show_selected_div();

					// Debug events
					$("#display_debug_contents").click(function(e){
						e.preventDefault();
						if ($("#debug_contents").is(':hidden'))
						{
							$(this).text('<?php echo addslashes(__('Hide debug contents',$this->domain));?>');
						}
						else
						{
							$(this).text('<?php echo addslashes(__('Display debug contents',$this->domain));?>');
						}
						$("#debug_contents").slideToggle();
					});

					$(".debug_xml").focus(function(){
						$(this).select();
					});

					// In-store pickup show/hide
					$("#in-store-pickup").click(function(){
						if ($(this).is(':checked')) {
							$("#in-store-pickup-text").show();
						}
						else {
							$("#in-store-pickup-text").hide();
						}
					});

					// Deselect all selected third party when none is selected
					$("#third_party_none").click(function(){
						$(".third_party_chkbx").each(function(){
							$(this).attr('checked',false);
							$(".modules").hide();
						});
					});

					$("input.third_party_chkbx[type=checkbox]").click(function(){
						if($(this).is(':checked')) {
							$("#third_party_none").attr('checked',false);
						}
					})
				});
			</script>
			
			<style type="text/css">
				table.package_class    {border-collapse:collapse; border:1px solid #ddd}
				table.package_class tr.first td {border-top:1px solid #ddd;}
				table.package_class td {border-bottom:1px solid #ddd; padding:3px;}
			</style>
			
		</div>
		<div id="icon-options-general" class="icon32"><br /></div>
		<h2><?php _e('eShop Shipping Extension Settings', $this->domain); ?> v<?php echo ESHOP_SHIPPING_EXTENSION_VERSION; ?></h2>
		
		<div id="poststuff" class="metabox-holder has-right-sidebar">

			<form method="post" action="options.php" enctype="multipart/form-data" >
			<?php settings_fields($this->options_name); ?>
			<div id="side-info-column" class="inner-sidebar">
				<div class="meta-box-sortables">
					<div id="save_shortcut" class="postbox">
						<h3><?php _e('Save Settings', $this->domain); ?></h3>
						<div class="inside">
							<p class="submit">
								<input type="submit" class="button-primary" value="<?php _e('Save Preferences',$this->domain); ?>" />
							</p>
						</div>
					</div>
					<div id="about" class="postbox">
						<h3 id="about-sidebar"><?php _e('About the Author:', $this->domain); ?></h3>
						<div class="inside">
						
							<p><?php _e('This plugin is brought to you by <a href="http://usestrict.net" target="_new">UseStrict Consulting</a>, ' . 
									    'where you can find information on Perl, PHP, and Web Technologies in general.',$this->domain); ?>
							</p>
								
							<p><?php _e('You can reach us on Twitter <a href="http://twitter.com/vinnyusestrict" target="_new">@vinnyusestrict</a> and ' . 
										'<a href="http://www.facebook.com/vinny.alves" target="_new">Facebook</a>',$this->domain); ?></p>
							
							<?php if (! defined('USC_IS_PAID')) : ?>											
								<p><strong><?php _e('Donate', $this->domain); ?>:</strong><br />
								<?php _e('Writing and maintaining a plugin takes time and coffee - a lot of it. If you enjoy this plugin, ' . 
										 'please consider making a donation towards my next all-nighter ;-). <strong>Thank You!</strong>', $this->domain ); ?>

								</p>
								<p>
									<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&<?php _e('hosted_button_id=VLQU2MMXKB6S2',$this->domain); ?>" 
									   target="_new">
										<img src="<?php _e('https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif',$this->domain);?>" 
											 border="0" alt="<?php _e('PayPal - The safer, easier way to pay online!', $this->domain);?>"
											 title="<?php _e('PayPal - The safer, easier way to pay online!', $this->domain);?>" />
									</a>
								</p>
							<?php endif;?>
						</div>
					</div>
				</div>
			</div> <!--  end of sidebar -->
			
			
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
									<p><strong><?php _e('Debug Mode',$this->domain); ?>:</strong>
										<select name="<?php echo $this->options_name?>[debug_mode]">
											<option value="0" <?php selected('0', $opts['debug_mode']) ?>><?php _e('No', $this->domain)?></option>
											<option value="1" <?php selected('1', $opts['debug_mode']) ?>><?php _e('Yes', $this->domain)?></option>
										</select><br />
										<em><?php _e('Debug mode saves the request and response XMLs every time a quote is triggered. These files may be 
										   needed for support requests. Do not turn it on unless something is wrong!',$this->domain); ?></em> 
										  
										  <?php if ($opts['debug_mode']) :?>
										  <br /><br /><a href="#" id="display_debug_contents"><?php _e('Display debug contents',$this->domain); ?></a><br />
										  <?php endif;?>
									</p>
									
									<?php if ($opts['debug_mode']) :?>
									<div id="debug_contents">
										<table style="width:90%">
											<tr>
												<th style="width:45%"><?php _e('Latest Request XML',$this->domain); ?></th>
												<th style="width:45%"><?php _e('Latest Response XML',$this->domain); ?></th>
											</tr>
											<tr>
												<td>
													<?php 
													$request_xml = __('No data found',$this->domain);
													if (file_exists($this->debug_request_file))
													{
														$request_xml = file_get_contents($this->debug_request_file);
													}	
													?>
													<textarea id="request_xml" class="debug_xml"><?php echo $request_xml; ?></textarea>		
												</td>
												<td>
													<?php 
													$response_xml = __('No data found',$this->domain);
													if (file_exists($this->debug_response_file))
													{
														$response_xml = file_get_contents($this->debug_response_file);
													}	
													?>
													<textarea id="response_xml" class="debug_xml"><?php echo $response_xml; ?></textarea>		
												</td>
											</tr>
										</table>
									</div>
									<?php endif;?>
									
									<hr />
									<div id="usc_global_zip_code">
									<p><strong><?php _e('Zip/Postal Code of origin?',$this->domain); ?></strong><br />
									<input type="text" id="from_zip" name="<?php echo $this->options_name?>[from_zip]" 
										      value="<?php echo $opts['from_zip']; ?>" /> </p>
									</div>
									<hr />
									<p><strong><?php _e('Select which interfaces (if any) you want to use:',$this->domain); ?></strong><br />
									<em><?php _e('None checked reverts to eShop\'s default settings.',$this->domain); ?></em></p>
									
									<table>
										<?php $checked = (count($opts['third_party']) == 1 || $opts['third_party'][0] == 'on') ? 'checked="checked"' : ''; ?>
										<tr>
											<th><?php _e('None',$this->domain);?></th>
											<td><input type="radio" id="third_party_none" <?php echo $checked; ?> name="<?php echo $this->options_name; ?>[third_party][]" /></td>
										</tr>
										
										<?php foreach ($this->modules as $k => $v) :
											if ($v->is_postal !== true) continue; // Show mutually exclusive services first
										
											$checked = in_array($k,$opts['third_party']) ? 'checked="checked"' : ''; 
										?>
										<tr>
											<th style="width: 150px"><label for="<?php echo "third_party_$k"?>"><?php echo $v->module_name . ($v->version ? " (v$v->version)" : ''); ?></label></th>
											<td><input type="radio" class="third_party_chkbx" id="third_party_<?php echo $k?>" value="<?php echo $k; ?>" <?php echo $checked ?> name="<?php echo $this->options_name; ?>[third_party][]" /></td>
										</tr>
										<?php endforeach; ?>
										
										<?php foreach ($this->modules as $k => $v) :
											if ($v->is_postal === true) continue; // Show services that play nicely with anyone
											$checked = in_array($k,$opts['third_party']) ? 'checked="checked"' : ''; 
										?>
										<tr>
											<th style="width: 150px"><label for="<?php echo "third_party_$k"?>"><?php echo $v->module_name . ($v->version ? " (v$v->version)" : ''); ?></label></th>
											<td><input type="checkbox" class="third_party_chkbx" id="third_party_<?php echo $k?>" value="<?php echo $k; ?>" <?php echo $checked ?> name="<?php echo $this->options_name; ?>[third_party][]" /></td>
										</tr>
										<?php endforeach; ?>
									</table>
									<?php if (has_filter(usc_after_carrier_list))
										  {
												echo apply_filters('usc_after_carrier_list',$opts); 
										  }?>
									<br />
									<hr />									
									<p><strong><?php _e('Shipping Details CSS Styles:',$this->domain); ?></strong><br />
									
									<textarea id="general_css" name="<?php echo $this->options_name; ?>[css]"><?php echo $css_contents;?></textarea>
									</p>
									<hr />
									<p><strong><?php _e('In-Store Pickup:', $this->domain); ?></strong><br />
									<input type="checkbox" id="in-store-pickup" name="<?php echo $this->options_name; ?>[in_store_pickup]"
										<?php echo $opts['in_store_pickup'] ? 'checked="checked"' : ''; ?>
									 />
									<label for="in-store-pickup"><?php _e('Enable in-store pickup',$this->domain); ?></label> - 
									<em><?php _e('Allow your clients to pick up their purchase in the store.', $this->domain); ?>.
									<?php _e('Style it using <b>div#usc_pickup_text</b>', $this->domain); ?></em>
									</p>
									<textarea style="display:<?php echo $opts['in_store_pickup'] ? 'block' : 'none'; ?>" id="in-store-pickup-text" name="<?php echo $this->options_name; ?>[in_store_pickup_text]"><?php echo $opts['in_store_pickup_text']; ?></textarea>
									<hr />
									
									<div class="postbox">
										<h3><?php _e('Package Options', $this->domain); ?></h3>
										<div class="inside">
											<p><?php _e('Select your package preferences below and create Package Classes to assign to your products.' .
													    'The plugin will then fetch the shipping rates based on the total dimensions of your products, ' .
													    'as if they were all in one box. Also, make sure to always set the fallback dimensions in the ' . 
													    'Courier Settings Boxes in case a product is missing a Package Class.',$this->domain);?></p>
											<p><strong><?php _e('Don\'t forget to assign the package classes to your products in the Product Entry form or you will ' . 
													            'not be able to make your product available when editing it!',$this->domain); ?></strong></p>
											<hr />
											
											<h4><?php _e('Package Options',$this->domain);?></h4>
											<table class="package_class">
												<tr class="first">
													<td width="25" style="text-align:center"><input type="radio" id="global_radio" class="pack_radio" name="<?php echo $this->options_name; ?>[package_class]" 
																	value="global" <?php checked('global',$opts['package_class']);?>/></td>
													<td width="150" style="font-weight:bold">
													<label for="global_radio"><?php _e('Global',$this->domain);?></label></td>
													<td>
														<?php _e('Specifies a single dimension set for the whole store. ' . 
																 'This was the only available option prior to version 1.4. <strong>It is appropriate for stores where ' . 
																 'the all products in the client\'s cart fit in a single box of an invariable size</strong>.',$this->domain);?></td>
												</tr>
												
												<tr>
													<td style="text-align:center"><input type="radio" id="product_radio" class="pack_radio" name="<?php echo $this->options_name; ?>[package_class]" 
														value="product" <?php checked('product',$opts['package_class']);?>/></td>
													<td style="font-weight:bold">
													<label for="product_radio"><?php _e('Per Product',$this->domain);?></label></td>
													<td>
														<?php _e('Assign packages at the product level. You will have to set the package class for every single product, ' . 
																 'depending on its size. <strong>It best suits stores that have a single option per product or where the product options do not ' . 
																 'vary in size</strong>.',$this->domain); ?>
													</td>
												</tr>
												
												<tr>
													<td style="text-align:center"><input type="radio" id="po_radio" class="pack_radio" name="<?php echo $this->options_name; ?>[package_class]" 
														value="product_option" <?php checked('product_option',$opts['package_class']);?> /></td>
													<td style="font-weight:bold">
													<label for="po_radio"><?php _e('Per Product Option',$this->domain);?></label></td>
													<td>
														<?php _e('Different Product Options get assigned a package class. <strong>This works for stores that offer small/medium/large ' .
																 'products as part of the Options of a Product.</strong>',$this->domain); ?>
													</td>
												</tr>
											</table>
											
											<div id="pack_div" <?php echo $opts['package_class'] == 'global' ? 'style="display:none"' : ''?>>
											<hr />
											
											<h4><?php _e('Package Classes',$this->domain);?></h4>
											<p><?php _e('Create your Package Classes below. This is not required if you selected "Global" above.',$this->domain); ?></p>
											
											<div style="float:right; width:200px; border:1px solid #ddd; padding:10px; font-weight: bold">
												<p><?php _e('Keep in mind that "Length" is required to be the largest value!',$this->domain);?></p>
											</div>
											
											<script type="text/javascript">
												jQuery(document).ready(function($){

													// Show/Hide package classes
													$("input.pack_radio").click(function(){
														if ($(this).val() == 'global') {
															$("#pack_div").hide();
														}
														else {
															$("#pack_div").show();
														}
													});

													// Bulk checks
													$("#usc_pack_select").click(function(){
														var chk = typeof $(this).attr('checked') === 'undefined' ? false : true;
														$(".pack_child").attr('checked',chk);
													});

													// Handle bulk checkbox when others are clicked
													$(".pack_child").live('click',function(){
														var init = $(this).attr('checked'),
														    all_same = true;
														   
														$(".pack_child").each(function(){
															if (init !== $(this).attr('checked')) {
																// Toggle bulk
																all_same = false;
															}
														});

														if (all_same === true) {
															$("#usc_pack_select").attr('checked',init);
														}
														else {
															$("#usc_pack_select").attr('checked',false);
														}
													});


													// Delete a row
													$("a.del_pack_row").live('click',function(e){
														e.preventDefault();
														var rowid = $(this).attr('rel');
														$("#package_classes tr[rel="+rowid+"]").remove();

														if ($("input.pack_child").length == 0) {
															$("#usc_pack_select").css('visibility','hidden');
														}
													});

													// Bulk delete
													$("#usc_pack_bulk_delete").click(function(e){
														e.preventDefault();

														var num_sel = $("input.pack_child:checked").length;
														if (! num_sel) {
															alert("<?php _e('Select at least one row to delete!',$this->domain);?>");
															return;
														}

														$("input.pack_child:checked").each(function(){
															var rel = $(this).attr('rel');
															$("#package_classes tr[rel="+rel+"]").remove();
														});

														$("#usc_pack_select").attr('checked',false);

														if (! $("input.pack_child").length) {
															$("#usc_pack_select").css('visibility','hidden');
														}
														
													});

													// Add a row
													$("a#usc_pack_add_more").live('click',function(e){
														e.preventDefault();
														// Find last data row
														var highest_rel = 0, new_rel, last_row, data;
														$("#usc_pack_select").css('visibility','visible');

														$("#package_classes tr.pack_data").each(function(){
															
															var this_rel = parseInt($(this).attr('rel'),10);
																														
															if (this_rel > highest_rel) highest_rel = this_rel;
														});

														new_rel = highest_rel + 1;
														
														last_row = $("#package_classes tr.pack_data").filter(':last');

														data = $("<tr/>",{rel: new_rel, 'class': 'pack_data'})
															.append($('<td><input type="checkbox" class="pack_child" rel="'+new_rel+'" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][name]" size="20"/></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][length]" value="" size="8" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][width]" value="" size="8" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][height]" value="" size="8" /></td>'))
															.append($('<td><a href="#" class="del_pack_row" rel="'+new_rel+'" style="color:red"><?php _e('Delete',$this->domain);?></a></td>'));

														last_row.after(data);
														$("#pack_class_div").scrollTop($("#pack_class_div").height());
													});
													
												});

												

											</script>
											
											<div id="pack_class_div" style="max-height:400px;overflow:auto">
												<table id="package_classes">
													<tr>
														<th><input type="checkbox" id="usc_pack_select" /></th>
														<th style="text-align:center"><?php _e('Name',$this->domain); ?></th>
														<th style="text-align:center"><?php _e('Length',$this->domain); ?></th>
														<th style="text-align:center"><?php _e('Width',$this->domain); ?></th>
														<th style="text-align:center"><?php _e('Height',$this->domain); ?></th>
														<th>&nbsp;</th>
													</tr>
													<?php $count=0; foreach ($opts['package_class_elements'] as $pe) : $count++; ?>
													<tr rel="<?php echo $count ?>" class="pack_data">
														<td>
														<?php if ($count != 1) :?>
															<input type="checkbox" class="pack_child" rel="<?php echo $count; ?>" />
														<?php endif;?>
														</td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][name]"; ?>" value="<?php echo htmlspecialchars($pe['name']); ?>" size="20"/></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][length]"; ?>" value="<?php echo $pe['length']; ?>" size="8" /></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][width]"; ?>" value="<?php echo $pe['width']; ?>" size="8" /></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][height]"; ?>" value="<?php echo $pe['height']; ?>" size="8" /></td>
														<td style="color:red">
														<?php if ($count != 1) :?>
															<a href="#" class="del_pack_row" rel="<?php echo $count; ?>" style="color:red"><?php _e('Delete',$this->domain);?></a>
														<?php endif;?>	
														</td>
													</tr>
													<?php endforeach;?>
													<tr>
														<td colspan="7">&nbsp;</td>
													</tr>
													<tr>
														<td colspan="2" style="text-align:left"><a href="#" id="usc_pack_add_more"><?php _e('Add More',$this->domain); ?></a></td>
														<td colspan="3">&nbsp;</td>
														<td colspan="2" style="text-align:right"><a href="#" id="usc_pack_bulk_delete" style="color:red"><?php _e('Bulk Delete',$this->domain); ?></a></td>
													</tr>
												</table>
												</div>
												<?php // Added filter to show package class admin data ?>
												<?php echo apply_filters('usc_end_package_class_div',''); ?>											
											</div>
										</div>
									</div>
									
									<hr />
									
									<?php if ( ! isset($opts['display_format']) ) $opts['display_format'] = 'as-dropdown'; ?>
									
									<p>
										<strong><?php _e('Shipping Service Display:', $this->domain); ?></strong><br /><br />
										<?php _e('When displaying the list of available shipping services to the visitor, display them...', $this->domain); ?><br><br>
									<label><input type="radio" id="as-dropdown" name="<?php echo $this->options_name; ?>[display_format]" value="as-dropdown"
										<?php echo checked($opts['display_format'], 'as-dropdown') ; ?>
									 /> <?php _e('As Drop-down')?></label><br>
									 <label><input type="radio" id="as-radio-list" name="<?php echo $this->options_name; ?>[display_format]" value="as-radio-list"
										<?php echo checked($opts['display_format'], 'as-radio-list') ; ?>
									 /> <?php _e('As List of Radio Buttons')?></label>
									 
									 </p>
									 
									 <hr>
									
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
									<?php echo $v->admin_form_html(); ?>
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