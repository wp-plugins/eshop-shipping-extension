<?php defined('ABSPATH') or die("No direct access allowed");

class USC_override_eShop_Order_Handle 
{

	function __construct()
	{
		// NOOP
	}
	

	function do_override($espost,$checkid)
	{
		//This function puts the order into the db.
		global $wpdb, $blog_id,$eshopoptions;
		
		if (!is_user_logged_in() && isset($eshopoptions['users']) && $eshopoptions['users']=='yes' && isset($_SESSION['eshop_user'.$blog_id])) {
			//set up blank user if in case anything goes phooey
			$user_id=0;
			if(get_bloginfo('version')<'3.1')
				require_once ( ABSPATH . WPINC . '/registration.php' );
			//auto create a new user if they don't exist - only works if not logged in ;)
			$user_email=$espost['email'];
			$utable=$wpdb->prefix ."users";
			$filtnames=apply_filters('eshop_add_username',$espost['first_name'],$espost['last_name']);
			$names=str_replace(" ","",$filtnames);
			$username = strtolower($names);
			$eshopch = $wpdb->get_results("SHOW TABLE STATUS LIKE '$utable'");
		
			//a unique'ish number
			$altusername=strtolower($names.$eshopch[0]->Auto_increment);
			if(!email_exists($user_email)){
				if(username_exists($username))
					$username=$altusername;
		
				if(!username_exists($username)){
					$random_password = wp_generate_password( 12, false );
					$user_id = wp_create_user( $username, $random_password, $user_email );
					$eshopuser['company']=$espost['company'];
					$eshopuser['phone']=$espost['phone'];
					$eshopuser['address1']=$espost['address1'];
					$eshopuser['address2']=$espost['address2'];
					$eshopuser['city']=$espost['city'];
					$eshopuser['country']=$espost['country'];
					$eshopuser['state']=$espost['state'];
					$eshopuser['zip']=$espost['zip'];
					if(isset($espost['altstate']) && $espost['altstate']!='')
						$eshopuser['altstate']=$espost['altstate'];
					if(!is_numeric($espost['state'])){
						$statechk=$wpdb->escape($espost['state']);
						$sttable=$wpdb->prefix.'eshop_states';
						$eshopuser['state']=$wpdb->get_var("SELECT id FROM $sttable where code='$statechk' limit 1");
					}else{
						$eshopuser['state']=$espost['state'];
					}
					update_user_meta( $user_id, 'eshop', $eshopuser );
					update_user_meta( $user_id, 'first_name', $espost['first_name'] );
					update_user_meta( $user_id, 'last_name',$espost['last_name'] );
					update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
					wp_new_user_notification($user_id, $random_password);
				}
			}
		}else{
			global $current_user;
			get_currentuserinfo();
			$user_id=$current_user->ID;
		}
		if(!isset($eshopoptions['users'])) $user_id='0';
		
		
		//$wpdb->show_errors();
		if (get_magic_quotes_gpc()) {
			$espost=stripslashes_array($espost);
		}
		
		$custom_field = date('YmdHis');
		
		if ($espost['custom'])
		{
			$custom_field=$wpdb->escape($espost['custom']);
		}
		$first_name=$wpdb->escape($espost['first_name']);
		$last_name=$wpdb->escape($espost['last_name']);
		$email=$wpdb->escape($espost['email']);
		//set up some defaults
		$phone=$company=$address1=$address2=$city=$zip=$state=$country=$paidvia='';
		if(isset($espost['phone']))
			$phone=$wpdb->escape($espost['phone']);
		if(isset($espost['company']))
			$company=$wpdb->escape($espost['company']);
		if(isset($espost['address1']))
			$address1=$wpdb->escape($espost['address1']);
		if(isset($espost['address2']))
			$address2=$wpdb->escape($espost['address2']);
		if(isset($espost['city']))
			$city=$wpdb->escape($espost['city']);
		if(isset($espost['zip']))
			$zip=$wpdb->escape($espost['zip']);
		if(isset($espost['state']))
			$state=$wpdb->escape($espost['state']);
		if(isset($espost['country']))
			$country=$wpdb->escape($espost['country']);
		$paidvia=$wpdb->escape($_SESSION['eshop_payment'.$blog_id]);
		if(strtolower($paidvia)==__('cash','eshop')){
			$eshopcash = $eshopoptions['cash'];
			if($eshopcash['rename']!='')
				$paidvia=$eshopcash['rename'];
		}
		if(strtolower($paidvia)==__('bank','eshop')){
			$eshopbank = $eshopoptions['bank'];
			if($eshopbank['rename']!='')
				$paidvia=$eshopbank['rename'];
		}
		if(isset($espost['state']) && $espost['state']=='' && isset($espost['altstate']) && $espost['altstate']!='')
			$state=$wpdb->escape($espost['altstate']);
		
		if(isset($espost['ship_name']) && $espost['ship_name'] != ''){
			$ship_name=$wpdb->escape($espost['ship_name']);
		}else{
			$ship_name=$first_name.' '.$last_name;
		}
		if(isset($espost['ship_phone']) && $espost['ship_phone'] != ''){
			$ship_phone=$wpdb->escape($espost['ship_phone']);
		}else{
			$ship_phone=$phone;
		}
		if(isset($espost['ship_company']) && $espost['ship_company'] != ''){
			$ship_company=$wpdb->escape($espost['ship_company']);
		}else{
			$ship_company=$company;
		}
		if(isset($espost['ship_address']) && $espost['ship_address'] != ''){
			$ship_address=$wpdb->escape($espost['ship_address']);
		}else{
			$ship_address=$address1.' '.$address2;
		}
		if(isset($espost['ship_city']) && $espost['ship_city'] != ''){
			$ship_city=$wpdb->escape($espost['ship_city']);
		}else{
			$ship_city=$city;
		}
		if(isset($espost['ship_postcode']) && $espost['ship_postcode'] != ''){
			$ship_postcode=$wpdb->escape($espost['ship_postcode']);
		}else{
			$ship_postcode=$zip;
		}
		if(isset($espost['ship_country']) && $espost['ship_country'] != ''){
			$ship_country=$wpdb->escape($espost['ship_country']);
		}else{
			$ship_country=$country;
		}
		if(isset($espost['ship_state']) && $espost['ship_state'] != ''){
			$ship_state=$wpdb->escape($espost['ship_state']);
		}else{
			$ship_state=$state;
		}
		
		if(empty($espost['ship_state']) && !empty($espost['ship_altstate']))
			$ship_state=$wpdb->escape($espost['ship_altstate']);
		if(isset($espost['reference'])){
			$reference=$wpdb->escape($espost['reference']);
		}else{
			$reference='';
		}
		if(isset($espost['comments'])){
			$comments=$wpdb->escape($espost['comments']);
		}else{
			$comments='';
		}
		if(isset($espost['affiliate']))
			$affiliate=$wpdb->escape($espost['affiliate']);
		else
			$affiliate='';
		$detailstable=$wpdb->prefix.'eshop_orders';
		$itemstable=$wpdb->prefix.'eshop_order_items';
		$processing=__('Processing&#8230;','eshop');
		//readjust state if needed
		$sttable=$wpdb->prefix.'eshop_states';
		$getstate=$eshopoptions['shipping_state'];
		if($eshopoptions['show_allstates'] != '1'){
			$stateList=$wpdb->get_results("SELECT id,code,stateName FROM $sttable WHERE list='$getstate' ORDER BY stateName",ARRAY_A);
		}else{
			$stateList=$wpdb->get_results("SELECT id,code,stateName,list FROM $sttable ORDER BY list,stateName",ARRAY_A);
		}
		foreach($stateList as $code => $value){
			$eshopstatelist[$value['code']]=$value['id'];
		}
		if(isset($eshopstatelist[$state]))	$state=$eshopstatelist[$state];
		if(isset($eshopstatelist[$ship_state]))	$ship_state=$eshopstatelist[$ship_state];
		//if (!is_user_logged_in()) {
		$eshopching=$wpdb->get_var("SELECT checkid from $detailstable where checkid='$checkid' limit 1");
		if($eshopching!=$checkid){
			$query1=$wpdb->query("INSERT INTO $detailstable
					(checkid, first_name, last_name,company,email,phone, address1, address2, city,
					state, zip, country, reference, ship_name,ship_company,ship_phone,
					ship_address, ship_city, ship_postcode,	ship_state, ship_country,
					custom_field,transid,edited,comments,thememo,paidvia,affiliate,user_id,admin_note,user_notes)VALUES(
					'$checkid',
					'$first_name',
					'$last_name',
					'$company',
					'$email',
					'$phone',
					'$address1',
					'$address2',
					'$city',
					'$state',
					'$zip',
					'$country',
					'$reference',
					'$ship_name',
					'$ship_company',
					'$ship_phone',
					'$ship_address',
					'$ship_city',
					'$ship_postcode',
					'$ship_state',
					'$ship_country',
					'$custom_field',
					'$processing',
					NOW(),
					'$comments',
					'',
					'$paidvia',
					'$affiliate',
					'$user_id',
					'',''
			);");
						
				
			$i=1;
			//this is here to generate just one code per order
			$code=eshop_random_code();
			while($i<=$espost['numberofproducts']){
				//test
				$addoprice=0;
					$chk_id='item_number_'.$i;
					$chk_qty='quantity_'.$i;
				$chk_amt='amount_'.$i;
				//$chk_opt=$itemoption.$i;
				$chk_opt='item_name_'.$i;
				$chk_postid='postid_'.$i;
				$chk_weight='weight_'.$i;
				//tax
				$tax_amt = $tax_rate = '';
				if(isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
					$chk_tax='tax_'.$i;
					$chk_tax_rate='tax_rate_'.$i;
					if(isset($espost[$chk_tax])){
						$tax_amt=$wpdb->escape($espost[$chk_tax]);
						$tax_rate=$wpdb->escape($espost[$chk_tax_rate]);
					}
				}
				$item_id=$wpdb->escape($espost[$chk_id]);
				$item_qty=$wpdb->escape($espost[$chk_qty]);
				$item_amt=$wpdb->escape(str_replace(',', "", $espost[$chk_amt]));;
				$optname=$wpdb->escape($espost[$chk_opt]);
				$post_id=$wpdb->escape($espost[$chk_postid]);
				$weight=$wpdb->escape($espost[$chk_weight]);
				$dlchking=$espost['eshopident_'.$i];
				//add opt sets
				if(isset($_SESSION['eshopcart'.$blog_id][$dlchking]['optset'])){
					$data['optset']=$_SESSION['eshopcart'.$blog_id][$dlchking]['optset'];
					$data['addoprice']=$addoprice;
					$data=eshop_parse_optsets($data);
					$optset=$data['optset'];
					$addoprice=$data['addoprice'];
				}else{
					$optset='';
				}
				$optset=$wpdb->escape($optset);
				//end
				$thechk=$_SESSION['eshopcart'.$blog_id][$dlchking]['option'];
				$option_id=$wpdb->escape($thechk);
				if(strpos($thechk,' ')===true){
					$edown=explode(' ',$thechk);
					$edl=$edown[1];
				}else{
					$edl=$thechk;
				}
				$eshop_product=maybe_unserialize(get_post_meta( $post_id, '_eshop_product',true ));
				$dlchk='';
				
				if(isset($eshop_product['products'][$edl]['download']))
					$dlchk=$eshop_product['products'][$edl]['download'];
				
				if($dlchk!=''){
					//there are downloads.
					$queryitem=$wpdb->query("INSERT INTO $itemstable
							(checkid, item_id,item_qty,item_amt,tax_rate,tax_amt,optname,post_id,option_id,down_id,optsets,weight)values(
									'$checkid','$item_id','$item_qty','$item_amt', '$tax_rate', '$tax_amt',
									'$optname','$post_id','$option_id',
									'$dlchk','$optset','$weight');");
				
									$wpdb->query("UPDATE $detailstable set downloads='yes' where checkid='$checkid'");
									//add to download orders table
									$dloadtable=$wpdb->prefix.'eshop_download_orders';
									//$email,$checkid already set
									$producttable=$wpdb->prefix.'eshop_downloads';
					$grabit=$wpdb->get_row("SELECT id,title, files FROM $producttable where id='$dlchk'");
					$downloads = $eshopoptions['downloads_num'];
					$wpdb->query("INSERT INTO $dloadtable
					(checkid, title,purchased,files,downloads,code,email)values(
					'$checkid',
					'$grabit->title',
					NOW(),
					'$grabit->files',
					'$downloads',
					'$code',
					'$email');"
					);
			
				}else{
					$queryitem=$wpdb->query("INSERT INTO $itemstable
					(checkid, item_id,item_qty,item_amt,tax_rate,tax_amt,optname,post_id,option_id,optsets,weight)values(
							'$checkid','$item_id','$item_qty','$item_amt','$tax_rate', '$tax_amt',
							'$optname','$post_id','$option_id','$optset','$weight');");
				}
				$i++;
			
			}
			$postage=$wpdb->escape(str_replace(',', "", $espost['shipping_1']));
			$shiptaxamt=$shiptaxrate='';
			if(isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
				if(isset($_SESSION['shipping'.$blog_id]['cost']))
					$postage=$wpdb->escape(str_replace(',', "", $_SESSION['shipping'.$blog_id]['cost']));
			
				if(isset($_SESSION['shipping'.$blog_id]['tax']))
					$shiptaxamt=$wpdb->escape(str_replace(',', "", $_SESSION['shipping'.$blog_id]['tax']));
				
				if(isset($_SESSION['shipping'.$blog_id]['taxrate']))
					$shiptaxrate=$wpdb->escape(str_replace(',', "", $_SESSION['shipping'.$blog_id]['taxrate']));
			}
			$postage_name='';
			if(isset($_SESSION['eshopshiptype'.$blog_id])  && !eshop_only_downloads() && $_SESSION['eshopshiptype'.$blog_id]!='0'){
				$st=$_SESSION['eshopshiptype'.$blog_id]-1;
				$typearr=explode("\n", $eshopoptions['ship_types']);
				$postage_name=stripslashes(esc_attr($typearr[$st])).' ';
			}
			$postage_name.=__('Shipping','eshop') . '<br />' . apply_filters('usc_shipping_info_for_orders', $espost, $_SESSION);
			$querypostage=$wpdb->query("INSERT INTO  $itemstable
			(checkid, item_id,item_qty,item_amt,tax_rate,tax_amt,optsets)values(
			'$checkid',
			'$postage_name',
			'1',
			'$postage',
			'$shiptaxrate',
			'$shiptaxamt',
			'');");
			//update the discount codes used, and remove from remaining
			$disctable=$wpdb->prefix.'eshop_discount_codes';
			
			if(eshop_discount_codes_check()){
				if(isset($_SESSION['eshop_discount'.$blog_id]) && valid_eshop_discount_code($_SESSION['eshop_discount'.$blog_id])){
					$discvalid=$wpdb->escape($_SESSION['eshop_discount'.$blog_id]);
					do_action('eshop_discount_code_used',$checkid,$discvalid);
					$wpdb->query("UPDATE $disctable SET used=used+1 where disccode='$discvalid' limit 1");
		
					$remaining=$wpdb->get_var("SELECT remain FROM $disctable where disccode='$discvalid' && dtype!='2' && dtype!='5' limit 1");
				
					//reduce remaining
				if(is_numeric($remaining) && $remaining!='')
					$wpdb->query("UPDATE $disctable SET remain=remain-1 where disccode='$discvalid' limit 1");
				}
			}
					
			do_action('eshoporderhandle',$espost,$checkid);
		
			if($eshopoptions['status']!='live'){
				echo "<p class=\"testing\"><strong>".__('Test Mode &#8212; No money will be collected. This page will not auto redirect in test mode.','eshop')."</strong></p>\n";
			}
		}
	}
}