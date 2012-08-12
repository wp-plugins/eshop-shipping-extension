jQuery(document).ready(function($){
	
	var eShopShippingModule_packages = window.eShopShippingModule_packages || {};
	
	/**
	 * Package_class drop-down object
	 */
	eShopShippingModule_packages.mk_dropdown = function(name,selected){
		
		var sel_node, option, v;
		
		if (typeof eShopShippingModule_packages.pc_elements == 'undefined') return;
		
		if (! selected) selected = '';
		
		sel_node = $("<select/>",{name: name});
		
		option = $("<option />").attr('value','').text(eShopShippingModule_packages.lang.select);
		if (selected == '') option.attr('selected','selected');
		sel_node.append(option);
		
		for (i in eShopShippingModule_packages.pc_elements) {
			
			v = eShopShippingModule_packages.pc_elements[i];
			option = $("<option />").attr('value',v).text(v);
			
			if (selected == v) option.attr('selected','selected');
			
			sel_node.append(option);
		}

		return sel_node;
	};
	
	
	
	if (eShopShippingModule_packages.package_class == 'product') {
		
		// Show prod-level package class
		var name = 'eshop_product_package_class';
		var sel_node = eShopShippingModule_packages.mk_dropdown(name,eShopShippingModule_packages.sel_prod_level);
		
		$("input#eshop_stock_available")
	    	.parent()
	    	.after(jQuery("<p/>").html('<strong>'+eShopShippingModule_packages.lang.package_class+'</strong> ')
	    						 .append(sel_node)
	    );
		
		// Add hidden inputs to save product_option values and not lose them between switches
		for (i in eShopShippingModule_packages.sel_prod_opt_level) {
			var input = $("<input />",{name:'eshop_opt_package_class_'+i,
									   value: eShopShippingModule_packages.sel_prod_opt_level[i],
									   type: 'hidden'});
			$("input#eshop_stock_available").after(input);
		}
			
		
	}
	else if (eShopShippingModule_packages.package_class == 'product_option') {
		
		// Show option level package class
		$("table.eshoppopt > thead > tr").append("<th>"+eShopShippingModule_packages.lang.package_class+"</th>");
		
		var count = 1;
		$("table.eshoppopt > tbody > tr").each(function(){
			var name = 'eshop_opt_package_class_' + count;
			var sel_node = eShopShippingModule_packages.mk_dropdown(name,eShopShippingModule_packages.sel_prod_opt_level[count]);
			sel_node.css('width:150px');
			
			$(this).append($('<td/>').append(sel_node));
			
			count = count+1;
		});
		
		// Add hidden inputs to save product values and not lose them between switches
		var input = $("<input />", {type: 'hidden', name: 'eshop_product_package_class', value: eShopShippingModule_packages.sel_prod_level});
		$("table.eshoppopt").after(input);
	}
	else {
		// Global options, make everything a hidden field
		
		// Add hidden inputs to save product_option values and not lose them between switches
		for (i in eShopShippingModule_packages.sel_prod_opt_level) {
			var input = $("<input />",{name:'eshop_opt_package_class_'+i,
									   value: eShopShippingModule_packages.sel_prod_opt_level[i],
									   type: 'hidden'});
			$("input#eshop_stock_available").after(input);
		}
		
		// Add hidden inputs to save product values and not lose them between switches
		var input = $("<input />", {type: 'hidden', name: 'eshop_product_package_class', value: eShopShippingModule_packages.sel_prod_level});
		$("input#eshop_stock_available").after(input);
	}
	
});