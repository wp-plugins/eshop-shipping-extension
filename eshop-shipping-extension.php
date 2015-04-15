<?php defined('ABSPATH') or die("No direct access allowed");
/*
* Plugin Name:   eShop Shipping Extension
* Plugin URI:	 http://usestrict.net/2012/06/eshop-shipping-extension-for-wordpress-canada-post/
* Description:   eShop extension to use third-party shipping services. Currently supports Canada Post, UPS, USPS, and Correios. Correios, UPS, and USPS modules can be purchased at http://goo.gl/rkmu0
* Version:       2.3.2
* Author:        Vinny Alves
* Author URI:    http://www.usestrict.net
*
* License:       GNU General Public License, v2 (or newer)
* License URI:  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* Copyright (C) 2012-2015 www.usestrict.net, released under the GNU General Public License.
*/
define('ESHOP_SHIPPING_EXTENSION_ABSPATH', plugin_dir_path(__FILE__));
define('ESHOP_SHIPPING_EXTENSION_INCLUDES', ESHOP_SHIPPING_EXTENSION_ABSPATH . '/includes');
define('ESHOP_SHIPPING_EXTENSION_MODULES', ESHOP_SHIPPING_EXTENSION_INCLUDES . '/modules');
define('ESHOP_SHIPPING_EXTENSION_THIRD_PARTY', ESHOP_SHIPPING_EXTENSION_INCLUDES . '/third-party');
define('ESHOP_SHIPPING_EXTENSION_VERSION', '2.3.2');
define('ESHOP_SHIPPING_EXTENSION_DOMAIN', 'eshop-shipping-extension');
define('ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL',plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/css'));
define('ESHOP_SHIPPING_EXTENSION_MODULES_URL',plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/modules'));
define('ESHOP_SHIPPING_EXTENSION_DBG_REQUEST', ESHOP_SHIPPING_EXTENSION_ABSPATH . '/debug_request.xml');
define('ESHOP_SHIPPING_EXTENSION_DBG_RESPONSE', ESHOP_SHIPPING_EXTENSION_ABSPATH . '/debug_response.xml');


class USC_eShop_Shipping_Extension
{
	var $options;
	var $eshop_options;
	var $eshop_is_active;
	var $eshop_is_installed;

	var $domain         = ESHOP_SHIPPING_EXTENSION_DOMAIN;
	var $options_name   = 'eshop-shipping-extension';
	var $active_modules = array();
	var $css_filename   = 'USC_eShop_Shipping.css';

	var $debug_request_file  = ESHOP_SHIPPING_EXTENSION_DBG_REQUEST;
	var $debug_response_file = ESHOP_SHIPPING_EXTENSION_DBG_RESPONSE;
	
	var $helper;
	
	function __construct()
	{
		if ( ! session_id() )
			session_start();
		
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); # needed for "is plugin active"
		
		$this->eshop_is_installed = file_exists(WP_PLUGIN_DIR . '/eshop/eshop.php') ? TRUE : FALSE;
		$this->eshop_is_active    = is_plugin_active('eshop/eshop.php') ? TRUE : FALSE;
		$this->eshop_options      = $this->get_eshop_options();
		$this->active_modules     = $this->init_active_modules();
		
		$this->helper = new USC_eShop_Shipping_Extension_helper();
				
		// Add common JS script always - this gets localized if it's not Admin (see below).
		wp_enqueue_script( $this->domain, ESHOP_SHIPPING_EXTENSION_MODULES_URL . '/../eshop_shipping_extension.js', array( 'jquery' ),  ESHOP_SHIPPING_EXTENSION_VERSION);
		
		// Add the filter to update the cart form with the shipping fields
		add_filter('usc_add_shipping_fields', array(&$this,'add_shipping_fields'), 10, 3);
		
		// Add the filter to show the shipping selection in the order
		add_filter('usc_shipping_info_for_orders', array(&$this, 'shipping_info_for_orders'), 10, 0);

		// Add filter for handling additional_services string
		add_filter('usc_calc_additional_services', array(&$this, 'calc_additional_services'), 10, 2);
		
		// Add filter to get options
		add_filter('usc_ese_options', array(&$this, 'get_options'), 10, 1);
		
		// Load language files for admin and ajax calls
		add_action('plugins_loaded', array(&$this,'load_lang'));
		
		// Show any admin notices
		add_action('admin_notices', array(&$this,'admin_notices'));
		
		// Copy any extra third-party modules into self
		register_activation_hook(__FILE__ , array(&$this,'install_extra_modules'));

		// Add ajax handling for non-logged in people
		add_action('wp_ajax_nopriv_' . $this->domain . '-get-rates', array(&$this,'get_rates'));
		
		if (is_admin())
		{
			$this->sync_modules();
			
			// Add ajax capability for logged-in people, even though it's not really
			// the admin interface.
			add_action('wp_ajax_' . $this->domain . '-get-rates', array(&$this,'get_rates'));
			
			// Add ajax capability to refresh state list when changing the country.
			add_action('wp_ajax_' . $this->domain . '-refresh-states', array(&$this,'refresh_states'));
			
			// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
			wp_localize_script( $this->domain . '-refresh-states', 'eShopShippingModule', array( ) );
			
		}
		else
		{
			// Do nothing if the user has not selected Method 4
			// or if no module is active
			if ((int)$this->eshop_options['shipping'] !== 4)
			{
				return;
			}
			elseif (sizeof($this->active_modules) == 0)
			{
				return;
			}

			add_action( 'init', array( &$this, 'init') );
		}
		
	}

	function init()
	{
		$opts = $this->get_options();
		if ( ! isset($opts['display_format']) )
			$opts['display_format'] = 'as-dropdown';
			
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( $this->domain, 'eShopShippingModule', array('currency'   => $this->eshop_options['currency_symbol'],
																		'ajaxurl'    => preg_replace( '/^https?:/', '', admin_url( 'admin-ajax.php' ) ),
																		'ajaxaction' => $this->domain . '-get-rates',
																		'method'     => (is_user_logged_in()?'GET':'POST'),
																		'svc_display_pref' => $opts['display_format'],
																		'lang'       => $this->js_msgs() ) );
		
		// Add CSS file
		wp_enqueue_style( $this->domain . '-style', ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL . '/' . $this->css_filename,
						  null,  ESHOP_SHIPPING_EXTENSION_VERSION , 'all' );
	}
	
	
	/**
	 * Method: sync_modules
	 * Description: a workaround for Automated updates that don't trigger register_activation_hooks
	 */
	function sync_modules()
	{
		$version = get_option('eshop-shipping-extension-version');
		if (! $version || $version !== ESHOP_SHIPPING_EXTENSION_VERSION)
		{
			$this->install_extra_modules();
		}
	}
	
	/**
	 * Method: get_states_for_country()
	 * @param string $country_code
	 * Description: Wrapper around helper _get_states_for_country so it can be accessible from children
	 */
	function get_states_for_country($country_code,$active_module=null)
	{
		if (!$active_module) $active_module = $this;
		
		if (! $this->helper)
		{
			$this->helper = new USC_eShop_Shipping_Extension_helper();
		}
		
		return $this->helper->_get_states_for_country($country_code,$active_module);
	}

	
	
	/**
	 * Method: refresh_states()
	 * @param string $country_code
	 * Description: Ajax interface for refreshing the state list when country changes
	 */
	function refresh_states()
	{
		$country_code = $_REQUEST['country_code'];
		$force_module = $_REQUEST['force_module'];
		
		if ($force_module)
		{
			$this->active_module = new $force_module();
		}
		
		$states = $this->get_states_for_country($country_code,$this->active_module);
		header("Content-type: application/json");

		$out['success'] = true;
        $out['data']    = $states;

        echo json_encode($out);
        exit; // WP requirement for ajax-related methods
	}
	
	
	/**
	 * Method: debug_mode()
	 * Description: Returns value of debug mode. Needs to be a method because child classes don't necessarily hit __construct() to set a variable.
	 */
	function debug_mode()
	{
		$opts = $this->get_options();
		return $opts['debug_mode'];
	}
	
	/**
	 * Method: get_eshop_options()
	 * Description: Gets/caches eshop options
	 * @param bool $force
	 */
	function get_eshop_options($force_reload=false)
	{
		global $eshopoptions;
		
		if (! $this->eshop_options || $force_reload === true)
		{
			if (! empty($eshopoptions))
			{
				$this->eshop_options = $eshopoptions;
			}
			else
			{
				$this->eshop_options = get_option('eshop_plugin_settings');
			}
		}
		
		return $this->eshop_options;
	}
	
	/**
	 * Method: init_active_modules()
	 * Description: Sets and returns the shipping modules currently selected
	 */
	function init_active_modules()
	{
		$opts   = $this->get_options();
		$path   = ESHOP_SHIPPING_EXTENSION_MODULES;
		
		foreach ((array)$opts['third_party'] as $active)
		{		
			$files = glob($path . '/*/' . $active . '.php');
			$file  = $files[0];
			
			if (file_exists($file))
			{
				require_once($file);
				$this->active_modules[] = new $active(); 
			}
		}

		return $this->active_modules;
	}
	
	
	/**
	 * Method: get_rates()
	 * Description: Wrapper for the active module's get_rates() method
	 */
	function get_rates()
	{
		global $blog_id;
		
		$opts = $this->get_options();
		
		// reset the session variable
		$_SESSION['usc_3rd_party_shipping'.$blog_id] = array();
		
		header("Content-type: application/json");
		
		// Allow blacklisting of service
		$blacklist = apply_filters('usc_ese_blacklist', array());
		
		if ( (! isset($blacklist['free_shipping']) || ! $blacklist['free_shipping']) 
			 && is_shipfree(calculate_total()))
		{
			$service_info = array();
			$service_name = __('Your purchase is eligible for free shipping.',$this->domain);
			$service_info[$service_name]['success'] = true;
			$service_info[$service_name]['data'][$service_name] = array();
			
			echo json_encode($service_info);
			exit; // WP requirement for ajax-related methods
		}
		
		if (! count($this->active_modules))
		{
			$out['success'] = false;
			$out['msgs'][] = __('No shipping module is currently active!', $this->domain);
			
			echo json_encode($out);
			exit; // WP requirement for ajax-related methods
		}
		
		
		foreach ($this->active_modules as $mod)
		{
			if (isset($blacklist[$mod->module_name]) && $blacklist[$mod->module_name])
				continue;
			
			$out[$mod->module_name] = $mod->get_rates($_REQUEST);
			
			if ($out[$mod->module_name]['success'] == false)
			{
				error_log("Error getting rates for " . get_class($mod) . ": " . print_r($out,1));
			}
			
			$out = apply_filters( 'usc_ese_filter_rates', $out );
		}

		// The filter below is wrong, but we need to keep backwards compatibility, so let's call it again, right.
		if (has_filter('usc_do_handling'))	$out = apply_filters('usc_do_handling', $opts, $out);
		
		// Allow for sorting, price manipulation, etc.
		$out = apply_filters('usc_ese_filter_services_array', $out, $opts );
		
		if ($opts['in_store_pickup'])
		{
			$service_info = array();
			$service_name = __('In-Store Pickup',$this->domain);
			$service_info[$service_name]['success'] = true;
			$service_info[$service_name]['data'][$service_name]['price'] = '0.00';
			$service_info[$service_name]['data'][$service_name]['details']['usc_pickup'] = $opts['in_store_pickup_text'];
				
			$out = $service_info + $out;
				
			$_SESSION['usc_3rd_party_shipping'.$blog_id] = $service_info + (array)$_SESSION['usc_3rd_party_shipping'.$blog_id];
		}
		
		echo json_encode($out);
		exit; // WP requirement for ajax-related methods
	}
	
	
	/**
	 * Method: get_options()
	 * Description: Returns default/saved options for this shipping module
	 * @param bool $force - to force reloading of the options
	 * @return array
	 */
	function get_options($force = false)
	{
		if ($this->options && $force === false)
		{
			return $this->options;
		}
	
		$default = array();
		$default[$this->options_name]['third_party'] = array(); 
		$default[$this->options_name]['debug_mode']  = 0;
	
		$this->options = get_option($this->options_name, $default);

		// Copy the from_zip value into the active module's opts array 
		$this->options[$this->active_module]['from_zip'] = $this->options['from_zip'];
		
		if (! isset($this->options['in_store_pickup_text']))
		{
			$this->options['in_store_pickup_text'] = __('Enter a text for your customer, such as the address where to pick up the package.',$this->domain);
		}
		
		// Backwards compatibility
		if (! is_array($this->options['third_party']))
		{
			$this->options['third_party'] = array($this->options['third_party']);
		}
		
		return $this->options;
	}
	
	
	/**
	 * Method: add_shipping_fields()
	 * Description: calls child module's add_shipping_fields()
	 * @param string $form_html
	 * @param array $reqdarray
	 */
	function add_shipping_fields($form_html, $reqdarray, $position = 'under_shipping')
	{
		global $blog_id, $eshopoptions;
		
		if ($position != 'under_shipping' && $eshopoptions['hide_shipping'] != 'yes')
		{
			return $form_html;
		}
		
		$free_shipping_applies = is_shipfree(calculate_total());
		
		if ($free_shipping_applies)
		{
			$required = '';
			$shipping_title = __('Shipping', 'eshop');
		}
		else
		{
			$required = eshop_checkreqd($reqdarray,'shipping');
			$shipping_title = __('Please Choose Shipping','eshop');
		}
		
		$out  = '<fieldset class="eshop fld0"><legend id="shiplegend">'. $shipping_title .$required.'</legend>';
		$out .= '<div id="usc_shipping_error_msgs"></div><div id="usc_shipping_options">';

		if ($free_shipping_applies)
		{
			$out .= __('Your purchase is eligible for free shipping.',$this->domain);
			$out .= "<input type=\"hidden\" name=\"eshop_shiptype\" value=\"0\" /></div></fieldset>";
			
			return $form_html . $out;
		}
		
		// Not free shipping, so continue
		
		// Build select with options if the form is being re-displayed after an error
		if (isset($_POST['eshop_shiptype']))
		{
			$out .= '<script type="text/javascript">' ."\n";
			$out .= 'eShopShippingModule.startup_details = [{success: true, data:{}, selected_service:"'.$_POST['eshop_shiptype'].'", additional_services:{}}];' ."\n";
			
			if (isset($_POST['additional_shipping_services']))
			{
				$svc_array = explode('; ',$_POST['additional_shipping_services']);
				foreach($svc_array as $svc)
				{
					$out .= 'eShopShippingModule.startup_details[0].additional_services["'.$svc.'"] = 1;' ."\n";
				}
			}
			
			
			$svc_groups = array();
			foreach ($_SESSION['usc_3rd_party_shipping'.$blog_id] as $k => $v)
			{
				if ($k == __('In-Store Pickup',$this->domain))
				{
					$svc_groups[$k] = $v;
				}
				else
				{
					preg_match('/^([^-]+)/',$k,$grp_name);
					$svc_groups[trim($grp_name[1])]['data'][$k] = $v;
					$svc_groups[trim($grp_name[1])]['success'] = true;
				}
			}
			
			// Rebuild the shipping and details form from JS. Delete startup data after the first rendering
			$out .= 'eShopShippingModule.startup_details[1] = ' . json_encode($svc_groups) . ';' . "\n";
			$out .= 'jQuery(document).ready(function(){
						eShopShippingModule.create_shipping_html(eShopShippingModule.startup_details,true);
						delete eShopShippingModule.startup_details;
				     });';
			$out .= '</script>' ."\n";
		}
		
		$view_update = isset($_POST['eshop_shiptype']) ?  __('Update Shipping Options', $this->domain) : __('View Shipping Options', $this->domain);
		
		$out .= '</div><div id="usc_shipping_details"></div>';
		$out .= '<a id="usc_update_shipping_options" href="#">' . $view_update . '</a>';
		$out .= '<img style="display:none" id="usc_shipping_throbber" class="usc_shipping_throbber" src="' . plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/images/arrows-throbber.gif') . '" />';
		$out .= "</fieldset>";
		
		$form_html .= $out;
		
		return $form_html;
	}
	
	
	/**
	 * Method: get_options_name()
	 * Description: returns $this->options_name for child modules 
	 * @return string
	 */
	function get_options_name()
	{
		return $this->options_name;
	}
	
	
	/**
	 * Method: load_lang
	 * Description: Loads the locale file into the domain
	 */
	function load_lang()
	{
		load_plugin_textdomain($this->domain, false, $this->domain . '/includes/Languages/' );
	}
	
	
	/**
	 * Method: set_notice()
	 * Description: Sets notices to be displayed
	 * @param string $msg
	 * @param bool $is_error
	 */
	function set_notice($msg, $is_error = false)
	{
		$_SESSION['usc_notices'][] = array('msg' => $msg, 'is_error' => $is_error);
	}
	
	
	/**
	 * Method: admin_notices
	 * Desc: Shows important notices in the admin interface
	 */
	function admin_notices()
	{
		if (isset($_SESSION['usc_notices']))
		{
			foreach ($_SESSION['usc_notices'] as $notice)
			{
				$class = $notice['is_error'] === true ? 'error' : 'updated fade';
				
				echo sprintf('<div class="%s"><p><strong>%s</strong></p></div>',$class, $notice['msg']);
			}
			
			unset($_SESSION['usc_notices']);
		}
	}
	
	
	/**
	 * Method: get_mode()
	 * Description: taps into eShop's mode
	 */
	function get_mode()
	{
		$eshop_opts = $this->get_eshop_options();
		
		if (!$eshop_opts)
		{
			$status = 'testing';
		}
		else
		{
			$status = $eshop_opts['status'];
		}
		
		return $status;
	}
	
	
	/**
	 * Method: do_recursive()
	 * Description: helper method to be used in data cleanups, etc.
	 */
	protected function do_recursive(&$var, $func)
	{
		if (is_array($var))
		{
			foreach ($var as $k => $v)
			{
				$this->do_recursive($var[$k],$func);
			}
		}
		elseif (is_callable($func))
		{
			$var = $func($var);
		}
	}
	
	
	/**
	 * Method: convert_to_kilos()
	 * Description: converts a float into kilos according to eshop_weight_unit
	 */
	function convert_to_kilos($input)
	{
		$eshop_opts = $this->get_eshop_options();
		$units_from = str_replace('.','',strtolower($eshop_opts['weight_unit'])); // strip out any periods
		$out        = array('success' => true);
		
		if (! is_numeric($input) || $input == '0')
		{
			$out['succes'] = false;
			$out['msgs'][] = __('Invalid value to convert into Kilos!', $this->domain);
			
			return $out;
		}
		
		switch ($units_from) {
			case 'k':
			case 'kg':
			case 'kilo':
			case 'kilos':
				$out['data'] = $input;
				break;
			case 'l':
			case 'lb':
			case 'lbs':
			case 'pound':
			case 'pounds':
				$out['data'] = $input * 0.45359237;
				break;
			case 'g':
			case 'gr':
			case 'gram':
			case 'grams':
				$out['data'] = $input / 1000;
				break;
			default:
				$out['data'] = $input;
		}
	
		return $out;
	}
	
	
	/**
	 * Method: install_extra_modules()
	 * Description: look for any extra modules the user may have and copy the files over
	 */
	function install_extra_modules()
	{
		// Look for extra modules under eshop-shipping-extension-*
		// Copy contents from js and third-party into framework dir
		$plugins_dir = ESHOP_SHIPPING_EXTENSION_ABSPATH . '../';
		$dir_str     = $plugins_dir . $this->domain . '-*/modules/*/*';
		$files       = glob($dir_str);
		$cert_files  = glob($plugins_dir . $this->domain . '-*/includes/third-party/cert/*');

		
		foreach ($files as $file)
		{
			// Get file names
			preg_match('!/([^/]+)/modules/([^/]+)/([^\.]+\.(js|php))$!', $file, $matches);

			if ($matches[0])
			{
				$plugin    = $matches[1] . '/' .  $matches[1] . '.php'; // this is the module plugin file
				$vendor_id = $matches[2];
				$to_dir    = ESHOP_SHIPPING_EXTENSION_ABSPATH . 'includes/modules/' . $vendor_id;

				// Don't do anything if the plugin isn't active!
				if (! is_plugin_active($plugin)) continue;
				
				// Create the module dir if it doesn't exist
				if (! is_dir($to_dir))
				{
					if (! @mkdir($to_dir))
					{
						$e = error_get_last();
						$this->set_notice(__('Failed to create directory: ', $this->domain) . $to_dir . sprintf(' (%s)', $e['message']), true);
					}
				}
				
				$filepath = $matches[3];
				
				if (! @copy($file, $to_dir . '/' . $filepath))
				{
					$e = error_get_last();
					$this->set_notice(__('Failed to install module file: ', $this->domain) . $filepath . sprintf(' (%s) ' . __LINE__, $e['message']), true);
				}
			}
		}
		
		// Install cert file
		// eshop-shipping-extension-*/includes/third-party/cert/*
		foreach ($cert_files as $file)
		{
			preg_match('!(.+?/includes/third-party/cert/(.+))$!', $file, $matches);

			$from      = $matches[1];
			$file_name = $matches[2];
			$to        = ESHOP_SHIPPING_EXTENSION_ABSPATH . 'includes/third-party/cert/' . $file_name;

			if (! @copy($from, $to))
			{
				$e = error_get_last();
				$this->set_notice(__('Failed to install module file: ', $this->domain) . $file_name . sprintf(' (%s) ' . __LINE__, $e['message']), true);
			}
		}
		
		// Set the correct version in the DB
		update_option('eshop-shipping-extension-version',ESHOP_SHIPPING_EXTENSION_VERSION);
	}
	
	
	/**
	 * Method: js_msgs()
	 * Description: global javascript messages (translation)
	 */
	function js_msgs()
	{
		$msgs = array();
		
		// Child messages
		foreach ($this->active_modules as $mod)
		{
			if (method_exists($mod, 'get_js_msgs'))
			{
				$msgs = $msgs + $mod->get_js_msgs();
			}
		}
		
		$msgs['update-shipping-options'] = __('Update Shipping Options', $this->domain);
		$msgs['invalid_weight'] = __('Invalid Weight', $this->domain);

		return $msgs;
	}
	
	
	/**
	 * Method: calc_additional_services()
	 * Desc: splits additional services string and returns sum of values found in session
	 * @param string $main_service - e.g Priority Mail
	 * @param string $input_string - the string with all selected additional services
	 * @param string $split        - the field separator
	 */
	function calc_additional_services($main_service,$input_string,$split='; ')
	{
		global $blog_id;
		
		$sel_services = explode($split, $input_string);
		$sess         = $_SESSION['usc_3rd_party_shipping'. $blog_id][$main_service]['services'];
		$sum          = 0;
		
		foreach ($sel_services as $service)
		{
			if (isset($sess[$service]) && is_numeric($sess[$service]))
			{
				$sum += $sess[$service];
			}
		}
		
		return $sum;
	}
	
	
	/**
	 * Method: shipping_info_for_orders()
	 * Desc: Takes shipping information from the session and post and formats it nicely
	 * Returns: string
	 */
	function shipping_info_for_orders()
	{
		global $blog_id;
		
		$sel_svc = $_SESSION['usc_3rd_party_shipping' . $blog_id][$_POST['eshop_shiptype']];
		
		$svcs = array('Service: ' . $_POST['eshop_shiptype'] . ' ('.$sel_svc['price'].')');
		
		$extra_arr = explode('; ', $_POST['additional_shipping_services']);
		
		foreach ($extra_arr as $extra)
		{
			if (isset($sel_svc['services']) && isset($sel_svc['services'][$extra]))
			{
				$svcs[] = 'Extra: ' . $extra . ' ('.$sel_svc['services'][$extra].')';
			}
		}
		 
		$str = join('<br />', $svcs);
		return "<small>$str</small>"; 		
	}
	
	
	/**
	 * Method: make_hash_from_values()
	 * Desc: Takes an assoc_array's values and transforms them into val => 1 
	 * Returns: array
	 */
	function make_hash_from_values($input)
	{
		$output = array();
		if (is_array($input))
		{
			foreach ($input as $key => $val)
			{
				$output[$val] = 1;
			}
		}
		else
		{
			$output = $input;
		}
		
		return $output;
	}
	
	
	/**
	 * Method: get_package_class_by_name()
	 * Desc: Wrapper function for helper to get package class dimensions using its name
	 * Returns: array
	 */
	function get_package_class_by_name($name)
	{
		if (! $this->helper)
		{
			$this->helper = new USC_eShop_Shipping_Extension_helper();
		}

		return $this->helper->_get_package_class_by_name($name);
	}
	
	
	/**
	 * Method: mergeXML()
	 * Desc: Wrapper function for helper to insert child XML objects into a parent XML
	 * Returns: array
	 */
	function mergeXML(&$xml, $child)
	{
		if (! $this->helper)
		{
			$this->helper = new USC_eShop_Shipping_Extension_helper();
		}
	
		return $this->helper->_mergeXML($xml, $child);
	}
	
	
	/**
	 * Method: convert_currency()
	 * Desc: Converts received currency into eShop currency if required
	 * @param string $from_curr
	 * @param float $from_value
	 * Returns: decimal
	 */
	function convert_currency($from_curr, $from_value)
	{
		global $wp_locale;
		
		$eshop_opts = $this->get_eshop_options();
		$to_curr    = $eshop_opts['currency'];
		$from_value = str_replace(',', '.', $from_value);
		
		if ($from_value == '0.00') return $from_value;
		
		if (strtolower($from_curr) == strtolower($to_curr)) return $from_value;
		
		$url = "http://www.google.com/ig/calculator?hl=en&q=${from_value}${from_curr}%3D%3F${to_curr}";
		
		// Can't use json_decode here because of PHP bug with unquoted keys
		$out = wp_remote_get($url);
		if (is_wp_error($out) )
			return $from_value;

		preg_match('/rhs:\s*"([^ ]+)/',$out['body'],$matches);
		
		if (! isset($matches[1])) return $from_value;
		
		$value = preg_replace('/\xa0/i','',$matches[1]); // remove pesky hex characters from the number, e.g. 2\xa0345.67.
		
		$conv = number_format($value,2,$wp_locale->number_format['decimal_point'],'');

		return $conv;
	}
	
}


// Class for common helper functions
class USC_eShop_Shipping_Extension_helper extends USC_eShop_Shipping_Extension 
{
	private $opts;
	private $pack_classes = array();
	
	function __construct()
	{
		$this->opts = $this->get_options();
	}
	
	
	function _get_states_for_country($country_code,$active_module=NULL)
	{
		if (!$active_module) $active_module = $this;
		
		if (method_exists($active_module,'_child_get_states_for_country'))
		{
			return $active_module->_child_get_states_for_country($country_code);
		}	

		global $wpdb;
		$table_name = $wpdb->prefix . "eshop_states";
			 
		$sql =<<<EOF
	SELECT stateName as state_name, code as state_code
	FROM $table_name
	where list = %s
	order by state_name
EOF;
			 
		$sql  = $wpdb->prepare($sql, $country_code);
		$rows = $wpdb->get_results($sql,ARRAY_A);
			 
		$results = array();
		foreach ($rows as $row)
		{
// 			$name           = htmlentities($row['state_name'], NULL, 'UTF-8');
			$name           = $row['state_name'];
			$results[$name] = $row['state_code'];
		}
			 
		return $results;
	}
	
	
	function _get_package_class_by_name($name)
	{
		if (! $this->pack_classes[$name]) 
		{
			foreach ($this->opts['package_class_elements'] as $val)
			{
				$this->pack_classes[$val['name']] = array('length' => $val['length'],
														  'width'  => $val['width'],
														  'height' => $val['height']
														  );
			}
		}
		
		return $this->pack_classes[$name];
	}
	
	
	function _mergeXML(&$base, $add)
	{
		if ( count($add) != 0 )
		{
			$new = $base->addChild($add->getName());
		}
		else 
		{
			$new = $base->addChild($add->getName(), $add);
		}
		
		foreach ($add->attributes() as $a => $b)
		{
			$new->addAttribute($a, $b);
		}
		if ( count($add) != 0 )
		{
			foreach ($add->children() as $child)
			{
				$this->mergeXML($new, $child);
			}
		}
	}
}


/*
 * Kick off Module functionality
 */
if (is_admin())
{
	# REMEMBER: Ajax requests land here
	require_once(ESHOP_SHIPPING_EXTENSION_INCLUDES . '/admin.php');
	$USC_eShop_Shipping_Extension_Admin = new USC_eShop_Shipping_Extension_Admin();
}
else
{
	$USC_eShop_Shipping_Extension = new USC_eShop_Shipping_Extension();
	$eshop_opts = $USC_eShop_Shipping_Extension->get_eshop_options();

	if ( (! $_REQUEST['action'] || 
			$_REQUEST['action'] !== $USC_eShop_Shipping_Extension->domain . '-get-rates') && # Only override anything if it's not a get-rates ajax call 
		 sizeof($USC_eShop_Shipping_Extension->active_modules) &&                            # and there is an active shipping module
		 (int)$eshop_opts['shipping'] === 4)                                                 # and the user selected weight mode
	{
		
		require_once(ESHOP_SHIPPING_EXTENSION_INCLUDES . '/override_eshop_display_cart.php');
		require_once(ESHOP_SHIPPING_EXTENSION_INCLUDES . '/override_eshop_showform.php');
		require_once(ESHOP_SHIPPING_EXTENSION_INCLUDES . '/override_eshop_order_handle.php');
		
		$USC_override_eShop_showform     = new USC_override_eShop_showform();
		$USC_override_eShop_Display_Cart = new USC_override_eShop_Display_Cart();
		$USC_override_eShop_Order_Handle = new USC_override_eShop_Order_Handle();
		
		// Override eshop cart display
		function display_cart($shopcart,$change,$eshopcheckout,$pzone='',$shiparray='')
		{
			global $USC_override_eShop_Display_Cart;

			return $USC_override_eShop_Display_Cart->do_override($shopcart,$change,$eshopcheckout,$pzone,$shiparray);
		}
		
		
		// Override eshop showform
		function eshopShowform($first_name,$last_name,$company,$phone,$email,$address1,
							   $address2,$city,$state,$altstate,$zip,$country,$reference,$comments,$ship_name,
							   $ship_company,$ship_phone,$ship_address,$ship_city,$ship_postcode,$ship_state,$ship_altstate,$ship_country)
		{
			global $USC_override_eShop_showform;
			
			return $USC_override_eShop_showform->do_override($first_name,$last_name,$company,$phone,$email,$address1,
							   $address2,$city,$state,$altstate,$zip,$country,$reference,$comments,$ship_name,
							   $ship_company,$ship_phone,$ship_address,$ship_city,$ship_postcode,$ship_state,$ship_altstate,$ship_country);
		}
		
		
		// Override order handle (add shipping name to the order table)
		function orderhandle($usc_post,$checkid)
		{
			global $USC_override_eShop_Order_Handle;
			
			return $USC_override_eShop_Order_Handle->do_override($usc_post,$checkid);
		}
	}
}



/* End of file eshop-shipping-extension.php */
/* Location: eshop-shipping-extension/eshop-shipping-extension.php */
