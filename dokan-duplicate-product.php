<?php
/*
Plugin Name: Dokan - Duplicate Product
Plugin URI: https://wedevs.com/products/dokan/product-duplicator/ ‎
Description: Product Duplicate add-on for Dokan
Version: 0.1
Author:  weDevs
Author URI: http://wedevs.com
License: GPL2
*/

/**
 * Copyright (c) 2015 weDevs (email: info@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Dokan_Duplicate_Product class
 *
 * @class Dokan_Duplicate_Product The class that holds the entire Dokan_Duplicate_Product plugin
 */
class Dokan_Duplicate_Product {

    /**
     * Constructor for the Dokan_Duplicate_Product class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {

        // Localize our plugin
        add_action( 'init', array( $this, 'is_dependency_installed' ) );
        add_action( 'init', array( $this, 'localization_setup' ) );

        add_filter( 'dokan_settings_fields', array( $this, 'dokan_duplicate_product_button_text' ) );

        add_action( 'woocommerce_single_product_summary', array( $this, 'add_to_my_product_button' ), 100 );
        add_filter( 'woocommerce_duplicate_product_capability', array( $this, 'add_duplicate_capability' ) );
        add_action( 'template_redirect', array( $this, 'product_clone_redirect' ) );
    }

    /**
     * Check if dependency is available
     * @since 1.0.0
     */
    function is_dependency_installed(){
        if ( !class_exists( 'WeDevs_Dokan' )){
            add_action( 'admin_notices', array ( $this, 'need_dependency_class' ) );
        }
    }

    /*
     * print error notice if dependency not active
     * @since 1.0.0
     */
    function need_dependency_class(){
        $error = sprintf( __( '<b>Dokan Product Duplicator </b> requires %sDokan plugin%s to be installed & activated!' , 'dokan-product-duplicator' ), '<a target="_blank" href="https://wedevs.com/products/plugins/dokan/">', '</a>' );

        $message = '<div class="error"><p>' . $error . '</p></div>';

        echo $message;
    }

    /**
     * Initializes the Dokan_Duplicate_Product() class
     *
     * Checks for an existing Dokan_Duplicate_Product() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Dokan_Duplicate_Product();
        }

        return $instance;
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'dokan-product-duplicator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Check if a user is seller
     *
     * @param array $settings_fields
     * @return array
     */
    public function dokan_duplicate_product_button_text( $settings_fields ) {
        $settings_fields['dokan_selling']['product_duplicate_check'] = array(
            'name'    => 'product_duplicate_check',
            'label'   => __( 'Allow Product duplicate', 'dokan-product-duplicator' ),
            'desc'    => __( 'Allow vendors to copy other vendors product to their store', 'dokan-product-duplicator' ),
            'type'    => 'checkbox',
            'default' => 'on',
        );
        $settings_fields['dokan_selling']['duplicate_button_txt'] = array(
            'name'    => 'duplicate_button_txt',
            'label'   => __( 'Duplicate Button Text', 'dokan-product-duplicator' ),
            'desc'    => __( 'Product duplicate button text on single product page', 'dokan-product-duplicator' ),
            'default' => 'Add To My Store',
            'type'    => 'text',
        );

        return $settings_fields;
    }

    /**
     * Set Product Duplication Button
     */
    public function add_to_my_product_button() {
        global $post;

        if ( current_user_can( 'dokandar' ) && ( $post->post_author != get_current_user_id() ) && dokan_is_seller_enabled( get_current_user_id() ) && dokan_get_option( 'product_duplicate_check', 'dokan_selling', 'on' ) == 'on' ) {

            if ( class_exists( 'Dokan_Product_Subscription' ) ) {
                $remaining_product = dps_user_remaining_product( get_current_user_id() );
                if ( $remaining_product == 0 ) {
                    if( Dokan_Product_Subscription::is_dokan_plugin() ) {
                        $permalink = dokan_get_navigation_url('subscription');
                    } else {
                        $page_id = dokan_get_option( 'subscription_pack', 'dokan_product_subscription' );
                        $permalink = get_permalink( $page_id );
                    }
                    // $page_id = dokan_get_option( 'subscription_pack', 'dokan_product_subscription' );
                    $info    = sprintf( __( 'Sorry! You can not add any product. Please <a href="%s">update your package</a>.', 'dokan-product-duplicator' ), $permalink );
                    echo "<p class='dokan-info'>" . $info . "</p>";
                } else {
                    ?>
                    <form method="post">
                        <?php echo "<p class='dokan-info'>". sprintf( __( 'You can add %d more product(s).', 'dokan-product-duplicator' ), $remaining_product ); ?>

                            <?php wp_nonce_field( 'dokan_duplicate_product', 'dokan_duplicate_product_nonce' ); ?>
                            <input type="submit" name="add_to_my_store" id="add_to_my_store" class="single_add_to_cart_button button alt" value="<?php echo dokan_get_option( 'duplicate_button_txt', 'dokan_selling', 'Add To My Store' ); ?>"/>
                            <style type="text/css">
                                #add_to_my_store { margin-top:10px; }
                            </style>
                        </p>
                    </form>
                    <?php
                }
            } else {
                ?>
                <form method="post">
                    <div class="dokan-form-group">
                        <?php wp_nonce_field( 'dokan_duplicate_product', 'dokan_duplicate_product_nonce' ); ?>
                        <input type="submit" name="add_to_my_store" id="add_to_my_store" class="single_add_to_cart_button button alt" value="<?php echo dokan_get_option( 'duplicate_button_txt', 'dokan_selling', 'Add To My Store' ); ?>"/>
                    </div>
                    <style type="text/css">
                        #add_to_my_store { margin-top:10px; }
                    </style>
                </form>
                <?php
            }
        }

    }

    /**
     * Manage Product Duplication Capability
     *
     * @param string
     */
    public function add_duplicate_capability( $role ) {
        $role = 'dokandar';
        return $role;
    }

    /**
     * Product Duplicate and Redirect to Edit Page
     */
    public function product_clone_redirect() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        if ( class_exists( 'Dokan_Product_Subscription' ) ) {
            if ( ! Dokan_Product_Subscription::can_post_product() ) {
                return;
            }
        }

        $errors = array();

        if ( isset( $_POST['add_to_my_store'] ) && wp_verify_nonce( $_POST['dokan_duplicate_product_nonce'], 'dokan_duplicate_product' ) ) {

            if ( apply_filters( 'dokan_can_add_product', $errors ) ) {
                return;
            }
            global $post;

            if ( ! $post ) {
                return;
            }

            $wo_dup = new WC_Admin_Duplicate_Product();

            // Compatibility for WC 3.0.0+
            if ( version_compare( WC_VERSION, '2.7', '>' ) ) {
                $product = wc_get_product( $post->ID );
                $clone_product =  $wo_dup->product_duplicate( $product );
                $clone_product_id =  $clone_product->get_id();
            } else {
                $clone_product_id =  $wo_dup->duplicate_product( $post );
            }

            $product_status = dokan_get_new_post_status();

            wp_update_post( array( 'ID' => intval( $clone_product_id ), 'post_status' => $product_status ) );

            wp_redirect( dokan_edit_product_url( $clone_product_id ) );
            exit;
        }
    }

} // Dokan_Duplicate_Product

$dokan_duplicate_product = Dokan_Duplicate_Product::init();