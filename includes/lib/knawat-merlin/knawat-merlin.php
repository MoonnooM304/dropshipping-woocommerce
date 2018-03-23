<?php
/**
 * Knawat_Merlin 
 * Better WordPress Setup Wizard
 *
 * The following code is a derivative work from the
 * Merlin WP by Richard Tabor.
 *
 * @package   Knawat_Merlin
 * @version   1.0.0
 * @link      https://knawat.com/
 * @author    Dharmesh Patel, from knawat.com
 * @copyright Copyright (c) 2018, knawat.com
 * @license   Licensed GPLv3 for open source use, or Knawat_Merlin Commercial License for commercial use
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Knawat_Merlin.
 */
class Knawat_Merlin {
	/**
	 * Current plugin.
	 *
	 * @var string
	 */
	protected $plugin;

	/**
	 * Current step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Steps.
	 *
	 * @var    array
	 */
	protected $steps = array();

	/**
	 * TGMPA instance.
	 *
	 * @var    object
	 */
	protected $tgmpa;

	
	/**
	 * The text string array.
	 *
	 * @var array $strings
	 */
	protected $strings = null;

	/**
	 * The location where Knawat_Merlin is located within the plugin.
	 *
	 * @var string $directory
	 */
	protected $directory = null;

	/**
	 * Top level admin page.
	 *
	 * @var string $merlin_url
	 */
	protected $merlin_url = null;

	/**
	 * Turn on dev mode if you're developing.
	 *
	 * @var string $dev_mode
	 */
	protected $dev_mode = false;

	/**
	 * Setup plugin version.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function version() {

		if ( ! defined( 'MERLIN_VERSION' ) ) {
			define( 'MERLIN_VERSION', '0.1.3' );
		}
	}

	/**
	 * Class Constructor.
	 *
	 * @param array $config Package-specific configuration args.
	 * @param array $strings Text for the different elements.
	 */
	function __construct( $config = array(), $strings = array() ) {

		$this->version();

		$config = wp_parse_args( $config, array(
			'directory' => '',
			'merlin_url'=> 'merlin',
			'dev_mode' 	=> '',
			'plugin'	=>	__( 'Knawat', 'dropshipping-woocommerce' )
		) );

		// Set config arguments.
		$this->directory 			= $config['directory'];		
		$this->merlin_url			= $config['merlin_url'];
		$this->dev_mode 			= $config['dev_mode'];
		$this->plugin 				= $config['plugin'];

		$this->slug  				= 'dropshipping-woocommerce';

		// Strings passed in from the config file.
		$this->strings 				= $strings;

		// Is Dev Mode turned on?
		/*if ( true != $this->dev_mode ) {

			// Has this plugin been setup yet?
			$already_setup 			= get_option( 'merlin_' . $this->slug . '_completed' );

			// Return if Knawat_Merlin has already completed it's setup.
			if ( $already_setup ) {
				return;
			}
		}*/

		// Get TGMPA.
		if ( class_exists( 'TGM_Plugin_Activation' ) ) {
			$this->tgmpa = isset( $GLOBALS['tgmpa'] ) ? $GLOBALS['tgmpa'] : TGM_Plugin_Activation::get_instance();
		}

		add_action( 'admin_init', array( $this, 'redirect' ), 30 );
		add_action( 'admin_init', array( $this, 'steps' ), 30, 0 );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_page' ), 30, 0 );
		add_action( 'admin_footer', array( $this, 'svg_sprite' ) );
		add_filter( 'tgmpa_load', array( $this, 'load_tgmpa' ), 10, 1 );
		add_action( 'wp_ajax_merlin_plugins', array( $this, '_ajax_plugins' ), 10, 0 );
		add_action( 'wp_ajax_merlin_knawat_connect', array( $this, 'merlin_knawat_connect' ), 10, 0 );
		add_action( 'upgrader_post_install', array( $this, 'post_install_check' ), 10, 2 );
	}

	/**
	 * Set redirection transient.
	 */
	public static function plugin_activated() {
		set_transient( 'dropshipping-woocommerce_merlin_redirect', 1 );
	}

	/**
	 * Redirection transient.
	 */
	public function redirect() {

		if ( ! get_transient( $this->slug . '_merlin_redirect' ) ) {
			return;
		}

		delete_transient( $this->slug . '_merlin_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page='.$this->merlin_url ) );

		exit;
	}

	/**
	 * Conditionally load TGMPA
	 *
	 * @param string $status User's manage capabilities.
	 */
	function load_tgmpa( $status ) {
		return is_admin() || current_user_can( 'install_plugins' );
	}

	/**
	 * Determine if the user already has theme content installed.
	 * This can happen if swapping from a previous theme or updated the current theme.
	 * We change the UI a bit when updating / swapping to a new theme.
	 *
	 * @access public
	 */
	protected function is_possible_upgrade() {
		return false;
	}

	/**
	 * After a theme update, we clear the slug_merlin_completed option.
	 * This prompts the user to visit the update page again.
	 *
	 * @param 		string $return To end or not.
	 * @param 		string $plugin  The current plugin.
	 */
	function post_install_check( $return, $plugin ) {

		if ( is_wp_error( $return ) ) {
			return $return;
		}

		update_option( 'merlin_' . $this->slug . '_completed', false );

		return $return;
	}

	/**
	 * Add the admin menu item, under Appearance.
	 */
	function add_admin_menu() {
		// Strings passed in from the config file.
		$strings = $this->strings;

		$this->hook_suffix = add_submenu_page( 'knawat_dropship',
			esc_html( $strings['admin-menu'] ), esc_html( $strings['admin-menu'] ), 'manage_options', $this->merlin_url, array( $this, 'admin_page' )
		);
	}

	/**
	 * Add the admin page.
	 */
	function admin_page() {

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Do not proceed, if we're not on the right page.
		if ( empty( $_GET['page'] ) || $this->merlin_url !== $_GET['page'] ) {
			return;
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

		// Use minified libraries if dev mode is turned on.
		//$suffix = ( ( true == $this->dev_mode ) ) ? '' : '.min';
		$suffix = '';

		// Enqueue styles.
		wp_enqueue_style( 'knawat-merlin', KNAWAT_DROPWC_PLUGIN_URL .'includes/lib/'. $this->directory . '/assets/css/knawat-merlin' . $suffix . '.css', array( 'wp-admin' ), MERLIN_VERSION );

		// Enqueue javascript.
		wp_enqueue_script( 'knawat-merlin', KNAWAT_DROPWC_PLUGIN_URL .'includes/lib/'. $this->directory . '/assets/js/knawat-merlin' . $suffix . '.js', array( 'jquery-core' ), MERLIN_VERSION );

		// Localize the javascript.
		if ( class_exists( 'TGM_Plugin_Activation' ) ) {
			// Check first if TMGPA is included.
			wp_localize_script( 'knawat-merlin', 'merlin_params', array(
				'tgm_plugin_nonce' 	=> array(
					'update'  	=> wp_create_nonce( 'tgmpa-update' ),
					'install' 	=> wp_create_nonce( 'tgmpa-install' ),
				),
				'tgm_bulk_url' 		=> $this->tgmpa->get_tgmpa_url(),
				'ajaxurl'      		=> admin_url( 'admin-ajax.php' ),
				'wpnonce'      		=> wp_create_nonce( 'merlin_nonce' ),
			) );
		} else {
			// If TMGPA is not included.
			wp_localize_script( 'knawat-merlin', 'merlin_params', array(
				'ajaxurl'      		=> admin_url( 'admin-ajax.php' ),
				'wpnonce'      		=> wp_create_nonce( 'merlin_nonce' ),
			) );
		}

		ob_start();

		/**
		 * Start the actual page content.
		 */
		$this->header(); ?>

		<div class="merlin__wrapper">

			<div class="merlin__content merlin__content--<?php echo esc_attr( strtolower( $this->steps[ $this->step ]['name'] ) ); ?>">

				<?php
				// Content Handlers.
				$show_content = true;

				if ( ! empty( $_REQUEST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
					$show_content = call_user_func( $this->steps[ $this->step ]['handler'] );
				}

				if ( $show_content ) {
					$this->body();
				} ?>

			<?php $this->step_output(); ?>

			</div>

			<?php echo sprintf( '<a class="return-to-dashboard" href="%s">%s</a>', esc_url( admin_url( '/' ) ), esc_html( $strings['return-to-dashboard'] ) ); ?>

		</div>

		<?php $this->footer(); ?>
		
		<?php
		exit;
	}

	/**
	 * Output the header.
	 */
	protected function header() {

		// Strings passed in from the config file.
		$strings = $this->strings; 

		// Get the current step.
		$current_step = strtolower( $this->steps[ $this->step ]['name'] ); ?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<?php printf( esc_html( $strings['title%s%s%s'] ), '<ti', 'tle>', '</title>' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="merlin__body merlin__body--<?php echo esc_attr( $current_step ); ?> merlin__drawer--open">
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	protected function body() {
		isset( $this->steps[ $this->step ] ) ? call_user_func( $this->steps[ $this->step ]['view'] ) : false;
	}

	/**
	 * Output the footer.
	 */
	protected function footer() {
		?>
		</body>
		<?php do_action( 'admin_footer' ); ?>
		<?php do_action( 'admin_print_footer_scripts' ); ?>
		</html>
		<?php
	}

	/**
	 * SVG
	 */
	function svg_sprite() {

		// Define SVG sprite file.
		$svg = KNAWAT_DROPWC_PLUGIN_DIR .'includes/lib/'. $this->directory . '/assets/images/sprite.svg';

		// If it exists, include it.
		if ( file_exists( $svg ) ) {
			require_once apply_filters( 'merlin_svg_sprite', $svg );
		}
	}

	/**
	 * Return SVG markup.
	 *
	 * @param array $args {
	 *     Parameters needed to display an SVG.
	 *
	 *     @type string $icon  Required SVG icon filename.
	 *     @type string $title Optional SVG title.
	 *     @type string $desc  Optional SVG description.
	 * }
	 * @return string SVG markup.
	 */
	function svg( $args = array() ) {

		// Make sure $args are an array.
		if ( empty( $args ) ) {
			return __( 'Please define default parameters in the form of an array.', 'dropshipping-woocommerce' );
		}

		// Define an icon.
		if ( false === array_key_exists( 'icon', $args ) ) {
			return __( 'Please define an SVG icon filename.', 'dropshipping-woocommerce' );
		}

		// Set defaults.
		$defaults = array(
			'icon'        => '',
			'title'       => '',
			'desc'        => '',
			'aria_hidden' => true, // Hide from screen readers.
			'fallback'    => false,
		);

		// Parse args.
		$args = wp_parse_args( $args, $defaults );

		// Set aria hidden.
		$aria_hidden = '';

		if ( true === $args['aria_hidden'] ) {
			$aria_hidden = ' aria-hidden="true"';
		}

		// Set ARIA.
		$aria_labelledby = '';

		if ( $args['title'] && $args['desc'] ) {
			$aria_labelledby = ' aria-labelledby="title desc"';
		}

		// Begin SVG markup.
		$svg = '<svg class="icon icon--' . esc_attr( $args['icon'] ) . '"' . $aria_hidden . $aria_labelledby . ' role="img">';

		// If there is a title, display it.
		if ( $args['title'] ) {
			$svg .= '<title>' . esc_html( $args['title'] ) . '</title>';
		}

		// If there is a description, display it.
		if ( $args['desc'] ) {
			$svg .= '<desc>' . esc_html( $args['desc'] ) . '</desc>';
		}

		$svg .= '<use xlink:href="#icon-' . esc_html( $args['icon'] ) . '"></use>';

		// Add some markup to use as a fallback for browsers that do not support SVGs.
		if ( $args['fallback'] ) {
			$svg .= '<span class="svg-fallback icon--' . esc_attr( $args['icon'] ) . '"></span>';
		}

		$svg .= '</svg>';

		return $svg;
	}

	/**
	 * Adds data attributes to the body, based on Customizer entries.
	 */
	function svg_allowed_html() {

		$array = array(
			'svg' => array(
				'class' => array(),
				'aria-hidden' => array(),
				'role' => array(),
			),
			'use' => array(
				'xlink:href' => array(),
			),
		);

		return apply_filters( 'merlin_svg_allowed_html', $array );

	}

	/**
	 * Loading merlin-spinner.
	 */
	function loading_spinner() {

		// Define the spinner file.
		$spinner = KNAWAT_DROPWC_PLUGIN_DIR .'includes/lib/'. $this->directory . '/assets/images/spinner.php';

		// Retrieve the spinner.
		include apply_filters( 'merlin_loading_spinner', $spinner );

	}

	/**
	 * Setup steps.
	 */
	function steps() {

		$this->steps = array(
			'welcome' => array(
				'name'    => esc_html__( 'Welcome', 'dropshipping-woocommerce' ),
				'view'    => array( $this, 'welcome' ),
				'handler' => array( $this, 'welcome_handler' ),
			),
		);

		$this->steps['knawat_connect'] = array(
			'name'    => esc_html__( 'Login with Knawat.com', 'dropshipping-woocommerce' ),
			'view'    => array( $this, 'knawat_connect' ),
			'handler' => array( $this, 'welcome_handler' ),
		);

		// Show the plugin importer, only if TGMPA is included.
		if ( class_exists( 'TGM_Plugin_Activation' ) ) {
			$this->steps['plugins'] = array(
				'name'    => esc_html__( 'Plugins', 'dropshipping-woocommerce' ),
				'view'    => array( $this, 'plugins' ),
			);
		}

		$this->steps['ready'] = array(
			'name'    => esc_html__( 'Ready', 'dropshipping-woocommerce' ),
			'view'    => array( $this, 'ready' ),
		);

		$this->steps = apply_filters( $this->slug . '_merlin_steps', $this->steps );
	}

	/**
	 * Output the steps
	 */
	protected function step_output() {
		$ouput_steps 	= $this->steps;
		$array_keys 	= array_keys( $this->steps );
		$current_step 	= array_search( $this->step, $array_keys );

		array_shift( $ouput_steps ); ?>

		<ol class="dots">

			<?php foreach ( $ouput_steps as $step_key => $step ) :

				$class_attr = '';
				$show_link = false;

				if ( $step_key === $this->step ) {
					$class_attr = 'active';
				} elseif ( $current_step > array_search( $step_key, $array_keys ) ) {
					$class_attr = 'done';
					$show_link = true;
				} ?>

				<li class="<?php echo esc_attr( $class_attr ); ?>">
					<a href="<?php echo esc_url( $this->step_link( $step_key ) ); ?>" title="<?php echo esc_attr( $step['name'] ); ?>"></a>
				</li>

			<?php endforeach; ?>

		</ol>

		<?php
	}

	/**
	 * Get the step URL.
	 *
	 * @param 	string $step Name of the step, appended to the URL.
	 */
	protected function step_link( $step ) {
		return add_query_arg( 'step', $step );
	}

	/**
	 * Get the next step link.
	 */
	protected function step_next_link() {
		$keys = array_keys( $this->steps );
		$step = array_search( $this->step, $keys ) + 1;

		return add_query_arg( 'step', $keys[ $step ] );
	}

	/**
	 * Introduction step
	 */
	protected function welcome() {

		// Has this plugin been setup yet? Compare this to the option set when you get to the last panel.
		$already_setup 			= get_option( 'merlin_' . $this->slug . '_completed' );
		
		// PLugin Name.
		$plugin 					= ucfirst( $this->plugin );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header 				= ! $already_setup ? $strings['welcome-header%s'] : $strings['welcome-header-success%s'];
		$paragraph 				= ! $already_setup ? $strings['welcome%s'] : $strings['welcome-success%s'];
		$start 					= $strings['btn-start'];
		$no 					= $strings['btn-no'];
		?>

		<div class="merlin__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'welcome' ) ), $this->svg_allowed_html() ); ?>
			
			<h1><?php echo esc_html( sprintf( $header, $plugin ) ); ?></h1>

			<p><?php echo esc_html( sprintf( $paragraph, $plugin ) ); ?></p>
	
		</div>

		<footer class="merlin__content__footer">
			<a href="<?php echo esc_url( wp_get_referer() && ! strpos( wp_get_referer(), 'update.php' ) ? wp_get_referer() : admin_url( '/' ) ); ?>" class="merlin__button merlin__button--skip"><?php echo esc_html( $no ); ?></a>
			<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--next merlin__button--proceed merlin__button--colorchange"><?php echo esc_html( $start ); ?></a>
			<?php wp_nonce_field( 'merlin' ); ?>
		</footer>

	<?php
	}

	/**
	 * Handles save button from welcome page.
	 * This is to perform tasks when the setup wizard has already been run.
	 */
	protected function welcome_handler() {

		check_admin_referer( 'merlin' );

		return false;
	}

	/**
	 * Final step
	 */
	protected function knawat_connect() {

		// Strings passed in from the config file.
		$strings = $this->strings;
		/*$knawat_options = get_option( KNAWAT_DROPWC_OPTIONS, array() );
		if( isset( $knawat_options['knawat_status'] ) && $knawat_options['knawat_status'] != '' && $knawat_options['knawat_status'] == 'connected' ) {
			$already_setup = 1;
		}*/
		$already_setup = 0;
		if( knawat_dropshipwc_is_connected() ){
			$already_setup = 1;	
		}
				
		// Text strings.
		$header 				= ! $already_setup ? $strings['knawat-header'] : $strings['knawat-header-success'];
		$paragraph 				= ! $already_setup ? $strings['knawat'] : $strings['knawat-success%s'];
		$action 				= $strings['knawat-action-link'];
		$skip 					= $strings['btn-skip'];
		$next 					= $strings['btn-next'];
		$install 				= $strings['btn-knawat-connect'];
		?>

		<div class="merlin__content--transition">
			
			<?php echo wp_kses( $this->svg( array( 'icon' => 'welcome' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( $header ); ?></h1>
				
			<p id="knawat-connect-text"><?php echo esc_html( $paragraph ); ?></p>

			<?php if( !$already_setup ){ ?> 
				<div class="knawat_connect_step_1" style="display: none;">
					<p><strong><?php _e( 'Step 1', 'dropshipping-woocommerce'); ?></strong></p>
					<a class="merlin__button merlin__button--blue merlin__button--fullwidth merlin__button--popin" onclick="KnawatPopup1();" style="margin-top: 15px; margin-bottom: 10px;">
						<?php _e( 'Login with Knawat.com', 'dropshipping-woocommerce'); ?>
					</a>

					<p><strong><?php _e( 'Step 2', 'dropshipping-woocommerce'); ?></strong></p>
					<span class="merlin__button merlin__button--blue merlin__button--fullwidth merlin__button--popin" style="margin-top: 15px;margin-bottom: 10px;background: #ddd;cursor: not-allowed;">
						<?php _e( 'Connect with Knawat.com', 'dropshipping-woocommerce'); ?>
					</span>
				</div>

				<div class="knawat_connect_step_2" style="display: block;">
					<!-- <p><strong><?php _e( 'Step 2', 'dropshipping-woocommerce'); ?></strong></p> -->
					<a class="merlin__button merlin__button--blue merlin__button--fullwidth merlin__button--popin" onclick="KnawatPopup2();" style="margin-top: 15px; margin-bottom: 10px;">
						<?php _e( 'Connect with Knawat.com', 'dropshipping-woocommerce'); ?>
					</a>
				</div>
			<?php }else{ ?>
				<div class="knawat_dropshipwc_connected" style="margin-top: 15px;">
					<span class="dashicons dashicons-yes" style="background-color: green;color: #fff;border-radius: 50%;padding: 4px 4px 3px 3px;"></span> 
					<strong style="color: green; font-size: 18px;" > <?php esc_html_e( 'Connected', 'dropshipping-woocommerce' ); ?></strong>
				</div>
			<?php } ?>

			<input id="knawat-connect-status" type="hidden" name="knawat_status" value="<?php if( $already_setup ){ echo 'connected'; } ?>" required="required" >

		</div>

		<form action="" method="post">

			<footer class="merlin__content__footer">
				
				<a id="close" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--skip merlin__button--closer merlin__button--proceed"><?php echo esc_html( $skip ); ?></a>
				<a id="skip" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--skip merlin__button--proceed"><?php echo esc_html( $skip ); ?></a>
				<a href="<?php echo esc_url( $this->step_next_link() ); ?>" id="knawat_connect_next" class="merlin_knawat merlin__button merlin__button--next button-next" data-callback="knawat_connect">
					<span class="merlin__button--loading__text"><?php echo esc_html( $install ); ?></span><?php echo $this->loading_spinner(); ?>
				</a>
				<?php wp_nonce_field( 'merlin' ); ?>
			</footer>
		</form>

	<?php
	}

	/**
	 * Theme plugins
	 */
	protected function plugins() {

		// Variables.
		$url     				= wp_nonce_url( add_query_arg( array( 'plugins' => 'go' ) ), 'merlin' );
		$method  				= '';
		$fields 				= array_keys( $_POST );
		$creds   				= request_filesystem_credentials( esc_url_raw( $url ), $method, false, false, $fields );

		tgmpa_load_bulk_installer();

		if ( false === $creds ) {
			return true;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, $fields );
			return true;
		}

		// Are there plugins that need installing/activating?
		$plugins 				= $this->get_tgmpa_plugins();
		$count 					= count( $plugins['all'] );
		$class 					= $count ? null : 'no-plugins';

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header 				= $count ? $strings['plugins-header'] : $strings['plugins-header-success'];
		$paragraph 				= $count ? $strings['plugins'] : $strings['plugins-success%s'];
		$action 				= $strings['plugins-action-link'];
		$skip 					= $strings['btn-skip'];
		$next 					= $strings['btn-next'];
		$install 				= $strings['btn-plugins-install'];
		?>

		<div class="merlin__content--transition">
			
			<?php echo wp_kses( $this->svg( array( 'icon' => 'plugins' ) ), $this->svg_allowed_html() ); ?>

			<svg class="icon icon--checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
				<circle class="icon--checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			</svg>

			<h1><?php echo esc_html( $header ); ?></h1>
				
			<p><?php echo esc_html( $paragraph ); ?></p>

			<?php if ( $count ) { ?>
				<a id="merlin__drawer-trigger" class="merlin__button merlin__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>
			<?php  } ?>

		</div>

		<form action="" method="post">

			<?php if ( $count ) : ?>

				<ul class="merlin__drawer merlin__drawer--install-plugins">
				
				<?php 
				$required_plugins = array();
				$recommanded_plugins = array();
				if( !empty( $plugins['all'] ) ){
					foreach ( $plugins['all'] as $slug1 => $plugin1 ){
						if( isset( $plugin1['required'] ) && $plugin1['required'] == 1 ){
							$required_plugins[$slug1] = $plugin1;
						}else{
							$recommanded_plugins[$slug1] = $plugin1;
						}
					}
					$plugins['all'] = array_merge( $required_plugins, $recommanded_plugins );	
				}				

				foreach ( $plugins['all'] as $slug => $plugin ) : ?>

					<li class="merlin__drawer--install-plugins__list-item status status--Pending" data-slug="<?php echo esc_attr( $slug ); ?>">
						<input type="checkbox" name="<?php echo esc_attr( $slug ); ?>" class="checkbox" id="default_plugin_<?php echo esc_attr( $slug ); ?>" value="1" <?php echo ( ! isset( $plugin['required'] ) || $plugin['required'] ) ? ' checked disabled' : ''; ?> data-slug="<?php echo esc_attr( $slug ); ?>">
						<label for="default_plugin_<?php echo esc_attr( $slug ); ?>">
							<i></i>
							<span><a href="//wordpress.org/plugins/<?php echo esc_attr( $slug ); ?>/" target="_blank"><?php echo esc_html( $plugin['name'] ); ?></a>*</span>
						</label>

						<span class="iplugins">
							<?php
							$keys = array();

							if ( isset( $plugins['install'][ $slug ] ) ) {
								$keys[] = esc_html__( 'Install', 'dropshipping-woocommerce' );
							}
							if ( isset( $plugins['update'][ $slug ] ) ) {
								$keys[] = esc_html__( 'Update', 'dropshipping-woocommerce' );
							}
							if ( isset( $plugins['activate'][ $slug ] ) ) {
								$keys[] = esc_html__( 'Activate', 'dropshipping-woocommerce' );
							}
							echo implode( esc_html__( 'and', 'dropshipping-woocommerce' ) , $keys );
							?>
						</span>

						<div class="spinner"></div>

					</li>
				<?php endforeach; ?>

				</ul>
				<br />
				<small style="color: #a1a5a8;"><?php _e('* Developed by third party','dropshipping-woocommerce'); ?></small>
			<?php endif; ?>

			<footer class="merlin__content__footer <?php echo esc_attr( $class ); ?>">
				<?php if ( $count ) : ?>
					<a id="close" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--skip merlin__button--closer merlin__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a id="skip" href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--skip merlin__button--proceed"><?php echo esc_html( $skip ); ?></a>
					<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--next button-next" data-callback="install_plugins">
						<span class="merlin__button--loading__text"><?php echo esc_html( $install ); ?></span><?php echo $this->loading_spinner(); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $this->step_next_link() ); ?>" class="merlin__button merlin__button--next merlin__button--proceed merlin__button--colorchange"><?php echo esc_html( $next ); ?></a>
				<?php endif; ?>
				<?php wp_nonce_field( 'merlin' ); ?>
			</footer>
		</form>

	<?php
	}

	/**
	 * Final step
	 */
	protected function ready() {

		// Theme Name.
		$plugin 					= ucfirst( $this->plugin );

		// Strings passed in from the config file.
		$strings = $this->strings;

		// Text strings.
		$header 				= $strings['ready-header'];
		$paragraph 				= $strings['ready'];
		$action 				= $strings['ready-action-link'];
		$skip 					= $strings['btn-skip'];
		$next 					= $strings['btn-next'];
		$big_btn 				= $strings['ready-big-button'];

		// Links.
		$link_1 				= $strings['ready-link-1'];
		$link_2 				= $strings['ready-link-2'];
		$link_3 				= $strings['ready-link-3'];

		$allowed_html_array = array(
			'a' => array(
				'href' 		=> array(),
				'title' 	=> array(),
				'target' 	=> array(),
			),
		);

		update_option( 'merlin_' . $this->slug . '_completed', time() ); ?>

		<div class="merlin__content--transition">

			<?php echo wp_kses( $this->svg( array( 'icon' => 'done' ) ), $this->svg_allowed_html() ); ?>
			
			<h1><?php echo esc_html( sprintf( $header, $plugin ) ); ?></h1>

			<p><?php echo esc_html( $paragraph ); ?></p>

		</div>

		<footer class="merlin__content__footer merlin__content__footer--fullwidth">
			
			<a href="<?php echo esc_url( 'https://app.knawat.com/autologin' ); ?>" class="merlin__button merlin__button--blue merlin__button--fullwidth merlin__button--popin"><?php echo esc_html( $big_btn ); ?></a>
			
			<a id="merlin__drawer-trigger" class="merlin__button merlin__button--knockout"><span><?php echo esc_html( $action ); ?></span><span class="chevron"></span></a>
			
			<ul class="merlin__drawer merlin__drawer--extras">

				<li><?php echo wp_kses( $link_1, $allowed_html_array ); ?></li>
				<li><?php echo wp_kses( $link_2, $allowed_html_array ); ?></li>
				<!-- <li><?php //echo wp_kses( $link_3, $allowed_html_array ); ?></li> -->

			</ul>

		</footer>

	<?php
	}

	/**
	 * Get registered TGMPA plugins
	 *
	 * @return    array
	 */
	protected function get_tgmpa_plugins() {
		$plugins  = array(
			'all'      => array(), // Meaning: all plugins which still have open actions.
			'install'  => array(),
			'update'   => array(),
			'activate' => array(),
		);
		
		foreach ( $this->tgmpa->plugins as $slug => $plugin ) {
			if ( $this->tgmpa->is_plugin_active( $slug ) && false === $this->tgmpa->does_plugin_have_update( $slug ) ) {
				continue;
			} else {
				if( !isset( $plugin['recommended_by'] ) ){
					continue;
				}
				$plugins['all'][ $slug ] = $plugin;
				if ( ! $this->tgmpa->is_plugin_installed( $slug ) ) {
					$plugins['install'][ $slug ] = $plugin;
				} else {
					if ( false !== $this->tgmpa->does_plugin_have_update( $slug ) ) {
						$plugins['update'][ $slug ] = $plugin;
					}
					if ( $this->tgmpa->can_plugin_activate( $slug ) ) {
						$plugins['activate'][ $slug ] = $plugin;
					}
				}
			}
		}
		return $plugins;
	}

	/**
	 * Do plugins' AJAX
	 *
	 * @internal    Used as a calback.
	 */
	function _ajax_plugins() {

		if ( ! check_ajax_referer( 'merlin_nonce', 'wpnonce' ) || empty( $_POST['slug'] ) ) {
			exit( 0 );
		}

		$json = array();
		$tgmpa_url = $this->tgmpa->get_tgmpa_url();
		$plugins = $this->get_tgmpa_plugins();

		foreach ( $plugins['activate'] as $slug => $plugin ) {
			if ( $_POST['slug'] === $slug ) {
				$json = array(
					'url'           => $tgmpa_url,
					'plugin'        => array( $slug ),
					'tgmpa-page'    => $this->tgmpa->menu,
					'plugin_status' => 'all',
					'_wpnonce'      => wp_create_nonce( 'bulk-plugins' ),
					'action'        => 'tgmpa-bulk-activate',
					'action2'       => - 1,
					'message'       => esc_html__( 'Activating', 'dropshipping-woocommerce' ),
				);
				break;
			}
		}

		foreach ( $plugins['update'] as $slug => $plugin ) {
			if ( $_POST['slug'] === $slug ) {
				$json = array(
					'url'           => $tgmpa_url,
					'plugin'        => array( $slug ),
					'tgmpa-page'    => $this->tgmpa->menu,
					'plugin_status' => 'all',
					'_wpnonce'      => wp_create_nonce( 'bulk-plugins' ),
					'action'        => 'tgmpa-bulk-update',
					'action2'       => - 1,
					'message'       => esc_html__( 'Updating', 'dropshipping-woocommerce' ),
				);
				break;
			}
		}

		foreach ( $plugins['install'] as $slug => $plugin ) {
			if ( $_POST['slug'] === $slug ) {
				$json = array(
					'url'           => $tgmpa_url,
					'plugin'        => array( $slug ),
					'tgmpa-page'    => $this->tgmpa->menu,
					'plugin_status' => 'all',
					'_wpnonce'      => wp_create_nonce( 'bulk-plugins' ),
					'action'        => 'tgmpa-bulk-install',
					'action2'       => - 1,
					'message'       => esc_html__( 'Installing', 'dropshipping-woocommerce' ),
				);
				break;
			}
		}

		if ( $json ) {
			$json['hash'] = md5( serialize( $json ) );
			wp_send_json( $json );
		} else {
			wp_send_json( array( 'done' => 1, 'message' => esc_html__( 'Success', 'dropshipping-woocommerce' ) ) );
		}

		exit;
	}

	/**
	 * Save Knawat API Key.
	 */
	function merlin_knawat_connect() {

		if ( ! check_ajax_referer( 'merlin_nonce', 'wpnonce' ) ) {
			exit( 0 );
		}

		if( empty( $_POST['kAPIKey'] ) || $_POST['kAPIKey'] != 'connected' ){
			wp_send_json(
				array(
					'error' => esc_html__( 'Please Complete Process Step 1 & Step 2 for connect your site with Knawat.', 'dropshipping-woocommerce' )
				)
			);
		}
		
		$kAPIKey = sanitize_text_field( $_POST['kAPIKey'] );
		$knawat_options = get_option( KNAWAT_DROPWC_OPTIONS, array() );
		$knawat_options['knawat_status'] = $kAPIKey;
		update_option( KNAWAT_DROPWC_OPTIONS, $knawat_options );

		wp_send_json(
			array(
				'done' => 1,
				'message' => esc_html__( 'Success', 'dropshipping-woocommerce' )
			)
		);
	}
}
