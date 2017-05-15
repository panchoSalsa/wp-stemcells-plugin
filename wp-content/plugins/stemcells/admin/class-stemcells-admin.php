<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.mind.uci.edu/
 * @since      1.0.0
 *
 * @package    Stemcells
 * @subpackage Stemcells/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Stemcells
 * @subpackage Stemcells/admin
 * @author     Ken Francisco <frfranco@uci.edu>
 */
class Stemcells_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stemcells_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stemcells_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/stemcells-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Stemcells_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Stemcells_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/stemcells-admin.js', array( 'jquery' ), $this->version, false );

		// wp_enqueue_script( 'papaparser', 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.3.2/papaparse.js');
		wp_enqueue_script( 'papaparser', 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.3.2/papaparse.js');

	}


	public function add_plugin_admin_menu() {

	    /*
	     * Add a settings page for this plugin to the Settings menu.
	     *
	     * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
	     *
	     *        Administration Menus: http://codex.wordpress.org/Administration_Menus
	     *
	     */


	    // adding menu to admin dashboard
	    // source=https://premium.wpmudev.org/blog/creating-wordpress-admin-pages/?utm_expid=3606929-105.kKHVTz43T_CV513Vo9oSow.0&utm_referrer=https%3A%2F%2Fwww.google.com%2F
	    // source=https://developer.wordpress.org/reference/functions/add_menu_page/

	    ChromePhp::log('admin_menu()');
	    add_menu_page( 'stemcells', 'stemcells', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page'), 'dashicons-cart');
	}

	public function display_plugin_setup_page() {
		include_once( 'partials/stemcells-admin-display.php' );
	}

	// add dashicons to front end
	//source=http://www.wpsuperstars.net/how-to-use-dashicons/
	public function load_dashicons_front_end() {
		wp_enqueue_style( 'dashicons' );
	}

	// public function load_paparser_front_end() {
	// 	wp_enqueue_script( 'papaparser', 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/4.3.2/papaparse.js', array( 'jquery '));
	// }
}
