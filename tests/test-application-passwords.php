<?php
/**
 * Class Test_Application_Passwords
 *
 * @package WP_Irving
 */

// phpcs:disable

namespace WP_Irving;

use WP_Irving\Integrations\Application_Passwords_Auth;
use WP_UnitTestCase;

/**
 * Test Application Passwords integration.
 */
class Test_Application_Passwords extends WP_UnitTestCase {
	protected $user;

	protected static $singleton;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$singleton = Application_Passwords_Auth::instance();
	}

	function set_up() {
		parent::set_up();

		$this->user = self::factory()->user->create_and_get(
			[
				'user_login' => 'irving-user',
				'user_pass'  => 'password',
			]
		);
	}

	public function tear_down() {
		// Reset some Application Password singleton properties.
		self::$singleton->session_token = null;
		self::$singleton->new_application_password = null;

		parent::tear_down();
	}

	public function test_login_creates_application_password() {
		$this->assertEmpty(
			\WP_Application_Passwords::get_user_application_passwords( $this->user->ID )
		);

		// Sign in.
		$_POST['log'] = $this->user->user_login;
		$_POST['pwd'] = 'password';
		wp_signon();

		$updated_app_pws = \WP_Application_Passwords::get_user_application_passwords( $this->user->ID );

		// Confirm that a new application password was created.
		$this->assertNotEmpty( $updated_app_pws );
		$this->assertMatchesRegularExpression(
			'/^' . self::$singleton->app_name . ' | ID .*? | EXP \d{10}$/',
			$updated_app_pws[0]['name']
		);
	}

	public function test_logout_removes_application_password() {
		// Prepare the server state.
		$_POST['log'] = $this->user->user_login;
		$_POST['pwd'] = 'password';
		wp_signon();
		wp_set_current_user( $this->user->ID );

		// Confirm that an application password exists for the user.
		$app_passwords = \WP_Application_Passwords::get_user_application_passwords( $this->user->ID );
		$this->assertNotEmpty( $app_passwords );

		$_COOKIE[ Application_Passwords_Auth::UUID_COOKIE_NAME ] = $app_passwords[0]['uuid'];

		wp_logout();

		$this->assertEmpty(
			\WP_Application_Passwords::get_user_application_passwords( $this->user->ID )
		);
	}

	public function test_expired_application_passwords_cannot_be_used() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		// Create an application password and verify it works.
		$app_pw = self::$singleton->create_application_password( $this->user->ID );
		$user   = wp_authenticate_application_password( null, $this->user->user_login, $app_pw['unhashed_password'] );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( $this->user->ID, $user->ID );

		// Set the expiration on the password to a long time ago.
		\WP_Application_Passwords::update_application_password(
			$this->user->ID,
			$app_pw['uuid'],
			[
				'name' => preg_replace( '/(?<=\| EXP )\d{10}$/', 1577854800, $app_pw['name'] ),
			]
		);

		// Confirm that this password is now rejected by Irving.
		$error = wp_authenticate_application_password( null, $this->user->user_login, $app_pw['unhashed_password'] );
		$this->assertWPError( $error );
		$this->assertSame( 'irving_application_password_expired', $error->get_error_code() );
	}

	public function test_old_irving_application_passwords_can_be_removed_automatically() {
		// Create an application password and update its expiration.
		$app_pw = self::$singleton->create_application_password( $this->user->ID );
		\WP_Application_Passwords::update_application_password(
			$this->user->ID,
			$app_pw['uuid'],
			[
				'name' => preg_replace( '/(?<=\| EXP )\d{10}$/', 1577854800, $app_pw['name'] ),
			]
		);

		$this->assertNotEmpty(
			\WP_Application_Passwords::get_user_application_passwords( $this->user->ID )
		);

		self::$singleton->prune_old_application_passwords( $this->user->ID );

		$this->assertEmpty(
			\WP_Application_Passwords::get_user_application_passwords( $this->user->ID )
		);
	}
}
