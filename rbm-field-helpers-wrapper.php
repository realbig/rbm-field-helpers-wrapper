<?php
/**
 * Plugin Name: RBM Field Helpers Wrapper
 * Plugin URI: https://github.com/realbig/rbm-field-helpers-wrapper
 * Description: A simple wrapper plugin for RBM Field Helpers. It uses the legacy "_rbm" prefix and may be a good option in cases where you have legacy RBM FH data and you do not want to bother migrating it
 * Version: 0.1.0
 * Text Domain: rbm-field-helpers-wrapper
 * Author: Eric Defore
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'RBM_Field_Helpers_Wrapper' ) ) {

	/**
	 * Main RBM_Field_Helpers_Wrapper class
	 *
	 * @since	  1.0.0
	 */
	class RBM_Field_Helpers_Wrapper {
		
		/**
		 * @var			array $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			array $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;
		
		/**
		 * @var			RBM_FieldHelpers RBM FH
		 * @since		{{VERSION}}
		 */
		public $field_helpers;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true RBM_Field_Helpers_Wrapper
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'rbm-field-helpers-wrapper' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'RBM_Field_Helpers_Wrapper_VER' ) ) {
				// Plugin version
				define( 'RBM_Field_Helpers_Wrapper_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'RBM_Field_Helpers_Wrapper_DIR' ) ) {
				// Plugin path
				define( 'RBM_Field_Helpers_Wrapper_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'RBM_Field_Helpers_Wrapper_URL' ) ) {
				// Plugin URL
				define( 'RBM_Field_Helpers_Wrapper_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'RBM_Field_Helpers_Wrapper_FILE' ) ) {
				// Plugin File
				define( 'RBM_Field_Helpers_Wrapper_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = RBM_Field_Helpers_Wrapper_DIR . '/languages/';
			$lang_dir = apply_filters( 'rbm_field_helpers_wrapper_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'rbm-field-helpers-wrapper' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'rbm-field-helpers-wrapper', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/rbm-field-helpers-wrapper/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/rbm-field-helpers-wrapper/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'rbm-field-helpers-wrapper', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/rbm-field-helpers-wrapper/languages/ folder
				load_textdomain( 'rbm-field-helpers-wrapper', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'rbm-field-helpers-wrapper', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			require_once __DIR__ . '/core/rbm-field-helpers/rbm-field-helpers.php';
		
			$this->field_helpers = new RBM_FieldHelpers( array(
				'ID'   => '_rbm', // Your Theme/Plugin uses this to differentiate its instance of RBM FH from others when saving/grabbing data
				'l10n' => array(
					'field_table'    => array(
						'delete_row'    => __( 'Delete Row', 'rbm-field-helpers-wrapper' ),
						'delete_column' => __( 'Delete Column', 'rbm-field-helpers-wrapper' ),
					),
					'field_select'   => array(
						'no_options'       => __( 'No select options.', 'rbm-field-helpers-wrapper' ),
						'error_loading'    => __( 'The results could not be loaded', 'rbm-field-helpers-wrapper' ),
						/* translators: %d is number of characters over input limit */
						'input_too_long'   => __( 'Please delete %d character(s)', 'rbm-field-helpers-wrapper' ),
						/* translators: %d is number of characters under input limit */
						'input_too_short'  => __( 'Please enter %d or more characters', 'rbm-field-helpers-wrapper' ),
						'loading_more'     => __( 'Loading more results...', 'rbm-field-helpers-wrapper' ),
						/* translators: %d is maximum number items selectable */
						'maximum_selected' => __( 'You can only select %d item(s)', 'rbm-field-helpers-wrapper' ),
						'no_results'       => __( 'No results found', 'rbm-field-helpers-wrapper' ),
						'searching'        => __( 'Searching...', 'rbm-field-helpers-wrapper' ),
					),
					'field_repeater' => array(
						'collapsable_title' => __( 'New Row', 'rbm-field-helpers-wrapper' ),
						'confirm_delete'    => __( 'Are you sure you want to delete this element?', 'rbm-field-helpers-wrapper' ),
						'delete_item'       => __( 'Delete', 'rbm-field-helpers-wrapper' ),
						'add_item'          => __( 'Add', 'rbm-field-helpers-wrapper' ),
					),
					'field_media'    => array(
						'button_text'        => __( 'Upload / Choose Media', 'rbm-field-helpers-wrapper' ),
						'button_remove_text' => __( 'Remove Media', 'rbm-field-helpers-wrapper' ),
						'window_title'       => __( 'Choose Media', 'rbm-field-helpers-wrapper' ),
					),
					'field_checkbox' => array(
						'no_options_text' => __( 'No options available.', 'rbm-field-helpers-wrapper' ),
					),
				),
			) );
			
			require_once __DIR__ . '/core/rbm-field-helpers-functions.php';
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'rbm-field-helpers-wrapper',
				RBM_Field_Helpers_Wrapper_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Field_Helpers_Wrapper_VER
			);
			
			wp_register_script(
				'rbm-field-helpers-wrapper',
				RBM_Field_Helpers_Wrapper_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Field_Helpers_Wrapper_VER,
				true
			);
			
			wp_localize_script( 
				'rbm-field-helpers-wrapper',
				'rBMFieldHelpersWrapper',
				apply_filters( 'rbm_field_helpers_wrapper_localize_script', array() )
			);
			
			wp_register_style(
				'rbm-field-helpers-wrapper-admin',
				RBM_Field_Helpers_Wrapper_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Field_Helpers_Wrapper_VER
			);
			
			wp_register_script(
				'rbm-field-helpers-wrapper-admin',
				RBM_Field_Helpers_Wrapper_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : RBM_Field_Helpers_Wrapper_VER,
				true
			);
			
			wp_localize_script( 
				'rbm-field-helpers-wrapper-admin',
				'rBMFieldHelpersWrapper',
				apply_filters( 'rbm_field_helpers_wrapper_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true RBM_Field_Helpers_Wrapper
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \RBM_Field_Helpers_Wrapper The one true RBM_Field_Helpers_Wrapper
 */
add_action( 'plugins_loaded', 'rbm_field_helpers_wrapper_load' );
function rbm_field_helpers_wrapper_load() {

	require_once __DIR__ . '/core/rbm-field-helpers-wrapper-functions.php';
	RBMFIELDHELPERSWRAPPER();

}
