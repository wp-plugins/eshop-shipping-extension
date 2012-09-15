jQuery(document).ready(function($){
	
	window.eShopShippingModule = window.eShopShippingModule || {};
	
	/**
	 *  @package eShopShippingModule
	 *  @var     object details
	 *  @desc    Holds shipping details after ajax call. Accessed by Shipping option drop-down 
	 */
	eShopShippingModule.details = {};
	
	/**
	 * @package   eShopShippingModule
	 * @function  call_get_rates()
	 * @desc      Wrapper for get_rates
	 * @param     bool from_button 
	 */
	eShopShippingModule.call_get_rates = function(from_button){
		
		var validated_fields,
			country,
			fields = {address1 : $("#address1").val(), 
					  address2 : $("#address2").val(), 
					  city     : $("#city").val(), 
					  altstate : $("#altstate").val(), 
					  state    : $("#state").val(), 
					  zip      : $("#zip").val(), 
					  weight   : $("#cart_weight").val(),
					  country  : $("#country").val(),
					  shipcountry  : $("#shipcountry").val(),
					  ship_address : $("#ship_address").val(), 
					  ship_city    : $("#ship_city").val(), 
					  ship_state   : $("#ship_state").val(), 
					  ship_altstate : $("#ship_altstate").val(), 
					  ship_postcode : $("#ship_postcode").val(),
					  amount        : $("input[name=amount]").val()
					  };
		
		for (i in fields) {
			fields[i] = $.trim(fields[i]); 
		}
		
		// Strip any spaces inside the zip codes
		fields.zip           = fields.zip.replace(/\s+/g, '');
		fields.ship_postcode = fields.ship_postcode.replace(/\s+/g,'');
		
		validated_fields = eShopShippingModule.validate_fields(fields);
		
		if (eShopShippingModule.has_errors(validated_fields) === true) {
			return false;
		}
		
		// Change link verbiage
		$("#usc_update_shipping_options").text(eShopShippingModule.lang['update-shipping-options']);
		
		// Make the call
		eShopShippingModule.get_rates(validated_fields.data);
		
	};
	
	/**
	 * @package   eShopShippingModule
	 * @function  get_rates()
	 * @desc      Triggers the ajax call to get the rates
	 * @param     object fields 
	 */
	eShopShippingModule.get_rates = function(fields) {
		
		fields.action = eShopShippingModule.ajaxaction;
		$("#usc_shipping_throbber").show();
		
		$.ajax({
			type    : eShopShippingModule.method,
			url     : eShopShippingModule.ajaxurl,
			data    : fields,
			dataType: 'json',			
			success : function(ajax_response){
				
				$("#usc_shipping_throbber").hide();
				eShopShippingModule.create_shipping_html(ajax_response);
				
			}
		});
	};
	
	/**
	 * @package  eShopShippingModule
	 * @function create_shipping_html()
	 * @desc     Creates the actual HTML for shipping
	 * @param    object ajax_response
	 */
	eShopShippingModule.create_shipping_html = function(ajax_response) {
		
		// Handle/show errors
		if (eShopShippingModule.has_errors(ajax_response) === true) {
			return false;
		}
		
		eShopShippingModule.details  = {}; // Reset details
		eShopShippingModule.services = {}; // Reset services

		$("#usc_shipping_options").html("<select id=\"usc_shipping_services\" name=\"eshop_shiptype\"></select>");
		
		$.each(ajax_response.data, function(key, value) {
			
			eShopShippingModule.details[key]  = value['details'];  // Populate details object
			eShopShippingModule.services[key] = value['services']; // Populate services object
			
			var attrs = (typeof ajax_response.selected_service !== 'undefined' &&
				     ajax_response.selected_service == key) ? {selected : "selected", value : key} : {value : key};
			
			// Create dropdown options
			$('#usc_shipping_services')
		          .append($('<option>', attrs)
		          .text(key + ' ('+eShopShippingModule.currency+' ' + value['price'] + ')')); 
		});
		
		
		// Create details HTML for the selected option
		eShopShippingModule.create_details_html();
	};
	
	
	/**
	 * @package  eShopShippingModule
	 * @function create_details_html()
	 * @desc     Creates the details HTML for the selected shipping option
	 * @param    protected eShopShippingModule.details
	 */
	eShopShippingModule.create_details_html = function () {
		
		var sel_option = $("#usc_shipping_services :selected").val(),
	    dls, dld, pickup;
	
	// Reset the HTML div
	$("#usc_shipping_details").html('');

	if (typeof eShopShippingModule.services !== 'undefined' && 
		typeof eShopShippingModule.services[sel_option] !== 'undefined') {
		
		dls = $("<dl>",{class: 'usc_shipping_details'});
	
		$.each(eShopShippingModule.services[sel_option], function(key,value){
			
			// We can't simply use an array of inputs for the additional services
			// because eShop has some un-overrideable functions that end up getting 'Array' as value.
			// So we create individual ones and then use jQuery to merge them into CSVs to handle elsewhere.
			var id = key.replace(/\s+/g,'_');
			var checked = (typeof eShopShippingModule.startup_details !== 'undefined' &&
						   typeof eShopShippingModule.startup_details.selected_services[key] !== 'undefined') ? ' checked="checked"' : '';
			var input = '<input id="item_'+id+'" type="checkbox" name="additional_shipping_service" value="'+key+'" '+checked+'/>';
			
			var curr = value.match(/^\d+\.\d+$/) ? eShopShippingModule.currency : '';
			
			dls.append('<dt>' +input +' <label for="item_'+id+'">'+key+'</label></dt>')
			   .append('<dd><label for="item_'+id+'">'+curr+' '+value+'</label></dd>');
		});
		
		$("#usc_shipping_details").append($("<span>",{class : 'usc_details_headline'}).html(eShopShippingModule.translate('Extra Services'))).append(dls);
		$("#usc_shipping_details").append($("<div>",{style : 'clear:both'}));
	}
	
	if (typeof eShopShippingModule.details[sel_option] !== 'undefined') {
	
	    dld = $("<dl>",{class: 'usc_shipping_details'});
		
		$.each(eShopShippingModule.details[sel_option], function(key,value){
			
			var curr = value.match(/^\d+\.\d+$/) ? eShopShippingModule.currency : '';
			var transl = value.match(/\d+-\d+-\d+/) ? eShopShippingModule.format_date(value) : eShopShippingModule.translate(value);
			
			if (key == 'usc_pickup') {
				pickup = value;
			}
			else {
				dld.append('<dt>'+eShopShippingModule.translate(key)+'</dt>')
				   .append('<dd>'+curr+transl+'</dd>');
			}
		});

		if (pickup) {
			$("#usc_shipping_details").html('<div id="usc_pickup_text">'+pickup+'</div>');
		}
		else {
			$("#usc_shipping_details").append($("<span>",{class : 'usc_details_headline'}).html(eShopShippingModule.translate('Service Details'))).append(dld);
		}
	}
		
	};
	
	
	/**
	 * @package  eShopShippingModule
	 * @function has_errors()
	 * @desc     Clears all shipping divs and displays any errors received.
	 * @param    object data
	 * @returns  bool (true if errors were found, false otherwise)
	 */
	eShopShippingModule.has_errors = function(data) {
	
		// Clear shipping divs
		$("#usc_shipping_options,#usc_shipping_error_msgs,#usc_shipping_details").html("");
		
		// If errors, show them and return
		if(data.success === false) {
			
			$("#usc_shipping_error_msgs").html('<ul></ul>');
			
			for (var i=0,j = data.msgs.length; i<j; i+=1) {
				$("#usc_shipping_error_msgs").append('<li>'+data.msgs[i]+'</li>');
			}
			
			return true;
		}
		
		return false;
	};
	
	/**
	 * @package  eShopShippingModule
	 * @function validate_fields()
	 * @desc     Validates/parses the form fields
	 * @param    object fields 
	 * @return   object City/State fields for shipping
	 */
	eShopShippingModule.validate_fields = function(fields) {
		
		var altstate = (fields.ship_altstate || fields.altstate),
	    state    = altstate ? altstate : (fields.ship_state || fields.state),
	    country  = (fields.shipcountry || fields.country),
	    city     = (fields.ship_city || fields.city),
	    zip      = (fields.ship_postcode || fields.zip),
	    address  = (fields.ship_address || fields.address1),
	    phone    = (fields.ship_phone || fields.phone),
	    name,errors   = [];
	
	
	if (fields.ship_name) {
		name = fields.ship_name;
	}
	else {
		fields.first_name = typeof fields.first_name != 'undefined' ? fields.first_name : '';
		fields.last_name  = typeof fields.last_name != 'undefined' ? fields.last_name : '';
		name = fields.first_name + ' ' + fields.last_name;
	}
	
	
	// Canada and US require zip/postal_code
	// Others don't
//	if (country === 'US' || country === 'CA') {
//
//		if (! zip) {
//			errors.push(eShopShippingModuleUPS.lang.missing_zip);
//		}
//	}
	
	if (fields.weight != parseFloat(fields.weight)) {
			errors.push(eShopShippingModuleUPS.lang.invalid_weight + ' "' + fields.weight + '"');
	}
	
	// Errors are handled by the caller
	if (errors.length > 0) {
		return {
			success : false,
			msgs : errors
		};
	}
		
	
	return { 
		success : true,
		data : { city   : city,
				 state  : state,
				 country: country,
				 weight : fields.weight,
				 zip    : zip,
				 address: address,
				 name   : name,
				 phone  : phone,
				 amount : fields.amount
			   }
	};
		
	};
	
	
	/**
	 * @package  eShopShippingModule
	 * @function format_date()
	 * @desc     Formats a date returned by ajax
	 * @param    string date
	 * @returns  string formatted date
	 */
	eShopShippingModule.format_date = function (date) {
		
		if (! (ymd = date.match(/(\d+)-(\d+)-(\d+)/)) ) { 
			return date;
		}
		
		return ymd[2] + '/' + ymd[3] + '/' + ymd[1];
	};
	
	
	/**
	 * 
	 * 
	 */
	eShopShippingModule.refresh_states = function(country_code,prequel,sequel,force_module){
		
		var data = {
			    country_code: country_code,
			    action: eShopShippingModule.ajaxaction
		};
		
		if (typeof force_module !== 'undefined') {
			data.force_module = force_module;
		}
		
		if (prequel){
			prequel();
		}
		
		$.ajax({
				type    : eShopShippingModule.method,
				url     : eShopShippingModule.ajaxurl,
				data    : data,
				dataType: 'json',			
				success : function(ajax_response){
					if (sequel) {
						sequel(ajax_response);
					}
				}
		});
	
	};
	
	
	/**
	 *  @package eShopShippingModule
	 *  @var     object details
	 *  @desc    Holds shipping details after ajax call. Accessed by Shipping option drop-down 
	 */
	eShopShippingModule.replace_field = function(jtarget,data){
		var name = jtarget.attr('name'), 
		    id   = jtarget.attr('id'),
		    has_elements = 0,
		    replace;
		
		for (i in data) {
			if (data.hasOwnProperty(i)){
				has_elements++;
				break;
			}
		}

		if (has_elements) {
			replace = $("<select>",{name:name,id:id});
			// Create dropdown options
			$.each(data, function(key, value) {
				replace
			          .append($('<option>', {value:value})
			        		  .text(key));
			});
		}
		else {
			replace = $("<input type=\"text\" name=\""+name+"\" id=\""+id+"\">");
		}
		
		jtarget.replaceWith(replace);
		
	};
	
	
	/**
	 * @package  eShopShippingModule
	 * @function translate()
	 * @desc     Checks lang object for occurrence of key, returns translation or untouched key.
	 * @param    string str_to_translate
	 * @returns  string
	 */
	eShopShippingModule.translate = function(t){
		
		return typeof eShopShippingModule.lang[t] == 'undefined' ? t : eShopShippingModule.lang[t]; 
	};
	
	
	if ($.fn.jquery >= 1.7) {
		
		$("#usc_shipping_services").on("change", function(){
			eShopShippingModule.create_details_html();
		});
	}
	else {
		$("#usc_shipping_services").live('change',  function() {
			eShopShippingModule.create_details_html();
		});
	}
	
	
	$("#country,#shipcountry").change(function(){
		eShopShippingModule.call_get_rates();
	});
	
	$("#usc_update_shipping_options").click(function(e){
		eShopShippingModule.call_get_rates();
		e.preventDefault();
	});
	
	$("#state, #zip, #country, #shipcountry" +
	  "#ship_state, #ship_altstate, #ship_postcode").blur(function(){
		  
		  // Check that we have a state/zip/country before calling get_rates
		  var state   = ($.trim($("#ship_state").val()) || $.trim($("#ship_altstate").val()) || $.trim($("#state").val()) || $.trim($("#altstate").val())),
		      country = ($.trim($("#country").val())    || $.trim($("#shipcountry").val())),
		      zip     = ($.trim($("#zip").val())        || $.trim($("#ship_postcode").val()));
		  
		  if (state && country && zip) {
			  eShopShippingModule.call_get_rates();
		  }
	});
	
	// Handle the additional_services upon submission
	$("#submitit").closest('form').submit(function(){
		var as_input = $('<input>',{type: 'hidden', name : 'additional_shipping_services'});
		
		var vals = [];
		$("input[name=additional_shipping_service]:checked").each(function(){
			vals.push($(this).val());
		});
		
		if (vals.length) {
			as_input.val(vals.join('; '));
			$(this).append(as_input);
		}
	});
	
});