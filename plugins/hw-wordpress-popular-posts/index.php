<?php
/**
 * Module Name: Popular Posts
 * Module URI:
 * Description:
 * Version: 1.0
 * Author URI: http://hoangweb.com
 * Author: Hoangweb
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

//main plugin file
include_once ('hw-wordpress-popular-posts.php');
/**
 * Class HW_Module_PopularPosts
 */
class HW_Module_PopularPosts extends HW_Module {
    public function __construct() {

    }

    /**
     * @hook wp_enqueue_scripts
     */
    public function enqueue_scripts() {

    }

    /**
     * @hook admin_enqueue_scripts
     */
    public function admin_enqueue_scripts() {

    }
    public function print_head(){}
    public function print_footer(){}
}
add_action('hw_modules_load', 'HW_Module_PopularPosts::init');