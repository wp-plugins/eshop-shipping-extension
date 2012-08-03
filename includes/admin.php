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
		
		
		// Initialize any Shipping modules
		$this->initialize_modules();
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
		$third_party[] = 'none';
		
		$this->do_recursive($input,'trim');
		
		if (! in_array($input['third_party'], $third_party))
		{
			add_settings_error('third_party','third_party',__('Invalid option selected!'), 'error');
		}
		
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
		<?php settings_errors();?>
		<div class="wrap">
			<script type="text/javascript">
				jQuery(document).ready(function($){

					// Shows the selected div - used by onchange and onload
					show_selected_div = function(){

						var val = $("#third_party_select").val();
						
						if (val == 'none') {
							
							$("div.modules").slideUp();
						}
						else {
							mod_div = $("div."+val);
							// Hide all modules except active one
							$("div.modules").not(mod_div).hide();

							if (mod_div.is(':hidden')){
								mod_div.slideDown();
							}
						}
					};

					// On click...
					$("h3.toggle_child").click(function(){
						$(this).next().slideToggle();
					});

					// On change...
					$("#third_party_select").change(function(){
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
							
							<?php if (! defined('USC_IS_PAID')) : ?>											
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
							<?php endif;?>
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
									<p><strong><?php _e('Debug Mode',$this->domain); ?>:</strong>
										<select name="<?php echo $this->options_name?>[debug_mode]">
											<option value="0" <?php selected('0', $opts['debug_mode']) ?>><?php _e('No', $this->domain)?></option>
											<option value="1" <?php selected('1', $opts['debug_mode']) ?>><?php _e('Yes', $this->domain)?></option>
										</select><br />
										<em><?php _e('Debug mode saves the request and response XMLs every time a quote is triggered. These files may be 
										   needed for support requests. Do not turn it on unless something is wrong!',$this->domain); ?></em> 
										  
										  <?php if ($opts['debug_mode']) :?>
										  <p><a href="#" id="display_debug_contents"><?php _e('Display debug contents',$this->domain); ?></a></p>
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
									
									
									<div id="usc_global_zip_code">
									<p><strong><?php _e('Zip/Postal Code of origin?',$this->domain); ?></strong><br />
									<input type="text" id="from_zip" name="<?php echo $this->options_name?>[from_zip]" 
										      value="<?php echo $opts['from_zip']; ?>" /> </p>
									</div>
									
									<p><strong><?php _e('Select which interface (if any) you want to use:',$this->domain); ?></strong><br />
									<select id="third_party_select" name="<?php echo $this->options_name; ?>[third_party]">
										<option value="none" <?php selected($opts['third_party'],'none');?>><?php _e('None',$this->domain); ?></option>
										<?php foreach ($this->modules as $k => $v) :?>
										<option value="<?php echo $k; ?>" <?php selected($opts['third_party'],$k);?>><?php echo $v->module_name; ?></option>
										<?php endforeach;?>
									</select> <em><?php _e('"None" means eShop\'s default settings.',$this->domain); ?></em></p>
									<p><strong><?php _e('Shipping Details CSS Styles:',$this->domain); ?></strong><br />
									
									<textarea id="general_css" name="<?php echo $this->options_name; ?>[css]"><?php echo $css_contents;?></textarea>
									</p>
									
									<hr />
									
									<div class="postbox">
										<h3><?php _e('Package Options', $this->domain); ?></h3>
										<div class="inside">
											<p><?php _e('Select your package preferences below and create Package Classes to assign to your products.' .
													    'The plugin will then fetch the shipping rates based on the total dimensions of your products, ' .
													    'as if they were all in one box.',$this->domain);?></p>
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
													<td style="text-align:center"><input type="radio" id="package_radio" class="pack_radio" name="<?php echo $this->options_name; ?>[package_class]" 
														value="package" <?php checked('package',$opts['package_class']);?>/></td>
													<td style="font-weight:bold">
													<label for="package_radio"><?php _e('Per Product',$this->domain);?></label></td>
													<td>
														<?php _e('Assign packages at the product level. You will have to set the package class for every single product, ' . 
																 'depending on its size. <strong>It best suits stores that have a single option per product or where the product options do not ' . 
																 'vary in size</strong>.',$this->domain); ?>
													</td>
												</tr>
												
												<tr>
													<td style="text-align:center"><input type="radio" id="po_radio" class="pack_radio" name="<?php echo $this->options_name; ?>[package_class]" 
														value="package_option" <?php checked('package_option',$opts['package_class']);?> /></td>
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
															if ($(this).attr('rel') > highest_rel) highest_rel = $(this).attr('rel');
														});

														new_rel = (parseInt(highest_rel,10) + 1);
														last_row = $("#package_classes tr.pack_data").filter(':last');

														data = $("<tr/>",{rel: new_rel, 'class': 'pack_data'})
															.append($('<td><input type="checkbox" class="pack_child" rel="'+new_rel+'" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][name]" size="20"/></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][length]" value="" size="8" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][width]" value="" size="8" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][height]" value="" size="8" /></td>'))
															.append($('<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name; ?>[package_class_elements]['+new_rel+'][girth]" value="" size="8" /></td>'))
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
														<th style="text-align:center"><?php _e('Girth',$this->domain); ?></th>
														<th>&nbsp;</th>
													</tr>
													<?php $count=0; foreach ($opts['package_class_elements'] as $pe) : $count++; ?>
													<tr rel="<?php echo $count ?>" class="pack_data">
														<td>
														<?php if ($count != 1) :?>
															<input type="checkbox" class="pack_child" rel="<?php echo $count; ?>" />
														<?php endif;?>
														</td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][name]"; ?>" value="<?php echo $pe['name']; ?>" size="20"/></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][length]"; ?>" value="<?php echo $pe['length']; ?>" size="8" /></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][width]"; ?>" value="<?php echo $pe['width']; ?>" size="8" /></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][height]"; ?>" value="<?php echo $pe['height']; ?>" size="8" /></td>
														<td><input type="text" class="package_class_elements" name="<?php echo $this->options_name . "[package_class_elements][$count][girth]"; ?>" value="<?php echo $pe['girth']; ?>" size="8" /></td>
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
											
											</div>
										</div>										
									</div>
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