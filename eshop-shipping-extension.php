<?php defined('ABSPATH') or die("No direct access allowed");
/*
* Plugin Name:   eShop Shipping Extension
* Plugin URI:	 http://usestrict.net/2012/06/eshop-shipping-extension-for-wordpress-canada-post/
* Description:   eShop extension to use third-party shipping services. Currently supports Canada Post.
* Version:       1.1.6
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
* Copyright (C) 2012 www.usestrict.net, released under the GNU General Public License.
*/
define('ESHOP_SHIPPING_EXTENSION_ABSPATH', plugin_dir_path(__FILE__));
define('ESHOP_SHIPPING_EXTENSION_INCLUDES', ESHOP_SHIPPING_EXTENSION_ABSPATH . '/includes');
define('ESHOP_SHIPPING_EXTENSION_THIRD_PARTY', ESHOP_SHIPPING_EXTENSION_INCLUDES . '/third-party');
define('ESHOP_SHIPPING_EXTENSION_VERSION', '1.1.6');
define('ESHOP_SHIPPING_EXTENSION_DOMAIN', 'eshop-shipping-extension');
define('ESHOP_SHIPPING_EXTENSION_DOMAIN_JS_URL',plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/js'));
define('ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL',plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/css'));


class USC_eShop_Shipping_Extension
{
	var $options;
	var $eshop_options;
	var $eshop_is_active;
	var $eshop_is_installed;

	var $domain        = ESHOP_SHIPPING_EXTENSION_DOMAIN;
	var $options_name  = 'eshop-shipping-extension';
	var $active_module = 'none';
	var $css_filename  = 'USC_eShop_Shipping.css';
	
	
	function __construct()
	{
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); # needed for is plugin active
		
		$this->eshop_is_installed = file_exists(ABSPATH . 'wp-content/plugins/eshop/eshop.php') ? TRUE : FALSE;
		$this->eshop_is_active    = is_plugin_active('eshop/eshop.php') ? TRUE : FALSE;
		$this->eshop_options      = $this->get_eshop_options();
		$mod                      = $this->init_active_module();
		
		// Add the filter to update the cart form with the shipping fields
		add_filter('usc_add_shipping_fields', array(&$this,'add_shipping_fields'),10,2);

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
			// Add ajax capability for logged-in people, even though it's not really
			// the admin interface.
			add_action('wp_ajax_' . $this->domain . '-get-rates', array(&$this,'get_rates'));
		}
		else
		{
			// Do nothing if the user has not selected Method 4
			// or if no module is active
			if ((int)$this->eshop_options['shipping'] !== 4)
			{
				return;
			}
			elseif (!is_object($mod))
			{
				return;
			}

			require_once( ABSPATH . 'wp-includes/pluggable.php'); // imports is_user_logged_in()
			
			// embed the javascript file that makes the AJAX request
			wp_enqueue_script( $this->domain . '-get-rates', ESHOP_SHIPPING_EXTENSION_DOMAIN_JS_URL . '/' . get_class($mod) . '.js', array( 'jquery' ),  ESHOP_SHIPPING_EXTENSION_VERSION);
			
			// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
			wp_localize_script( $this->domain . '-get-rates', 'eShopShippingModule', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
																							'ajaxaction' => $this->domain . '-get-rates',
																							'method'  => (is_user_logged_in()?'GET':'POST'),
																							'lang'    => $mod->get_js_msgs() ) );

			// Add CSS file
			wp_enqueue_style( $this->domain . '-style', ESHOP_SHIPPING_EXTENSION_DOMAIN_CSS_URL . '/' . $this->css_filename, 
							  null,  ESHOP_SHIPPING_EXTENSION_VERSION , 'all' );
			
		}
		
	}

		
	/**
	 * Method: get_eshop_options()
	 * Description: Gets/caches eshop options
	 * @param bool $force
	 */
	function get_eshop_options($force=false)
	{
		if (! $this->eshop_options || $force === true)
		{
			$this->eshop_options = get_option('eshop_plugin_settings');
		}
		
		return $this->eshop_options;
	}
	
	/**
	 * Method: init_active_module()
	 * Description: Sets and returns the shipping module currently selected
	 */
	function init_active_module()
	{
		$opts   = $this->get_options();
		$active = $opts['third_party'];
		$path   = ESHOP_SHIPPING_EXTENSION_THIRD_PARTY;
		
		if (isset($active) && $active !== 'none')
		{
			$file = $path . '/' . $active . '.php';
			if (file_exists($file))
			{
				require_once($file);
				$this->active_module = new $active(); 
			}
		}

		return $this->active_module;
	}
	
	
	/**
	 * Method: get_rates()
	 * Description: Wrapper for the active module's get_rates() method
	 */
	function get_rates()
	{
		$mod = $this->active_module;
		header("Content-type: application/json");
		
		if (! is_object($mod))
		{
			$out['success'] = false;
			$out['msgs'][] = __('No shipping module is currently active!');
			
			echo json_encode($out);
			exit; // WP requirement for ajax-related methods
		}
		
		$out = $mod->get_rates($_REQUEST);

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
		$default[$this->options_name]['third_party'] = 'none';
// 		$default[$this->options_name]['mode']        = 'test';
	
		$this->options = get_option($this->options_name, $default);
	
		// Copy the from_zip value into the active module's opts array 
		$this->options[$this->active_module]['from_zip'] = $this->options['from_zip'];
		
		return $this->options;
	}
	
	
	/**
	 * Method: add_shipping_fields()
	 * Description: calls child module's add_shipping_fields()
	 * @param string $form_html
	 * @param array $reqdarray
	 */
	function add_shipping_fields($form_html,$reqdarray)
	{
		if (is_object($this->active_module))
		{
			return $this->active_module->add_shipping_fields($form_html,$reqdarray);
		}
		
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
				$this->do_recursive($v,$func);
			}
		}
		elseif (is_callable($func))
		{
				$func($var);
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
		$dir_str     = $plugins_dir . $this->domain . '-*/includes/*/*';
		$files       = glob($dir_str);

		foreach ($files as $file)
		{
			// Get file names
			preg_match(',/([^/]+)/(includes/[^/]+/[^\.]+\.(js|php))$,', $file, $matches);
			
			if ($matches[0])
			{
				$module = $matches[1] . '/' .  $matches[1] . '.php';
				
				// Don't do anything if the module isn't active!
				if (! is_plugin_active($module)) continue;
				
				$filepath = $matches[2];
				
				if (! @copy($file, ESHOP_SHIPPING_EXTENSION_ABSPATH . $filepath))
				{
					$e = error_get_last();
					$this->set_notice(__('Failed to install module file: ', $this->domain) . $filepath . sprintf(' (%s)', $e['message']), true);
				}
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
		 is_object($USC_eShop_Shipping_Extension->active_module) &&    # and there is an active shipping module
		 (int)$eshop_opts['shipping'] === 4)                         # and the user selected weight mode
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
		function orderhandle($_POST,$checkid)
		{
			global $USC_override_eShop_Order_Handle;
			
			return $USC_override_eShop_Order_Handle->do_override($_POST,$checkid);
		}
	}
}



/* End of file eshop-shipping-extension.php */
/* Location: eshop-shipping-extension/eshop-shipping-extension.php */