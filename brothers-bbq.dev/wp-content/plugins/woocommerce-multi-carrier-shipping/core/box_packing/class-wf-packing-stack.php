<?php
class WF_Boxpack_Stack {

    private $boxes;
    private $items;
    private $packages;
    private $cannot_pack;

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
            //include_once( 'class-wc-boxpack-box.php' );
            //include_once( 'class-wc-boxpack-item.php' );
    }

    /**
     * clear_items function.
     *
     * @access public
     * @return void
     */
    public function clear_items() {
            $this->items = array();
    }

    /**
     * clear_boxes function.
     *
     * @access public
     * @return void
     */
    public function clear_boxes() {
            $this->boxes = array();
    }

    /**
     * add_item function.
     *
     * @access public
     * @return void
     */
    public function add_item( $length, $width, $height, $weight, $value = '', $meta = array() ) {
            $this->items[] = new WF_Boxpack_Item_Stack( $length, $width, $height, $weight, $value, $meta );
    }

    /**
     * add_box function.
     *
     * @access public
     * @param mixed $length
     * @param mixed $width
     * @param mixed $height
     * @param mixed $weight
     * @return void
     */
    public function add_box( $length, $width, $height, $weight = 0 ) {
            $new_box = new WF_Boxpack_Box_Stack( $length, $width, $height, $weight );
            $this->boxes[] = $new_box;
            return $new_box;
    }

    /**
     * get_packages function.
     *
     * @access public
     * @return void
     */
    public function get_packages() {
            return $this->packages ? $this->packages : array();
    }

    /**
     * pack function.
     *
     * @access public
     * @return void
     */
    public function pack() {
            try {
                    // We need items
                    if ( sizeof( $this->items ) == 0 ) {
                            throw new Exception( 'No items to pack!' );
                    }

                    // Clear packages
                    $this->packages = array();

                    // Order the boxes by volume
                    $this->boxes = $this->order_boxes( $this->boxes );

                    if ( ! $this->boxes ) {
                            $this->cannot_pack = $this->items;
                            $this->items       = array();
                    }

                    // Keep looping until packed
                    while ( sizeof( $this->items ) > 0 ) {
                            $this->items       = $this->order_items( $this->items );
                            $possible_packages = array();
                            $best_package      = '';

                            // Attempt to pack all items in each box
                            foreach ( $this->boxes as $box ) {
                                    
                                    $possible_packages[] = $box->pack_by_length( $this->items );
                                    $possible_packages[] = $box->pack_by_height( $this->items );
                                    $possible_packages[] = $box->pack_by_width( $this->items );

                            }

                            // Find the best success rate
                            $best_percent = 0;
                            foreach ( $possible_packages as $package ) 
                            {
                                    if ( $package->percent > $best_percent ) {
                                            $best_percent = $package->percent;
                                    }
                            }

                            if ( $best_percent == 0 ) {
                                    $this->cannot_pack = $this->items;
                                    $this->items       = array();
                            } else {
                                    // Get smallest box with best_percent
                                    $possible_packages = array_reverse( $possible_packages );

                                    foreach ( $possible_packages as $package ) {
                                            if ( $package->percent == $best_percent ) {
                                                    $best_package = $package;
                                                    break; // Done packing
                                            }
                                    }

                                    // Update items array
                                    $this->items = $best_package->unpacked;

                                    // Store package
                                    $this->packages[] = $best_package;
                            }
                    }

                    // Items we cannot pack (by now) get packaged individually
                    if ( $this->cannot_pack ) {
                            foreach ( $this->cannot_pack as $item ) {
                                    $package           = new stdClass();
                                    $package->id       = '';
                                    $package->weight   = (float) $item->get_weight();
                                    $package->length   = $item->get_length();
                                    $package->width    = $item->get_width();
                                    $package->height   = $item->get_height();
                                    $package->value    = $item->get_value();
                                    $package->unpacked = true;
                                    $this->packages[]  = $package;
                            }
                    }

            } catch (Exception $e) {

                    // Display a packing error for admins
                    if ( current_user_can( 'manage_options' ) ) {
                            echo 'Packing error: ',  $e->getMessage(), "\n";
                    }

    }
    }

    /**
     * Order boxes by weight and volume
     * $param array $sort
     * @return array
     */
    private function order_boxes( $sort ) {
            if ( ! empty( $sort ) ) {
                    uasort( $sort, array( $this, 'box_sorting' ) );
            }
            return $sort;
    }

    /**
     * Order items by weight and volume
     * $param array $sort
     * @return array
     */
    private function order_items( $sort ) {
            if ( ! empty( $sort ) ) {
                    uasort( $sort, array( $this, 'item_sorting' ) );
            }
            return $sort;
    }

    /**
     * order_by_volume function.
     *
     * @access private
     * @return void
     */
    private function order_by_volume( $sort ) {
            if ( ! empty( $sort ) ) {
                    uasort( $sort, array( $this, 'volume_based_sorting' ) );
            }
            return $sort;
    }

    /**
     * item_sorting function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function item_sorting( $a, $b ) {
            if ( $a->get_volume() == $b->get_volume() ) {
            if ( $a->get_weight() == $b->get_weight() ) {
                    return 0;
                }
                return ( $a->get_weight() < $b->get_weight() ) ? 1 : -1;
        }
        return ( $a->get_volume() < $b->get_volume() ) ? 1 : -1;
    }

    /**
     * box_sorting function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function box_sorting( $a, $b ) {
            if ( $a->get_volume() == $b->get_volume() ) {
            if ( $a->get_max_weight() == $b->get_max_weight() ) {
                    return 0;
                }
                return ( $a->get_max_weight() < $b->get_max_weight() ) ? 1 : -1;
        }
        return ( $a->get_volume() < $b->get_volume() ) ? 1 : -1;
    }

    /**
     * volume_based_sorting function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function volume_based_sorting( $a, $b ) {
            if ( $a->get_volume() == $b->get_volume() ) {
            return 0;
        }
        return ( $a->get_volume() < $b->get_volume() ) ? 1 : -1;
    }

}


/**
* WF_Boxpack_Box class.
*/
class WF_Boxpack_Box_Stack {

    /** @var string ID of the box - given to packages */
    private $id = '';

    /** @var float Weight of the box itself */
    private $weight;

    /** @var float Max allowed weight of box + contents */
    private $max_weight = 0;

    /** @var float Outer dimension of box sent to shipper */
    private $outer_height;

    /** @var float Outer dimension of box sent to shipper */
    private $outer_width;

    /** @var float Outer dimension of box sent to shipper */
    private $outer_length;

    /** @var float Inner dimension of box used when packing */
    private $height;

    /** @var float Inner dimension of box used when packing */
    private $width;

    /** @var float Inner dimension of box used when packing */
    private $length;

    /** @var float Dimension is stored here if adjusted during packing */
    private $packed_height;
    private $maybe_packed_height = null;

    /** @var float Dimension is stored here if adjusted during packing */
    private $packed_width;
    private $maybe_packed_width = null;

    /** @var float Dimension is stored here if adjusted during packing */
    private $packed_length;
    private $maybe_packed_length = null;

    /** @var float Volume of the box */
    private $volume;

    /** @var Array Valid box types which affect packing */
    private $valid_types = array( 'box', 'tube', 'envelope', 'packet' );

    /** @var string This box type */
    private $type = 'box';

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct( $length, $width, $height, $weight = 0 ) {
            $dimensions = array( $length, $width, $height );

            sort( $dimensions );

            $this->outer_length = $this->length = $dimensions[2];
            $this->outer_width  = $this->width  = $dimensions[1];
            $this->outer_height = $this->height = $dimensions[0];
            $this->weight       = $weight;
    }

    /**
     * set_id function.
     *
     * @access public
     * @param mixed $weight
     * @return void
     */
    public function set_id( $id ) {
            $this->id = $id;
    }

    /**
     * Set the volume to a specific value, instead of calculating it.
     * @param float $volume
     */
    public function set_volume( $volume ) {
            $this->volume = floatval( $volume );
    }

    /**
     * Set the type of box
     * @param string $type
     */
    public function set_type( $type ) {
            if ( in_array( $type, $this->valid_types ) ) {
                    $this->type = $type;
            }
    }

    /**
     * Get max weight.
     *
     * @return float
     */
    public function get_max_weight() {
            return floatval( $this->max_weight );
    }

    /**
     * set_max_weight function.
     *
     * @access public
     * @param mixed $weight
     * @return void
     */
    public function set_max_weight( $weight ) {
            $this->max_weight = $weight;
    }

    /**
     * set_inner_dimensions function.
     *
     * @access public
     * @param mixed $length
     * @param mixed $width
     * @param mixed $height
     * @return void
     */
    public function set_inner_dimensions( $length, $width, $height ) {
            $dimensions = array( $length, $width, $height );

            sort( $dimensions );

            $this->length = $dimensions[2];
            $this->width  = $dimensions[1];
            $this->height = $dimensions[0];
    }

    /**
     * See if an item fits into the box.
     *
     * @param object $item
     * @return bool
     */
    public function can_fit_by_length( $item ) {
            $can_fit = ( $this->get_length() >= $this->packed_length+$item->get_length() && $this->get_width() >= $item->get_width() && $this->get_height() >= $item->get_height() && $item->get_volume() < $this->get_volume() ) ? true : false;
            return $can_fit;
    }

    /**
     * See if an item fits into the box.
     *
     * @param object $item
     * @return bool
     */
    public function can_fit_by_width( $item ) {
            $can_fit = ( $this->get_length() >= $item->get_length() && $this->get_width() >= $this->packed_width+$item->get_width() && $this->get_height() >= $item->get_height() && $item->get_volume() < $this->get_volume() ) ? true : false;
            return $can_fit;
    }

    /**
     * See if an item fits into the box.
     *
     * @param object $item
     * @return bool
     */
    public function can_fit_by_height( $item ) {
            $can_fit = ( $this->get_length() >= $item->get_length() && $this->get_width() >= $item->get_width() && $this->get_height() >= $this->packed_height+$item->get_height() && $item->get_volume() < $this->get_volume() ) ? true : false;
            return $can_fit;
    }

    /**
     * Reset packed dimensions to originals
     */
    private function reset_packed_dimensions() {
            $this->packed_length = 0;
            $this->packed_width  = 0;
            $this->packed_height = 0;
    }

    /**
     * pack_by_length function.
     *
     * @access public
     * @param mixed $items
     * @return object Package
     */
    public function pack_by_length( $items ) {
            $packed        = array();
            $unpacked      = array();
            $packed_weight = (float) $this->get_weight();
            $packed_length = 0;
            $packed_value  = 0;
            $packed_volume  = 0;
            
            $this->reset_packed_dimensions();

            while ( sizeof( $items ) > 0 ) {
                    $item = array_shift( $items );

                    // Check dimensions
                    if ( ! $this->can_fit_by_length( $item ) ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check max weight
                    if ( ( $packed_weight + $item->get_weight() ) > $this->get_max_weight() && $this->get_max_weight() > 0 ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check volume
                    if ( ( $packed_length + $item->get_length() ) > $this->get_length() ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Pack
                    $packed[]      = $item;
                    $packed_length += $item->get_length();
                    $packed_weight += (float)$item->get_weight();
                    $packed_value  += $item->get_value();
                    $packed_volume  += $item->get_volume();
                    $this->packed_length  += $item->get_length();
                    

                    // Adjust dimensions if needed, after this item has been packed inside
                    if ( ! is_null( $this->maybe_packed_height ) ) {
                            $this->packed_height       = $this->maybe_packed_height;
                            $this->packed_length       = $this->maybe_packed_length;
                            $this->packed_width        = $this->maybe_packed_width;
                            $this->maybe_packed_height = null;
                            $this->maybe_packed_length = null;
                            $this->maybe_packed_width  = null;
                    }
            }

            // Get weight of unpacked items
            $unpacked_weight = 0;
            $unpacked_volume = 0;
            foreach ( $unpacked as $item ) {
                    $unpacked_weight += (float)$item->get_weight();
                    $unpacked_volume += $item->get_volume();
            }

            $package           = new stdClass();
            $package->id       = $this->id;
            $package->packed   = $packed;
            $package->unpacked = $unpacked;
            $package->weight   = $packed_weight;
            $package->volume   = $packed_volume;
            $package->length   = $this->get_outer_length();
            $package->width    = $this->get_outer_width();
            $package->height   = $this->get_outer_height();
            $package->value    = $packed_value;
            ///$package->volume_empty_percentage=($unpacked_volume /    ($packed_volume+$unpacked_volume) ) * 100;

            // Calculate packing success % based on % of weight and volume of all items packed
            $packed_weight_ratio = null;
            $packed_volume_ratio = null;

            if ( $packed_weight + $unpacked_weight > 0 ) {
                    $packed_weight_ratio = $packed_weight / ( $packed_weight + $unpacked_weight );
            }
            if ( $packed_volume + $unpacked_volume ) {
                    $packed_volume_ratio = $packed_volume / ( $packed_volume + $unpacked_volume );
            }

            if ( is_null( $packed_weight_ratio ) && is_null( $packed_volume_ratio ) ) {
                    // Fallback to amount packed
                    $package->percent = ( sizeof( $packed ) / ( sizeof( $unpacked ) + sizeof( $packed ) ) ) * 100;
            } elseif ( is_null( $packed_weight_ratio ) ) {
                    // Volume only
                    $package->percent = $packed_volume_ratio * 100;
            } elseif ( is_null( $packed_volume_ratio ) ) {
                    // Weight only
                    $package->percent = $packed_weight_ratio * 100;
            } else {
                    $package->percent = $packed_weight_ratio * $packed_volume_ratio * 100;
            }

            return $package;
    }
    /**
     * pack_by_height  function.
     *
     * @access public
     * @param mixed $items
     * @return object Package
     */
    public function pack_by_height( $items ) {
            $packed        = array();
            $unpacked      = array();
            $packed_weight = (float)$this->get_weight();
            $packed_height = 0;
            $packed_value  = 0;
            $packed_volume  = 0;

            $this->reset_packed_dimensions();

            while ( sizeof( $items ) > 0 ) {
                    $item = array_shift( $items );

                    // Check dimensions
                    if ( ! $this->can_fit_by_height( $item ) ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check max weight
                    if ( ( $packed_weight + $item->get_weight() ) > $this->get_max_weight() && $this->get_max_weight() > 0 ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check volume
                    if ( ( $packed_height + $item->get_height() ) > $this->get_height() ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Pack
                    $packed[]      = $item;
                    $packed_height += $item->get_height();
                    $packed_weight += (float)$item->get_weight();
                    $packed_value  += $item->get_value();
                    $packed_volume  += $item->get_volume();
                    $this->packed_height  += $item->get_height();
                            
                    // Adjust dimensions if needed, after this item has been packed inside
                    if ( ! is_null( $this->maybe_packed_height ) ) {
                            $this->packed_height       = $this->maybe_packed_height;
                            $this->packed_length       = $this->maybe_packed_length;
                            $this->packed_width        = $this->maybe_packed_width;
                            $this->maybe_packed_height = null;
                            $this->maybe_packed_length = null;
                            $this->maybe_packed_width  = null;
                    }
            }

            // Get weight of unpacked items
            $unpacked_weight = 0;
            $unpacked_volume = 0;
            foreach ( $unpacked as $item ) {
                    $unpacked_weight += (float)$item->get_weight();
                    $unpacked_volume += $item->get_volume();
            }

            $package           = new stdClass();
            $package->id       = $this->id;
            $package->packed   = $packed;
            $package->unpacked = $unpacked;
            $package->weight   = $packed_weight;
            $package->volume   = $packed_volume;
            $package->length   = $this->get_outer_length();
            $package->width    = $this->get_outer_width();
            $package->height   = $this->get_outer_height();
            $package->value    = $packed_value;

            // Calculate packing success % based on % of weight and volume of all items packed
            $packed_weight_ratio = null;
            $packed_volume_ratio = null;

            if ( $packed_weight + $unpacked_weight > 0 ) {
                    $packed_weight_ratio = $packed_weight / ( $packed_weight + $unpacked_weight );
            }
            if ( $packed_volume + $unpacked_volume ) {
                    $packed_volume_ratio = $packed_volume / ( $packed_volume + $unpacked_volume );
            }

            if ( is_null( $packed_weight_ratio ) && is_null( $packed_volume_ratio ) ) {
                    // Fallback to amount packed
                    $package->percent = ( sizeof( $packed ) / ( sizeof( $unpacked ) + sizeof( $packed ) ) ) * 100;
            } elseif ( is_null( $packed_weight_ratio ) ) {
                    // Volume only
                    $package->percent = $packed_volume_ratio * 100;
            } elseif ( is_null( $packed_volume_ratio ) ) {
                    // Weight only
                    $package->percent = $packed_weight_ratio * 100;
            } else {
                    $package->percent = $packed_weight_ratio * $packed_volume_ratio * 100;
            }

            return $package;
    }
    /**
     * pack_by_width function.
     *
     * @access public
     * @param mixed $items
     * @return object Package
     */
    public function pack_by_width( $items ) {
            $packed        = array();
            $unpacked      = array();
            $packed_weight = (float)$this->get_weight();
            $packed_width = 0;
            $packed_value  = 0;
            $packed_volume  = 0;

            $this->reset_packed_dimensions();

            while ( sizeof( $items ) > 0 ) {
                    $item = array_shift( $items );

                    // Check dimensions
                    if ( ! $this->can_fit_by_width( $item ) ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check max weight
                    if ( ( $packed_weight + $item->get_weight() ) > $this->get_max_weight() && $this->get_max_weight() > 0 ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Check volume
                    if ( ( $packed_width + $item->get_width() ) > $this->get_width() ) {
                            $unpacked[] = $item;
                            continue;
                    }

                    // Pack
                    $packed[]      = $item;
                    $packed_width+= $item->get_width();
                    $packed_weight += (float)$item->get_weight();
                    $packed_value  += $item->get_value();
                    $packed_volume  += $item->get_volume();
                    $this->packed_width+= $item->get_width();

                    // Adjust dimensions if needed, after this item has been packed inside
                    if ( ! is_null( $this->maybe_packed_height ) ) {
                            $this->packed_height       = $this->maybe_packed_height;
                            $this->packed_length       = $this->maybe_packed_length;
                            $this->packed_width        = $this->maybe_packed_width;
                            $this->maybe_packed_height = null;
                            $this->maybe_packed_length = null;
                            $this->maybe_packed_width  = null;
                    }
            }

            // Get weight of unpacked items
            $unpacked_weight = 0;
            $unpacked_volume = 0;
            foreach ( $unpacked as $item ) {
                    $unpacked_weight += (float)$item->get_weight();
                    $unpacked_volume += $item->get_volume();
            }

            $package           = new stdClass();
            $package->id       = $this->id;
            $package->packed   = $packed;
            $package->unpacked = $unpacked;
            $package->weight   = $packed_weight;
            $package->volume   = $packed_volume;
            $package->length   = $this->get_outer_length();
            $package->width    = $this->get_outer_width();
            $package->height   = $this->get_outer_height();
            $package->value    = $packed_value;

            // Calculate packing success % based on % of weight and volume of all items packed
            $packed_weight_ratio = null;
            $packed_volume_ratio = null;

            if ( $packed_weight + $unpacked_weight > 0 ) {
                    $packed_weight_ratio = $packed_weight / ( $packed_weight + $unpacked_weight );
            }
            if ( $packed_volume + $unpacked_volume ) {
                    $packed_volume_ratio = $packed_volume / ( $packed_volume + $unpacked_volume );
            }

            if ( is_null( $packed_weight_ratio ) && is_null( $packed_volume_ratio ) ) {
                    // Fallback to amount packed
                    $package->percent = ( sizeof( $packed ) / ( sizeof( $unpacked ) + sizeof( $packed ) ) ) * 100;
            } elseif ( is_null( $packed_weight_ratio ) ) {
                    // Volume only
                    $package->percent = $packed_volume_ratio * 100;
            } elseif ( is_null( $packed_volume_ratio ) ) {
                    // Weight only
                    $package->percent = $packed_weight_ratio * 100;
            } else {
                    $package->percent = $packed_weight_ratio * $packed_volume_ratio * 100;
            }

            return $package;
    }

    /**
     * get_volume function.
     * @return float
     */
    public function get_volume() {
            if ( $this->volume ) {
                    return $this->volume;
            } else {
                    return floatval( $this->get_height() * $this->get_width() * $this->get_length() );
            }
    }

    /**
     * get_height function.
     * @return float
     */
    public function get_height() {
            return $this->height;
    }

    /**
     * get_width function.
     * @return float
     */
    public function get_width() {
            return $this->width;
    }

    /**
     * get_width function.
     * @return float
     */
    public function get_length() {
            return $this->length;
    }

    /**
     * get_weight function.
     * @return float
     */
    public function get_weight() {
            return $this->weight;
    }

    /**
     * get_outer_height
     * @return float
     */
    public function get_outer_height() {
            return $this->outer_height;
    }

    /**
     * get_outer_width
     * @return float
     */
    public function get_outer_width() {
            return $this->outer_width;
    }

    /**
     * get_outer_length
     * @return float
     */
    public function get_outer_length() {
            return $this->outer_length;
    }

    /**
     * get_packed_height
     * @return float
     */
    public function get_packed_height() {
            return $this->packed_height;
    }

    /**
     * get_packed_width
     * @return float
     */
    public function get_packed_width() {
            return $this->packed_width;
    }

    /**
     * get_width get_packed_length.
     * @return float
     */
    public function get_packed_length() {
            return $this->packed_length;
    }
}


/**
* WF_Boxpack_Item_Stack class.
*/
class WF_Boxpack_Item_Stack {

    public $weight;
    public $height;
    public $width;
    public $length;
    public $volume;
    public $value;
    public $meta;

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct( $length, $width, $height, $weight, $value = '', $meta = array() ) {
            $dimensions = array( $length, $width, $height );

            sort( $dimensions );

            $this->length = $dimensions[2];
            $this->width  = $dimensions[1];
            $this->height = $dimensions[0];

            $this->volume = $width * $height * $length;
            $this->weight = $weight;
            $this->value  = $value;
            $this->meta   = $meta;
    }

    /**
     * get_volume function.
     *
     * @access public
     * @return void
     */
    function get_volume() {
            return $this->volume;
    }

    /**
     * get_height function.
     *
     * @access public
     * @return void
     */
    function get_height() {
            return $this->height;
    }

    /**
     * get_width function.
     *
     * @access public
     * @return void
     */
    function get_width() {
            return $this->width;
    }

    /**
     * get_width function.
     *
     * @access public
     * @return void
     */
    function get_length() {
            return $this->length;
    }

    /**
     * get_width function.
     *
     * @access public
     * @return void
     */
    function get_weight() {
            return (float)$this->weight;
    }

    /**
     * get_value function.
     *
     * @access public
     * @return void
     */
    function get_value() {
            return $this->value;
    }

    /**
     * get_meta function.
     *
     * @access public
     * @return void
     */
    function get_meta( $key = '' ) {
            if ( $key ) {
                    if ( isset( $this->meta[ $key ] ) ) {
                            return $this->meta[ $key ];
                    } else {
                            return null;
                    }
            } else {
                    return array_filter( (array) $this->meta );
            }
    }
}
