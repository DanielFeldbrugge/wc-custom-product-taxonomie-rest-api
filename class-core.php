<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://danfield.eu
 * @since             1.0.1
 * @package           CustomProductTaxonomies
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Product Taxonomies to WC Rest API
 * Plugin URI:        https://danfield.eu
 * Description:       Adds Custom Product Taxonomies to WC Rest API
 * Version:           1.0.9
 * Author:            Daniel Feldbrugge
 * Author URI:        https://danfield.eu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       CustomProductTaxonomies
 * Domain Path:       /languages
 */

namespace CustomProductTaxonomies;

if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

/**
 * Class Core
 *
 * @package CustomProductTaxonomies
 */
class Core
{

    public $post_type = 'product';

    public $skip_tax = [
        'product_type',
        'product_visibility',
        'product_cat',
        'product_tag',
        'product_shipping_class',

    ];

    public $prefix_attr = 'pa_';

    function run()
    {
        $this->skip_tax = apply_filters('cpt_skiplist', $this->skip_tax);

        $this->post_type = apply_filters('cpt_post_type', $this->post_type);

        add_action('rest_api_init', [$this, 'register_custom_taxonomies_api']);


        add_action('init', [$this, 'custom_tax']);
    }

    function custom_tax(){

// Add new taxonomy, make it hierarchical like categories
//first do the translations part for GUI

        $labels = array(
            'name' => _x( 'Brands', 'taxonomy general name' ),
            'singular_name' => _x( 'Brand', 'taxonomy singular name' ),
            'search_items' =>  __( 'Search Brands' ),
            'all_items' => __( 'All Brands' ),
            'parent_item' => __( 'Parent Brand' ),
            'parent_item_colon' => __( 'Parent Brand:' ),
            'edit_item' => __( 'Edit Brand' ),
            'update_item' => __( 'Update Brand' ),
            'add_new_item' => __( 'Add New Brand' ),
            'new_item_name' => __( 'New Brand Name' ),
            'menu_name' => __( 'Brands' ),
        );

        $capabilities = array(
            'manage_terms'               => 'manage_woocommerce',
            'edit_terms'                 => 'manage_woocommerce',
            'delete_terms'               => 'manage_woocommerce',
            'assign_terms'               => 'manage_woocommerce',
        );

// Now register the taxonomy
        $args = array(
            'labels'                     => $labels,
            'show_in_rest'               => true,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => false,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'capabilities'               => $capabilities,


        );
        register_taxonomy( 'brands', array( 'product' ), $args );
        register_taxonomy_for_object_type( 'brands', 'product' );
    }

    function register_custom_taxonomies_api()
    {

        foreach (get_object_taxonomies($this->post_type) as $tax) {

            //If tax in skip list, then skip
            if (in_array($tax, $this->skip_tax)) {
                continue;
            }

            //If tax is attribute, then skip
            if (substr($tax, 0, strlen($this->prefix_attr)) === $this->prefix_attr) {
                continue;
            }

            //Use as attribute in WC API
            register_rest_field('product', $tax, array(
                'get_callback' => [$this, 'product_custom_taxonomy'],
                'schema' => null,
            ));
        }

    }

    function product_custom_taxonomy($post, $attr, $request, $object_type)
    {

        $terms = array();

        // Get terms
        foreach (wp_get_post_terms($post['id'], $attr) as $term) {
            $terms[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }

        return $terms;
    }

}

(new Core())->run();
