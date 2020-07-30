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
