<?php

/*
  Plugin Name: WPEC Bulk Category Pricing
  Plugin URI: http://zao.is/
  Description: Adds bulk category pricing to WPEC.  Product categories can be select as 'bulk pricing', quantity threshold specified, and discount amount set.  Then during checkout, if the set amount of items from that category are in the cart, the indicated discount is applied to each item.
  Author: Zao
  Version: 1.0.2
  Author URI: http://www.zao.is
  License: GPLv2
 */

class WPEC_Bulk_Category_Pricing {

    private $discount = 0;

    public function __construct() {
        add_action( 'wpsc_product_category_add_form_fields' , array( $this, 'category_forms_add' ) );
        add_action( 'wpsc_product_category_edit_form_fields', array( $this, 'category_forms_edit' ) );
        add_action( 'created_wpsc_product_category'         , array( $this, 'category_forms_save' ), 11, 2 );
        add_action( 'edited_wpsc_product_category'          , array( $this, 'category_forms_save' ), 11, 2 );
        add_action( 'init'                                  , array( $this, 'refresh_cart' )       , 12 );
        add_filter( 'wpsc_price'                            , array( $this, 'modify_price' )       , 11, 2 );
    }

    public function category_forms_add() {

        ?>

        <div id="poststuff" class="postbox">
                <h3 class="hndle"><?php _e( 'Bulk Pricing Settings', 'wpsc-bulk-category' ); ?></h3>
                <div class="inside">
                        <table class='category_forms'>
                            <?php
                                if ( ! isset( $category['term_id'] ) )
                                    $category['term_id'] = '';

                                $use_bulk_pricing = wpsc_get_categorymeta( (int)$category['term_id'], 'use_bulk_pricing' );
                                $bulk_pricing_threshold = wpsc_get_categorymeta( (int)$category['term_id'], 'bulk_pricing_threshold' );
                                $bulk_pricing_discount = wpsc_get_categorymeta( (int)$category['term_id'], 'bulk_pricing_discount' );
                            ?>
                            <tr>
                                <td>
                                        <?php _e( 'This category uses bulk pricing.', 'wpsc-bulk-category' ); ?>
                                </td>
                                <td>
                                    <input type="radio" value='1' name="use_bulk_pricing" <?php checked( '1', $use_bulk_pricing ); ?> /><?php _e( 'Yes', 'wpsc-bulk-category' ); ?><br />
                                    <input type="radio" value='0' name="use_bulk_pricing" <?php checked( '0', $use_bulk_pricing ); ?> /><?php _e( 'No', 'wpsc-bulk-category' ); ?>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                        <?php _e( 'Bulk pricing threshold.  Specify how many products in this category need to be in the shopping cart before the discount is applied.', 'wpsc-bulk-category' ); ?>
                                </td>
                                <td>
                                     <input type="text" size="6" value='<?php echo $bulk_pricing_threshold; ?>' name="bulk_pricing_threshold" />
                                </td>
                            </tr>

                            <tr>
                                <td>
                                        <?php _e( 'Bulk pricing discount.  Specify, in specific currency (e.g. 1.99) what the discount per product should be.', 'wpsc-bulk-category' ); ?>
                                </td>
                                <td>
                                     <input type="text" size="6" value='<?php echo $bulk_pricing_discount; ?>' name="bulk_pricing_discount" />
                                </td>
                            </tr>
                        </table>
                </div>
        </div>
    <?php

    }

    public function category_forms_edit() {
        
            $category_id = absint( $_REQUEST['tag_ID'] );
            $use_bulk_pricing = wpsc_get_categorymeta( $category_id, 'use_bulk_pricing' );
            $bulk_pricing_threshold = wpsc_get_categorymeta( $category_id, 'bulk_pricing_threshold' );
            $bulk_pricing_discount = wpsc_get_categorymeta( $category_id, 'bulk_pricing_discount' );

        ?>
        <tr>
            <td colspan="2">
                <h3><?php _e( 'Bulk Category Settings', 'wpsc-bulk-category' ); ?></h3>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
        <label><?php _e( 'This category uses bulk pricing.', 'wpsc-bulk-category' ); ?></label>
            </th>
            <td>
                <input type="radio" class="wpsc_cat_box" value='1' name="use_bulk_pricing" <?php checked( '1', $use_bulk_pricing ); ?> /><?php _e( 'Yes', 'wpsc-bulk-category' ); ?>
                <input type="radio" class="wpsc_cat_box" value='0' name="use_bulk_pricing" <?php checked( '0', $use_bulk_pricing ); ?> /><?php _e( 'No', 'wpsc-bulk-category' ); ?>
                <br />
      </td>
    </tr>

        <tr class="form-field">
            <th scope="row" valign="top">
        <label><?php _e( 'Bulk pricing threshold.  Specify how many products in this category need to be in the shopping cart before the discount is applied.', 'wpsc-bulk-category' ); ?></label>
            </th>
            <td>
                <input type="text" class="wpsc_cat_image_size" size="6" value='<?php echo $bulk_pricing_threshold; ?>' name="bulk_pricing_threshold" />
                <br />
            </td>
    </tr>

        <tr class="form-field">
            <th scope="row" valign="top">
        <label><?php _e( 'Bulk pricing discount.  Specify, in specific currency (e.g. 1.99) what the discount per product should be.', 'wpsc-bulk-category' ); ?></label>
            </th>
            <td>
                <input type="text" class="wpsc_cat_image_size" size="6" value='<?php echo $bulk_pricing_discount; ?>' name="bulk_pricing_discount" />
                <br />
            </td>
    </tr>

        <?php

    }

    public function category_forms_save( $category_id, $tt_id ) {
        
        if( ! empty( $_POST ) ) {

            extract( $_POST );
            
            wpsc_update_categorymeta( $category_id, 'use_bulk_pricing', $use_bulk_pricing );
            wpsc_update_categorymeta( $category_id, 'bulk_pricing_threshold', $bulk_pricing_threshold );
            wpsc_update_categorymeta( $category_id, 'bulk_pricing_discount', $bulk_pricing_discount );

        }
        
    }

    /*
     * This function checks the categories in the cart, the quantities in each category, and whether or not the current
     * item is in a bulk category that has the qualified amount of items in the cart
     */

    private function is_item_bulk( $product_id = 0 ) {
        global $wpsc_cart, $wpdb;

        $bulk_categories = $wpdb->get_col( "SELECT object_id FROM `".WPSC_TABLE_META."` WHERE `meta_key` = 'use_bulk_pricing' AND meta_value = 1" );

        $category_ids = array_unique( $wpsc_cart->get_item_category_ids() );

        $bulk = false;

        foreach( $category_ids as $id ) {
            if( in_array( $id, $bulk_categories  ) )
                $bulk = true;
        }

        if( ! $bulk )
            return $bulk;

        //At this point, we know we have categories in the cart that are potentially eligible for bulk pricing.

        $eligible_products = array();

        foreach( $wpsc_cart->cart_items as $item ) {
            foreach( $item->category_id_list as $cid ) {
                if( in_array( $cid, $bulk_categories ) )
                    $eligible_products[$cid]['quantity'][$item->product_id] = $item->quantity;
            }
        }
        
        //Loop de doop

        foreach( $eligible_products as $cat => &$product ) {

            $bulk_price_threshold = wpsc_get_categorymeta( $cat, 'bulk_pricing_threshold' );
            
            if( array_sum( $product['quantity'] ) < $bulk_price_threshold )
                unset( $eligible_products[$cat] );
            else
                $product['discount'] = wpsc_get_categorymeta( $cat, 'bulk_pricing_discount' );
            
        }

        return $eligible_products;
    }



    public function modify_price( $price, $product_id ) {

        $object_terms = wp_get_object_terms( $product_id, 'wpsc_product_category' );

        //If is_item_bulk method is populated, it's populated with an array of the categories
        $eligible_products = $this->is_item_bulk( $product_id );
        
        //If no items in cart are in bulk categories, just return the price
        if( ! $eligible_products )
            return $price;
        
        //Loop through and subtract discount from eligible items
        foreach( $object_terms as $term ) {
          $price = $price - $eligible_products[$term->term_id]['discount'];
        }

       unset( $eligible_products );

       return $price;
    }


    public function refresh_cart() {
        global $wpsc_cart;

        if( is_admin() )
            return;
       
        foreach( $wpsc_cart->cart_items as $key => &$value ) {
            $wpsc_cart->cart_items[$key]->refresh_item();
            $wpsc_cart->clear_cache();
        }
    }
}

new WPEC_Bulk_Category_Pricing;

?>