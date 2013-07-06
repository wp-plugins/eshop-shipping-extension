<?php defined('ABSPATH') or die("No direct access allowed");

class USC_override_eShop_Display_Cart 
{

	function __construct()
	{
		// NOOP
	}
	

	function do_override($shopcart, $change, $eshopcheckout, $pzone='', $shiparray='')
	{
		//The cart display.
		global $wpdb, $blog_id,$eshopoptions;
		if(!isset($_SESSION['shipping'.$blog_id]) || !is_array($_SESSION['shipping'.$blog_id])) $_SESSION['shipping'.$blog_id]=array();
		if($pzone=='widget'){
			$pzone='';
			$iswidget='w';
		}else{
			$iswidget='';
		}
		$echo ='';
		$check=0;
		$sub_total=0;
		$tempshiparray=array();
		//this checks for an empty cart, may not be required but leaving in just in case.
		$eshopcartarray=$_SESSION['eshopcart'.$blog_id];
		
		if($change==true){
			if(isset($_SESSION['eshop_discount'.$blog_id]))
				unset($_SESSION['eshop_discount'.$blog_id]);
		}
		
		foreach ($eshopcartarray as $productid => $opt){
			if(is_array($opt)){
				foreach($opt as $qty){
					$check=$check+$qty;
				}
			}
		}
		//therefore if cart exists and has products
		if($check > 0){
			//global $final_price, $sub_total;
			// no fieldset/legend added - do we need it?
			if ($change == 'true'){
				$echo.= '<form action="'.get_permalink($eshopoptions['cart']).'" method="post" id="eshopcartform" class="eshop eshopcart">';
			}
			$echo.= '<table class="eshop cart" summary="'.__('Shopping cart contents overview','eshop').'">
			<caption>'.__('Shopping Cart','eshop').'</caption>
			<thead>
			<tr class="thead">';
			$echo .='<th id="cartItem'.$iswidget.'" class="nb">'.__('Item Description','eshop').'</th>
			<th id="cartQty'.$iswidget.'" class="bt">'.__('<abbr title="Quantity">Qty</abbr>','eshop').'</th>';
				
			$totalstring = __('Sub-Total','eshop');
				
			$echo .= '<th id="cartTotal'.$iswidget.'" class="btbr">'.$totalstring.'</th>';
			$etax = $eshopoptions['etax'];
						
			if(($pzone!='' && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1')|| ('yes' == $eshopoptions['downloads_only'] && isset($etax['unknown']) && $etax['unknown']!='')){
				$echo .= '<th id="carttax" class="bt">'.__('Tax %','eshop').'</th>
				<th id="carttaxamt" class="btbr">'.__('Tax Amt','eshop').'</th>';
			}
			if($iswidget=='' && $change == 'true'){
				$eshopdeleteheaderimage=apply_filters('eshop_delete_header_image',WP_PLUGIN_URL.'/eshop/no.png');
				$echo.= '<th id="cartDelete" class="btbr"><img src="'.$eshopdeleteheaderimage.'" alt="'.__('Delete','eshop').'" title="'.__('Delete','eshop').'" /></th>';
			}
			$echo .= '</tr></thead><tbody>';
			//display each item as a table row
			$calt=0;
			$shipping=0;
			$totalweight=0;
			$taxtotal=0;
			$currsymbol=$eshopoptions['currency_symbol'];
			$eshopcartarray=$_SESSION['eshopcart'.$blog_id];
			foreach ($eshopcartarray as $productid => $opt){
				$addoprice=0;
				if(is_array($opt)){
					$key=$opt['option'];
					$calt++;
					$alt = ($calt % 2) ? '' : ' class="alt"';
					$echo.= "\n<tr".$alt.">";
					//do the math for weight
					$eshop_product=maybe_unserialize(get_post_meta( $opt['postid'], '_eshop_product',true ));
					$eimg='';
					/* image insertion */
					if( is_numeric($eshopoptions['image_in_cart']) || (isset($eshopoptions['widget_cart_type']) && $eshopoptions['widget_cart_type']<='1' && $iswidget=='w' ) ){
						$imgsize=$eshopoptions['image_in_cart'];
						if($iswidget=='w'){
							$imgsize=$eshopoptions['widget_cart_img'];
							if($imgsize=='') $imgsize=100;
						}
						$w=get_option('thumbnail_size_w');
						$h=get_option('thumbnail_size_h');
						if($imgsize!=''){
							$w=round(($w*$imgsize)/100);
							$h=round(($h*$imgsize)/100);
						}
						if (has_post_thumbnail( $opt['postid'] ) ) {
							$eimg='<a class="itemref" href="'.get_permalink($opt['postid']).'">'.get_the_post_thumbnail( $opt['postid'], array($w, $h)).'</a>'."\n";
						}else{
							$eimage=eshop_files_directory();
							$eshopnoimage=apply_filters('eshop_no_image',$eimage['1'].'noimage.png');
							$eimg='<a class="itemref" href="'.get_permalink($opt['postid']).'"><img src="'.$eshopnoimage.'" height="'.$h.'" width="'.$w.'" alt="" /></a>'."\n";
						}
					}
					/* end */
					//opsets
		
					if(isset($opt['optset'])){
						$data['optset']=$opt['optset'];
						$data['addoprice']=$addoprice;
						$data=eshop_parse_optsets($data);
						$optset='<span class="eshopoptsets">'.$data['optset'].'</span>';
						$addoprice=$data['addoprice'];
					}else{
						$optset='';
					}
					$echooptset=apply_filters('eshop_optset_cart_display',$optset);
					if( !has_filter( 'eshop_optset_cart_display') ) $echooptset=nl2br($optset);
					$textdesc='<a href="'.get_permalink($opt['postid']).'">'.stripslashes($opt["pname"]).' <span class="eshopidetails">('.$opt['pid'].' : '.stripslashes($opt['item']).')</span></a>'.$echooptset;
					$echoimg=$eimg;
					if(isset($eshopoptions['widget_cart_type']) && $eshopoptions['widget_cart_type']=='1' && $iswidget=='w'  ){
						$textdesc='';
					}
					if($iswidget=='w' && isset($eshopoptions['widget_cart_type']) && $eshopoptions['widget_cart_type']=='2'){
						$echoimg='';
					}
					$echo.= '<td id="prod'.$calt.$iswidget.'" headers="cartItem'.$iswidget.'" class="leftb cartitem">'.$echoimg.$textdesc.'</td>'."\n";
		
					$echo.= "<td class=\"cqty lb\" headers=\"cartQty$iswidget prod".$calt.$iswidget."\">";
					// if we allow changes, quantities are in text boxes
					if ($change == true){
						//generate acceptable id
						//$toreplace=array(" ","-","$","\r","\r\n","\n","\\","&","#",";");
						$accid=$productid.$key;
						$accid='c'.md5($accid);//str_replace($toreplace, "", $accid);
						$echo.= '<label for="'.$accid.$iswidget.'"><input class="short" type="text" id="'.$accid.$iswidget.'" name="'.$productid.'['.$key.']" value="'.$opt["qty"].'" size="3" maxlength="3" /></label>';
					}else{
						$echo.= $opt["qty"];
					}
					/* DISCOUNT */
					$opt["price"]+=$addoprice;
					if(is_discountable(calculate_total())>0){
						$discount=is_discountable(calculate_total())/100;
						$disc_line= round($opt["price"]-($opt["price"] * $discount), 2);
					}
					$eline = $line_total = $opt["price"] * $opt["qty"];
					if(isset($disc_line))
						$eline = $disc_line*$opt["qty"];
					$echo.= "</td>\n<td headers=\"cartTotal$iswidget prod".$calt.$iswidget."\" class=\"amts\">".sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($eline,__('2','eshop')))."</td>\n";
						
					//TAX
					$etax = $eshopoptions['etax'];
					if(($pzone!='' && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1') || ('yes' == $eshopoptions['downloads_only'] && isset($etax['unknown']) && $etax['unknown']!='')){						
						if(isset($eshop_product['products'][$opt['option']]['tax']) && $eshop_product['products'][$opt['option']]['tax']!='' && $eshop_product['products'][$opt['option']]['tax']!='0'){
							if($pzone!='')
								$taxrate=eshop_get_tax_rate($eshop_product['products'][$opt['option']]['tax'], $pzone);
							else
								$taxrate=$etax['unknown'];
							$ttotax=$line_total;
							if(isset($disc_line))
								$ttotax=$disc_line * $opt["qty"];
							$taxamt=round(($ttotax * $taxrate)/100, 2);
							$echo.= '<td>'.$taxrate.'</td><td>'.sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($taxamt,__('2','eshop'))).'</td>';
							$taxtotal += $taxamt;
							$_SESSION['eshopcart'.$blog_id][$productid]['tax_rate']=$taxrate;
							$_SESSION['eshopcart'.$blog_id][$productid]['tax_amt']=$taxamt;
						}else{
							$echo.= '<td></td><td></td>';
						}
		
					}
					//
						
					if($iswidget=='' && $change == 'true'){
						$eshopdeleteimage=apply_filters('eshop_delete_image',WP_PLUGIN_URL.'/eshop/no.png');
						$echo .='<td headers="cartDelete" class="deletecartitem"><label for="delete'.$productid.$iswidget.'" class="hide">'.__('Delete this item','eshop').'</label><input type="image" src="'.$eshopdeleteimage.'" id="delete'.$productid.$iswidget.'" name="eshopdeleteitem['.$productid.']" value="'.$opt["qty"].'" title="'.__('Delete this item','eshop').'"/></td>';
					}
					$echo .="</tr>\n";
					if(isset($disc_line))
						$sub_total+=$disc_line*$opt["qty"];
					else
						$sub_total+=$line_total;
					//weight
					if(isset($opt['weight']))
						$totalweight+=$opt['weight']*$opt['qty'];
				}
			}
			// display subtotal row - total for products only
			$disc_applied='';
			if(is_discountable(calculate_total())>0){
				$discount=is_discountable(calculate_total());
				$disc_applied='<small>('.sprintf(__('Including Discount of <span>%s%%</span>','eshop'),number_format_i18n(round($discount, 2),2)).')</small>';
			}
			if($iswidget==''  && $change == 'true')
				$emptycell='<td headers="cartDelete" class="eshopempty"></td>';
			else
				$emptycell='';
		
			if(($pzone!='' && isset($taxtotal) && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1') || ('yes' == $eshopoptions['downloads_only'] && isset($etax['unknown']) && $etax['unknown']!='')){
				$emptycell='<td headers="subtotal carttaxamt" class="amts lb" colspan="2">'.sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($taxtotal,__('2','eshop'))).'</td>';
			}
			$echo.= "<tr class=\"stotal\"><th id=\"subtotal$iswidget\" class=\"leftb\">".__('Sub-Total','eshop').' '.$disc_applied."</th><td headers=\"subtotal$iswidget cartTotal$iswidget\" class=\"amts lb\" colspan=\"2\">".sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($sub_total,__('2','eshop')))."</td>$emptycell</tr>\n";
		
				
				
			$final_price=$sub_total;
			$_SESSION['final_price'.$blog_id]=$final_price;
			// SHIPPING PRICE HERE
			$shipping=0;
			//$pzone will only be set after the checkout address fields have been filled in
			// we can only work out shipping after that point
			
			// USC: $pzone is set to '' if a state is missing. We really don't care about state zones anymore
			// so force $pzone to be non empty so we can see the shipping stuff.
			if ($_POST['eshop_shiptype'])
			{
				$pzone = 'USC-SHIPPING';
			} 
			
			if($pzone!='' || ('yes' == $eshopoptions['downloads_only'] && isset($etax['unknown']) && $etax['unknown']!='')){
				if($pzone!=''){
					//shipping for cart.
					if($eshopoptions['shipping_zone']=='country'){
						$table=$wpdb->prefix.'eshop_countries';
					}else{
						$table=$wpdb->prefix.'eshop_states';
					}
					$table2=$wpdb->prefix.'eshop_rates';
					switch($eshopoptions['shipping']){
						case '1'://( per quantity of 1, prices reduced for additional items )
							foreach ($shiparray as $nowt => $shipclass){
								//add to temp array for shipping
								if(!in_array($shipclass, $tempshiparray)) {
									if($shipclass!='F'){
										array_push($tempshiparray, $shipclass);
										$shipzone='zone'.$pzone;
										$shipcost = $wpdb->get_var("SELECT $shipzone FROM $table2 WHERE class='$shipclass' and items='1' and rate_type='shipping' limit 1");
										$shipping+=$shipcost;
									}
								}else{
									if($shipclass!='F'){
										$shipzone='zone'.$pzone;
										$shipcost = $wpdb->get_var("SELECT $shipzone FROM $table2 WHERE class='$shipclass'  and items='2' and rate_type='shipping' limit 1");
										$shipping+=$shipcost;
									}
								}
							}
							break;
						case '2'://( once per shipping class no matter what quantity is ordered )
							foreach ($shiparray as $nowt => $shipclass){
								if(!in_array($shipclass, $tempshiparray)) {
									array_push($tempshiparray, $shipclass);
									if($shipclass!='F'){
										$shipzone='zone'.$pzone;
										$shipcost = $wpdb->get_var("SELECT $shipzone FROM $table2 WHERE class='$shipclass' and items='1' and rate_type='shipping' limit 1");
										$shipping+=$shipcost;
									}
								}
							}
							break;
						case '3'://( one overall charge no matter how many are ordered )
							$shiparray=array_unique($shiparray);
							foreach ($shiparray as $nowt => $shipclass){
								if($shipclass!='F'){
									$shipzone='zone'.$pzone;
									$shipcost = $wpdb->get_var("SELECT $shipzone FROM $table2 WHERE class='A' and items='1' and rate_type='shipping' limit 1");
									$shipping+=$shipcost;
								}
							}
							break;
						case '4'://by weight/zone etc
							// USC Eshop Shipping Modules Customization
							//$totalweight
							// 							$shipzone='zone'.$pzone;
							// 							$shipcost=$wpdb->get_var("SELECT $shipzone FROM $table2 where weight <= '$totalweight' && class='$shiparray' and rate_type='ship_weight' order by weight DESC limit 1");
							
							
							$shipping += $_SESSION['usc_3rd_party_shipping'.$blog_id][$_POST['eshop_shiptype']]['price'];
							$shipping += apply_filters('usc_calc_additional_services',$_POST['eshop_shiptype'],$_POST['additional_shipping_services']);
							
							$_SESSION['eshopshiptype'.$blog_id] = $_POST['eshop_shiptype'];
							$shiparray = 0;
							// End USC Eshop Shipping Modules Customization
					}
					//display shipping cost
					//discount shipping?
					if(is_shipfree(calculate_total())  || eshop_only_downloads()) $shipping=0;

					
					$echo.= '<tr class="alt shippingrow"><th headers="cartItem'.$iswidget.'" id="scharge" class="leftb">';
					if($eshopoptions['shipping']=='4' && !eshop_only_downloads() && $shiparray!='0'){
						$eshopoptions['ship_types']=trim($eshopoptions['ship_types']);
						$typearr=explode("\n", $eshopoptions['ship_types']);
						//darn, had to add in unique to be able to go back a page
						$echo.=' <a href="'.get_permalink($eshopoptions['checkout']).'?eshoprand='.rand(2,100).'#shiplegend" title="'.__('Change Shipping','eshop').'">'.stripslashes(esc_attr($typearr[$shiparray-1])).'</a> ';
					}else{
						$echo .=__('Shipping','eshop');
					}
					if($eshopoptions['cart_shipping']!=''){
						$ptitle=get_post($eshopoptions['cart_shipping']);
						// 						$echo.=' <small>(<a href="'.get_permalink($eshopoptions['cart_shipping']).'">'.__($ptitle->post_title,'eshop').'</a>)</small>';
					}
		
					$echo.='</th>
					<td headers="cartItem scharge" class="amts lb" colspan="2">'.sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($shipping,__('2','eshop'))).'</td>';
					if($pzone!='' && isset($taxtotal) && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
						$taxrate=eshop_get_tax_rate($eshopoptions['etax']['shipping'], $pzone);
						$ttotax=$shipping;
						$taxamt=round(($ttotax * $taxrate)/100, 2);
						$taxtext = '';
						if($taxamt > '0.00')
							$taxtext = sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($taxamt,__('2','eshop')));
						$echo.= '<td>'.$taxrate.'</td><td>'.$taxtext.'</td>';
						$shiptax=$taxamt;
						$_SESSION['shipping'.$blog_id]['tax']=$shiptax;
						$_SESSION['shipping'.$blog_id]['taxrate']=$taxrate;
		
					}
					$echo .= '</tr>';
					$_SESSION['shipping'.$blog_id]['cost']=$shipping;
					$final_price=$sub_total+$shipping;
					$_SESSION['final_price'.$blog_id]=$final_price;
				}
				$excltax = '';
				if(isset($taxtotal) && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
					$excltax = __('(excl.tax)','eshop');
				}
		
				$echo.= '<tr class="total"><th id="cTotal'.$iswidget.'" class="leftb">'.__('Total Order Charges','eshop')."</th>\n<td headers=\"cTotal$iswidget cartTotal$iswidget\"  colspan=\"2\" class = \"amts lb\"><strong>".sprintf( __('%1$s%2$s <span>%3$s</span>','eshop'), $currsymbol, number_format_i18n($final_price, __('2','eshop')),$excltax)."</strong></td>";
				if(isset($shiptax) && isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
					$withtax = $final_price + $shiptax + $taxtotal;
				}
				if('yes' == $eshopoptions['downloads_only'] && isset($etax['unknown']) && $etax['unknown']!=''){
					$withtax = $final_price + $taxtotal;
				}
				if(isset($eshopoptions['tax']) && $eshopoptions['tax']=='1'){
					$echo.= '<td headers="taxtotal" class="taxttotal amts lb" colspan="2"><strong>'.sprintf( __('%1$s%2$s <span>%3$s</span>','eshop'), $currsymbol, number_format_i18n($withtax,__('2','eshop')), __('(incl.tax)','eshop')).'</strong></td>';
				}
				$echo .= "</tr>";
			}
		
			$echo.= "</tbody></table>\n";
			// display unset/update buttons
			if($change == true){
				$echo.= "<div class=\"cartopt\"><input type=\"hidden\" name=\"save\" value=\"true\" />\n<input type=\"hidden\" name=\"eshopnon\" value=\"set\" />\n";
				$echo .= wp_nonce_field('eshop_add_product_cart','_wpnonce',true,false);
				$echo.= "<p><label for=\"update\"><input type=\"submit\" class=\"button\" id=\"update\" name=\"update\" value=\"".__('Update Cart','eshop')."\" /></label>";
				$echo.= "<label for=\"unset\"><input type=\"submit\" class=\"button\" id=\"unset\" name=\"unset\" value=\"".__('Empty Cart','eshop')."\" /></label></p>\n";
				$echo.= "</div>\n";
			}
			if ($change == 'true'){
				$echo.= "</form>\n";
			}
		}else{
			//if cart is empty - display a message - this is only a double check and should never be hit
			$echo.= "<p class=\"eshoperror error\">".__('Your shopping cart is currently empty.','eshop')."</p>\n";
		}
		if($eshopoptions['status']!='live'){
			$echo ="<p class=\"testing\"><strong>".__('Test Mode &#8212; No money will be collected.','eshop')."</strong></p>\n".$echo;
		}
		if(isset($_SESSION['eshop_discount'.$blog_id]) && valid_eshop_discount_code($_SESSION['eshop_discount'.$blog_id])){
			$echo .= '<p class="eshop_dcode">'.sprintf(__('Discount Code <span>%s</span> has been applied to your cart.','eshop'),$_SESSION['eshop_discount'.$blog_id]).'</p>'."\n";
		}
		//test
		if(isset($totalweight))
			$_SESSION['eshop_totalweight'.$blog_id]['totalweight']=$totalweight;
			
			
		if($iswidget=='w'){
			$echo.= '<br /><a class="cartlink" href="'.get_permalink($eshopoptions['cart']).'">'.__('Edit Cart','eshop').'</a>';
			$echo .='<br /><a class="checkoutlink" href="'.get_permalink($eshopoptions['checkout']).'">'.__('Checkout','eshop').'</a>';
		}
		
		return $echo;
	}
}