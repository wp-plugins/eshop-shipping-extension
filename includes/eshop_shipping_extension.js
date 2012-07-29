jQuery(document).ready(function($){
	
	window.eShopShippingModule = window.eShopShippingModule || {};
	
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
	
});