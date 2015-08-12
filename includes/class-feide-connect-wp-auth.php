<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/skrodal/
 * @since      1.0.0
 *
 * @package    Feide_Connect_Wp_Auth
 * @subpackage Feide_Connect_Wp_Auth/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @link       https://github.com/skrodal/feide-connect-wp-auth
 * @author     Simon Skrødal <simon.skrodal@uninett.no>
 *
 * @package    Feide_Connect_Wp_Auth
 * @subpackage Feide_Connect_Wp_Auth/includes
 */
 

class Feide_Connect_Wp_Auth {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Feide_Connect_Wp_Auth_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * Options stored in DB
	 */
	 
	 private $plugin_options;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'feide-connect-wp-auth';
		$this->version = '1.0.0';

		$this->plugin_options = get_option($this->plugin_name);
		
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Feide_Connect_Wp_Auth_Loader. Orchestrates the hooks of the plugin.
	 * - Feide_Connect_Wp_Auth_i18n. Defines internationalization functionality.
	 * - Feide_Connect_Wp_Auth_Admin. Defines all hooks for the admin area.
	 * - Feide_Connect_Wp_Auth_Public. Defines all hooks for the public side of the site.
	 * - Feide_Connect_Wp_Auth_Login. The Feide Connect OAuth class. 
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-feide-connect-wp-auth-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-feide-connect-wp-auth-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-feide-connect-wp-auth-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-feide-connect-wp-auth-public.php';
		
		/**
		 * The Feide Connect OAuth class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-feide-connect-wp-auth-login.php';
		
		$this->loader = new Feide_Connect_Wp_Auth_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Feide_Connect_Wp_Auth_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Feide_Connect_Wp_Auth_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Feide_Connect_Wp_Auth_Admin( $this->get_plugin_name(), $this->get_version() );
		// Styles and scripts
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		// Add menu item
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		// Add Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );
		// Save/Update our plugin options
		$this->loader->add_action('admin_init', $plugin_admin, 'options_update');

		// Stuff to hook into ONLY if OAuth option is enabled
		if($this->plugin_options['enable_plugin'] === 1) {
			// Hook for the query_vars and template_redirect
			$this->loader->add_filter('query_vars', $plugin_admin,  'oauthTriggersFilter');
			$this->loader->add_action('template_redirect', $plugin_admin,  'oAuthQvarHandler');
			// Catch logout event
			$this->loader->add_action('wp_logout', $plugin_admin, 'fc_logout_handler');
		}
	}
	
	
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Feide_Connect_Wp_Auth_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		// Stuff to hook into ONLY if OAuth option is enabled
		if($this->plugin_options['enable_plugin'] === 1) {
			// Change login logo, url and title
			$this->loader->add_filter( 'login_headerurl', $plugin_public, 'fc_login_change_logo_url' );
			$this->loader->add_filter( 'login_headertitle', $plugin_public, 'fc_login_change_logo_title' );
			$this->loader->add_action( 'login_enqueue_scripts', $plugin_public, 'fc_login_change_logo' );
			// Edit login form to include Feide Connect button
			$this->loader->add_filter('login_message', $plugin_public, 'fc_login_add_feide_connect');
		}

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Feide_Connect_Wp_Auth_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
