<?php defined('ABSPATH') or die("No direct access allowed");
/**
 * @package   USC_eShop_Canada_Post
 * @desc      Manage eShop <-> Canada Post interface
 * @author    Vinny Alves (vinny@usestrict.net)
 * @copyright 2012
 */
class USC_eShop_Canada_Post extends USC_eShop_Shipping_Extension
{
	private $my_options_name = 'canada-post-module';
	public $module_name      = 'Canada Post';
	public $options          = array();
	private $live_url        = 'https://soa-gw.canadapost.ca/rs/ship/price';
	private $test_url        = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';
	
	function __construct()
	{
		// NOOP
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
		if ($this->options && !$force)
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

		$options = get_option($parent_optname, $default);

		if ($options)
		{
			$this->options             = $options[$this->my_options_name];
			$this->options['from_zip'] = $options['from_zip'];
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
				add_settings_error($key,$key, sprintf(__('%s is a required value!', $this->domain), ucwords(str_replace('_', ' ',$key))), 'error');
			}
			elseif (is_array($val)) // Handles test/live credentials
			{
				foreach ($val as $k => $v)
				{
					if (! isset($v) || $v === '')
					{
						add_settings_error($k,$k, sprintf(__('%s %s is a required value!',$this->domain), ucwords($key), ucwords($k)), 'error');
					}
					elseif (! preg_match('/^[a-zA-Z0-9]+$/',$v))
					{
						add_settings_error($k,$k, sprintf(__('%s %s does not contain a valid string',$this->domain), ucwords($key), ucwords($k)), 'error');
					}
					
				}
			}
		}
		
		if (! preg_match('/^[0-9]+$/',$input[$this->my_options_name]['customer_number']))
		{
			add_settings_error('customer_number','customer_number', __('Customer Number must be a number!', $this->domain), 'error');
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
	 * @method  form_html()
	 * @desc    Returns the html that makes up the form fields
	 * @return  string
	 */
	function form_html()
	{
		$opts = $this->get_options();
		$po   = parent::get_options_name();
		
		$customer_number = __('Customer Number', $this->domain);
		$test_uname      = __('Test Username', $this->domain);
		$test_pass       = __('Test Password', $this->domain);
		$live_uname      = __('Live Username', $this->domain);
		$live_pass       = __('Live Password', $this->domain);
		
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
		$opts = $this->get_options();

		$mailed_by        = $opts['customer_number'];
		$from_postal_code = str_replace(' ','',strtoupper($input['from_zip']));
		$conv             = $this->convert_to_kilos($input['weight']);
		$out              = array('success' => true);
		$to_zip           = str_replace(' ','',strtoupper($input['zip']));
		
		if ($conv['success'] === false)
		{
			return $conv;
		}
		
		$weight = $conv['data'];

		switch ($input['country']) 
		{
			case 'CA':
				$destination = "<domestic><postal-code>{$to_zip}</postal-code></domestic>";
				break;
			case 'US':
				$destination = "<united-states><zip-code>{$to_zip}</zip-code></united-states>";
				break;
			default:
				$destination = "<international><country-code>{$input[country]}</country-code></international>";
		}
		
		$out['data'] =<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
			<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate">
			  <customer-number>{$mailed_by}</customer-number>
			  <parcel-characteristics>
			    <weight>{$weight}</weight>
			  </parcel-characteristics>
			  <origin-postal-code>{$from_postal_code}</origin-postal-code>
			  <destination>
					{$destination}
			  </destination>
			</mailing-scenario>
XML;
			  
		return $out;
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
		
		if (!$xml) 
		{
			$out['success'] = false;
			$out['msgs'][] = __('Failed loading XML', $this->domain) . $curl_response . "\n"; 	
			
			foreach(libxml_get_errors() as $error) {
				$out['msgs'][] = $error->message;
			}
			
			return $out;	
		} 
		
		$service_price = array();
		// Make the call to Canada Post
		if ($xml->{'price-quotes'} ) 
		{
			$priceQuotes = $xml->{'price-quotes'}->children('http://www.canadapost.ca/ws/ship/rate');
			if ( $priceQuotes->{'price-quote'} ) 
			{
				foreach ( $priceQuotes as $priceQuote ) 
				{
					// Need to cast as string or we get objects back
					$service_name = (string)$priceQuote->{'service-name'};
					$out['data'][$service_name]['price'] = (string)$priceQuote->{'price-details'}->{'due'};
					
					$service_price[$service_name] = (string)$priceQuote->{'price-details'}->{'due'};
					
					$details = array();
					foreach ($priceQuote->{'service-standard'}->children() as $det)
					{
						$name = $det->getName();
						
						if ($name == 'am-delivery' || $name == 'expected-transit-time') continue;
						
						$details[$name] = (string)$det;

						$out['data'][$service_name]['details'] = $details;
					}
				}
			}
		}
		
		// Save values in session to keep visitor from tampering
		// with the prices
		$_SESSION['usc_3rd_party_shipping'.$blog_id] = $service_price;
		
		if ($xml->{'messages'} ) 
		{
			$messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');
			foreach ( $messages as $message ) 
			{
				$out['msgs'][] = (string)$message->description;
			}
		}
		
		if (count($out['data']) == 0)
		{
			if (count($out['msgs']) == 0)
			{
				$out['msgs'][] = __('No shipping plans were found for your options!', $this->domain);
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
	
}


/* End of file eShop_Canada_Post.php */
/* Location: eshop-shipping-extension/includes/third-party-services/eShop_Canada_Post.php */