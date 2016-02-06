<?php
#root>plugins/tgmpa-require-plugins.php
/**
 * hoangweb modified thoese classes:
HW_TGM_Bulk_Installer
HW_TGM_Bulk_Installer_Skin
HW_TGM_Module_Activation
HW_TGMPA_List_Table
 *
 * doc: http://tgmpluginactivation.com/
 */

if ( ! class_exists( 'HW_TGM_Module_Activation' ) ) {
    /**
     * Automatic plugin installation and activation library.
     *
     * Creates a way to automatically install and activate plugins from within themes.
     * The plugins can be either pre-packaged, downloaded from the WordPress
     * Plugin Repository or downloaded from a private repository.
     *
     * @since 1.0.0
     *
     * @package TGM-Plugin-Activation
     * @author  Thomas Griffin <thomasgriffinmedia.com>
     * @author  Gary Jones <gamajo.com>
     */
    class HW_TGM_Module_Activation {

        /**
         * Holds a copy of itself, so it can be referenced by the class name.
         *
         * @since 1.0.0
         *
         * @var HW_TGM_Module_Activation
         */
        public static $instance;

        /**
         * Holds arrays of plugin details.
         *
         * @since 1.0.0
         *
         * @var array
         */
        public $plugins = array();

        /**
         * Name of the querystring argument for the admin page.
         *
         * @since 1.0.0
         *
         * @var string
         */
        public $menu = 'tgmpa-install-modules';

        /**
         * Default absolute path to folder containing pre-packaged plugin zip files.
         *
         * @since 2.0.0
         *
         * @var string Absolute path prefix to packaged zip file location. Default is empty string.
         */
        public $default_path = '';

        /**
         * Flag to show admin notices or not.
         *
         * @since 2.1.0
         *
         * @var boolean
         */
        public $has_notices = true;

        /**
         * Flag to determine if the user can dismiss the notice nag.
         *
         * @since 2.4.0
         *
         * @var boolean
         */
        public $dismissable = true;

        /**
         * Message to be output above nag notice if dismissable is false.
         *
         * @since 2.4.0
         *
         * @var string
         */
        public $dismiss_msg = '';

        /**
         * Flag to set automatic activation of plugins. Off by default.
         *
         * @since 2.2.0
         *
         * @var boolean
         */
        public $is_automatic = false;

        /**
         * Optional message to display before the plugins table.
         *
         * @since 2.2.0
         *
         * @var string Message filtered by wp_kses_post(). Default is empty string.
         */
        public $message = '';

        /**
         * Holds configurable array of strings.
         *
         * Default values are added in the constructor.
         *
         * @since 2.0.0
         *
         * @var array
         */
        public $strings = array();

        /**
         * Holds the version of WordPress.
         *
         * @since 2.4.0
         *
         * @var int
         */
        public $wp_version;

        /**
         * Adds a reference of this object to $instance, populates default strings,
         * does the tgmpa_init action hook, and hooks in the interactions to init.
         *
         * @since 1.0.0
         *
         * @see HW_TGM_Module_Activation::init()
         */
        public function __construct() {

            self::$instance = $this;

            $this->strings = array(
                'page_title'                     => __( 'Install Required Modules', 'tgmpa' ),
                'menu_title'                     => __( 'Install Modules', 'tgmpa' ),
                'installing'                     => __( 'Installing Module: %s', 'tgmpa' ),
                'oops'                           => __( 'Something went wrong.', 'tgmpa' ),
                'notice_can_install_required'    => _n_noop( 'This theme requires the following module: %1$s.', 'This theme requires the following modules: %1$s.' ),
                'notice_can_install_recommended' => _n_noop( 'This theme recommends the following modules: %1$s.', 'This theme recommends the following modules: %1$s.' ),
                'notice_cannot_install'          => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s module. Contact the administrator of this site for help on getting the module installed.', 'Sorry, but you do not have the correct permissions to install the %s modules. Contact the administrator of this site for help on getting the modules installed.' ),
                'notice_can_activate_required'   => _n_noop( 'The following required module is currently inactive: %1$s.', 'The following required modules are currently inactive: %1$s.' ),
                'notice_can_activate_recommended'=> _n_noop( 'The following recommended module is currently inactive: %1$s.', 'The following recommended modules are currently inactive: %1$s.' ),
                'notice_cannot_activate'         => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s module. Contact the administrator of this site for help on getting the module activated.', 'Sorry, but you do not have the correct permissions to activate the %s modules. Contact the administrator of this site for help on getting the modules activated.' ),
                'notice_ask_to_update'           => _n_noop( 'The following module needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following modules need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ),
                'notice_cannot_update'           => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s module. Contact the administrator of this site for help on getting the module updated.', 'Sorry, but you do not have the correct permissions to update the %s modules. Contact the administrator of this site for help on getting the modules updated.' ),
                'install_link'                   => _n_noop( 'Begin installing module', 'Begin installing modules' ),
                'activate_link'                  => _n_noop( 'Begin activating module', 'Begin activating modules' ),
                'return'                         => __( 'Return to Required Modules Installer', 'tgmpa' ),
                'dashboard'                      => __( 'Return to the dashboard', 'tgmpa' ),
                'plugin_activated'               => __( 'Module activated successfully.', 'tgmpa' ),
                'activated_successfully'         => __( 'The following hoangweb module was activated successfully:', 'tgmpa' ),
                'deactivated_successfully'         => __( 'The following hoangweb module was deactivated successfully:', 'tgmpa' ),
                'complete'                       => __( 'All modules installed and activated successfully. %1$s', 'tgmpa' ),
                'dismiss'                        => __( 'Dismiss this notice', 'tgmpa' ),
            );

            // Set the current WordPress version.
            global $wp_version;
            $this->wp_version = $wp_version;

            // Announce that the class is ready, and pass the object (for advanced use).
            do_action_ref_array( 'hw_tgmpa_init', array( $this ) );

            // When the rest of WP has loaded, kick-start the rest of the class.
            add_action( 'init', array( $this, 'init' ),10 );
            add_action('hw_build_custom_wp_menu', array(&$this, 'add_custom_submenu')); //build custom wp menu for modules manage page
        }

        /**
         * check this utilities for available
         * @return bool
         */
        public static function is_complete_load() {
            return count(HW_TGM_Module_Activation::$instance->plugins)? true: false;
        }
        /**
         * Initialise the interactions between this class and WordPress.
         *
         * Hooks in three new methods for the class: admin_menu, notices and styles.
         *
         * @since 2.0.0
         *
         * @see HW_TGM_Module_Activation::admin_menu()
         * @see HW_TGM_Module_Activation::notices()
         * @see HW_TGM_Module_Activation::styles()
         */
        public function init() {
            do_action( 'hw_tgmpa_register' );
            // After this point, the plugins should be registered and the configuration set.

            // Proceed only if we have plugins to handle.
            if ( $this->plugins ) {
                $sorted = array();

                foreach ( $this->plugins as $plugin ) {
                    $sorted[] = $plugin['name'];
                }

                array_multisort( $sorted, SORT_ASC, $this->plugins );

                #add_action( 'admin_menu', array( $this, 'admin_menu' ) );
                add_action( 'admin_menu', array( $this, 'hw_admin_menu' ) );    //by hoang
                add_action( 'admin_head', array( $this, 'dismiss' ) );
                add_filter( 'install_plugin_complete_actions', array( $this, 'actions' ) );
                add_action( 'switch_theme', array( $this, 'flush_plugins_cache' ) );

                // Load admin bar in the header to remove flash when installing plugins.
                if ( $this->is_tgmpa_page() ) {
                    remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
                    remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 );
                    add_action( 'wp_head', 'wp_admin_bar_render', 1000 );
                    add_action( 'admin_head', 'wp_admin_bar_render', 1000 );
                    #add_filter( 'views_options-general.php', array($this, 'get_views'));
                    $this->add_screen_options();
                }

                if ( $this->has_notices ) {
                    #add_action( 'admin_notices', array( $this, 'notices' ) );
                    #add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
                    #add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
                    add_action( 'switch_theme', array( $this, 'update_dismiss' ) );
                }

                // Setup the force activation hook.
                foreach ( $this->plugins as $plugin ) {
                    if ( isset( $plugin['force_activation'] ) && '1' == $plugin['force_activation'] ) {
                        add_action( 'admin_init', array( $this, 'force_activation' ) );
                        break;
                    }
                }

                // Setup the force deactivation hook.
                foreach ( $this->plugins as $plugin ) {
                    if ( isset( $plugin['force_deactivation'] ) && true === $plugin['force_deactivation'] ) {
                        add_action( 'switch_theme', array( $this, 'force_deactivation' ) );
                        break;
                    }
                }
            }

        }

        /**
         * return list of registered modules
         * @param $query
         * @return array
         */
        public static function &get_register_modules($query = array(), $relation = 'AND') {
            if(is_string($query) && $query) $query = array('slug' => $query);
            if(empty($query)) return HW_TGM_Module_Activation::$instance->plugins;
            $list = array();
            $relation= strtoupper($relation);
            foreach(HW_TGM_Module_Activation::$instance->plugins as $module) {
                $match=1;
                foreach ($query as $name => $val) {
                    if($name ==='core' && $val==1  ) {
                        if(!isset($module['force_activation']) && ($relation)=='AND') {
                            $match=0;break;
                        }
                        elseif(isset($module['force_activation']) && ($relation)=='OR') {
                            $match=1;break;
                        }
                        #continue;
                    }
                    if($name==='can_export' && $val==1  ) {
                        if(isset($module['can_export']) && !$module['can_export'] && ($relation)=='AND') {
                            $match=0;break;
                        }
                        elseif(isset($module['can_export']) && $module['can_export'] && ($relation)=='OR') {
                            $match=1;break;
                        }
                        #continue;
                    }
                    if($name === 'active' && $val==1 ) {
                        if(!hw_is_active_module($module['slug']) && ($relation=='AND')) {
                            $match=0;break;
                        }
                        elseif(hw_is_active_module($module['slug']) && $relation=='OR') {
                            $match=1;
                            break;
                        }

                    }
                    if($name === 'slug'  ) {
                        if($val !== $module['slug'] && $relation=='AND') {
                            $match=0;break;
                        }
                        elseif($val == $module['slug'] && $relation=='OR') {
                            $match=1;break;
                        }
                    }
                }
                if($match) $list[$module['slug']] = $module;
            }
            return $list;
        }
        /**
         * Handles calls to show plugin information via links in the notices.
         *
         * We get the links in the admin notices to point to the TGMPA page, rather
         * than the typical plugin-install.php file, so we can prepare everything
         * beforehand.
         *
         * WP doesn't make it easy to show the plugin information in the thickbox -
         * here we have to require a file that includes a function that does the
         * main work of displaying it, enqueue some styles, set up some globals and
         * finally call that function before exiting.
         *
         * Down right easy once you know how...
         *
         * @since 2.1.0
         *
         * @global string $tab Used as iframe div class names, helps with styling
         * @global string $body_id Used as the iframe body ID, helps with styling
         * @return null Returns early if not the TGMPA page.
         */
        public function admin_init() {

            if ( ! $this->is_tgmpa_page() ) {
                return;
            }

            if ( isset( $_REQUEST['tab'] ) && 'plugin-information' == $_REQUEST['tab'] ) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for install_plugin_information().

                wp_enqueue_style( 'plugin-install' );

                global $tab, $body_id;
                $body_id = $tab = 'plugin-information';

                install_plugin_information();

                exit;
            }

        }
        /**
         * This should return an array: array ( id => link )
         * Then in your prepare_items method you can retrieve the customvar method and alter your query according to it's value.
         * @return array
         */
        public function get_views(){
            $views = array();
            $current = ( !empty($_REQUEST['module_status']) ? $_REQUEST['module_status'] : 'all');

            //All link
            $class = ($current == 'all' ? ' class="current"' :'');
            $all_url = remove_query_arg('module_status');
            $views['all'] = "<a href='{$all_url }' {$class} >Tất cả</a>";

            //Foo link
            $active_url = add_query_arg('module_status','active');
            $class = ($current == 'foo' ? ' class="current"' :'');
            $views['active'] = "<a href='{$active_url}' {$class} >Kích hoạt</a>";

            //Bar link
            $inactive_url = add_query_arg('module_status','inactive');
            $class = ($current == 'bar' ? ' class="current"' :'');
            $views['inactive'] = "<a href='{$inactive_url}' {$class} >Không kích hoạt</a>";

            return $views;
        }

        /**
         * match module searching
         * @param $module
         * @param $s
         * @return bool
         */
        public static function match_module_search($module,$s) {
            foreach((array) $module as $value) {
                if(strpos(strtolower($value), strtolower($s)) !==false) return true;
            }
            return false;
        }
        /**
         * Enqueues thickbox scripts/styles for plugin info.
         *
         * Thickbox is not automatically included on all admin pages, so we must
         * manually enqueue it for those pages.
         *
         * Thickbox is only loaded if the user has not dismissed the admin
         * notice or if there are any plugins left to install and activate.
         *
         * @since 2.1.0
         */
        public function thickbox() {

            if ( ! get_user_meta( get_current_user_id(), 'hw_tgmpa_dismissed_notice', true ) ) {
                add_thickbox();
            }

        }

        /**
         * Adds submenu page under 'Appearance' tab.
         *
         * This method adds the submenu page letting users know that a required
         * plugin needs to be installed.
         *
         * This page disappears once the plugin has been installed and activated.
         *
         * @since 1.0.0
         *
         * @see HW_TGM_Module_Activation::init()
         * @see HW_TGM_Module_Activation::install_plugins_page()
         */
        /*public function admin_menu() {

            // Make sure privileges are correct to see the page
            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            $this->populate_file_path();

            foreach ( $this->plugins as $plugin ) {
                if ( ! is_plugin_active( $plugin['file_path'] ) ) {
                    add_theme_page(
                        $this->strings['page_title'],          // Page title.
                        $this->strings['menu_title'],          // Menu title.
                        'edit_theme_options',                  // Capability.
                        $this->menu,                           // Menu slug.
                        array( $this, 'install_plugins_page' ) // Callback.
                    );
                break;
                }
            }

        }*/
        /**
         * Adds submenu page under 'Appearance' tab.
         *
         * This method adds the submenu page letting users know that a required
         * plugin needs to be installed.
         *
         * This page disappears once the plugin has been installed and activated.
         *
         * @since 1.0.0
         *
         * @see HW_TGM_Module_Activation::init()
         * @see HW_TGM_Module_Activation::install_plugins_page()
         */
        public function hw_admin_menu() {

            // Make sure privileges are correct to see the page
            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            //modify by hoang
            $this->populate_file_path();

            foreach ( $this->plugins as $plugin ) {
                if ( $this->hw_is_exists_module( $plugin['file_path'] ) ) {
                    /*$hook = add_theme_page(
                        $this->strings['page_title'],          // Page title.
                        $this->strings['menu_title'],          // Menu title.
                        'edit_theme_options',                  // Capability.
                        $this->menu,                           // Menu slug.
                        array( $this, 'hw_install_module_page' ) // Callback.
                    );*/
                    add_submenu_page(
                        #'options-general.php',  // Page title.
                        'admin.php',  //HW_HOANGWEB_Settings admin.php?page=
                        $this->strings['page_title'],
                        $this->strings['menu_title'],   // Menu title.
                        'manage_options',   // Capability.
                        $this->menu,    // Menu slug. #'admin.php?page='. $this->menu,    //or
                        #'admin.php?page='. $this->menu,
                        array( $this, 'hw_install_module_page' ) // Callback.
                    );
                    HW_HOANGWEB_Settings::add_custom_submenu_page('module-activation', array(
                        'HW Modules',
                        'manage_options',
                        admin_url('admin.php?page='. $this->menu)
                    ));
                    break;
                }
            }
        }

        /**
         * @hook hw_build_custom_wp_menu
         * @param $tree
         */
        public function add_custom_submenu($tree) {
            $root_hoangweb = &$tree['root_hoangweb'];
            #$root_hoangweb[] = array();

        }
        /**
         * @hook plugins_loaded
         */
        private function add_screen_options() {
            HW_HOANGWEB::load_class('HW_Screen_Option');
            if(class_exists('HW_HELP', false)) {
                $hook_slug = HW_HELP::load_settings_page_hook_slug($this->menu,'');
            }
            else $hook_slug = '';
            $screen = HW_Screen_Option::get_instance(
                'modules_manager',
                $hook_slug
            );
            #HW_Screen_Option::get('modules-manager')->get_option('xx');
            $screen->add_options(array(
                'per_page' => array('label'=>'Số lượng/trang', 'default'=>'10'),
                // create more options http://w-shadow.com/blog/2010/06/29/adding-stuff-to-wordpress-screen-options/
                //'abc' => array('label'=>'ABC',  'default'=>'65' )
            ));
            $screen->addition_text('Hiển thị danh sách Modules Hoangweb.');
        }
        /**
         * check whether module is exists?
         * @param $name module name
         */
        public function hw_is_exists_module($name) {
            $plugin_path = HW_HOANGWEB_PLUGINS. '/'.$name;
            if(file_exists($plugin_path . '/'.$name.'.php') || file_exists($plugin_path . "/index.php")) {
                return true;
            }
            return false;
        }

        /**
         * install module page callback
         */
        public function hw_install_module_page(){#var_dump('hw_install_module_page');
            // Store new instance of plugin table in object.
            $plugin_table = new HW_TGMPA_List_Table();

            // Return early if processing a plugin installation action.
            if ( (isset( $_POST['action'] ) && in_array($_POST['action'], array('tgmpa-bulk-activate', 'tgmpa-bulk-deactivate') ) && $plugin_table->hw_process_bulk_actions()) || $this->hw_do_module_install() ) {
                return;
            }
            hw_get_modules(true);   //refresh plugins list
            ?>
            <div class="tgmpa wrap">
                <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
                <?php $plugin_table->prepare_items(); ?>

                <?php if ( isset( $this->message ) ) {
                    echo wp_kses_post( $this->message );
                } ?>

                <form id="tgmpa-plugins" action="" method="post">
                    <input type="hidden" name="tgmpa-page" value="<?php echo $this->menu; ?>" />
                    <?php
                    //searchbox
                    $plugin_table->search_box('Tìm kiếm', 'search_id');
                    ?>
                    <?php $plugin_table->display(); ?>
                </form>

            </div>
        <?php
        }
        /**
         * Echoes plugin installation form.
         *
         * This method is the callback for the admin_menu method function.
         * This displays the admin page and form area where the user can select to install and activate the plugin.
         *
         * @since 1.0.0
         *
         * @return null Aborts early if we're processing a plugin installation action
         */
        /*public function install_plugins_page() {

            // Store new instance of plugin table in object.
            $plugin_table = new HW_TGMPA_List_Table;

            // Return early if processing a plugin installation action.
            if ( isset( $_POST['action'] ) && 'tgmpa-bulk-install' == $_POST['action'] && $plugin_table->process_bulk_actions() || $this->do_plugin_install() ) {
                return;
            }

            ?>
            <div class="tgmpa wrap">
                <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
                <?php $plugin_table->prepare_items(); ?>

                <?php if ( isset( $this->message ) ) {
                    echo wp_kses_post( $this->message );
                } ?>

                <form id="tgmpa-plugins" action="" method="post">
                    <input type="hidden" name="tgmpa-page" value="<?php echo $this->menu; ?>" />
                    <?php $plugin_table->display(); ?>
                </form>

            </div>
            <?php

        }*/

        /**
         * Installs a plugin or activates a plugin depending on the hover
         * link clicked by the user.
         *
         * Checks the $_GET variable to see which actions have been
         * passed and responds with the appropriate method.
         *
         * Uses WP_Filesystem to process and handle the plugin installation
         * method.
         *
         * @since 1.0.0
         *
         * @uses WP_Filesystem
         * @uses WP_Error
         * @uses WP_Upgrader
         * @uses Plugin_Upgrader
         * @uses Plugin_Installer_Skin
         *
         * @return boolean True on success, false on failure
         */
        /*protected function do_plugin_install() {

            // All plugin information will be stored in an array for processing.
            $plugin = array();

            // Checks for actions from hover links to process the installation.
            if ( isset( $_GET['plugin'] ) && ( isset( $_GET['tgmpa-install'] ) && 'install-plugin' == $_GET['tgmpa-install'] ) ) {
                check_admin_referer( 'tgmpa-install' );

                $plugin['name']   = $_GET['plugin_name']; // Plugin name.
                $plugin['slug']   = $_GET['plugin']; // Plugin slug.
                $plugin['source'] = $_GET['plugin_source']; // Plugin source.

                // Pass all necessary information via URL if WP_Filesystem is needed.
                $url = wp_nonce_url(
                    add_query_arg(
                        array(
                            'page'          => urlencode( $this->menu ),
                            'plugin'        => urlencode( $plugin['slug'] ),
                            'plugin_name'   => urlencode( $plugin['name'] ),
                            'plugin_source' => urlencode( $plugin['source'] ),
                            'tgmpa-install' => 'install-plugin',
                        ),
                        network_admin_url( 'options-general.php' )
                    ),
                    'tgmpa-install'
                );
                $method = ''; // Leave blank so WP_Filesystem can populate it as necessary.
                $fields = array( 'tgmpa-install' ); // Extra fields to pass to WP_Filesystem.

                if ( false === ( $creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields ) ) ) {
                    return true;
                }

                if ( ! WP_Filesystem( $creds ) ) {
                    request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields ); // Setup WP_Filesystem.
                    return true;
                }

                require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api.
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes.

                // Set plugin source to WordPress API link if available.
                if ( isset( $plugin['source'] ) && 'repo' == $plugin['source'] ) {
                    $api = plugins_api( 'plugin_information', array( 'slug' => $plugin['slug'], 'fields' => array( 'sections' => false ) ) );

                    if ( is_wp_error( $api ) ) {
                        wp_die( $this->strings['oops'] . var_dump( $api ) );
                    }

                    if ( isset( $api->download_link ) ) {
                        $plugin['source'] = $api->download_link;
                    }
                }

                // Set type, based on whether the source starts with http:// or https://.
                $type = preg_match( '|^http(s)?://|', $plugin['source'] ) ? 'web' : 'upload';

                // Prep variables for Plugin_Installer_Skin class.
                $title = sprintf( $this->strings['installing'], $plugin['name'] );
                $url   = add_query_arg( array( 'action' => 'install-plugin', 'plugin' => urlencode( $plugin['slug'] ) ), 'update.php' );
                if ( isset( $_GET['from'] ) ) {
                    $url .= add_query_arg( 'from', urlencode( stripslashes( $_GET['from'] ) ), $url );
                }

                $url   = esc_url_raw( $url );

                $nonce = 'install-plugin_' . $plugin['slug'];

                // Prefix a default path to pre-packaged plugins.
                $source = ( 'upload' == $type ) ? $this->default_path . $plugin['source'] : $plugin['source'];

                // Create a new instance of Plugin_Upgrader.
                $upgrader = new Plugin_Upgrader( $skin = new Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

                // Perform the action and install the plugin from the $source urldecode().
                $upgrader->install( $source );

                // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                wp_cache_flush();

                // Only activate plugins if the config option is set to true.
                if ( $this->is_automatic ) {
                    $plugin_activate = $upgrader->plugin_info(); // Grab the plugin info from the Plugin_Upgrader method.
                    $activate        = activate_plugin( $plugin_activate ); // Activate the plugin.
                    $this->populate_file_path(); // Re-populate the file path now that the plugin has been installed and activated.

                    if ( is_wp_error( $activate ) ) {
                        echo '<div id="message" class="error"><p>' . $activate->get_error_message() . '</p></div>';
                        echo '<p><a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), network_admin_url( 'options-general.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                        return true; // End it here if there is an error with automatic activation
                    }
                    else {
                        echo '<p>' . $this->strings['plugin_activated'] . '</p>';
                    }
                }

                // Display message based on if all plugins are now active or not.
                $complete = array();
                foreach ( $this->plugins as $plugin ) {
                    if ( ! is_plugin_active( $plugin['file_path'] ) ) {
                        echo '<p><a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), network_admin_url( 'options-general.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                        $complete[] = $plugin;
                        break;
                    }
                    // Nothing to store.
                    else {
                        $complete[] = '';
                    }
                }

                // Filter out any empty entries.
                $complete = array_filter( $complete );

                // All plugins are active, so we display the complete string and hide the plugin menu.
                if ( empty( $complete ) ) {
                    echo '<p>' .  sprintf( $this->strings['complete'], '<a href="' . esc_url( network_admin_url() ) . '" title="' . esc_attr__( 'Return to the Dashboard', 'tgmpa' ) . '">' . __( 'Return to the Dashboard', 'tgmpa' ) . '</a>' ) . '</p>';
                    echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
                }

                return true;
            }
            // Checks for actions from hover links to process the activation.
            elseif ( isset( $_GET['plugin'] ) && ( isset( $_GET['tgmpa-activate'] ) && 'activate-plugin' == $_GET['tgmpa-activate'] ) ) {
                check_admin_referer( 'tgmpa-activate', 'tgmpa-activate-nonce' );

                // Populate $plugin array with necessary information.
                $plugin['name']   = $_GET['plugin_name'];
                $plugin['slug']   = $_GET['plugin'];
                $plugin['source'] = $_GET['plugin_source'];

                $plugin_data = get_plugins( '/' . $plugin['slug'] ); // Retrieve all plugins.
                $plugin_file = array_keys( $plugin_data ); // Retrieve all plugin files from installed plugins.
                $plugin_to_activate = $plugin['slug'] . '/' . $plugin_file[0]; // Match plugin slug with appropriate plugin file.
                $activate = activate_plugin( $plugin_to_activate ); // Activate the plugin.

                if ( is_wp_error( $activate ) ) {
                    echo '<div id="message" class="error"><p>' . $activate->get_error_message() . '</p></div>';
                    echo '<p><a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), network_admin_url( 'options-general.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                    return true; // End it here if there is an error with activation.
                }
                else {
                    // Make sure message doesn't display again if bulk activation is performed immediately after a single activation.
                    if ( ! isset( $_POST['action'] ) ) {
                        $msg = $this->strings['activated_successfully'] . ' <strong>' . $plugin['name'] . '.</strong>';
                        echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
                    }
                }
            }

            return false;

        }
*/
        /**
         * install my modules
         */
        protected  function hw_do_module_install() {
            // All plugin information will be stored in an array for processing.
            $plugin = array();
            if ( isset( $_GET['module'] ) && ( isset( $_GET['tgmpa-activate'] ) && 'activate-plugin' == $_GET['tgmpa-activate'] ) ) {
                check_admin_referer( 'tgmpa-activate', 'tgmpa-activate-nonce' );
                //check require plugin/module
                if(!empty($this->plugins[$_GET['module']]['require_wp_plugin'])) {
                    $require_plugin = $this->plugins[$_GET['module']]['require_wp_plugin'];
                    if(!is_plugin_active($require_plugin)) {
                        hw_inline_msg("Xin lỗi, module '{$_GET['module']}' yêu cầu cài trước plugin ".hw_install_plugin_link($require_plugin));
                        return;
                    }
                }
                if(isset($this->plugins[$_GET['module']]['require_module'])) {
                    $require_module = $this->plugins[$_GET['module']]['require_module'];
                    if(!hw_is_active_module($require_module)) {
                        hw_inline_msg("Xin lỗi, module {$_GET['module']} yêu cầu cài trước module ".($require_module));
                        return;
                    }
                }
                // Populate $plugin array with necessary information.
                $plugin[]   = $_GET['module'];
                $plugin[]   = $_GET['module_name'];
                if(isset($this->plugins[$_GET['module']]['source']) && $this->plugins[$_GET['module']]['source']) {
                    $plugin[] = $this->plugins[$_GET['module']]['source'];
                }

                if(!hw_activate_modules($plugin)) {
                    hw_inline_msg(sprintf('Xin lỗi, module %s đã cài trước đó hoặc yêu cầu module khác đã không tim thấy !', $_GET['module']));
                }
                else hw_inline_msg(sprintf('Cài đặt module %s thành công !', $_GET['module']), 'updated');

                echo '<p><a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), admin_url( 'admin.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                // Make sure message doesn't display again if bulk activation is performed immediately after a single activation.
                if ( ! isset( $_POST['action'] ) ) {
                    $msg = $this->strings['activated_successfully'] . ' <strong>' . $_GET['module_name'] . '.</strong>';
                    echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
                }
            }
            elseif(isset( $_GET['module'] ) && ( isset( $_GET['tgmpa-deactivate'] ) && 'deactivate-plugin' == $_GET['tgmpa-deactivate'] )) {
                check_admin_referer( 'tgmpa-deactivate', 'tgmpa-deactivate-nonce' );
                // Populate $plugin array with necessary information.
                // Populate $plugin array with necessary information.
                $plugin[]   = $_GET['module'];
                $plugin[]   = $_GET['module_name'];
                if(isset($this->plugins[$_GET['module']]['source']) && $this->plugins[$_GET['module']]['source']) {
                    $plugin[] = $this->plugins[$_GET['module']]['source'];
                }

                hw_deactivate_modules($plugin);
                // Make sure message doesn't display again if bulk activation is performed immediately after a single activation.
                if ( ! isset( $_POST['action'] ) ) {
                    $msg = $this->strings['deactivated_successfully'] . ' <strong>' . $_GET['module_name'] . '.</strong>';
                    echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
                }
            }
        }
        /**
         * Echoes required plugin notice.
         *
         * Outputs a message telling users that a specific plugin is required for
         * their theme. If appropriate, it includes a link to the form page where
         * users can install and activate the plugin.
         *
         * @since 1.0.0
         *
         * @global object $current_screen
         * @return null Returns early if we're on the Install page.
         */
        public function notices() {

            global $current_screen;

            // Remove nag on the install page.
            if ( $this->is_tgmpa_page() ) {
                return;
            }

            // Return early if the nag message has been dismissed.
            if ( get_user_meta( get_current_user_id(), 'hw_tgmpa_dismissed_notice', true ) ) {
                return;
            }

            #$installed_plugins = get_plugins(); // Retrieve a list of all the plugins
            $installed_plugins = hw_get_modules(); // Retrieve a list of all the modules
            $this->populate_file_path();

            $message             = array(); // Store the messages in an array to be outputted after plugins have looped through.
            $install_link        = false;   // Set to false, change to true in loop if conditions exist, used for action link 'install'.
            $install_link_count  = 0;       // Used to determine plurality of install action link text.
            $activate_link       = false;   // Set to false, change to true in loop if conditions exist, used for action link 'activate'.
            $activate_link_count = 0;       // Used to determine plurality of activate action link text.

            foreach ( $this->plugins as $plugin ) {
                // If the plugin is installed and active, check for minimum version argument before moving forward.
                if ( hw_is_active_module( $plugin['file_path'] ) ) {
                    // A minimum version has been specified.
                    if ( isset( $plugin['version'] ) ) {
                        if ( isset( $installed_plugins[$plugin['file_path']]['Version'] ) ) {
                            // If the current version is less than the minimum required version, we display a message.
                            if ( version_compare( $installed_plugins[$plugin['file_path']]['Version'], $plugin['version'], '<' ) ) {
                                if ( current_user_can( 'install_plugins' ) ) {
                                    $message['notice_ask_to_update'][] = $plugin['name'];
                                } else {
                                    $message['notice_cannot_update'][] = $plugin['name'];
                                }
                            }
                        }
                        // Can't find the plugin, so iterate to the next condition.
                        else {
                            continue;
                        }
                    }
                    // No minimum version specified, so iterate over the plugin.
                    else {
                        continue;
                    }
                }

                // Not installed.
                if ( ! isset( $installed_plugins[$plugin['file_path']] ) ) {
                    $install_link = true; // We need to display the 'install' action link.
                    $install_link_count++; // Increment the install link count.
                    if ( current_user_can( 'install_plugins' ) ) {
                        if ( isset($plugin['required']) && $plugin['required']) {
                            $message['notice_can_install_required'][] = $plugin['name'];
                        }
                        // This plugin is only recommended.
                        else {
                            $message['notice_can_install_recommended'][] = $plugin['name'];
                        }
                    }
                    // Need higher privileges to install the plugin.
                    else {
                        $message['notice_cannot_install'][] = $plugin['name'];
                    }
                }
                // Installed but not active.
                elseif ( hw_is_inactive_module( $plugin['file_path'] ) ) {
                    $activate_link = true; // We need to display the 'activate' action link.
                    $activate_link_count++; // Increment the activate link count.
                    if ( current_user_can( 'activate_plugins' ) ) {
                        if ( isset( $plugin['required'] ) && $plugin['required'] ) {
                            $message['notice_can_activate_required'][] = $plugin['name'];
                        }
                        // This plugin is only recommended.
                        else {
                            $message['notice_can_activate_recommended'][] = $plugin['name'];
                        }
                    }
                    // Need higher privileges to activate the plugin.
                    else {
                        $message['notice_cannot_activate'][] = $plugin['name'];
                    }
                }
            }

            // If we have notices to display, we move forward.
            if ( ! empty( $message ) ) {
                krsort( $message ); // Sort messages.
                $rendered = ''; // Display all nag messages as strings.

                // If dismissable is false and a message is set, output it now.
                if ( ! $this->dismissable && ! empty( $this->dismiss_msg ) ) {
                    $rendered .= '<p><strong>' . wp_kses_post( $this->dismiss_msg ) . '</strong></p>';
                }

                // Grab all plugin names.
                foreach ( $message as $type => $plugin_groups ) {
                    $linked_plugin_groups = array();

                    // Count number of plugins in each message group to calculate singular/plural message.
                    $count = count( $plugin_groups );

                    // Loop through the plugin names to make the ones pulled from the .org repo linked.
                    foreach ( $plugin_groups as $plugin_group_single_name ) {
                        $external_url = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'external_url' );
                        $source       = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'source' );

                        if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
                            $linked_plugin_groups[] = '<a href="' . esc_url( $external_url ) . '" title="' . esc_attr( $plugin_group_single_name ) . '" target="_blank">' . $plugin_group_single_name . '</a>';
                        }
                        elseif ( ! $source || preg_match( '|^http://wordpress.org/extend/plugins/|', $source ) ) {
                            $url = add_query_arg(
                                array(
                                    'tab'       => 'plugin-information',
                                    'plugin'    => urlencode( $this->_get_plugin_data_from_name( $plugin_group_single_name ) ),
                                    'TB_iframe' => 'true',
                                    'width'     => '640',
                                    'height'    => '500',
                                ),
                                network_admin_url( 'plugin-install.php' )
                            );

                            $linked_plugin_groups[] = '<a href="' . esc_url( $url ) . '" class="thickbox" title="' . esc_attr( $plugin_group_single_name ) . '">' . $plugin_group_single_name . '</a>';
                        }
                        else {
                            $linked_plugin_groups[] = $plugin_group_single_name; // No hyperlink.
                        }

                        if ( isset( $linked_plugin_groups ) && (array) $linked_plugin_groups ) {
                            $plugin_groups = $linked_plugin_groups;
                        }
                    }

                    $last_plugin = array_pop( $plugin_groups ); // Pop off last name to prep for readability.
                    $imploded    = empty( $plugin_groups ) ? '<em>' . $last_plugin . '</em>' : '<em>' . ( implode( ', ', $plugin_groups ) . '</em> and <em>' . $last_plugin . '</em>' );

                    $rendered .= '<p>' . sprintf( translate_nooped_plural( $this->strings[$type], $count, 'tgmpa' ), $imploded, $count ) . '</p>';
                }

                // Setup variables to determine if action links are needed.
                $show_install_link  = $install_link ? '<a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), network_admin_url( 'admin.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['install_link'], $install_link_count, 'tgmpa' ) . '</a>' : '';
                $show_activate_link = $activate_link ? '<a href="' . esc_url( add_query_arg( 'page', urlencode( $this->menu ), network_admin_url( 'admin.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['activate_link'], $activate_link_count, 'tgmpa' ) . '</a>'  : '';

                // Define all of the action links.
                $action_links = apply_filters(
                    'hw_tgmpa_notice_action_links',
                    array(
                        'install'  => ( current_user_can( 'install_plugins' ) )  ? $show_install_link  : '',
                        'activate' => ( current_user_can( 'activate_plugins' ) ) ? $show_activate_link : '',
                        'dismiss'  => $this->dismissable ? '<a class="dismiss-notice" href="' . esc_url( add_query_arg( 'tgmpa-dismiss', 'dismiss_admin_notices' ) ) . '" target="_parent">' . $this->strings['dismiss'] . '</a>' : '',
                    )
                );

                $action_links = array_filter( $action_links ); // Remove any empty array items.
                if ( $action_links ) {
                    $rendered .= '<p>' . implode( ' | ', $action_links ) . '</p>';
                }

                // Register the nag messages and prepare them to be processed.
                $nag_class = version_compare( $this->wp_version, '3.8', '<' ) ? 'updated' : 'update-nag';
                if ( ! empty( $this->strings['nag_type'] ) ) {
                    add_settings_error( 'hw-tgmpa', 'tgmpa', $rendered, sanitize_html_class( strtolower( $this->strings['nag_type'] ) ) );
                } else {
                    add_settings_error( 'hw-tgmpa', 'tgmpa', $rendered, $nag_class );
                }
            }

            // Admin options pages already output settings_errors, so this is to avoid duplication.
            if ( /*'options-general'*/'admin' !== $current_screen->parent_base ) {
                settings_errors( 'hw-tgmpa' );
            }

        }

        /**
         * Add dismissable admin notices.
         *
         * Appends a link to the admin nag messages. If clicked, the admin notice disappears and no longer is visible to users.
         *
         * @since 2.1.0
         */
        public function dismiss() {

            if ( isset( $_GET['tgmpa-dismiss'] ) ) {
                update_user_meta( get_current_user_id(), 'hw_tgmpa_dismissed_notice', 1 );
            }

        }

        /**
         * Add individual plugin to our collection of plugins.
         *
         * If the required keys are not set or the plugin has already
         * been registered, the plugin is not added.
         *
         * @since 2.0.0
         *
         * @param array $plugin Array of plugin arguments.
         */
        public function register( $plugin ) {

            if ( ! isset( $plugin['slug'] ) || ! isset( $plugin['name'] ) ) {
                return;
            }

            foreach ( $this->plugins as $registered_plugin ) {
                if ( $plugin['slug'] == $registered_plugin['slug'] ) {
                    return;
                }
            }

            $this->plugins[$plugin['slug']] = $plugin;

        }

        /**
         * Amend default configuration settings.
         *
         * @since 2.0.0
         *
         * @param array $config Array of config options to pass as class properties.
         */
        public function config( $config ) {

            $keys = array( 'default_path', 'has_notices', 'dismissable', 'dismiss_msg', 'menu', 'is_automatic', 'message', 'strings' );

            foreach ( $keys as $key ) {
                if ( isset( $config[$key] ) ) {
                    if ( is_array( $config[$key] ) ) {
                        foreach ( $config[$key] as $subkey => $value ) {
                            $this->{$key}[$subkey] = $value;
                        }
                    } else {
                        $this->$key = $config[$key];
                    }
                }
            }

        }

        /**
         * Amend action link after plugin installation.
         *
         * @since 2.0.0
         *
         * @param array $install_actions Existing array of actions.
         * @return array                 Amended array of actions.
         */
        public function actions( $install_actions ) {

            // Remove action links on the TGMPA install page.
            if ( $this->is_tgmpa_page() ) {
                return false;
            }

            return $install_actions;

        }

        /**
         * Flushes the plugins cache on theme switch to prevent stale entries
         * from remaining in the plugin table.
         *
         * @since 2.4.0
         */
        public function flush_plugins_cache() {

            wp_cache_flush();

        }

        /**
         * Set file_path key for each installed plugin.
         *
         * @since 2.1.0
         */
        public function populate_file_path() {

            // Add file_path key for all plugins.
            foreach ( $this->plugins as $plugin => $values ) {
                #$this->plugins[$plugin]['file_path'] = $this->_get_plugin_basename_from_slug( $values['slug'] );
                $this->plugins[$plugin]['file_path'] = isset($values['slug'])? $values['slug'] : $values['name'];
            }

        }

        /**
         * Helper function to extract the file path of the plugin file from the
         * plugin slug, if the plugin is installed.
         *
         * @since 2.0.0
         *
         * @param string $slug Plugin slug (typically folder name) as provided by the developer.
         * @return string      Either file path for plugin if installed, or just the plugin slug.
         */
        protected function _get_plugin_basename_from_slug( $slug ) {

            $keys = array_keys( hw_get_plugins() );

            foreach ( $keys as $key ) {
                if ( preg_match( '|^' . $slug .'/|', $key ) ) {
                    return $key;
                }
            }

            return $slug;

        }

        /**
         * Retrieve plugin data, given the plugin name.
         *
         * Loops through the registered plugins looking for $name. If it finds it,
         * it returns the $data from that plugin. Otherwise, returns false.
         *
         * @since 2.1.0
         *
         * @param string $name    Name of the plugin, as it was registered.
         * @param string $data    Optional. Array key of plugin data to return. Default is slug.
         * @return string|boolean Plugin slug if found, false otherwise.
         */
        protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {

            foreach ( $this->plugins as $plugin => $values ) {
                if ( $name == $values['name'] && isset( $values[$data] ) ) {
                    return $values[$data];
                }
            }

            return false;

        }

        /**
         * Determine if we're on the TGMPA Install page.
         *
         * @since 2.1.0
         *
         * @return boolean True when on the TGMPA page, false otherwise.
         */
        protected function is_tgmpa_page() {

            if ( isset( $_GET['page'] ) && $this->menu === $_GET['page'] ) {
                return true;
            }

            return false;

        }

        /**
         * Delete dismissable nag option when theme is switched.
         *
         * This ensures that the user is again reminded via nag of required
         * and/or recommended plugins if they re-activate the theme.
         *
         * @since 2.1.1
         */
        public function update_dismiss() {

            delete_user_meta( get_current_user_id(), 'hw_tgmpa_dismissed_notice' );

        }

        /**
         * Forces plugin activation if the parameter 'force_activation' is
         * set to true.
         *
         * This allows theme authors to specify certain plugins that must be
         * active at all times while using the current theme.
         *
         * Please take special care when using this parameter as it has the
         * potential to be harmful if not used correctly. Setting this parameter
         * to true will not allow the specified plugin to be deactivated unless
         * the user switches themes.
         *
         * @since 2.2.0
         */
        public function force_activation() {

            // Set file_path parameter for any installed plugins.
            $this->populate_file_path();

            #$installed_plugins = get_plugins();
            $installed_plugins = hw_get_modules(true);

            foreach ( $this->plugins as $plugin ) {
                // Oops, plugin isn't there so iterate to next condition.
                if ( isset( $plugin['force_activation'] ) && $plugin['force_activation'] && ! isset( $installed_plugins[$plugin['file_path']] ) ) {
                    hw_activate_modules( array($plugin['slug'] ,$plugin['name']));//hw_is_inactive_module( $plugin['file_path'] )
                }

            }

        }

        /**
         * Forces plugin deactivation if the parameter 'force_deactivation'
         * is set to true.
         *
         * This allows theme authors to specify certain plugins that must be
         * deactived upon switching from the current theme to another.
         *
         * Please take special care when using this parameter as it has the
         * potential to be harmful if not used correctly.
         *
         * @since 2.2.0
         */
        public function force_deactivation() {

            // Set file_path parameter for any installed plugins.
            $this->populate_file_path();

            foreach ( $this->plugins as $plugin ) {
                // Only proceed forward if the paramter is set to true and plugin is active.
                if ( isset( $plugin['force_deactivation'] ) && $plugin['force_deactivation'] && hw_is_active_module( $plugin['file_path'] ) ) {
                    hw_deactivate_modules( $plugin['slug']);
                }
            }

        }

        /**
         * Returns the singleton instance of the class.
         *
         * @since 2.4.0
         *
         * @return object The HW_TGM_Module_Activation object.
         */
        public static function get_instance() {

            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HW_TGM_Module_Activation ) ) {
                self::$instance = new HW_TGM_Module_Activation();
            }

            return self::$instance;

        }

        /**
         * return all registered modules or one module given by slug
         * @param $slug
         * @param $rtype
         * @return mixed
         */
        public static function get_modules($slug='', $rtype = ARRAY_A) {
            $modules = self::get_instance()->plugins;  //self::get_instance()->plugins;   //self::$instance->plugins
            if($slug ) $modules = isset($modules[$slug])? $modules[$slug] : array();
            return $rtype == OBJECT? (object)$modules : $modules;
        }

        /**
         * return core modules
         * @return array
         */
        public static function get_core_modules() {
            return self::get_register_modules(array('core'=> 1));
        }
    }

    // Ensure only one instance of the class is ever invoked.
    $tgmpa = HW_TGM_Module_Activation::get_instance();

}

if ( ! function_exists( 'hw_tgmpa' ) ) {
    /**
     * Helper function to register a collection of required plugins.
     *
     * @since 2.0.0
     * @api
     *
     * @param array $plugins An array of plugin arrays.
     * @param array $config  Optional. An array of configuration values.
     */
    function hw_tgmpa( $plugins, $config = array() ) {

        foreach ( $plugins as $plugin ) {
            HW_TGM_Module_Activation::$instance->register( $plugin );
        }

        if ( $config ) {
            HW_TGM_Module_Activation::$instance->config( $config );
        }

    }
}

/**
 * WP_List_Table isn't always available. If it isn't available,
 * we load it here.
 *
 * @since 2.2.0
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
HW_HOANGWEB::load_class('HW_List_Table');
if ( ! class_exists( 'HW_TGMPA_List_Table' ) ) {
    /**
     * List table class for handling plugins.
     *
     * Extends the WP_List_Table class to provide a future-compatible
     * way of listing out all required/recommended plugins.
     *
     * Gives users an interface similar to the Plugin Administration
     * area with similar (albeit stripped down) capabilities.
     *
     * This class also allows for the bulk install of plugins.
     *
     * @since 2.2.0
     *
     * @package TGM-Plugin-Activation
     * @author  Thomas Griffin <thomas@thomasgriffinmedia.com>
     * @author  Gary Jones <gamajo@gamajo.com>
     */
    class HW_TGMPA_List_Table extends HW_List_Table {

        /**
         * References parent constructor and sets defaults for class.
         *
         * The constructor also grabs a copy of $instance from the TGMPA class
         * and stores it in the global object HW_TGM_Module_Activation::$instance.
         *
         * @since 2.2.0
         *
         * @global unknown $status
         * @global string $page
         */
        public function __construct() {

            global $status, $page;

            parent::__construct(
                array(
                    'singular' => 'hw-list-table-modules',
                    'plural'   => 'hw-list-table-modules',
                    'ajax'     => false,
                )
            );

        }

        /**
         * @hook admin_footer
         * @return mixed|void
         */
        public function _admin_footer() {
            echo "<script>
        jQuery(document).ready(function(){
            jQuery('table.hw-list-table-modules').find('tr .hw-module-active').closest('tr').addClass('hw-module-active-row');
            jQuery('table.hw-list-table-modules').find('tr .hw-module-inactive').closest('tr').addClass('hw-module-inactive-row');
            jQuery('table.hw-list-table-modules').find('tr .hw-module-force-activation').closest('tr').addClass('hw-module-force-activation-row');
        });

            </script>";
        }
        /**
         * Add extra markup in the toolbars before or after the list
         * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
         */
        public function extra_tablenav($which){
            if ( $which == "top" ){

            }
            if ( $which == "bottom" ){

            }
            ?>
            <!--
                //moved to hw_install_module_page method
            <p class="search-box">
                <input name="abc"/>
                <input type="search" id="search_id" name="q" value="<?php #echo hw_admin_search_query(); ?>" />
                <?php #submit_button( 'Tìm kiếm', 'button', '', false, array('id' => 'search-submit') ); ?>
            </p> -->
        <?php
        }
        /**
         * Gathers and renames all of our plugin information to be used by
         * WP_List_Table to create our table.
         *
         * @since 2.2.0
         * @param $status
         * @param $search
         *
         * @return array $table_data Information for use in table.
         */
        protected function _gather_plugin_data($status='all', $search ='') {

            // Load thickbox for plugin links.
            #HW_TGM_Module_Activation::$instance->admin_init();
            #HW_TGM_Module_Activation::$instance->thickbox();

            $plugins_list = HW_TGM_Module_Activation::$instance->plugins;   //plugins list

            // Prep variables for use and grab list of all installed plugins.
            $table_data        = array();
            $i                 = 0;
            #$installed_plugins = get_plugins();
            $installed_plugins = hw_get_modules(); //by hoang


            //pagination
            $per_page = HW_Screen_Option::get('modules_manager')->get_option('per_page', 5);#_print($per_page);
            if(!is_numeric($per_page)) $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = count($plugins_list);
            // only ncessary because we have sample data
            $plugins_list = array_slice($plugins_list,(($current_page-1)*$per_page),$per_page);

            $this->set_pagination_args( array(
                'total_items' => $total_items,                  //WE have to calculate the total number of items
                'per_page'    => $per_page                     //WE have to determine how many items to show on a page
            ) );

            foreach ( $plugins_list as $plugin ) {
                //search module
                if($search && !HW_TGM_Module_Activation::match_module_search( $plugin, $search)) {
                    continue;
                }

                //check status
                $is_active = hw_is_active_module( $plugin['file_path'] );
                if (($status == 'inactive' && $is_active) || ($status == 'active' && !$is_active)) {
                    continue; // No need to display plugins if they are installed and activated.
                }
                $table_data[$i] = $plugin;  //copy all information of the module
                $table_data[$i]['sanitized_plugin'] = $plugin['name'];
                $table_data[$i]['slug']             = $this->_get_plugin_data_from_name( $plugin['name'] );

                $external_url = $this->_get_plugin_data_from_name( $plugin['name'], 'external_url' );
                $source       = $this->_get_plugin_data_from_name( $plugin['name'], 'source' );

                if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
                    $table_data[$i]['plugin'] = '<strong><a href="' . esc_url( $external_url ) . '" title="' . esc_attr( $plugin['name'] ) . '" target="_blank">' . $plugin['name'] . '</a></strong>';
                }
                //install from wp repositories
                elseif ( ! $source || preg_match( '|^http://hoangweb.vn/modules/|', $source ) ) {
                    $url = add_query_arg(
                        array(
                           'tab'       => 'plugin-information',
                           'plugin'    => urlencode( $this->_get_plugin_data_from_name( $plugin['name'] ) ),
                           'TB_iframe' => 'true',
                           'width'     => '640',
                           'height'    => '500',
                        ),
                        network_admin_url( 'plugin-install.php' )
                    );

                    #$table_data[$i]['plugin'] = '<strong><a href="' . esc_url( $url ) . '" class="thickbox" title="' . esc_attr( $plugin['name'] ) . '">' . $plugin['name'] . '</a></strong>';
                    $table_data[$i]['plugin'] = "<strong>{$plugin['name']}</strong>";
                }
                else {
                    $table_data[$i]['plugin'] = '<strong>' . $plugin['name'] . '</strong>'; // No hyperlink.
                }

                if ( isset( $table_data[$i]['plugin'] ) && (array) $table_data[$i]['plugin'] ) {
                    $plugin['name'] = $table_data[$i]['plugin'];
                }

                if ( ! empty( $plugin['source'] ) ) {
                    // The plugin must be from a private repository.
                    if ( preg_match( '|^http(s)?://|', $plugin['source'] ) ) {
                        $table_data[$i]['source'] = __( 'Private Repository', 'tgmpa' );
                    // The plugin is pre-packaged with the theme.
                    } else {
                        $table_data[$i]['source'] = __( 'Pre-Packaged', 'tgmpa' );
                    }
                }
                $table_data[$i]['source'] = 'Hoangweb';
                if(isset($plugin['desc'])) $table_data[$i]['desc'] = $plugin['desc'];

                // The plugin is from the WordPress repository.
                /*else {
                    $table_data[$i]['source'] = __( 'WordPress Repository', 'tgmpa' );
                }*/

                $table_data[$i]['type'] = isset( $plugin['required'] ) && $plugin['required'] ? __( 'Required', 'tgmpa' ) : __( 'Recommended', 'tgmpa' );

                $status_class= array();
                if(isset( $installed_plugins[$plugin['file_path']] )
                    && isset($table_data[$i]['force_activation']) && $table_data[$i]['force_activation']==true) {
                    $status_class[] = 'hw-module-force-activation';
                }

                if ( ! isset( $installed_plugins[$plugin['file_path']] ) ) {
                    $status_class[] = 'hw-module-inactive';
                    $table_data[$i]['status'] = sprintf( '%1$s', __( '<span class="'.join(' ', $status_class).'">Not Installed</span>', 'tgmpa' ) );
                }/* elseif ( is_plugin_inactive( $plugin['file_path'] ) ) {
                    $table_data[$i]['status'] = sprintf( '%1$s', __( 'Installed But Not Activated', 'tgmpa' ) );
                }*/
                else{
                    $status_class[] = 'hw-module-active';
                    $table_data[$i]['status'] = sprintf( '%1$s', __( '<span style="color:blue" class="'.join(' ', $status_class).'"><strong>Installed</strong></span>', 'tgmpa' ) );
                }

                $table_data[$i]['file_path'] = $plugin['file_path'];
                $table_data[$i]['url']       = isset( $plugin['source'] ) ? $plugin['source'] : 'repo';

                $i++;
            }

            // Sort plugins by Required/Recommended type and by alphabetical listing within each type.
            $resort = array();
            $req    = array();
            $rec    = array();

            // Grab all the plugin types.
            foreach ( $table_data as $plugin ) {
                $resort[] = $plugin['type'];
            }

            // Sort each plugin by type.
            foreach ( $resort as $type ) {
                if ( 'Required' == $type ) {
                    $req[] = $type;
                } else {
                    $rec[] = $type;
                }
            }

            // Sort alphabetically each plugin type array, merge them and then sort in reverse (lists Required plugins first).
            sort( $req );
            sort( $rec );
            array_merge( $resort, $req, $rec );
            array_multisort( $resort, SORT_DESC, $table_data );

            return $table_data;

        }

        /**
         * Retrieve plugin data, given the plugin name. Taken from the
         * HW_TGM_Module_Activation class.
         *
         * Loops through the registered plugins looking for $name. If it finds it,
         * it returns the $data from that plugin. Otherwise, returns false.
         *
         * @since 2.2.0
         *
         * @param string $name Name of the plugin, as it was registered.
         * @param string $data Optional. Array key of plugin data to return. Default is slug.
         * @return string|boolean Plugin slug if found, false otherwise.
         */
        protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {

            foreach ( HW_TGM_Module_Activation::$instance->plugins as $plugin => $values ) {
                if ( $name == $values['name'] && isset( $values[$data] ) ) {
                    return $values[$data];
                }
            }

            return false;

        }

        /**
         * Create default columns to display important plugin information
         * like type, action and status.
         *
         * @since 2.2.0
         *
         * @param array $item         Array of item data.
         * @param string $column_name The name of the column.
         */
        public function column_default( $item, $column_name ) {

            switch ( $column_name ) {
                case 'source':
                case 'type':
                case 'desc':
                case 'status':
                    return $item[$column_name];
            }

        }

        /**
         * Create default title column along with action links of 'Install'
         * and 'Activate'.
         *
         * @since 2.2.0
         *
         * @param array $item Array of item data.
         * @return string     The action hover links.
         */
        public function column_plugin( $item ) {

            #$installed_plugins = get_plugins();
            $installed_plugins = hw_get_modules();

            // No need to display any hover links.
            if ( hw_is_active_module( $item['file_path'] ) ) {
                $actions = array();
            }
            $actions = array();
            // We need to display the 'Install' hover link.
            if(!isset($item['force_activation']) || $item['force_activation'] ==false){
            if ( ! isset( $installed_plugins[$item['file_path']] ) ) {
                $actions = array(
                    'install' => sprintf(
                        '<a href="%1$s" title="' . esc_attr__( 'Kích hoạt', 'tgmpa' ) . ' %2$s">' . __( 'Kích hoạt', 'tgmpa' ) . '</a>',
                        esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'page'          => urlencode( HW_TGM_Module_Activation::$instance->menu ),
                                        'module'        => urlencode( $item['slug'] ),
                                        'module_name'   => urlencode( $item['sanitized_plugin'] ),
                                        #'module_source' => urlencode( $item['url'] ),
                                        'tgmpa-activate'       => 'activate-plugin',
                                        'tgmpa-activate-nonce' => urlencode( wp_create_nonce( 'tgmpa-activate' ) ),
                                    ),
                                    #network_admin_url( 'options-general.php' )
                                    admin_url( 'admin.php' )
                                ),
                               'tgmpa-install'
                            )
                        ),
                        $item['sanitized_plugin']
                    ),
                );
            }
            else {
                $actions = array(
                    'install' => sprintf(
                        '<a href="%1$s" title="' . esc_attr__( 'Hủy kích hoạt', 'tgmpa' ) . ' %2$s">' . __( 'Hủy Kích hoạt', 'tgmpa' ) . '</a>',
                        esc_url(
                            wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'page'          => urlencode( HW_TGM_Module_Activation::$instance->menu ),
                                        'module'        => urlencode( $item['slug'] ),
                                        'module_name'   => urlencode( $item['sanitized_plugin'] ),
                                        #'module_source' => urlencode( $item['url'] ),
                                        'tgmpa-deactivate'       => 'deactivate-plugin',
                                        'tgmpa-deactivate-nonce' => urlencode( wp_create_nonce( 'tgmpa-deactivate' ) ),
                                    ),
                                    #network_admin_url( 'options-general.php' )
                                    admin_url( 'admin.php' )
                                ),
                                'tgmpa-install'
                            )
                        ),
                        $item['sanitized_plugin']
                    ),
                );
            }
            //setting page url
            $actions['config'] = sprintf(
                '<a href="%1$s" title="' . esc_attr__( 'Cấu hình', 'tgmpa' ) . ' %2$s">' . __( 'Cấu hình', 'tgmpa' ) . '</a>',

                    wp_nonce_url(
                        add_query_arg(
                            array(
                                'tab'       => HW_Module_Settings_page::valid_tab_slug($item['slug']),
                                'page'          => urlencode( HW_Module_Settings_page::PAGE_SLUG ),
                                'module'        => urlencode( $item['slug'] ),
                                'module_name'   => urlencode( $item['sanitized_plugin'] ),
                                #'module_source' => urlencode( $item['url'] ),
                                'tgmpa-tab-nonce' => urlencode( wp_create_nonce( 'tgmpa-tab-nonce' ) ),
                            ),
                            #network_admin_url( 'options-general.php' )
                            admin_url( 'admin.php' )
                        ),
                        'tgmpa-install'
                    )
                ,
                $item['sanitized_plugin']
            );
            }
            // We need to display the 'Activate' hover link.
            /*elseif ( is_plugin_inactive( $item['file_path'] ) ) {
                $actions = array(
                    'activate' => sprintf(
                        '<a href="%1$s" title="' . esc_attr__( 'Activate', 'tgmpa' ) . ' %2$s">' . __( 'Activate', 'tgmpa' ) . '</a>',
                        esc_url(
                            add_query_arg(
                                array(
                                    'page'                 => urlencode( HW_TGM_Module_Activation::$instance->menu ),
                                    'plugin'               => urlencode( $item['slug'] ),
                                    'plugin_name'          => urlencode( $item['sanitized_plugin'] ),
                                    'plugin_source'        => urlencode( $item['url'] ),
                                    'tgmpa-activate'       => 'activate-plugin',
                                    'tgmpa-activate-nonce' => urlencode( wp_create_nonce( 'tgmpa-activate' ) ),
                                ),
                                network_admin_url( 'options-general.php' )
                            )
                        ),
                        $item['sanitized_plugin']
                    ),
                );
            }
*/
            return sprintf( '%1$s %2$s', $item['plugin'], $this->row_actions( $actions ) );

        }

        /**
         * Required for bulk installing.
         *
         * Adds a checkbox for each plugin.
         *
         * @since 2.2.0
         *
         * @param array $item Array of item data.
         * @return string     The input checkbox with all necessary info.
         */
        public function column_cb( $item ) {
            if(!isset($item['force_activation']) || $item['force_activation'] ==false) {
                $plugin_url = $item['url']; // 'repo' (no escaping needed), URL or file path

                if ( __( 'Private Repository', 'tgmpa' ) === $item['source'] ) {
                    // Escape external URLs
                    $plugin_url = esc_url( $plugin_url );
                } elseif ( __( 'Pre-Packaged', 'tgmpa' ) === $item['source'] ) {
                    // Encode file path for use in attribute
                    $plugin_url = urlencode( HW_TGM_Module_Activation::$instance->default_path . $plugin_url );
                }

                $value = $item['file_path'] . ',' . $plugin_url . ',' . $item['sanitized_plugin'];

                return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s" />', esc_attr( $this->_args['singular'] ), esc_attr( $value ), esc_attr( $item['sanitized_plugin'] ) );
            }

        }

        /**
         * Sets default message within the plugins table if no plugins
         * are left for interaction.
         *
         * Hides the menu item to prevent the user from clicking and
         * getting a permissions error.
         *
         * @since 2.2.0
         */
        public function no_items() {

            printf( __( 'No plugins to install or activate. <a href="%1$s" title="Return to the Dashboard">Return to the Dashboard</a>', 'tgmpa' ), network_admin_url() );
            echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';

        }

        /**
         * Output all the column information within the table.
         *
         * @since 2.2.0
         *
         * @return array $columns The column names.
         */
        public function get_columns() {

            $columns = array(
                'cb'     => '<input type="checkbox" />',
                'plugin' => __( 'Module', 'tgmpa' ),
                'source' => __( 'Source', 'tgmpa' ),
                'type'   => __( 'Type', 'tgmpa' ),
                'desc' => __('Mô tả', 'tgmpa'),
                'status' => __( 'Status', 'tgmpa' )
            );

            return $columns;

        }
        /**
         * Columns to make sortable.
         *
         * @return array
         */
        public function get_sortable_columns() {
            $sortable_columns = array(
                'plugin' => array( 'plugin', true ),
                'status' => array( 'status', false )
            );

            return $sortable_columns;
        }
        /**
         * Defines all types of bulk actions for handling
         * registered plugins.
         *
         * @since 2.2.0
         *
         * @return array $actions The bulk actions for the plugin install table.
         */
        public function get_bulk_actions() {

            $actions = array(
                #'tgmpa-bulk-install'  => __( 'Install', 'tgmpa' ),
                'tgmpa-bulk-activate' => __( 'Activate', 'tgmpa' ),
                'tgmpa-bulk-deactivate' => __( 'Deactivate', 'tgmpa' ),
            );

            return $actions;

        }

        /**
         * Processes bulk installation and activation actions.
         *
         * The bulk installation process looks either for the $_POST
         * information or for the plugin info within the $_GET variable if
         * a user has to use WP_Filesystem to enter their credentials.
         *
         * @since 2.2.0
         */
        public function process_bulk_actions() {

            // Bulk installation process.
            if ( 'tgmpa-bulk-install' === $this->current_action() ) {
                check_admin_referer( 'bulk-' . $this->_args['plural'] );

                // Prep variables to be populated.
                $plugins_to_install = array();
                $plugin_installs    = array();
                $plugin_path        = array();
                $plugin_name        = array();

                // Look first to see if information has been passed via WP_Filesystem.
                if ( isset( $_GET['plugins'] ) ) {
                    $plugins = explode( ',', stripslashes( $_GET['plugins'] ) );
                }
                // Looks like the user can use the direct method, take from $_POST.
                elseif ( isset( $_POST['plugin'] ) ) {
                    $plugins = (array) $_POST['plugin'];
                }
                // Nothing has been submitted.
                else {
                    $plugins = array();
                }

                // Grab information from $_POST if available.
                if ( isset( $_POST['plugin'] ) ) {
                    foreach ( $plugins as $plugin_data ) {
                        $plugins_to_install[] = explode( ',', $plugin_data );
                    }

                    foreach ( $plugins_to_install as $plugin_data ) {
                        $plugin_installs[] = $plugin_data[0];
                        $plugin_path[]     = $plugin_data[1];
                        $plugin_name[]     = $plugin_data[2];
                    }
                }
                // Information has been passed via $_GET.
                else {
                    foreach ( $plugins as $key => $value ) {
                        // Grab plugin slug for each plugin.
                        if ( 0 == $key % 3 || 0 == $key ) {
                            $plugins_to_install[] = $value;
                            $plugin_installs[]    = $value;
                        }
                    }
                }

                // Look first to see if information has been passed via WP_Filesystem.
                if ( isset( $_GET['plugin_paths'] ) ) {
                    $plugin_paths = explode( ',', stripslashes( $_GET['plugin_paths'] ) );
                }
                // Looks like the user doesn't need to enter his FTP creds.
                elseif ( isset( $_POST['plugin'] ) ) {
                    $plugin_paths = (array) $plugin_path;
                }
                // Nothing has been submitted.
                else {
                    $plugin_paths = array();
                }

                // Look first to see if information has been passed via WP_Filesystem.
                if ( isset( $_GET['plugin_names'] ) ) {
                    $plugin_names = explode( ',', stripslashes( $_GET['plugin_names'] ) );
                }
                // Looks like the user doesn't need to enter his FTP creds.
                elseif ( isset( $_POST['plugin'] ) ) {
                    $plugin_names = (array) $plugin_name;
                }
                // Nothing has been submitted.
                else {
                    $plugin_names = array();
                }

                // Loop through plugin slugs and remove already installed plugins from the list.
                $i = 0;
                foreach ( $plugin_installs as $key => $plugin ) {
                    if ( preg_match( '|.php$|', $plugin ) ) {
                        unset( $plugin_installs[$key] );

                        // If the plugin path isn't in the $_GET variable, we can unset the corresponding path.
                        if ( ! isset( $_GET['plugin_paths'] ) )
                            unset( $plugin_paths[$i] );

                        // If the plugin name isn't in the $_GET variable, we can unset the corresponding name.
                        if ( ! isset( $_GET['plugin_names'] ) )
                            unset( $plugin_names[$i] );
                    }
                    $i++;
                }

                // No need to proceed further if we have no plugins to install.
                if ( empty( $plugin_installs ) ) {
                    return false;
                }
                $this->hw_install_plugins($plugin_installs, $plugin_paths,$plugin_names);
            }

            // Bulk activation process.
            if ( 'tgmpa-bulk-activate' === $this->current_action() ) {
                check_admin_referer( 'bulk-' . $this->_args['plural'] );

                // Grab plugin data from $_POST.
                $plugins             = isset( $_POST['plugin'] ) ? (array) $_POST['plugin'] : array();
                $plugins_to_activate = array();

                // Split plugin value into array with plugin file path, plugin source and plugin name.
                foreach ( $plugins as $i => $plugin ) {
                    $plugins_to_activate[] = explode( ',', $plugin );
                }

                foreach ( $plugins_to_activate as $i => $array ) {
                    if ( ! preg_match( '|.php$|', $array[0] ) ) {
                        unset( $plugins_to_activate[$i] );
                    }
                }

                // Return early if there are no plugins to activate.
                if ( empty( $plugins_to_activate ) ) {
                    return;
                }
                $this->hw_activate_plugins($plugins_to_activate);
            }
            wp_redirect( esc_url( add_query_arg() ) );
            exit;
        }

        /**
         * install plugins
         * @param $plugins_to_installs same as hw_activate_pluginn argument
         * @return bool
         */
        public function hw_install_plugins($plugins_to_installs = array()) {
            // Prep variables to be populated.
            $plugin_installs    = array();
            $plugin_paths    = array();
            $plugin_names    = array();

            foreach ($plugins_to_installs as $plugin) {
                $plugin_installs[] = $plugin[0];
                $plugin_paths[] = $plugin[1];
                $plugin_names[] = $plugin[2];
            }

            // Reset array indexes in case we removed already installed plugins.
            $plugin_installs = array_values( $plugin_installs );
            $plugin_paths    = array_values( $plugin_paths );
            $plugin_names    = array_values( $plugin_names );

            // If we grabbed our plugin info from $_GET, we need to decode it for use.
            $plugin_installs = array_map( 'urldecode', $plugin_installs );
            $plugin_paths    = array_map( 'urldecode', $plugin_paths );
            $plugin_names    = array_map( 'urldecode', $plugin_names );

            // Pass all necessary information via URL if WP_Filesystem is needed.
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page'          => urlencode( HW_TGM_Module_Activation::$instance->menu ),
                        'tgmpa-action'  => 'install-selected',
                        'plugins'       => urlencode( implode( ',', $plugins ) ),
                        'plugin_paths'  => urlencode( implode( ',', $plugin_paths ) ),
                        'plugin_names'  => urlencode( implode( ',', $plugin_names ) ),
                    ),
                    network_admin_url( 'admin.php' )
                ),
                'bulk-plugins'
            );
            $method = ''; // Leave blank so WP_Filesystem can populate it as necessary.
            $fields = array( 'action', '_wp_http_referer', '_wpnonce' ); // Extra fields to pass to WP_Filesystem.

            if ( false === ( $creds = request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields ) ) ) {
                return true;
            }

            if ( ! WP_Filesystem( $creds ) ) {
                request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields ); // Setup WP_Filesystem.
                return true;
            }

            require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes

            // Store all information in arrays since we are processing a bulk installation.
            $api          = array();
            $sources      = array();
            $install_path = array();

            // Loop through each plugin to install and try to grab information from WordPress API, if not create 'tgmpa-empty' scalar.
            $i = 0;
            foreach ( $plugin_installs as $plugin ) {
                $api[$i] = plugins_api( 'plugin_information', array( 'slug' => $plugin, 'fields' => array( 'sections' => false ) ) ) ? plugins_api( 'plugin_information', array( 'slug' => $plugin, 'fields' => array( 'sections' => false ) ) ) : (object) $api[$i] = 'tgmpa-empty';
                $i++;
            }

            if ( is_wp_error( $api ) ) {
                wp_die( HW_TGM_Module_Activation::$instance->strings['oops'] . var_dump( $api ) );
            }

            // Capture download links from $api or set install link to pre-packaged/private repo.
            $i = 0;
            foreach ( $api as $object ) {
                $sources[$i] = isset( $object->download_link ) && 'repo' == $plugin_paths[$i] ? $object->download_link : $plugin_paths[$i];
                $i++;
            }

            // Finally, all the data is prepared to be sent to the installer.
            $url   = esc_url_raw( add_query_arg( array( 'page' => urlencode( HW_TGM_Module_Activation::$instance->menu ) ), network_admin_url( 'admin.php' ) ) );
            $nonce = 'bulk-plugins';
            $names = $plugin_names;

            // Create a new instance of HW_TGM_Bulk_Installer.
            $installer = new HW_TGM_Bulk_Installer( $skin = new HW_TGM_Bulk_Installer_Skin( compact( 'url', 'nonce', 'names' ) ) );

            // Wrap the install process with the appropriate HTML.
            echo '<div class="tgmpa wrap">';
            echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';
            // Process the bulk installation submissions.
            $installer->bulk_install( $sources );
            echo '</div>';

            return true;
        }

        /**
         * activate plugins
         * @param $plugins_to_activate
         * @example $plugins_to_activate = [['plugin/path','repo','plugin name'],[]]
         */
        public function hw_activate_plugins($plugins_to_activate) {

            // Return early if there are no plugins to activate.
            if ( empty( $plugins_to_activate ) ) {
                return;
            }

            $plugins      = array();
            $plugin_names = array();

            foreach ( $plugins_to_activate as $plugin_string ) {
                $plugins[]      = $plugin_string[0];
                $plugin_names[] = $plugin_string[2];
            }

            $count       = count( $plugin_names ); // Count so we can use _n function.
            $last_plugin = array_pop( $plugin_names ); // Pop off last name to prep for readability.
            $imploded    = empty( $plugin_names ) ? '<strong>' . $last_plugin . '</strong>' : '<strong>' . ( implode( ', ', $plugin_names ) . '</strong> and <strong>' . $last_plugin . '</strong>.' );

            // Now we are good to go - let's start activating plugins.
            $activate = activate_plugins( $plugins );

            if ( is_wp_error( $activate ) ) {
                echo '<div id="message" class="error"><p>' . $activate->get_error_message() . '</p></div>';
            } else {
                printf( '<div id="message" class="updated"><p>%1$s %2$s.</p></div>', _n( 'The following plugin was activated successfully:', 'The following plugins were activated successfully:', $count, 'tgmpa' ), $imploded );
            }

            // Update recently activated plugins option.
            $recent = (array) get_option( 'recently_activated' );

            foreach ( $plugins as $plugin => $time ) {
                if ( isset( $recent[$plugin] ) ) {
                    unset( $recent[$plugin] );
                }
            }

            update_option( 'recently_activated', $recent );

            unset( $_POST ); // Reset the $_POST variable in case user wants to perform one action after another.
        }
        /**
         * Processes bulk installation and activation actions.
         *
         * The bulk installation process looks either for the $_POST
         * information or for the plugin info within the $_GET variable if
         * a user has to use WP_Filesystem to enter their credentials.
         *
         * @since 2.2.0
         */
        public function  hw_process_bulk_actions() {
            // Bulk activation process.
            if(!$this->current_action()) return;
            check_admin_referer( 'bulk-' . $this->_args['plural'] );
            $action = $this->current_action();  //current action
            $plugins             = isset( $_POST['plugin'] ) ? (array) $_POST['plugin'] : array();

            // Grab plugin data from $_POST.
            $this->process_bulk_action($plugins, $action);

            if(!empty($plugins_to_activate)) {
                unset( $_POST );// Reset the $_POST variable in case user wants to perform one action after another.
            }

        }

        /**
         * process bulk actions for module/plugin
         * @param array $plugins
         * @param $action action bulk
         */
        public function process_bulk_action($plugins = array(), $action='') {
            // Grab plugin data from $_POST.
            #$plugins             = isset( $_POST['plugin'] ) ? (array) $_POST['plugin'] : array();
            $plugins_to_activate = array();

            // Split plugin value into array with plugin file path, plugin source and plugin name.
            $plugins_need2activate = array();
            if(is_array($plugins))
            foreach ( $plugins as $i => $plugin ) {
                $_plugin = explode( ',', $plugin );
                if(isset(HW_TGM_Module_Activation::get_instance()->plugins[$_plugin[0]])) {
                    $module_info = HW_TGM_Module_Activation::get_instance()->plugins[$_plugin[0]];
                    $active = array();
                    //local downloaded plugins
                    if(!empty($module_info['require_wp_plugin']) && file_exists(WP_PLUGIN_DIR.'/'. trim($module_info['require_wp_plugin'], '/'))) {
                        $active[] = $module_info['require_wp_plugin'];
                        $active[] = 'repo';

                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/'. trim($module_info['require_wp_plugin'], '/'));
                        $active[] = $plugin_data['Name'];
                    }
                    if(!empty($module_info['require_module'])) { //check module that require an other
                        $require_module = $module_info['require_module'];
                        if(!hw_is_active_module($require_module)) {
                            hw_inline_msg("Xin lỗi, module `{$module_info['name']}` yêu cầu cài trước module ".($require_module));
                            $plugins_to_activate[]=  array($module_info['slug'], $module_info['name']);
                        }
                    }
                    if(!empty($module_info['require_wp_plugin']) && !is_plugin_active($module_info['require_wp_plugin']) && !empty($active)) {
                        $plugins_need2activate[] = $active;

                    }
                }
                $plugins_to_activate[] = $_plugin;
            }

            foreach ( $plugins_to_activate as $i => $array ) {
                if (isset($array[1]) && ! preg_match( '|.php$|', $array[1] ) ) {
                    unset( $plugins_to_activate[$i] );
                }
            }

            // Return early if there are no plugins to activate.
            if ( empty( $plugins_to_activate ) ) {
                return;
            }
            //activate plugins require for the your modules
            if(count($plugins_need2activate)) $this->hw_activate_plugins($plugins_need2activate);

            if ( 'tgmpa-bulk-activate' === $action ) {
                // Now we are good to go - let's start activating plugins.
                hw_activate_modules( $plugins_to_activate );
            }
            elseif('tgmpa-bulk-deactivate' ===  $action) {  //deactive module
                hw_deactivate_modules($plugins_to_activate);
            }
            // Update recently activated plugins option.
            $recent = (array) get_option( 'recently_activated' );

            foreach ( $plugins as $plugin => $time ) {
                if ( isset( $recent[$plugin] ) ) {
                    unset( $recent[$plugin] );
                }
            }

            update_option( 'recently_activated', $recent );
        }
        /**
         * Prepares all of our information to be outputted into a usable table.
         *
         * @since 2.2.0
         */
        public function prepare_items() {
            //Retrieve module_status for use in query to get items.
            $module_status = ( isset($_REQUEST['module_status']) ? $_REQUEST['module_status'] : 'all');

            $per_page              = 100; // Set it high so we shouldn't have to worry about pagination.
            $columns               = $this->get_columns(); // Get all necessary column information.
            $hidden                = array(); // No columns to hide, but we must set as an array.
            $sortable              = array(); // No reason to make sortable columns.
            $this->_column_headers = array( $columns, $hidden, $sortable ); // Get all necessary column headers.

            // Process our bulk actions here.
            $this->hw_process_bulk_actions(); #var_dump('prepare_items/hw_process_bulk_actions');

            // Store all of our plugin data into $items array so WP_List_Table can use it.
            $this->items = $this->_gather_plugin_data($module_status, hw_admin_search_query() );

        }

    }
}

add_action( 'admin_init', 'hw_tgmpa_load_bulk_installer' );
if ( ! function_exists( 'hw_tgmpa_load_bulk_installer' ) ) {
    function hw_tgmpa_load_bulk_installer() {
    /**
     * The WP_Upgrader file isn't always available. If it isn't available,
     * we load it here.
     *
     * We check to make sure no action or activation keys are set so that WordPress
     * doesn't try to re-include the class when processing upgrades or installs outside
     * of the class.
     *
     * @since 2.2.0
     */
    if ( ! class_exists( 'WP_Upgrader' ) && ( isset( $_GET['page'] ) && HW_TGM_Module_Activation::$instance->menu === $_GET['page'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        if ( ! class_exists( 'HW_TGM_Bulk_Installer' ) ) {
            /**
             * Installer class to handle bulk plugin installations.
             *
             * Extends WP_Upgrader and customizes to suit the installation of multiple
             * plugins.
             *
             * @since 2.2.0
             *
             * @package TGM-Plugin-Activation
             * @author  Thomas Griffin <thomasgriffinmedia.com>
             * @author  Gary Jones <gamajo.com>
             */
            class HW_TGM_Bulk_Installer extends WP_Upgrader {

                /**
                 * Holds result of bulk plugin installation.
                 *
                 * @since 2.2.0
                 *
                 * @var string
                 */
                public $result;

                /**
                 * Flag to check if bulk installation is occurring or not.
                 *
                 * @since 2.2.0
                 *
                 * @var boolean
                 */
                public $bulk = false;

                /**
                 * Processes the bulk installation of plugins.
                 *
                 * @since 2.2.0
                 *
                 * @param array $packages The plugin sources needed for installation.
                 * @return string|boolean Install confirmation messages on success, false on failure.
                 */
                public function bulk_install( $packages ) {

                    // Pass installer skin object and set bulk property to true.
                    $this->init();
                    $this->bulk = true;

                    // Set install strings and automatic activation strings (if config option is set to true).
                    $this->install_strings();
                    if ( HW_TGM_Module_Activation::$instance->is_automatic ) {
                        $this->activate_strings();
                    }

                    // Run the header string to notify user that the process has begun.
                    $this->skin->header();

                    // Connect to the Filesystem.
                    $res = $this->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
                    if ( ! $res ) {
                        $this->skin->footer();
                        return false;
                    }

                    // Set the bulk header and prepare results array.
                    $this->skin->bulk_header();
                    $results = array();

                    // Get the total number of packages being processed and iterate as each package is successfully installed.
                    $this->update_count   = count( $packages );
                    $this->update_current = 0;

                    // Loop through each plugin and process the installation.
                    foreach ( $packages as $plugin ) {
                        $this->update_current++; // Increment counter.

                        // Do the plugin install.
                        $result = $this->run(
                            array(
                                'package'           => $plugin, // The plugin source.
                                'destination'       => WP_PLUGIN_DIR, // The destination dir.
                                'clear_destination' => false, // Do we want to clear the destination or not?
                                'clear_working'     => true, // Remove original install file.
                                'is_multi'          => true, // Are we processing multiple installs?
                                'hook_extra'        => array( 'plugin' => $plugin, ), // Pass plugin source as extra data.
                            )
                        );

                        // Store installation results in result property.
                        $results[$plugin] = $this->result;

                        // Prevent credentials auth screen from displaying multiple times.
                        if ( false === $result ) {
                            break;
                        }
                    }

                    // Pass footer skin strings.
                    $this->skin->bulk_footer();
                    $this->skin->footer();

                    // Return our results.
                    return $results;

                }

                /**
                 * Performs the actual installation of each plugin.
                 *
                 * This method also activates the plugin in the automatic flag has been
                 * set to true for the TGMPA class.
                 *
                 * @since 2.2.0
                 *
                 * @param array $options The installation cofig options
                 * @return null/array Return early if error, array of installation data on success
                 */
                public function run( $options ) {

                    // Default config options.
                    $defaults = array(
                        'package'           => '',
                        'destination'       => '',
                        'clear_destination' => false,
                        'clear_working'     => true,
                        'is_multi'          => false,
                        'hook_extra'        => array(),
                    );

                    // Parse default options with config options from $this->bulk_upgrade and extract them.
                    $options = wp_parse_args( $options, $defaults );
                    extract( $options );

                    // Connect to the Filesystem.
                    $res = $this->fs_connect( array( WP_CONTENT_DIR, $destination ) );
                    if ( ! $res ) {
                        return false;
                    }

                        // Return early if there is an error connecting to the Filesystem.
                        if ( is_wp_error( $res ) ) {
                            $this->skin->error( $res );
                            return $res;
                        }

                        // Call $this->header separately if running multiple times.
                        if ( ! $is_multi )
                            $this->skin->header();

                        // Set strings before the package is installed.
                        $this->skin->before();

                        // Download the package (this just returns the filename of the file if the package is a local file).
                        $download = $this->download_package( $package );
                        if ( is_wp_error( $download ) ) {
                            $this->skin->error( $download );
                            $this->skin->after();
                            return $download;
                        }

                        // Don't accidentally delete a local file.
                        $delete_package = ( $download != $package );

                        // Unzip file into a temporary working directory.
                        $working_dir = $this->unpack_package( $download, $delete_package );
                        if ( is_wp_error( $working_dir ) ) {
                            $this->skin->error( $working_dir );
                            $this->skin->after();
                            return $working_dir;
                        }

                        // Install the package into the working directory with all passed config options.
                        $result = $this->install_package(
                            array(
                                'source'            => $working_dir,
                                'destination'       => $destination,
                                'clear_destination' => $clear_destination,
                                'clear_working'     => $clear_working,
                                'hook_extra'        => $hook_extra,
                            )
                        );

                        // Pass the result of the installation.
                        $this->skin->set_result( $result );

                        // Set correct strings based on results.
                        if ( is_wp_error( $result ) ) {
                            $this->skin->error( $result );
                            $this->skin->feedback( 'process_failed' );
                        }
                        // The plugin install is successful.
                        else {
                            $this->skin->feedback( 'process_success' );
                        }

                        // Only process the activation of installed plugins if the automatic flag is set to true.
                        /*if ( HW_TGM_Module_Activation::$instance->is_automatic ) {
                            // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                            wp_cache_flush();

                            // Get the installed plugin file and activate it.
                            $plugin_info = $this->plugin_info( $package );
                            $activate    = activate_plugin( $plugin_info );

                            // Re-populate the file path now that the plugin has been installed and activated.
                            HW_TGM_Module_Activation::$instance->populate_file_path();

                            // Set correct strings based on results.
                            if ( is_wp_error( $activate ) ) {
                                $this->skin->error( $activate );
                                $this->skin->feedback( 'activation_failed' );
                            }
                            // The plugin activation is successful.
                            else {
                                $this->skin->feedback( 'activation_success' );
                            }
                        }*/

                        // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                        wp_cache_flush();

                        // Set install footer strings.
                        $this->skin->after();
                        if ( ! $is_multi ) {
                            $this->skin->footer();
                        }

                        return $result;

                    }

                    /**
                     * Sets the correct install strings for the installer skin to use.
                     *
                     * @since 2.2.0
                     */
                    public function install_strings() {

                        $this->strings['no_package']          = __( 'Install package not available.', 'tgmpa' );
                        $this->strings['downloading_package'] = __( 'Downloading install package from <span class="code">%s</span>&#8230;', 'tgmpa' );
                        $this->strings['unpack_package']      = __( 'Unpacking the package&#8230;', 'tgmpa' );
                        $this->strings['installing_package']  = __( 'Installing the plugin&#8230;', 'tgmpa' );
                        $this->strings['process_failed']      = __( 'Plugin install failed.', 'tgmpa' );
                        $this->strings['process_success']     = __( 'Plugin installed successfully.', 'tgmpa' );

                    }

                    /**
                     * Sets the correct activation strings for the installer skin to use.
                     *
                     * @since 2.2.0
                     */
                    public function activate_strings() {

                        $this->strings['activation_failed']  = __( 'Plugin activation failed.', 'tgmpa' );
                        $this->strings['activation_success'] = __( 'Plugin activated successfully.', 'tgmpa' );

                    }

                    /**
                     * Grabs the plugin file from an installed plugin.
                     *
                     * @since 2.2.0
                     *
                     * @return string|boolean Return plugin file on success, false on failure
                     */
                    public function plugin_info() {

                        // Return false if installation result isn't an array or the destination name isn't set.
                        if ( ! is_array( $this->result ) ) {
                            return false;
                        }

                        if ( empty( $this->result['destination_name'] ) ) {
                            return false;
                        }

                        /// Get the installed plugin file or return false if it isn't set.
                        $plugin = get_plugins( '/' . $this->result['destination_name'] );
                        if ( empty( $plugin ) ) {
                            return false;
                        }

                        // Assume the requested plugin is the first in the list.
                        $pluginfiles = array_keys( $plugin );

                        return $this->result['destination_name'] . '/' . $pluginfiles[0];

                    }

                }
            }

            if ( ! class_exists( 'HW_TGM_Bulk_Installer_Skin' ) ) {
                /**
                 * Installer skin to set strings for the bulk plugin installations..
                 *
                 * Extends Bulk_Upgrader_Skin and customizes to suit the installation of multiple
                 * plugins.
                 *
                 * @since 2.2.0
                 *
                 * @package TGM-Plugin-Activation
                 * @author  Thomas Griffin <thomasgriffinmedia.com>
                 * @author  Gary Jones <gamajo.com>
                 */
                class HW_TGM_Bulk_Installer_Skin extends Bulk_Upgrader_Skin {

                    /**
                     * Holds plugin info for each individual plugin installation.
                     *
                     * @since 2.2.0
                     *
                     * @var array
                     */
                    public $plugin_info = array();

                    /**
                     * Holds names of plugins that are undergoing bulk installations.
                     *
                     * @since 2.2.0
                     *
                     * @var array
                     */
                    public $plugin_names = array();

                    /**
                     * Integer to use for iteration through each plugin installation.
                     *
                     * @since 2.2.0
                     *
                     * @var integer
                     */
                    public $i = 0;

                    /**
                     * Constructor. Parses default args with new ones and extracts them for use.
                     *
                     * @since 2.2.0
                     *
                     * @param array $args Arguments to pass for use within the class.
                     */
                    public function __construct( $args = array() ) {

                        // Parse default and new args.
                        $defaults = array( 'url' => '', 'nonce' => '', 'names' => array() );
                        $args     = wp_parse_args( $args, $defaults );

                        // Set plugin names to $this->plugin_names property.
                        $this->plugin_names = $args['names'];

                        // Extract the new args.
                        parent::__construct( $args );

                    }

                    /**
                     * Sets install skin strings for each individual plugin.
                     *
                     * Checks to see if the automatic activation flag is set and uses the
                     * the proper strings accordingly.
                     *
                     * @since 2.2.0
                     */
                    public function add_strings() {

                        // Automatic activation strings.
                        if ( HW_TGM_Module_Activation::$instance->is_automatic ) {
                            $this->upgrader->strings['skin_upgrade_start']        = __( 'The installation and activation process is starting. This process may take a while on some hosts, so please be patient.', 'tgmpa' );
                            $this->upgrader->strings['skin_update_successful']    = __( '%1$s installed and activated successfully.', 'tgmpa' ) . ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' . __( 'Show Details', 'tgmpa' ) . '</span><span class="hidden">' . __( 'Hide Details', 'tgmpa' ) . '</span>.</a>';
                            $this->upgrader->strings['skin_upgrade_end']          = __( 'All installations and activations have been completed.', 'tgmpa' );
                            $this->upgrader->strings['skin_before_update_header'] = __( 'Installing and Activating Plugin %1$s (%2$d/%3$d)', 'tgmpa' );
                        }
                        // Default installation strings.
                        else {
                            $this->upgrader->strings['skin_upgrade_start']        = __( 'The installation process is starting. This process may take a while on some hosts, so please be patient.', 'tgmpa' );
                            $this->upgrader->strings['skin_update_failed_error']  = __( 'An error occurred while installing %1$s: <strong>%2$s</strong>.', 'tgmpa' );
                            $this->upgrader->strings['skin_update_failed']        = __( 'The installation of %1$s failed.', 'tgmpa' );
                            $this->upgrader->strings['skin_update_successful']    = __( '%1$s installed successfully.', 'tgmpa' ) . ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' . __( 'Show Details', 'tgmpa' ) . '</span><span class="hidden">' . __( 'Hide Details', 'tgmpa' ) . '</span>.</a>';
                            $this->upgrader->strings['skin_upgrade_end']          = __( 'All installations have been completed.', 'tgmpa' );
                            $this->upgrader->strings['skin_before_update_header'] = __( 'Installing Plugin %1$s (%2$d/%3$d)', 'tgmpa' );
                        }

                    }

                    /**
                     * Outputs the header strings and necessary JS before each plugin installation.
                     *
                     * @since 2.2.0
                     */
                    public function before( $title = '' ) {

                        // We are currently in the plugin installation loop, so set to true.
                        $this->in_loop = true;

                        printf( '<h4>' . $this->upgrader->strings['skin_before_update_header'] . ' <img alt="" src="' . admin_url( 'images/wpspin_light.gif' ) . '" class="hidden waiting-' . $this->upgrader->update_current . '" style="vertical-align:middle;" /></h4>', $this->plugin_names[$this->i], $this->upgrader->update_current, $this->upgrader->update_count );
                        echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
                        echo '<div class="update-messages hide-if-js" id="progress-' . esc_attr( $this->upgrader->update_current ) . '"><p>';

                        // Flush header output buffer.
                        $this->before_flush_output();

                    }

                    /**
                     * Outputs the footer strings and necessary JS after each plugin installation.
                     *
                     * Checks for any errors and outputs them if they exist, else output
                     * success strings.
                     *
                     * @since 2.2.0
                     */
                    public function after( $title = '' ) {

                        // Close install strings.
                        echo '</p></div>';

                        // Output error strings if an error has occurred.
                        if ( $this->error || ! $this->result ) {
                            if ( $this->error ) {
                                echo '<div class="error"><p>' . sprintf( $this->upgrader->strings['skin_update_failed_error'], $this->plugin_names[$this->i], $this->error ) . '</p></div>';
                            } else {
                                echo '<div class="error"><p>' . sprintf( $this->upgrader->strings['skin_update_failed'], $this->plugin_names[$this->i] ) . '</p></div>';
                            }

                            echo '<script type="text/javascript">jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
                        }

                        // If the result is set and there are no errors, success!
                        if ( ! empty( $this->result ) && ! is_wp_error( $this->result ) ) {
                            echo '<div class="updated"><p>' . sprintf( $this->upgrader->strings['skin_update_successful'], $this->plugin_names[$this->i], 'jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').toggle();jQuery(\'span\', this).toggle(); return false;' ) . '</p></div>';
                            echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').hide();</script>';
                        }

                        // Set in_loop and error to false and flush footer output buffer.
                        $this->reset();
                        $this->after_flush_output();

                    }

                    /**
                     * Outputs links after bulk plugin installation is complete.
                     *
                     * @since 2.2.0
                     */
                    public function bulk_footer() {

                        // Serve up the string to say installations (and possibly activations) are complete.
                        parent::bulk_footer();

                        // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                        wp_cache_flush();

                        // Display message based on if all plugins are now active or not.
                        $complete = array();
                        foreach ( HW_TGM_Module_Activation::$instance->plugins as $plugin ) {
                            if ( ! hw_is_active_module( $plugin['file_path'] ) ) {
                                echo '<p><a href="' . esc_url( add_query_arg( 'page', urlencode( HW_TGM_Module_Activation::$instance->menu ), network_admin_url( 'admin.php' ) ) ) . '" title="' . esc_attr( HW_TGM_Module_Activation::$instance->strings['return'] ) . '" target="_parent">' . HW_TGM_Module_Activation::$instance->strings['return'] . '</a></p>';
                                $complete[] = $plugin;
                                break;
                            }
                            // Nothing to store.
                            else {
                                $complete[] = '';
                            }
                        }

                        // Filter out any empty entries.
                        $complete = array_filter( $complete );

                        // All plugins are active, so we display the complete string and hide the menu to protect users.
                        if ( empty( $complete ) ) {
                            echo '<p>' .  sprintf( HW_TGM_Module_Activation::$instance->strings['complete'], '<a href="' . esc_url( network_admin_url() ) . '" title="' . esc_attr__( 'Return to the Dashboard', 'tgmpa' ) . '">' . __( 'Return to the Dashboard', 'tgmpa' ) . '</a>' ) . '</p>';
                            echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
                        }

                    }

                    /**
                     * Flush header output buffer.
                     *
                     * @since 2.2.0
                     */
                    public function before_flush_output() {

                        wp_ob_end_flush_all();
                        flush();

                    }

                    /**
                     * Flush footer output buffer and iterate $this->i to make sure the
                     * installation strings reference the correct plugin.
                     *
                     * @since 2.2.0
                     */
                    public function after_flush_output() {

                        wp_ob_end_flush_all();
                        flush();
                        $this->i++;

                    }

                }
            }
        }
    }
}