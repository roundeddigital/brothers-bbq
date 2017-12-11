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

$county = array('US');
// change the $fee to set the surcharge to a value to suit
$fee = 13.00;

if ( in_array( WC()->customer->get_shipping_country(), $county ) ) :
    $woocommerce->cart->add_fee( 'Handling Fee', $fee, true, 'standard' );
endif;
}
