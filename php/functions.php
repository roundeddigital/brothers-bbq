<?php
//
// Recommended way to include parent theme styles.
//  (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
//
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );
}
//
// Your code goes below
//

add_filter( 'woocommerce_ship_to_different_address_checked','__return_false' );

/**
 * Add a 1% surcharge to your cart / checkout
 * change the $percentage to set the surcharge to a value to suit
 * Uses the WooCommerce fees API
 *
 * Add to theme functions.php
 */
add_action( 'woocommerce_cart_calculate_fees','woocommerce_custom_surcharge' );
function woocommerce_custom_surcharge() {
  global $woocommerce;

	if ( is_admin() && ! defined( 'DOING_AJAX' ) )
		return;

	$percentage = 0.04;
	$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;
	$woocommerce->cart->add_fee( 'Processing Fee', $surcharge, true, '' );

}

add_action( 'woocommerce_cart_calculate_fees','wc_add_surcharge' );
function wc_add_surcharge() {
global $woocommerce;

if ( is_admin() && ! defined( 'DOING_AJAX' ) )
return;

// global $woocommerce;
$total_items=0;
$total_item_cards=0;
$items = $woocommerce->cart->get_cart();

foreach($items as $item => $values) {
	$total_items=$total_items+1;
	$card_id = $values['data']->get_id();
	if ($card_id == 67295 || $card_id == 67298 || $card_id == 67299 || $card_id == 67300) {
		// echo "Total Cards: ".$total_item_cards=($total_item_cards+1)."<br>";
    $total_item_cards=$total_item_cards+1;
	}
	// echo "Total Items ".$total_items."<br>";
	// echo $values['data']->get_id()."<br>";
	// $_product =  wc_get_product( $values['data']->get_id());
  // echo "<b>".$_product->get_title().'</b>  <br> Quantity: '.$values['quantity'].'<br>';
	// echo "<b>".$_product->get_shipping_class().'</b><br>';
  // echo "<b>".$_product->get_shipping_class_id().'</b><br>';

  // $price = get_post_meta($values['product_id'] , '_price', true);
  // echo "  Price: ".$price."<br>";

  // echo "Flat Rates: ".  get_option("woocommerce_flat_rates");
}

if ($total_items == $total_item_cards) {
	$fee = 0.00;
} else {
	// change the $fee to set the surcharge
	$fee = 13.00;
}

$county = array('US');

if ( in_array( WC()->customer->get_shipping_country(), $county ) ) :
    $woocommerce->cart->add_fee( 'Handling Fee', $fee, true, 'standard' );
endif;
}
