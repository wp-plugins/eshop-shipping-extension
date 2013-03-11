<?php defined('ABSPATH') or die("No direct access allowed");
/**
 * @package   USC_eShop_Canada_Post
 * @desc      Manage eShop <-> Canada Post interface
 * @author    Vinny Alves (vinny@usestrict.net)
 * @copyright 2012
 */
class USC_eShop_Canada_Post extends USC_eShop_Shipping_Extension
{
	protected $my_options_name = 'canada-post-module';
	public  $module_name     = 'Canada Post';
	public  $options         = array();
	private $live_url        = 'https://soa-gw.canadapost.ca/rs/ship/price';
	private $test_url        = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';
	public $is_postal     = true; // Controls with which other modules this can be used. Canada Post/USPS/Correios are mutually exclusive.
	public $version;
	
	function __construct()
	{
		$this->version = '1.1F';
		add_filter('usc_carrier_service_list',array(&$this,'_get_all_service_names'),10,1);
	}
	
	function USC_eShop_Canada_Post()
	{
		$this->__construct();
	} 
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  get_options()
	 * @desc    Returns default/saved options for this shipping module
	 * @param   bool $force - to skip the cache, forcing the reload of the DB options
	 * @return  array
	 */
	function get_options($force = FALSE)
	{
		if ($this->options && $force === TRUE)
		{
			return $this->options;
		}
		
		$default        = array();
		$parent_optname = parent::get_options_name();
		
		$default[$parent_optname][$this->my_options_name]['customer_number']  = null;
		$default[$parent_optname][$this->my_options_name]['test']['username'] = null;
		$default[$parent_optname][$this->my_options_name]['test']['password'] = null;
		$default[$parent_optname][$this->my_options_name]['live']['username'] = null;
		$default[$parent_optname][$this->my_options_name]['live']['password'] = null;
		$default[$parent_optname][$this->my_options_name]['quote_type']       = 'counter';

		$options = get_option($parent_optname, $default);

		if ($options)
		{
			// Pass some parent options down to the kids
			$this->options               = $options[$this->my_options_name];
			$this->options['from_zip']   = $options['from_zip'];
			$this->options['debug_mode'] = $options['debug_mode'];
			$this->options['package_class'] = $options['package_class'];
			$this->options['in_store_pickup'] = $options['in_store_pickup'];
			$this->options['in_store_pickup_text'] = $options['in_store_pickup_text'];
		}
		else
		{
			$this->options = $default[$parent_optname][$this->my_options_name];
		}
		
		return $this->options;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  validate_input()
	 * @desc    validates module-specific fields
	 * @param   array $input
	 * @return  array validated $input
	 */
	function validate_input($input)
	{
		// Return untouched if this module isn't selected as active
		if ($input['third_party'] !== get_class($this))
		{
			return $input;
		}
		
		$this->do_recursive($input,'trim');
		
		
		foreach($input[$this->my_options_name] as $key => $val)
		{
			if (!is_array($val) && (! isset($val) || $val === ''))
			{
				if ($key === 'width' || $key === 'length' || $key === 'height')
				{
					if ($input['package_class'] !== 'global') continue;
					
					if ($input[$this->my_options_name]['unpackaged'] == 'false')
					{
						if (!$val)
						{
							add_settings_error($key,$key, sprintf(__('Canada Post: %s is required when Unpackaged = No!', $this->domain), ucwords(str_replace('_', ' ',$key))), 'error');
						}
						elseif (!is_numeric($val) || (int)$val > 999.9 || (int)$val < 0)
						{
							add_settings_error($key,$key, sprintf(__('Canada Post: %s has an invalid value. Must be between 0 and 999.9', $this->domain), ucwords(str_replace('_', ' ',$key))), 'error');
						}
					}
					
					continue;
				}
				else
				{
					add_settings_error($key,$key, sprintf(__('Canada Post: %s is a required value!', $this->domain), ucwords(str_replace('_', ' ',$key))), 'error');
				}
			}
			elseif (is_array($val)) // Handles test/live credentials
			{
				foreach ($val as $k => $v)
				{
					if (! isset($v) || $v === '')
					{
						add_settings_error($k,$k, sprintf(__('Canada Post:  %s %s is a required value!',$this->domain), ucwords($key), ucwords($k)), 'error');
					}
					elseif (! preg_match('/^[a-zA-Z0-9]+$/',$v))
					{
						add_settings_error($k,$k, sprintf(__('Canada Post: %s %s does not contain a valid string',$this->domain), ucwords($key), ucwords($k)), 'error');
					}
					
				}
			}
			
			if ($input['package_class'] == 'global' && ($key === 'height' || $key === 'width') && $val > $input[$this->my_options_name]['length'])
			{
				add_settings_error($key,$key, sprintf(__('Canada Post: %s cannot be larger than Length!', $this->domain), ucwords(str_replace('_', ' ',$key))), 'error');
			}
		}
		
		if (! preg_match('/^[0-9]+$/',$input[$this->my_options_name]['customer_number']))
		{
			add_settings_error('customer_number','customer_number', __('Canada Post: Customer Number must be a number!', $this->domain), 'error');
		}
		
		return $input;
		
	}
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  intro_paragraph()
	 * @desc    Returns the introductory paragraph for the module
	 * @return  string
	 */
	function intro_paragraph()
	{
		return __('<p>In order to use Canada Post API, you must first join the <a href="https://www.canadapost.ca/cpotools/apps/drc/home" target="_new"> '.
				  'Canada Post Developer Program</a>. It\'s free and you\'ll also get a VentureOne™ card which gives you some nice discounts on their services.' .
				  '<br /><br /><i>Note that it takes up to 24 hours for your registration to be processed ' .
				  'for production access, and the test mode will return dummy values.</i></p>',$this->domain);
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  admin_form_html()
	 * @desc    Returns the html that makes up the admin form fields
	 * @return  string
	 */
	function admin_form_html()
	{
		$opts = $this->get_options();
		$po   = parent::get_options_name();
		
		$customer_number = __('Customer Number', $this->domain);
		$test_uname      = __('Test Username', $this->domain);
		$test_pass       = __('Test Password', $this->domain);
		$live_uname      = __('Live Username', $this->domain);
		$live_pass       = __('Live Password', $this->domain);
		$length          = __('Length',$this->domain);
		$width           = __('Width',$this->domain);
		$height          = __('Height',$this->domain);
		$mailing_tube    = __('Mailing Tube',$this->domain);
		$unpackaged      = __('Unpackaged',$this->domain);
		
		$length_info       = __('Must always be the longest dimension.',$this->domain);
		$mailing_tube_info = __('A surcharge will be applied to mailing tubes that are cylindrical in shape. ' .
				'Cylinder shaped packages generate high processing costs due to their unique shape. '.
				'Customers are encouraged to use other non-cylinder shaped containers (e.g. triangular shape) to avoid the surcharge.',$this->domain);
		
		$unpackaged_info   = __('Yes indicates that the parcel will be unpackaged (e.g. tires).',$this->domain);
		
		
		$dimensions_text = __('<p class="cp_dimension">Although Canada Post does not require dimensions for their API calls (no errors occur if not passed), ' .
				' the size of the package may influence the price of the quote (e.g., if the volumetric weight is larger than the actual weight). '.
				' We recommend entering the dimensions of ' .
				' the largest box according to your products or adjusting product prices to absorb any differences. '.
				'<a href="http://www.canadapost.ca/tools/pg/manual/PGabcmail_web_business-e.asp#1378832" target="_new">Read more here.</a></p>'.
				'<p class="cp_dimension"><em>Note: All dimensions are in centimeters.</em></p>', $this->domain);
		
		$yes = __('Yes', $this->domain);
		$no  = __('No', $this->domain);
		
		$yes_no_array = array($no => 'false', $yes => 'true');
		foreach ($yes_no_array as $key => $val)
		{
			$mt_options         .= '<option value="'.$val.'" '.selected($val,$opts['mailing_tube'],false).'>'.$key.'</option>';
			$unpackaged_options .= '<option value="'.$val.'" '.selected($val,$opts['unpackaged'],false).'>'.$key.'</option>';
		}
		
		$quote_type = __('Quote Type',$this->domain);
		$quote_type_array = array(__('Counter',$this->domain) => 'counter', __('Commercial',$this->domain) => 'commercial');
		foreach ($quote_type_array as $key => $val)
		{
			$quote_type_options .= '<option value="'.$val.'" '.selected($val,$opts['quote_type'],false).'>'.$key.'</option>';
		} 
		$quote_type_info = __('Commercial rates are lower than Counter rates. Choose "Counter" if you are not printing your own labels.',$this->domain);
		
		$packaging_options_text = __('Packaging Options',$this->domain);
		
		return <<<EOF
			<table>
				<tr>
					<th>$customer_number:</th>
					<td><input type="text" name="{$po}[$this->my_options_name][customer_number]" value="{$opts[customer_number]}" /></td>
				</tr>
				<tr>
					<th>$test_uname:</th>
					<td><input type="text" name="{$po}[$this->my_options_name][test][username]" value="{$opts[test][username]}" /></td>
				</tr>
				<tr>
					<th>$test_pass:</th>
					<td><input type="text" name="{$po}[$this->my_options_name][test][password]" value="{$opts[test][password]}" /></td>
				</tr>
				<tr>
					<th>$live_uname:</th>
					<td><input type="text" name="{$po}[$this->my_options_name][live][username]" value="{$opts[live][username]}" /></td>
				</tr>
				<tr>
					<th>$live_pass:</th>
					<td><input type="text" name="{$po}[$this->my_options_name][live][password]" value="{$opts[live][password]}" /></td>
				</tr>
			</table>
		<hr />
		<h4>{$packaging_options_text}</h4>
		{$dimensions_text}
		<table>
			<tr class="cp_dimension">
				<th width="100">$length:</th>
				<td><input type="text" name="{$po}[$this->my_options_name][length]" value="{$opts[length]}" size="5" maxlength="5"/></td>
				<td><span id="length_info"><small>{$length_info}</small></span></td>
			</tr>
			<tr class="cp_dimension">
				<th>$width:</th>
				<td><input type="text" name="{$po}[$this->my_options_name][width]" value="{$opts[width]}" size="5" maxlength="5"/></td>
				<td><span id="width_info"><small>{$width_info}</small></span></td>
			</tr>
			<tr class="cp_dimension">
				<th>$height:</th>
				<td><input type="text" name="{$po}[$this->my_options_name][height]" value="{$opts[height]}" size="5" maxlength="5"/></td>
				<td><span id="height_info"><small>{$height_info}</small></span></td>
			</tr>
			<tr>
				<th>$mailing_tube:</th>
				<td>
					<select name="{$po}[$this->my_options_name][mailing_tube]">
						$mt_options
					</select>
				</td>
				<td><span id="mailing_tube_info"><small>{$mailing_tube_info}</small></span></td>
			</tr>
			<tr>
				<th>$unpackaged:</th>
				<td>
					<select name="{$po}[$this->my_options_name][unpackaged]">
						$unpackaged_options
					</select>
				</td>
				<td><span id="unpackaged_info"><small>{$unpackaged_info}</small></span></td>
			</tr>
			<tr>
				<th>$quote_type:</th>
				<td>
					<select name="{$po}[$this->my_options_name][quote_type]">
						$quote_type_options
					</select>
				</td>
				<td><span id="quote_type_info"><small>{$quote_type_info}</small></span></td>
			</tr>
			
		</table>
		
		
EOF;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  get_rates()
	 * @desc    Wrapper around actual get rates methods.
	 * @param   $fields
	 */
	function get_rates($input)
	{
		$fields = $this->_massage_params($input);

		if ($fields['success'] === false)
		{
			return $fields;
		}
		
		// Try to use the CURL method if available
		if (function_exists('curl_init'))
		{
			$out = $this->_get_rates_curl($fields['data']);
		}
		else
		{
			$out = $this->_get_rates_sock($fields['data']);
		}
		
		return $out;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  add_shipping_fields()
	 * @desc    called by apply_filter()->parent::add_shipping_fields()
	 * @param   string $form_html
	 * @param   array $reqdarray
	 * @return  string massaged $form_html
	 */
	function add_shipping_fields($form_html,$reqdarray)
	{
		$out .= '<fieldset class="eshop fld0"><legend id="shiplegend">'. __('Please Choose Shipping','eshop').eshop_checkreqd($reqdarray,'shipping').'</legend>';
		$out .= '<div id="usc_shipping_error_msgs"></div><div id="usc_shipping_options"></div><div id="usc_shipping_details"></div>';
		$out .= '<a id="usc_update_shipping_options" href="#">' . __('Update Shipping Options', $this->domain). '</a>';
		$out .= '<img style="display:none" id="usc_shipping_throbber" class="usc_shipping_throbber" src="' . plugins_url( ESHOP_SHIPPING_EXTENSION_DOMAIN . '/includes/images/arrows-throbber.gif') . '" />';
		$out .= "</fieldset>";
		
		return $form_html . $out;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _get_rates_sock()
	 * @desc    Sends a request to Canada Post using sockets
	 * @param   array $input
	 */
	function _get_rates_sock($input)
	{
		$opts = $this->get_options();
		$mode = $this->get_mode();
		
		if ($mode === 'testing')
		{
			$url = $this->test_url;
			$uname = $opts['test']['username'];
			$pass  = $opts['test']['password'];
		}
		else
		{
			$url = $this->live_url;
			$uname = $opts['live']['username'];
			$pass  = $opts['live']['password'];
		}
		
		$port    = 443;
		$timeout = 10;
		$cert    = ESHOP_SHIPPING_EXTENSION_THIRD_PARTY . '/cert/cacert.pem';
		$req     = $this->_make_xml_request($input);
		
		if ($req['success'] === false)
		{
			return $req;
		}
		
		$body = $req['data'];
		
		$lang = (defined('WPLANG') && strtolower(substr(WPLANG,0,3)) == 'fr-') ? 'fr-CA' : 'en-CA';
		$opts = array(
				'http' => array(
						'method' => "POST",
						'header' => "Authorization: Basic " . base64_encode($uname . ':' . $pass) . "\r\n" .
									"Content-Type: application/vnd.cpc.ship.rate+xml\r\n" .
									"Content-Length: " . strlen($body) . "\r\n",
									"Accept-language: " . $lang . "\r\n",
						'content' => $body
				)
		);
		
		$context = stream_context_create($opts);
		stream_context_set_option($context, 'tcp', 'local_cert', $cert);
		$response = file_get_contents($url,false,$context);
		
		return $this->_parse_xml_response($response);
	}
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _get_rates_curl()
	 * @desc    Sends a request to Canada Post using CURL
	 * @param   array $input
	 */
	function _get_rates_curl($input)
	{
		$opts = $this->get_options();
		$mode = $this->get_mode();

		if ($mode === 'testing')
		{
			$url = $this->test_url;
			$uname = $opts['test']['username'];
			$pass  = $opts['test']['password'];
		}
		else
		{
			$url = $this->live_url;
			$uname = $opts['live']['username'];
			$pass  = $opts['live']['password'];
		}
		
		$req = $this->_make_xml_request($input);
		
		if ($req['success'] === false)
		{
			return $req;
		}
		
		$body = $req['data'];
		
		$curl = curl_init($url); // Create REST Request
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_CAINFO, ESHOP_SHIPPING_EXTENSION_THIRD_PARTY . '/cert/cacert.pem'); // Signer Certificate in PEM format
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $uname . ':' . $pass);
		
		$headers   = array('Content-Type: application/vnd.cpc.ship.rate+xml', 'Accept: application/vnd.cpc.ship.rate+xml');
		$lang      = (defined('WPLANG') && strtolower(substr(WPLANG,0,2)) == 'fr') ? 'fr-CA' : 'en-CA';
		$headers[] = 'Accept-language: ' . $lang;
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		if ($mode === 'testing');
		{
			curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		}

		$curl_response = curl_exec($curl); // Execute REST Request
		
		return $this->_parse_xml_response($curl_response);
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _make_xml_request()
	 * @desc    Makes the necessary XML to send to Canada Post
	 * @param   array $input
	 * @return  string request xml
	 */
	private function _make_xml_request($input)
	{
		global $blog_id;
		
		$opts = $this->get_options();
		
		// Get total weight from cart session, as jQuery was not always passing the right value
		$total_weight = $_SESSION['eshop_totalweight'.$blog_id]['totalweight'];
		
		$from_postal_code = str_replace(' ','',strtoupper($input['from_zip']));
		$conv             = $this->convert_to_kilos($total_weight);
		$out              = array('success' => true);
		$to_zip           = str_replace(' ','',strtoupper($input['zip']));
		$xml              = new SimpleXMLElement('<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate" />');
		
		
		if ($conv['success'] === false)	return $conv;

		// Handle quote_type/customer_number options (no CN if QT is COUNTER)
		if ($opts['quote_type'] && $opts['quote_type'] === 'counter')
		{
			$xml->addChild('quote-type', $opts['quote_type']);
		}
		else
		{
			$xml->addChild('customer-number', $opts['customer_number']);
		}
		
		$xml->addChild('parcel-characteristics')->addChild('weight',number_format($conv['data'],3));
		
		if (! $opts['package_class']) $opts['package_class'] = '';
		
		switch($opts['package_class'])
		{
			case 'global':
				if ($opts['length'] && $opts['width'] && $opts['height'])
				{
					$dim['width']  = $opts['width'];
					$dim['height'] = $opts['height'];
					$dim['length'] = $opts['length'];
				}
				break;
			case 'product':
				
				$prod_avg = array();
				foreach ($_SESSION['eshopcart'.$blog_id] as $key => $val)
				{
					// Each key is a product
					$post_id = $val['postid'];
					$qty     = $val['qty']; 
					
					$is_free = apply_filters('usc_ese_is_free_class', false, $post_id);
					
					if ($is_free === true) continue;
					
					$prod_meta = maybe_unserialize(get_post_meta($post_id,'_eshop_product', TRUE));
					
					if (! $prod_meta['sel_package_class'])
					{
						// If at least one product doesn't have its own dimensions,
						// fall back to global dimensions
						if ($opts['length'] && $opts['width'] && $opts['height'])
						{
							$dim['width']  = $opts['width'];
							$dim['height'] = $opts['height'];
							$dim['length'] = $opts['length'];
						}
						
						break;
					}
					else 
					{
						// Get pack class and add up the dimensions times qty
						$pack_class = $this->get_package_class_by_name($prod_meta['sel_package_class']);
						$prod_avg[] = pow($pack_class['width'] * $pack_class['length'] * $pack_class['height'], 1/3);
						$num_items += $qty;
					}
				}
				
				if (count($prod_avg))
				{
					$sum = 0;
					foreach ($prod_avg as $pa)
					{
						$sum += $pa; 
					}
					
					$avg = $sum / count($prod_avg);
					$dim = $this->_make_bundle($avg,$num_items);
				}
					
				break;
			case 'product_option':
				
				$prod_avg = array();
				foreach ($_SESSION['eshopcart'.$blog_id] as $key => $val)
				{
					// Each key is a product
					$post_id = $val['postid'];
					$qty     = $val['qty'];
				
					$is_free = apply_filters('usc_ese_is_free_class', false, $post_id, $val['option']);
					
					if ($is_free === true) continue;

					$prod_meta = maybe_unserialize(get_post_meta($post_id,'_eshop_product', TRUE));
				
					if (! $prod_meta['products'][$val['option']]['sel_package_class'])
					{
						// If at least one product doesn't have its own dimensions,
						// fall back to global dimensions
						if ($opts['length'] && $opts['width'] && $opts['height'])
						{
							$dim['width']  = $opts['width'];
							$dim['height'] = $opts['height'];
							$dim['length'] = $opts['length'];
						}
				
						break;
					}
					else
					{
						// Get pack class and add up the dimensions times qty
						$pack_class = $this->get_package_class_by_name($prod_meta['products'][$val['option']]['sel_package_class']);
						$prod_avg[] = pow($pack_class['width'] * $pack_class['length'] * $pack_class['height'], 1/3);
						$num_items += $qty;
					}
				}
				
				if (count($prod_avg))
				{
					// Do yet another average to make sure everything fits snugly in the
					// end cube.
					$sum = 0;
					foreach ($prod_avg as $pa)
					{
						$sum += $pa; 
					}
					
					$avg = $sum / count($prod_avg);
					$dim = $this->_make_bundle($avg,$num_items);
				}
				break;
			default:
				break;
		}
		
		if ($dim)
		{
			$xml->{'parcel-characteristics'}->addChild('dimensions');
			$xml->{'parcel-characteristics'}->dimensions->addChild('length',$dim['length']);
			$xml->{'parcel-characteristics'}->dimensions->addChild('width', $dim['width']);
			$xml->{'parcel-characteristics'}->dimensions->addChild('height',$dim['height']);
		}
		
		
		if ($opts['unpackaged'] && $opts['unpackaged'] == 'true')
		{
			$xml->{'parcel-characteristics'}->addChild('unpackaged',$opts['unpackaged']);
		}
		
		$xml->addChild('origin-postal-code',$from_postal_code);

		$xml->addChild('destination');
		switch ($input['country'])
		{
			case 'CA':
				$xml->destination->addChild('domestic')->addChild('postal-code',$to_zip);
				break;
			case 'US':
				$xml->destination->addChild('united-states')->addChild('zip-code',$to_zip);
				break;
			default:
				$xml->destination->addChild('international')->addChild('country-code',$input['country']);
		}
		
		
		if ($this->debug_mode())
		{
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput       = true;
			$dom->loadXML(preg_replace('|<customer-number>[^<]+</customer-number>|', '<customer-number>***REMOVED***</customer-number>', $xml->asXML()));
			$dom->save($this->debug_request_file);
		}

		$out['data'] = $xml->asXML();
		return $out;
	}

	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _make_bundle()
	 * @desc    Simulates the packing of the objects, given average dimensions
	 * @param   $average_side, $num_items
	 * @return  array $total_dimensions
	 */
	private function _make_bundle($avg,$num_items)
	{
		$base  = array('row' => 1, 'col' => 1, 'lev' => 1);
		$count = 0;
		
		while ($count < $num_items)
		{
			$capacity = $base['row'] * $base['col'] * $base['lev'];
		
			$sorted = $base;
		
			arsort($sorted);
			$highest_dim = array_shift($sorted);
// 			$cur_width = $highest_dim * $avg;
// 			$cur_girth = $highest_dim + (2 * array_shift($sorted) + 2 * array_shift($sorted));
		
// 			if ( $cur_width >= $max_width ||
// 				 $cur_girth >= $max_girth)
// 			{
// 				break;
// 			}
		
			if ($count == $capacity)
			{
				if ($base['lev'] == $base['row'] &&
						$base['row'] == $base['col'])
				{
					$base['col']++;
				}
				elseif ($base['row'] < $base['col'])
				{
					$base['row']++;
				}
				elseif ($base['lev'] < $base['row'])
				{
					$base['lev']++;
				}
			}
		
			$count++;
		}
		
		$dim = array();
		arsort($base);
		
		$dim['length'] = number_format((array_shift($base) * $avg),1);
		$dim['width']  = number_format((array_shift($base) * $avg),1);
		$dim['height'] = number_format((array_shift($base) * $avg),1);
		
		return $dim;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _parse_xml_response()
	 * @desc    Parses the XML returned by Canada Post
	 * @param   string $response XML
	 * @return  array parsed data
	 */
	private function _parse_xml_response($response)
	{
		global $blog_id;
		
		libxml_use_internal_errors(true);
		$out = array(success => false);
		$xml = simplexml_load_string('<root>' . preg_replace('/<\?xml.*\?>/','',$response) . '</root>');
		$opts = $this->get_options();
		
		if (!$xml) 
		{
			$out['success'] = false;
			$out['msgs'][] = __('Failed loading XML', $this->domain) . $curl_response . "\n"; 	
			
			foreach(libxml_get_errors() as $error) {
				$out['msgs'][] = $error->message;
			}
			
			return $out;	
		}


		if ($this->debug_mode())
		{
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput       = true;
			$dom->loadXML($xml->asXML());
			$dom->save($this->debug_response_file);
		}
		
		
		$service_info = array();
		
		// Make the call to Canada Post
		if ($xml->{'price-quotes'} ) 
		{
			$priceQuotes = $xml->{'price-quotes'}->children('http://www.canadapost.ca/ws/ship/rate');
			if ( $priceQuotes->{'price-quote'} ) 
			{
				foreach ( $priceQuotes as $priceQuote ) 
				{
					// Need to cast as string or we get objects back
					$service_name = "Canada Post - " . (string)$priceQuote->{'service-name'};
					
					$due = (string)$priceQuote->{'price-details'}->{'due'};
					
					// Adjust automation discount. People aren't getting that discount at the post office
					$adjustment = 0;
					if (isset($opts['quote_type']) && $opts['quote_type'] === 'commercial')
					{
						foreach  ($priceQuote->{'price-details'}->adjustments->children() as $aj)
						{
							if ((string)$aj->{'adjustment-code'} == 'AUTDISC')
							{
								$adjustment = (float)$aj->{'adjustment-cost'} * -1;
							}
						}
					}
					
					$price = $this->convert_currency('CAD', number_format($due + $adjustment,2,'.',','));
					$service_info[$service_name]['price'] = $price;
					
					$details = array();
					foreach ($priceQuote->{'service-standard'}->children() as $det)
					{
						$name = $det->getName();
						
						if ($name == 'am-delivery' || $name == 'expected-transit-time') continue;
						
						$details[$name] = (string)$det;
						$service_info[$service_name]['details'] = $details;
					}
				}
			}
		}
		
		if ($xml->{'messages'} ) 
		{
			$messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');
			foreach ( $messages as $message ) 
			{
				$out['msgs'][] = (string)$message->description;
			}
		}
		
	    // Save values in session to keep visitor from tampering
        // with the prices
        if (count($service_info))
        {
            $out['data'] = $service_info;
            $_SESSION['usc_3rd_party_shipping'.$blog_id] = (array)$_SESSION['usc_3rd_party_shipping'.$blog_id] + $service_info;
        }
        
        if (count($out['data']) == 0)
        {
            if (count($out['msgs']) == 0)
            {
                $out['msgs'][] = __('No shipping plans were found for your options!',$this->domain);
            }
        }
        else
        {
            $out['success'] = true;
        }
        
        return $out;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _massage_params()
	 * @desc    Validates and sanitizes input params
	 * @param   $input
	 * @return  array massaged data
	 */
	private function _massage_params($input)
	{
		$this->do_recursive($input,'trim'); // Parent function
		
		// Only US and Canada require city, state, and zip information 
		if ($input['country'] ===  'US' || $input['country'] === 'CA')
		{
			$req_fields = array('city', 'state', 'zip', 'country', 'weight');
		}
		else
		{
			$req_fields = array('country', 'weight');
		} 
		
		$opts       = $this->get_options();
		$eshop_opts = $this->get_eshop_options();

		$input['from_zip']    = preg_replace('/\s+/','',$opts['from_zip']); // remove inner spaces from Canadian postal codes
		$input['weight_unit'] = $eshop_opts['weight_unit'];
		
		$out['success'] = true;
		
		foreach ($req_fields as $key)
		{
			if (! $input[$key])
			{
				$out['success'] = false;
				$out['msgs'][] = sprintf(__('Required field \'%s\' is missing!', $this->domain),$key);
			}
		}
		
		if ($out['success'] === false)
		{
			return $out;
		}
		
		$out['data'] = $input;
		
		return $out;
	}
		
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  get_js_msgs()
	 * @desc    Localized JavaScript messages
	 * @return  array
	 */
	function get_js_msgs()
	{
		$msgs = array('missing_zip'    => __('Zip/Postal code is required for US/Canada postage', $this->domain),
				      'invalid_weight' => __('Invalid weight', $this->domain),
					  'expected-delivery-date' => __('Expected Delivery Date', $this->domain),
				      'guaranteed-delivery'    => __('Guaranteed Delivery', $this->domain),
					  'true'				   => __('Yes', $this->domain),
				      'false'				   => __('No', $this->domain));
		
		return $msgs;
	}
	
	
	/**
	 * @package USC_eShop_Canada_Post
	 * @method  _get_all_service_names()
	 * @desc    Returns a list of all service names
	 * @return  array
	 */
	public function _get_all_service_names($list=array())
	{
		$list['Canada Post'] = array(
				'Expedited Parcel',
				'Expedited Parcel USA',
				'International Parcel Surface',
				'Priority',
				"Priority Worldwide envelope INT'L",
				'Priority Worldwide envelope USA',
				"Priority Worldwide pak INT'L",
				'Priority Worldwide pak USA',
				'Regular Parcel',
				'Xpresspost',
				"Xpresspost International",
				'Xpresspost USA',
		);
		
		return $list;
	}
	
}


/* End of file USC_eShop_Canada_Post.php */
/* Location: eshop-shipping-extension/includes/modules/canada-post-module/USC_eShop_Canada_Post.php */