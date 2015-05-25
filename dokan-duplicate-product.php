<?php
/*
Plugin Name: Dokan Duplicate Product
Plugin URI: http://wedevs.com
Description: Product Duplicate add-on for Dokan
Version: 0.1
Author:  weDevs
Author URI: http://wedevs.com
License: GPL2
*/

/**
 * Copyright (c) YEAR Your Name (email: Email). All rights reserved.
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
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        // Loads frontend scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_filter( 'dokan_settings_fields', array( $this, 'dokan_duplicate_product_button_text' ) );

        add_action( 'woocommerce_single_product_summary', array( $this, 'add_to_my_product_button' ), 100 );
        add_filter( 'woocommerce_duplicate_product_capability', array( $this, 'add_duplicate_capability' ) );
        add_action( 'template_redirect', array( $this, 'product_clone_redirect' ) );

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
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {

    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {

    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'dokan_duplicate_product', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {

        /**
         * All styles goes here
         */
        wp_enqueue_style( 'dokan_duplicate_product-styles', plugins_url( 'assets/css/style.css', __FILE__ ), false, date( 'Ymd' ) );

        /**
         * All scripts goes here
         */
        wp_enqueue_script( 'dokan_duplicate_product-scripts', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ), false, true );


        /**
         * Example for setting up text strings from Javascript files for localization
         *
         * Uncomment line below and replace with proper localization variables.
         */
        // $translation_array = array( 'some_string' => __( 'Some string to translate', 'dokan_duplicate_product' ), 'a_value' => '10' );
        // wp_localize_script( 'dokan_duplicate_product-scripts', 'dokan_duplicate_product', $translation_array ) );

    }

    /**
     * Check if a user is seller
     *
     * @param array $settings_fields
     * @return array
     */
    public function dokan_duplicate_product_button_text( $settings_fields ) {
        $settings_fields['dokan_selling']['duplicate_button_txt'] = array(
                'name'    => 'duplicate_button_txt',
                'label'   => __( 'Duplicate Button Text', 'dokan' ),
                'desc'    => __( 'Product duplicate button text on single product page', 'dokan' ),
                'type'    => 'select',
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
        if ( current_user_can( 'dokandar' ) && ( $post->post_author != get_current_user_id() )  ) {       
            ?>
            <form method="post">
            <div class="dokan-form-group">
                <?php wp_nonce_field( 'dokan_duplicate_product', 'dokan_duplicate_product_nonce' ); ?>
                <input type="submit" name="add_to_my_store" id="add_to_my_store" class="single_add_to_cart_button button alt" value="<?php echo dokan_get_option( 'duplicate_button_txt', 'dokan_selling', 'Add To My Store' ); ?>"/>
            </div>
            </form>
            <?php
        }

    }

    /**
     * Manage Product Duplication Capability
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

        if ( isset( $_POST['add_to_my_store'] ) && wp_verify_nonce( $_POST['dokan_duplicate_product_nonce'], 'dokan_duplicate_product' ) ) {
            
            global $post;

            $wo_dup = new WC_Admin_Duplicate_Product();

            $clone_product_id =  $wo_dup->duplicate_product( $post );  

            wp_redirect( dokan_edit_product_url( $clone_product_id ) );
            exit;
        }

    }


} // Dokan_Duplicate_Product

$dokan_duplicate_product = Dokan_Duplicate_Product::init();