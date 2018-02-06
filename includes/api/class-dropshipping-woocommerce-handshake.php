<?php
/**
 * REST API Dropshipping WooCommerce handshake Controller
 *
 * Handles requests to the /handshake endpoint.
 *
 * @author   Knawat.com
 * @category API
 * @package  Knawat_Dropshipping_Woocommerce/API
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( class_exists( 'WC_REST_System_Status_Controller' ) ):
/**
 * @package Knawat_Dropshipping_Woocommerce/API
 * @extends WC_REST_System_Status_Controller
 */
class Knawat_Dropshipping_Woocommerce_Handshake extends WC_REST_System_Status_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = KNAWAT_DROPWC_API_NAMESPACE;

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'handshake';


    /**
     * Register the route for /system_status
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_site_info' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );
    }

    /**
     * Get a system status info, by section.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_site_info( $request ) {
        $schema    = $this->get_item_schema();
        // Get default Woo Currency. remove all filters who change front-end currency.
        remove_all_filters( 'woocommerce_currency' );
        remove_all_filters( 'woocommerce_currency_symbol' );
        
        $mappings  = $this->get_item_mappings();
        $response  = array();
        $skip_sections = array( 'pages', 'database' );
        foreach ( $mappings as $section => $values ) {
            if( in_array( $section, $skip_sections ) ){
                continue;
            }
            foreach ( $values as $key => $value ) {
                if ( isset( $schema['properties'][ $section ]['properties'][ $key ]['type'] ) ) {
                    settype( $values[ $key ], $schema['properties'][ $section ]['properties'][ $key ]['type'] );
                }
            }
            settype( $values, $schema['properties'][ $section ]['type'] );
            $response[ $section ] = $values;
        }

        $response = $this->prepare_item_for_response( $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get site info like site_name, wp_version, plugins & theme
     *
     * @return array
     */
    function knawat_dropshipwc_get_siteinfo(){
        $site = array();
        $site['name'] = get_bloginfo('name');
        $site['url'] = get_site_url();
        $site['wp_version'] = get_bloginfo('version');
        if( is_multisite() ){
            $site['is_multisite'] = 1;
        }else{
            $site['is_multisite'] = 0;
        }
        $site['knawat_plugin_version'] = KNAWAT_DROPWC_VERSION;

        $site['plugins'] = $this->knawat_dropshipwc_get_active_plugins();
        $site['theme'] = $this->knawat_dropshipwc_get_active_theme();

        return $site;
    }

    /**
     * Get Active theme name & version.
     *
     * @return array
     */

    public function knawat_dropshipwc_get_active_theme(){
        $active_theme = wp_get_theme();
        $theme = array();
        if( !empty( $active_theme ) ){
            $theme['name']          = $active_theme->get('Name');
            $theme['theme_uri']     = $active_theme->get('ThemeURI');
            $theme['version']       = $active_theme->get('Version');
            $theme['stylesheet']    = $active_theme->get_stylesheet();
            $theme['template']      = $active_theme->get_template();
        }
        return $theme;
    }

    /**
     * Get Active Plugin names & versions.
     *
     * @return array
     */

    public function knawat_dropshipwc_get_active_plugins(){

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $active_plugins = get_option('active_plugins');
        $plugins = get_plugins();
        $activated_plugins = array();
        foreach ($active_plugins as $active_plugin ){           
            if( isset ( $plugins[$active_plugin] ) ) {
                $plugin = array();
                $plugin['Name']         = $plugins[$active_plugin]['Name'];
                $plugin['PluginURI']    = $plugins[$active_plugin]['PluginURI'];
                $plugin['Version']      = $plugins[$active_plugin]['Version'];
                $activated_plugins[$active_plugin] = $plugin;
            }
        }

        return $activated_plugins;
    }
}

add_action( 'rest_api_init', function () {
    $knawat_handshake = new Knawat_Dropshipping_Woocommerce_Handshake();
    $knawat_handshake->register_routes();
} );

endif;