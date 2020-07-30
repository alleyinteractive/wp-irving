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
        register_setting( 'wp-irving-integrations', 'irving_integrations_options' );

        // Register the section.
        add_settings_section(
            'irving_integrations_settings',
            __( 'Add keys for integrations to be passed to the front-end.', 'wp-irving' ),
            [ $this, 'set_irving_integrations_keys' ],
            'irving_integrations'
        );

        // Register a new field for the Google Analytics integration.
        add_settings_field(
            'irving_integrations_ga_key',
            __( 'Google Analytics Tracking ID', 'wp-irving' ),
            'get_ga_key',
            'irving_integrations_settings'
        );
    }

    public function set_irving_integrations_keys() {
        ?>
            <p><?php esc_html_e( 'Follow the white rabbit.', 'wp-irving' ); ?></p>
        <?php
    }

    public function get_ga_key() {
        ?>
            <p><?php esc_html_e( 'Take the red pill.', 'wp-irving' ); ?></p>
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
        ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">
                    <?php esc_html_e( 'WP-Irving - Integrations Manager', 'wp-irving' ); ?>
                </h1>

                <hr class="wp-header-end">

                <form method="post">
                    <?php settings_fields( 'irving_integrations' ); ?>

                    <?php do_settings_sections( 'irving_integrations' ); ?>

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
