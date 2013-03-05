<?php defined('ABSPATH') or die("No direct access allowed");

class USC_override_eShop_showform
{
	function __construct()
	{

	}
	
	function do_override($first_name,$last_name,$company,$phone,$email,$address1,
					   	 $address2,$city,$state,$altstate,$zip,$country,$reference,$comments,$ship_name,
					     $ship_company,$ship_phone,$ship_address,$ship_city,$ship_postcode,$ship_state,$ship_altstate,$ship_country)
	{
		global $wpdb, $blog_id,$eshopoptions, $USC_eShop_Shipping_Extension;
		
		$reqdvalues = array('shipping','first_name','last_name','email','phone','address','city','zip','pay');
		//setupshipping arrays
		if($eshopoptions['shipping'] != '4')
		{
			if($eshopoptions['shipping_zone'] == 'country')
			{
				$reqdvalues[] = 'country';
			}
			else
			{
				$reqdvalues[] = 'state';
			}
		}
		else
		{
			$creqd  = '';
			$dtable = $wpdb->prefix.'eshop_rates';
			$query  = $wpdb->get_results("SELECT DISTINCT(area) from $dtable where rate_type='ship_weight'");
			foreach($query as $k)
			{
				$reqdvalues[] = $k->area;
			}
		}
		
		$linkattr = apply_filters('eShopCheckoutLinksAttr','');
		
		$reqdarray = apply_filters('eshopCheckoutReqd', $reqdvalues );
		
		$xtralinks = eshop_show_extra_links();
		
		$echo = '
		<div class="hr"></div>
		<div class="eshopcustdetails custdetails">
		<p><small class="privacy"><span class="reqd" title="Asterisk">*</span> '.__('Denotes Required Field ','eshop').'
		'/*.__($xtralinks,'eshop')*/.'</small></p>
		<form action="'.esc_url($_SERVER['REQUEST_URI']).'" method="post" class="eshop eshopform">';
		
		if ($eshopoptions['shipping'] == '4'        && 
			'no' == $eshopoptions['downloads_only'] && 
			!eshop_only_downloads())
		{
			//only for ship by weight
// 			$echo.='<fieldset class="eshop fld0"><legend id="shiplegend">'. __('Please Choose Shipping','eshop').eshop_checkreqd($reqdarray,'shipping').'</legend>';
// 			$typearr=explode("\n", $eshopoptions['ship_types']);
// 			$cartweight=$_SESSION['eshop_totalweight'.$blog_id]['totalweight'];
// 			$eshopshiptable='';
// 			$eshopletter = "A";
// 			$dtable=$wpdb->prefix.'eshop_rates';
// 			$weightsymbol=$eshopoptions['weight_unit'];
// 			$currsymbol=$eshopoptions['currency_symbol'];
// 			$stype='';
// 			if(isset($_POST['eshop_shiptype'])) $stype=$_POST['eshop_shiptype'];
// 			$first=apply_filters('eshop_default_shipping','1');
// 			/* '1- text 2 - weight 3-weight symbol' */
// 			$echo .='<p>'.sprintf( __('%1$s %2$s %3$s','eshop'),__('Total weight: ','eshop'), number_format_i18n($cartweight,__('2','eshop')),$weightsymbol).'</p>';
// 			foreach ($typearr as $k=>$type){
// 				$k++;
// 				$query=$wpdb->get_results("SELECT * from $dtable  where weight<='$cartweight' &&  class='$k' && rate_type='ship_weight' order by weight DESC limit 1");
// 				if(count($query)==0)
// 					continue;
// 				if($query['0']->maxweight!='' && $cartweight > $query['0']->maxweight)
// 					continue;
// 				$eshopshiptableinner ='
// 				<table class="eshopshiprates eshop" summary="'.__('Shipping rates per mode','eshop').'">
// 				<thead>
// 				<tr>';
// 				for($z=1;$z<=$eshopoptions['numb_shipzones'];$z++){
// 					$y='zone'.$z;
// 					$echozone=sprintf(__('Zone %1$d','eshop'),$z);
// 					$dispzone=apply_filters('eshop_rename_ship_zone',array());
// 					if(isset($dispzone[$z]))
// 						$echozone=$dispzone[$z];
// 					$eshopshiptableinner.='<th id="'.$eshopletter.$y.'" class="'.$y.'">'. $echozone .'</th>';
// 				}
// 				$eshopshiptableinner.='</tr>
// 				</thead>
// 				<tbody>';
					
// 				$x=1;
// 				foreach ($query as $row){
// 					$alt = ($x % 2) ? '' : ' class="alt"';
// 					$eshopshiptableinner.='
// 					<tr'.$alt.'>';
// 					for($z=1;$z<=$eshopoptions['numb_shipzones'];$z++){
// 						$y='zone'.$z;
// 						$eshopshiptableinner.='<td headers="'.$eshopletter.$y.'" class="'.$y.'">'.sprintf( __('%1$s%2$s','eshop'), $currsymbol, $row->$y).'</td>';
// 					}
// 					$eshopshiptableinner.='</tr>';
// 					$x++;
// 				}
// 				$eshopletter++;
// 				$eshopshiptableinner.='</tbody></table>'."\n";
					
// 				if($row->area=='country')
// 					$eshopshiptableheadtext = sprintf( __('%1$s <small>%2$s</small>','eshop'),stripslashes(esc_attr($type)), __('(Shipping Zones by Country)','eshop'));
// 				else
// 					$eshopshiptableheadtext = sprintf( __('%1$s <small>%2$s</small>','eshop'),stripslashes(esc_attr($type)), __('(Shipping Zones by State/County/Province)','eshop'));
					
// 				if(isset($row->maxweight) && $row->maxweight!='')
// 					$eshopshiptableheadtext .= ' '.sprintf( __('Max. Weight %1$s %2$s','eshop'),$row->maxweight,$eshopoptions['weight_unit']);
// 				if($first=='1' && $stype==''){
// 					$stype=$k;
// 					$first=0;
// 				}
// 				$eshopshiptablehead='<span><input class="rad" type="radio" name="eshop_shiptype" value="'.$k.'" id="eshop_shiptype'.$k.'"'.checked($stype,$k,false).' /> <label for="eshop_shiptype'.$k.'">'.$eshopshiptableheadtext.'</label></span>';
					
// 				$eshopshiptable .= $eshopshiptablehead.$eshopshiptableinner;
		
// 			}
// 			if($eshopshiptable != '')
// 				$echo .= $eshopshiptable;
// 			else
// 				$echo .= '<input type="hidden" name="eshop_shiptype" value="0" id="eshop_shiptype0" />';
// 			$echo .='</fieldset>';
		}
		
		
		$echo.='<fieldset class="eshop fld1"><legend id="mainlegend">'. __('Please Enter Your Details','eshop').'</legend>
		<fieldset class="eshop fld2">';
		if('no' == $eshopoptions['downloads_only']){
			$echo .='<legend>'.__('Mailing Address','eshop').'</legend>';
		}else{
			$echo .='<legend>'.__('Contact Details','eshop').'</legend>';
		}
		$echo .='<span class="firstname"><label for="first_name">'.__('First Name','eshop').eshop_checkreqd($reqdarray,'first_name').'</label>
		<input class="med" type="text" name="first_name" value="'.$first_name.'" id="first_name" maxlength="40" size="40" /><br /></span>
		<span class="lastname"><label for="last_name">'.__('Last Name','eshop').eshop_checkreqd($reqdarray,'last_name').'</label>
		<input class="med" type="text" name="last_name" value="'.$last_name.'" id="last_name" maxlength="40" size="40" /><br /></span>';
		if('no' == $eshopoptions['downloads_only']){
			$echo .='<span class="company"><label for="company">'.__('Company','eshop').eshop_checkreqd($reqdarray,'company').'</label>
			<input class="med" type="text" name="company" value="'.$company.'" id="company" size="40" /><br /></span>';
		}
		$echo .='<span class="email"><label for="email">'.__('Email','eshop').eshop_checkreqd($reqdarray,'email').'</label>
		<input class="med" type="text" name="email" value="'.$email.'" id="email" maxlength="100" size="40" /><br /></span>';
		if('no' == $eshopoptions['downloads_only']){
			$echo .='<span class="phone"><label for="phone">'.__('Phone','eshop').eshop_checkreqd($reqdarray,'phone').'</label>
			<input class="med" type="text" name="phone" value="'.$phone.'" id="phone" maxlength="30" size="30" /><br /></span>
			<span class="address1"><label for="address1">'.__('Address','eshop').eshop_checkreqd($reqdarray,'address').'</label>
			<input class="med" type="text" name="address1" id="address1" value="'.$address1.'" maxlength="40" size="40" /><br /></span>
			<span class="address2"><label for="address2">'.__('Address (continued)','eshop').'</label>
			<input class="med" type="text" name="address2" id="address2" value="'.$address2.'" maxlength="40" size="40" /><br /></span>
			<span class="city"><label for="city">'.__('City or town','eshop').eshop_checkreqd($reqdarray,'city').'</label>
			<input class="med" type="text" name="city" value="'.$city.'" id="city" maxlength="40" size="40" /><br /></span>'.
			'<input type="hidden" id="cart_weight" name="cart_weight" value="' . $_SESSION['eshop_totalweight'.$blog_id]['totalweight'] . '" />'."\n";
		
			// state list from db
			$table=$wpdb->prefix.'eshop_states';
			$getstate=$eshopoptions['shipping_state'];
			if($eshopoptions['show_allstates'] != '1'){
				$stateList=$wpdb->get_results("SELECT id,code,stateName FROM $table WHERE list='$getstate' ORDER BY stateName",ARRAY_A);
			}else{
				$stateList=$wpdb->get_results("SELECT id,code,stateName,list FROM $table ORDER BY list,stateName",ARRAY_A);
			}
		
			if(sizeof($stateList)>0){
				$echo .='<span class="state"><label for="state">'.__('State/County/Province','eshop').eshop_checkreqd($reqdarray,'state').'</label>
				<select class="med pointer" name="state" id="state">';
				$echo .='<option value="">'.__('Please Select','eshop').'</option>';
				$echo .= apply_filters('eshop_states_na','<option value="">'.__('not applicable','eshop').'</option>');
				foreach($stateList as $code => $value){
					if(isset($value['list'])) $li=$value['list'];
					else $li='1';
					$eshopstatelist[$li][$value['id']]=array($value['code'],$value['stateName']);
				}
				$tablec=$wpdb->prefix.'eshop_countries';
				foreach($eshopstatelist as $egroup =>$value){
					$eshopcname=$wpdb->get_var("SELECT country FROM $tablec where code='$egroup' limit 1");
					$echo .='<optgroup label="'.$eshopcname.'">'."\n";
		
					foreach($value as $code =>$stateName){
						//$stateName=esc_attr($stateName);
						if (isset($state) && ($state == $stateName['0'] || $state == $code)){
							$echo.= '<option value="'.$code.'" selected="selected">'.$stateName['1']."</option>\n";
						}else{
							$echo.='<option value="'.$code.'">'.$stateName['1']."</option>\n";
						}
					}
					$echo .="</optgroup>\n";
				}
				$echo.= "</select><br /></span>\n";
			}else{
				$echo .='<input type="hidden" name="state" value="" />';
			}
			$echo .= '<span class="altstate"><label for="altstate">'.__('State/County/Province <small>if not listed above</small>','eshop').'</label>
			<input class="short" type="text" name="altstate" value="'.$altstate.'" id="altstate" size="20" /><br /></span>';
			$echo .= '
			<span class="zip"><label for="zip">'.__('Zip/Post code','eshop').eshop_checkreqd($reqdarray,'zip').'</label>
			<input class="short" type="text" name="zip" value="'.$zip.'" id="zip" maxlength="20" size="20" /><br /></span>
			<span class="country"><label for="country">'.__('Country','eshop').eshop_checkreqd($reqdarray,'country').'</label>
			<select class="med pointer" name="country" id="country">
			';
			// country list from db
			$tablec=$wpdb->prefix.'eshop_countries';
			$List=$wpdb->get_results("SELECT code,country FROM $tablec GROUP BY list,country",ARRAY_A);
			foreach($List as $key=>$value){
				$k=$value['code'];
				$v=$value['country'];
				$countryList[$k]=$v;
			}
			if(!isset($countryList)){
				wp_die(__('Error, please contact site owner.','eshop'));
			}
			$echo .='<option value="" selected="selected">'.__('Select your Country','eshop').'</option>';
			foreach($countryList as $code => $label){
				$label=htmlspecialchars($label);
				if (isset($country) && $country == $code){
					$echo.= "<option value=\"$code\" selected=\"selected\">$label</option>\n";
				}else{
					$echo.="<option value=\"$code\">$label</option>";
				}
			}
			$echo.= "</select></span>";
		}
		$echo .="</fieldset>";
		
		$echo = apply_filters('eshopaddtocheckout',$echo);
		
		$echo = apply_filters('usc_add_shipping_fields',$echo,$reqdarray,'under_mailing');
		
		if('yes' != $eshopoptions['hide_addinfo']){
			$echo .= '<fieldset class="eshop fld3">
			<legend>'.__('Additional information','eshop').'</legend>
			<span class="eshopreference"><label for="reference">'.__('Reference or <abbr title="Purchase Order number">PO</abbr>','eshop').eshop_checkreqd($reqdarray,'ref').'</label>
			<input type="text" class="med" name="reference" value="'.$reference.'" id="reference" size="30" /><br /></span>
			<label for="eshop-comments">'.__('Comments or special instructions','eshop').eshop_checkreqd($reqdarray,'comments').'</label>
			<textarea class="textbox" name="comments" id="eshop-comments" cols="60" rows="5">'.$comments.'</textarea>';
			$echo = apply_filters('eshopaddtoadditionalinformation',$echo);
			$echo .= "</fieldset>\n";
		}
		if('no' == $eshopoptions['downloads_only']){
			if('yes' != $eshopoptions['hide_shipping']){
				$echo .='<fieldset class="eshop fld4">
				<legend>'.__('Shipping address (if different)','eshop').'</legend>
				<span class="ship_name"><label for="ship_name">'.__('Name','eshop').'</label>
				<input class="med" type="text" name="ship_name" id="ship_name" value="'.stripslashes(esc_attr($ship_name)).'" maxlength="40" size="40" /><br /></span>
				<span class="ship_company"><label for="ship_company">'.__('Company','eshop').'</label>
				<input class="med" type="text" name="ship_company" value="'.stripslashes(esc_attr($ship_company)).'" id="ship_company" size="40" /><br /></span>
				<span class="ship_phone"><label for="ship_phone">'.__('Phone','eshop').'</label>
				<input class="med" type="text" name="ship_phone" value="'.$ship_phone.'" id="ship_phone" maxlength="30" size="30" /><br /></span>
				<span class="ship_address"><label for="ship_address">'.__('Address','eshop').'</label>
				<input class="med" type="text" name="ship_address" id="ship_address" value="'.stripslashes(esc_attr($ship_address)).'" maxlength="40" size="40" /><br /></span>
				<span class="ship_city"><label for="ship_city">'.__('City or town','eshop').'</label>
				<input class="med" type="text" name="ship_city" id="ship_city" value="'.stripslashes(esc_attr($ship_city)).'" maxlength="40" size="40" /><br /></span>'."\n";
				if(isset($stateList) && sizeof($stateList)>0){
					$echo .='<span class="ship_state"><label for="shipstate">'.__('State/County/Province','eshop').'</label>
					<select class="med pointer" name="ship_state" id="shipstate">';
					//state list from db, as above
					$echo .='<option value="" selected="selected">'.__('Please Select','eshop').'</option>';
					$echo .=apply_filters('eshop_states_na','<option value="">'.__('not applicable','eshop').'</option>');
					foreach($eshopstatelist as $egroup =>$value){
						$eshopcname=$wpdb->get_var("SELECT country FROM $tablec where code='$egroup' limit 1");
		
						$echo .='<optgroup label="'.$eshopcname.'">'."\n";
						foreach($value as $code =>$stateName){
							//$stateName=htmlspecialchars($stateName);
							if (isset($ship_state) && ($ship_state == $code ||$ship_state == $stateName['0']) ){
								$echo.= '<option value="'.$code.'" selected="selected">'.$stateName['1']."</option>\n";
							}else{
								$echo.='<option value="'.$code.'">'.$stateName['1']."</option>\n";
							}
						}
						$echo .="</optgroup>\n";
					}
					$echo .= '</select><br /></span>';
				}else{
					$echo .='<input type="hidden" name="ship_state" value="" />';
				}
				$echo .= '<span class="ship_altstate"><label for="ship_altstate">'.__('State/County/Province <small>if not listed above</small>','eshop').'</label>
				<input class="short" type="text" name="ship_altstate" value="'.stripslashes(esc_attr($ship_altstate)).'" id="ship_altstate" size="20" /><br /></span>';
		
				$echo .='<span class="shippostcode"><label for="ship_postcode">'.__('Zip/Post Code','eshop').'</label>
				<input class="short" type="text" name="ship_postcode" id="ship_postcode" value="'.$ship_postcode.'" maxlength="20" size="20" />
				<br /></span>
				<span class="shipcountry"><label for="shipcountry">'.__('Country','eshop').'</label>
				<select class="med pointer" name="ship_country" id="shipcountry">
				';
				$echo .='<option value="" selected="selected">'.__('Select your Country','eshop').'</option>';
				foreach($countryList as $code => $label){
					$label=htmlspecialchars($label);
					if (isset($ship_country) && $ship_country == $code){
						$echo.= "<option value=\"$code\" selected=\"selected\">$label</option>\n";
					}else{
						$echo.="<option value=\"$code\">$label</option>";
					}
				}
				$echo.= "</select></span>";
				$echo .='</fieldset>';
				
				$echo = apply_filters('usc_add_shipping_fields',$echo,$reqdarray,'under_shipping');
				
			}
		}
		$final_price=number_format($_SESSION['final_price'.$blog_id], 2,'.','');
		$discounttotal=0;
		if(eshop_discount_codes_check()){
			$eshop_discount='';
			if(isset($_POST['eshop_discount'])) $eshop_discount=esc_attr($_POST['eshop_discount']);
			$echo .='<fieldset class="eshop fld5"><legend><label for="eshop_discount">'.__('Discount Code','eshop').'</label></legend>
			<input class="med" type="text" name="eshop_discount" value="'.$eshop_discount.'" id="eshop_discount" size="40" /></fieldset>'."\n";
		}
		if(is_array($eshopoptions['method'])){
			$i=1;
			$eshopfiles=eshop_files_directory();
			$echo .='<fieldset class="eshop fld6 eshoppayvia"><legend>'.__('Pay Via', 'eshop').eshop_checkreqd($reqdarray,'pay').'</legend>'."\n";
			$echo = apply_filters('eshopaddtocheckoutpayvia',$echo);
			$echo .= "<ul>\n";
			$eshop_paymentx='';
			if(isset($_POST['eshop_payment'])) $eshop_paymentx = $_POST['eshop_payment'];
			if(sizeof((array)$eshopoptions['method'])!=1){
				foreach($eshopoptions['method'] as $k=>$eshoppayment){
					$replace = array(".");
					$eshoppayment = str_replace($replace, "", $eshoppayment);
					$eshoppayment_text=$eshoppayment;
					if($eshoppayment_text=='cash'){
						$eshopcash = $eshopoptions['cash'];
						if($eshopcash['rename']!='')
							$eshoppayment_text=$eshopcash['rename'];
					}
					if($eshoppayment_text=='bank'){
						$eshopbank = $eshopoptions['bank'];
						if($eshopbank['rename']!='')
							$eshoppayment_text=$eshopbank['rename'];
					}
					$eshopmi=apply_filters('eshop_merchant_img_'.$eshoppayment,array('path'=>$eshopfiles['0'].$eshoppayment.'.png','url'=>$eshopfiles['1'].$eshoppayment.'.png'));
					$eshopmerchantimgpath=$eshopmi['path'];
					$eshopmerchantimgurl=$eshopmi['url'];
					$dims=array('3'=>'');
					if(file_exists($eshopmerchantimgpath))
						$dims=getimagesize($eshopmerchantimgpath);
					$echo .='<li><input class="rad" type="radio" name="eshop_payment" value="'.$eshoppayment.'" id="eshop_payment'.$i.'"'.checked($eshop_paymentx,$eshoppayment,false).' /><label for="eshop_payment'.$i.'"><img src="'.$eshopmerchantimgurl.'" '.$dims[3].' alt="'.__('Pay via','eshop').' '.$eshoppayment_text.'" title="'.__('Pay via','eshop').' '.$eshoppayment_text.'" /></label></li>'."\n";
					$i++;
				}
			}else{
				foreach($eshopoptions['method'] as $k=>$eshoppayment){
					$replace = array(".");
					$eshoppayment = str_replace($replace, "", $eshoppayment);
					$eshoppayment_text=$eshoppayment;
					if($eshoppayment_text=='cash'){
						$eshopcash = $eshopoptions['cash'];
						if($eshopcash['rename']!='')
							$eshoppayment_text=$eshopcash['rename'];
					}
					if($eshoppayment_text=='bank'){
						$eshopbank = $eshopoptions['bank'];
						if($eshopbank['rename']!='')
							$eshoppayment_text=$eshopbank['rename'];
					}
					$eshopmi=apply_filters('eshop_merchant_img_'.$eshoppayment,array('path'=>$eshopfiles['0'].$eshoppayment.'.png','url'=>$eshopfiles['1'].$eshoppayment.'.png'));
					$eshopmerchantimgpath=$eshopmi['path'];
					$eshopmerchantimgurl=$eshopmi['url'];
					$dims='';
					if(file_exists($eshopmerchantimgpath))
						$dims=getimagesize($eshopmerchantimgpath);
					$echo .='<li><img src="'.$eshopmerchantimgurl.'" '.$dims[3].' alt="'.__('Pay via','eshop').' '.$eshoppayment_text.'" title="'.__('Pay via','eshop').' '.$eshoppayment_text.'" /><input type="hidden" name="eshop_payment" value="'.$eshoppayment.'" id="eshop_payment'.$i.'" /></li>'."\n";
					$i++;
				}
			}
			$echo .="</ul>\n";
			$echo .= eshopCartFields();
			$echo .="</fieldset>\n";
		}
		if('yes' == $eshopoptions['tandc_use']){
			if($eshopoptions['tandc_id']!='')
				$eshoptc='<a href="'.get_permalink($eshopoptions['tandc_id']).'"'.$linkattr.'>'.$eshopoptions['tandc'].'</a>';
			else
				$eshoptc=$eshopoptions['tandc'];
		
			$echo .='<p class="eshop_tandc"><input type="checkbox" name="eshop_tandc" id="eshop_tandc" value="1" /><label for="eshop_tandc">'.$eshoptc.'<span class="reqd">*</span></label></p>';
		}
		if(isset($eshopoptions['users']) && $eshopoptions['users']=='yes' && !is_user_logged_in()){
			if(isset($eshopoptions['users_text']) && $eshopoptions['users_text']!='')
				$edisplay=$eshopoptions['users_text'];
			else
				$edisplay=__('Sign me up to the site so I can view my orders.','eshop');
			$echo .='<p class="eshop_users"><input type="checkbox" name="eshop_users" id="eshop_users" value="1" /><label for="eshop_users">'.$edisplay.eshop_checkreqd($reqdarray,'signup').'</label></p>';
		}
		if('no' == $eshopoptions['downloads_only']){
			$echo .='<label for="submitit"><small id="eshopshowshipcost">'.__('<strong>Note:</strong> Submit to show shipping charges.','eshop').'</small></label><br />';
		}
		
		$echo .= '<input type="hidden" name="amount" value="'.$final_price.'" />';
		
		$echo .='<span class="buttonwrap"><input type="submit" class="button" id="submitit" name="submit" value="'.__('Proceed to Confirmation &raquo;','eshop').'" /></span>
		</fieldset>
		</form>
		</div>
		';
		if(get_bloginfo('version')<'2.5.1')
			remove_filter('the_content', 'wpautop');
		
		return $echo;
	}
}