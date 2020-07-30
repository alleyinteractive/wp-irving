<?php
/**
 * WP Irving Integrations Manager
 *
 * @package WP_Irving
 */

 namespace WP_Irving;

 /**
  * Class for managing integrations in Irving.
  */
class Integrations {

    /**
     * Holds the option values to be set.
     */
    private $options;


    /**
     * Class instance.
     *
     * @var null|self
     */
    protected static $instance;

    /**
     * Get class instance.
     *
     * @return self
     */
    public static function instance() {
        if ( ! isset( static::$instance ) ) {
            static::$instance = new static();
            static::$instance->setup();
        }
        return static::$instance;
    }

    /**
     * Class constructor.
     */
    public function setup() {
		// Register admin page.
        add_action( 'admin_menu', [ $this, 'register_admin' ] );
        // Register settings fields for integrations.
        add_action( 'admin_init', [ $this, 'register_settings_fields' ], 10, 2 );
    }

    /**
     * Register settings fields for display.
     */
    public function register_settings_fields() {
        // Register a new setting for the integrations manager to consume/set.
        register_setting( 'wp_irving_integrations', 'irving_integrations' );

        // Register the section.
        add_settings_section(
            'irving_integrations_settings',
            __( 'Add keys for integrations to be passed to the front-end.', 'wp-irving' ),
            '',
            'wp_irving_integrations'
        );

        // Register a new field for the Google Analytics integration.
        add_settings_field(
            'irving_integrations_ga_key',
            __( 'Google Analytics Tracking ID', 'wp-irving' ),
            [ $this, 'get_ga_key' ],
            'wp_irving_integrations',
            'irving_integrations_settings',
            [
                'id' => 'irving_integrations_ga_key',
            ]
        );
    }

    public function get_ga_key( $args ) {
        $ga_key = $this->options[ $args[ 'id' ] ];

        ?>
            <input type="text" name="irving_integrations[<?php echo esc_attr( $args[ 'id' ] ); ?>]" value="<?php echo isset( $ga_key ) ? esc_attr( $ga_key ) : '' ?>" />
        <?php
    }

    /**
     * Render the admin page in the settings submenu.
     */
    public function register_admin() {
        add_submenu_page(
            'options-general.php',
            __( 'Irving Integrations', 'wp-irving' ),
            __( 'Irving Integrations', 'wp-irving' ),
            'manage_options',
            'wp-irving-integrations',
            [ $this, 'render' ]
        );
    }

    /**
     * Render the settings page.
     */
    public function render() {
        $this->options = get_option( 'irving_integrations' );

        // Check if the user have submitted the settings
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'irving_integrations_messages',
                'irving_integrations_message',
                __( 'Settings Saved', 'wp-irving' ),
                'updated'
            );
        }

        ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">
                    <?php esc_html_e( 'WP-Irving - Integrations Manager', 'wp-irving' ); ?>
                </h1>

                <hr class="wp-header-end">

                <form method="post" action="options.php">
                    <?php settings_fields( 'wp_irving_integrations' ); ?>
                    <?php do_settings_sections( 'wp_irving_integrations' ); ?>
                    <?php submit_button( __( 'Save Settings', 'wp-irving' ) ); ?>
                </form>
            </div>
        <?php
    }
}

add_action(
    'init',
    function() {
        \WP_Irving\Integrations::instance();
    }
);
