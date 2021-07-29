<?php
/**
 * WP Irving integration for Coral.
 *
 * @package WP_Irving;
 */

namespace WP_Irving\Integrations;

use WP_Irving\Singleton;
use WP_Irving\Integrations;

/**
 * Class to integrate Coral with Irving.
 */
class Coral {
	use Singleton;

	/**
	 * Post type for username records,
	 *
	 * @var string
	 */
	private $post_type = 'wp-irving-coral-user';

	/**
	 * The option key for the integration.
	 *
	 * @var string
	 */
	private $option_key = 'coral';

	/**
	 * Holds the option values to be set.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	private $cache_group = 'wp-irving-coral';

	/**
	 * Cron hook.
	 *
	 * @var string
	 */
	private $comment_count_cron_hook = 'wp_irving_coral_comment_count_update';

	/**
	 * Option key for storing the cron execution timestamp.
	 *
	 * @var string
	 */
	private $cron_timestamp_key = 'wp_irving_coral_comment_count_cron_execution_timestamp';

	/**
	 * Setup the singleton. Validate JWT is installed, and setup hooks.
	 */
	public function setup() {
		// Retrieve any existing integrations options.
		$this->options = get_option( 'irving_integrations' );

		// Register settings fields for integrations.
		add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		// Register a hidden post type for username records.
		add_action( 'init', [ $this, 'register_post_type' ] );

		$sso_secret = $this->options[ $this->option_key ]['sso_secret'] ?? false;

		if ( ! empty( $sso_secret ) ) {
			// Expose data to the endpoint.
			add_filter(
				'wp_irving_data_endpoints',
				function ( $endpoints ) {
					return array_merge( $endpoints, $this->get_endpoint_settings() );
				}
			);
		}

		add_action( "save_post_{$this->post_type}", [ $this, 'delete_cached_values' ], 10, 2 );

		// Add hook for updated permalinks so that changes can be passed along to Coral.
		add_action( 'added_post_meta', [ $this, 'check_for_updated_permalink' ], 10, 3 );

		// Set up cron if needed.
		$this->comment_count_cron_setup();
	}

	/**
	 * Register settings fields for display.
	 */
	public function register_settings_fields() {
		// Register new fields for the Coral integration.
		add_settings_field(
			'wp_irving_coral_url',
			esc_html__( 'Coral URL', 'wp-irving' ),
			[ $this, 'render_coral_url_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_sso_secret',
			esc_html__( 'Coral SSO Secret', 'wp-irving' ),
			[ $this, 'render_coral_sso_secret_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_admin_email',
			esc_html__( 'Coral Admin Email', 'wp-irving' ),
			[ $this, 'render_coral_admin_email_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_admin_password',
			esc_html__( 'Coral Admin Password', 'wp-irving' ),
			[ $this, 'render_coral_admin_password_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_use_cron',
			esc_html__( 'Use cron to fetch Coral comments?', 'wp-irving' ),
			[ $this, 'render_coral_use_cron_input' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);

		add_settings_field(
			'wp_irving_coral_banned_names',
			esc_html__( 'Coral Banned Username Values', 'wp-irving' ),
			[ $this, 'render_coral_banned_usernames_textarea' ],
			'wp_irving_integrations',
			'irving_integrations_settings'
		);
	}

	/**
	 * Register a post type for internal username record storage.
	 */
	public function register_post_type() {
		register_post_type(
			$this->post_type,
			[
				'public'       => false,
				'show_in_rest' => false,
				'supports'     => [ 'title', 'excerpt' ],
			]
		);
	}

	/**
	 * Render an input for the Coral URL.
	 */
	public function render_coral_url_input() {
		// Check to see if there is an existing URL in the option.
		$coral_url = $this->options[ $this->option_key ]['url'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'coral_url' ); ?>]" value="<?php echo esc_attr( $coral_url ); ?>" />
		<?php
	}

	/**
	 * Render an input for the Coral SSO secret.
	 */
	public function render_coral_sso_secret_input() {
		// Check to see if there is an existing SSO secret in the option.
		$sso_secret = $this->options[ $this->option_key ]['sso_secret'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'coral_sso_secret' ); ?>]" value="<?php echo esc_attr( $sso_secret ); ?>" />
		<?php
	}

	/**
	 * Render an input for the Coral admin email.
	 */
	public function render_coral_admin_email_input() {
		// Check to see if there is an existing admin email in the option.
		$admin_email = $this->options[ $this->option_key ]['admin_email'] ?? '';

		?>
			<input type="text" name="irving_integrations[<?php echo esc_attr( 'coral_admin_email' ); ?>]" value="<?php echo esc_attr( $admin_email ); ?>" />
		<?php
	}

	/**
	 * Render an input for the Coral admin password.
	 */
	public function render_coral_admin_password_input() {
		// Check to see if there is an existing admin password in the option.
		$admin_password = $this->options[ $this->option_key ]['admin_password'] ?? '';

		?>
			<input type="password" name="irving_integrations[<?php echo esc_attr( 'coral_admin_password' ); ?>]" value="<?php echo esc_attr( $admin_password ); ?>" />
		<?php
	}

	/**
	 * Render a checkbox which enables the use of cron to fetch comment counts.
	 */
	public function render_coral_use_cron_input() {
		$use_cron   = wp_validate_boolean( $this->options[ $this->option_key ]['use_cron'] ?? false );
		$is_checked = $use_cron ? 'checked' : '';

		?>
			<input type="checkbox" name="irving_integrations[<?php echo esc_attr( 'coral_use_cron' ); ?>]" value="true" <?php echo esc_attr( $is_checked ); ?> />
		<?php
	}

	/**
	 * Render a textarea for strings banned from being included in Coral usernames.
	 */
	public function render_coral_banned_usernames_textarea() {
		// Check to see if there are existing banned usernames in the option.
		$banned_names = $this->options[ $this->option_key ]['banned_names'] ?? '';

		?>
			<textarea id="coral_banned_names" rows="10" cols="50" type="text" name="irving_integrations[<?php echo esc_attr( 'coral_banned_names' ); ?>]"><?php echo esc_attr( $banned_names ); ?></textarea>
			<label for="coral_banned_names">
				<p>
					<em><?php echo esc_html__( 'Values banned from being included in usernames should be input as comma-separated values.', 'wp-irving' ); ?></em>
				</p>
				<p>
					<em><?php echo esc_html__( '(e.g. BannedStringOne,AnotherBannedString)', 'wp-irving' ); ?></em>
				</p>
			</label>
		<?php
	}

	/**
	 * Get the endpoint settings.
	 *
	 * @return array Endpoint settings.
	 */
	public function get_endpoint_settings(): array {
		return [
			[
				'slug'     => 'validate_sso_user',
				'callback' => [ $this, 'process_validate_endpoint_request' ],
			],
			[
				'slug'     => 'set_sso_username',
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'process_set_username_endpoint_request' ],
			],
			[
				'slug'     => 'check_sso_username_availability',
				'callback' => [ $this, 'process_username_availability_request' ],
			],
		];
	}

	/**
	 * Get the data for the Pico user endpoint verification request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public function process_validate_endpoint_request( \WP_REST_Request $request ) {
		// Allow access from the frontend.
		header( 'Access-Control-Allow-Origin: ' . home_url() );
		// Do not cache the endpoint.
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

		$user_obj = [
			'id'    => sanitize_text_field( $request->get_param( 'id' ) ),
			'email' => sanitize_text_field( $request->get_param( 'user' ) ),
		];

		// Verify the user's credentials.
		$verified_data = apply_filters( 'wp_irving_verify_coral_user', $user_obj );

		// Bail early if the verified user isn't confirmed by the verification filter.
		if (
			empty( $verified_data ) ||
			empty( $verified_data['id'] ) ||
			empty( $verified_data['email'] )
		) {
			return [ 'status' => 'failed' ];
		}

		// Check for existing username, since the verification call returned 200 OK.
		$username = $this->get_username( $verified_data['id'] );

		if ( '' !== $username ) {
			$credentials = [
				'jti'  => uniqid(),
				'exp'  => time() + ( 90 * DAY_IN_SECONDS ), // JWT will expire in 90 days.
				'iat'  => time(),
				'user' => [
					'id'       => $verified_data['id'],
					'email'    => $verified_data['email'],
					'username' => $username,
				],
			];

			return [
				'status'           => 'success',
				'require_username' => false,
				'jwt'              => $this->build_jwt( $credentials ),
			];
		}

		// If username is not set then return the hash scheme.
		return [
			'status'            => 'success',
			'require_username'  => true,
			'username_set_hash' => $this->create_username_set_hash( $verified_data['id'], $verified_data['email'] ),
		];
	}

	/**
	 * Accept requests to set a username for a given email address.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array|\WP_REST_Response
	 */
	public function process_set_username_endpoint_request( \WP_REST_Request $request ) {
		$params   = json_decode( $request->get_body() );
		$id       = sanitize_text_field( $params->id );
		$username = sanitize_text_field( $params->username );
		$hash     = sanitize_text_field( $params->hash );

		// The security check is performed in this function.
		$status = $this->set_username( $id, $username, $hash );

		if ( $status ) {
			return [
				'status'   => 'success',
				'id'       => $id,
				'username' => $username,
			];
		}

		return new \WP_REST_Response(
			[
				'status' => 'unauthorized',
			],
			403
		);
	}

	/**
	 * Handle username availability checks.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	public function process_username_availability_request( \WP_REST_Request $request ) {
		$username = sanitize_text_field( $request->get_param( 'username' ) );

		if ( ! $username || '' === $username ) {
			return [
				'username'  => '',
				'available' => false,
			];
		}

		// Handle an edge case where usernames ending with '?' character cause the Coral SSO request to crash.
		if ( '?' === substr( $username, -1, 1 ) ) {
			return [
				'username'  => $username,
				'available' => false,
			];
		}

		// Retrieve any set banned values for Coral usernames from the Integrations options table.
		$banned_values = Integrations\get_option_value( 'coral', 'banned_names' );

		if ( ! empty( $banned_values ) ) {
			// Turn the value string into an array.
			$banned_values_arr = explode( ',', $banned_values );

			foreach ( $banned_values_arr as $banned_value ) {
				// If the username contains a banned value, return a failure response.
				if ( false !== strpos( $username, $banned_value ) ) {
					return [
						'banned'       => true,
						'banned_value' => $banned_value,
					];
				}
			}
		}

		return [
			'username'  => $username,
			'available' => ! $this->username_exists( $username ),
		];
	}

	/**
	 * Construct a HS256-encrypted JWT for SSO authentication.
	 *
	 * @param array $credentials The user to be authenticated.
	 * @return string The constructed JWT.
	 */
	public function build_jwt( array $credentials ): string {
		// Define the JWT header and payload.
		$header  = wp_json_encode(
			[
				'typ' => 'JWT',
				'alg' => 'HS256',
			]
		);
		$payload = wp_json_encode( $credentials );
		$secret  = $this->options[ $this->option_key ]['sso_secret'];

		// Base64 URL encode the header and payload.
		$base64_header  = $this->base64url_encode( $header );
		$base64_payload = $this->base64url_encode( $payload );

		// Generate the JWT signature.
		$signature = hash_hmac( 'sha256', $base64_header . '.' . $base64_payload, $secret, true );
		// Base64 URL encode the signature.
		$base64_signature = $this->base64url_encode( $signature );

		// Return the built JWT.
		return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
	}

	/**
	 * Base64 URL encode a target data string.
	 *
	 * @param string $data The data to be encoded.
	 * @return string The encoded data.
	 */
	public function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Retrieve a username record. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id The SSO ID of the user.
	 * @return string The username, or a blank string if none is set.
	 */
	private function get_username( $id ): string {
		global $wpdb;

		$key             = "get_username_{$id}";
		$stored_username = wp_cache_get( $key, $this->cache_group );

		if ( ! empty( $stored_username ) ) {
			return $stored_username;
		}

		$username = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT post_excerpt
					FROM {$wpdb->posts}
				WHERE
					post_title=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1",
				[
					$id,
					$this->post_type,
				]
			)
		);

		/**
		 * Cache the username.
		 * The cached value is deleted when the username is updated.
		 */
		if ( ! empty( $username ) ) {
			wp_cache_set( $key, $username, $this->cache_group );
		}

		return $username ?? '';
	}

	/**
	 * Retrieve a username record's post ID. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id The SSO ID of the user.
	 * @return int The post ID, or 0 if none is found.
	 */
	private function get_username_post_id( $id ): int {
		global $wpdb;

		$key            = "get_username_post_id_{$id}";
		$stored_post_id = wp_cache_get( $key, $this->cache_group );

		if ( ! empty( $stored_post_id ) ) {
			return $stored_post_id;
		}

		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT ID
					FROM {$wpdb->posts}
				WHERE
					post_title=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1",
				[
					$id,
					$this->post_type,
				]
			)
		);

		/**
		 * Cache the post ID.
		 * This can be set forever since the SSO ID to post ID relationship will never change.
		 */
		if ( ! empty( $post_id ) ) {
			wp_cache_set( $key, $post_id, $this->cache_group );
		}

		return $post_id ?? 0;
	}

	/**
	 * Check if a username is available. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $username The username to check.
	 * @return bool Whether the name is already in use (true) or not (false).
	 */
	private function username_exists( $username ): int {
		global $wpdb;

		if ( ! $username || '' === $username ) {
			return false;
		}

		$key                    = "username_exists_{$username}";
		$stored_username_exists = wp_cache_get( $key, $this->cache_group );

		if ( false !== $stored_username_exists ) {
			return wp_validate_boolean( $stored_username_exists );
		}

		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT ID
					FROM {$wpdb->posts}
				WHERE
					post_excerpt=%s AND
					post_type=%s AND
					post_status='publish'
				LIMIT 1",
				[
					$username,
					$this->post_type,
				]
			)
		) ?? 0;

		/**
		 * Cache the ID for the username.
		 * The cached value is deleted when the username is added/updated.
		 */
		wp_cache_set( $key, $post_id, $this->cache_group );

		return ( 0 < $post_id );
	}

	/**
	 * Create or update a username record. These functions use the post_title column
	 * for the user's SSO provider ID, and the post_excerpt column for the username.
	 *
	 * @param string $id       The SSO ID of the user.
	 * @param string $username The associated username.
	 * @param string $hash     The security hash verifying the request.
	 * @return bool Whether the create/update operation succeeded.
	 */
	private function set_username( $id, $username, $hash ): bool {
		$key         = 'username_set_hash_' . md5( $id );
		$stored_hash = get_transient( $key );

		// Stop if the hash doesn't exist, wasn't passed, or doesn't match the one on file.
		if ( ! $stored_hash || ! $hash || $hash !== $stored_hash ) {
			return false;
		}

		// Stop if the username is already taken by someone else.
		if ( $this->username_exists( $username ) ) {
			return false;
		}

		$args = [
			'post_title'   => $id,
			'post_excerpt' => $username,
			'post_body'    => '',
			'post_type'    => $this->post_type,
			'post_status'  => 'publish',
		];

		$post_id = $this->get_username_post_id( $id );
		
		if ( 0 < $post_id ) {
			$args['ID'] = $post_id;
		}

		$post = wp_insert_post( $args );

		return ( ! is_wp_error( $post ) );
	}

	/**
	 * Create a one-time use hash key for username setting.
	 *
	 * @param string $id    The SSO ID for this user.
	 * @param string $email The email for which the hash is valid.
	 * @return string The hash.
	 */
	private function create_username_set_hash( $id, $email ): string {
		$message = implode(
			':',
			[
				$id,
				$email,
				strval( microtime() ),
				strval( random_int( 0, 32768 ) ),
			]
		);
		$hash    = hash( 'sha256', $message );
		$key     = 'username_set_hash_' . md5( $id );

		set_transient( $key, $hash, 3600 );

		return $hash;
	}

	/**
	 * Delete the cached values when a username post is updated. These
	 * functions use the post_title column for the user's SSO provider ID, and
	 * the post_excerpt column for the username.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function delete_cached_values( $post_id, $post ) {
		$id       = $post->post_title;
		$username = $post->post_excerpt;
		wp_cache_delete( "get_username_{$id}", $this->cache_group );
		wp_cache_delete( "username_exists_{$username}", $this->cache_group );
	}

	/**
	 * Return the post type for use outside this class.
	 *
	 * @return string The post type.
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Hook into added post meta and identify additions of `_wp_old_slug`.
	 * Pass along the updated permalink to Coral so that the post continues to
	 * be properly identified.
	 *
	 * @param int    $meta_id   ID of updated metadata entry.
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Metadata key.
	 */
	public function check_for_updated_permalink( $meta_id, $object_id, $meta_key ) {
		// Verify the meta key.
		if ( '_wp_old_slug' !== $meta_key ) {
			return;
		}

		// Validate Coral is in use.
		$coral_url = untrailingslashit( Integrations\get_option_value( 'coral', 'url' ) );
		if ( empty( $coral_url ) ) {
			return;
		}

		// Attempt to update the permalink.
		$data = $this->update_permalink_in_coral( $object_id );

		// If something went wrong, retry once.
		if ( null === $data ) {
			$data = $this->update_permalink_in_coral( $object_id );
		}

		// Validate the returned URL matches our expectation.
		if ( ( $data['updateStory']['story']['url'] ?? '' ) === get_the_permalink( $object_id ) ) {
			return;
		}

		add_filter( 'wp_mail_content_type', [ $this, 'wp_mail_content_type' ] );

		// Something else went wrong; email the site admin.
		wp_mail( /* phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail */
			get_option( 'admin_email' ),
			__( 'Unsuccessful Coral Story URL Update', 'wp-irving' ),
			sprintf(
				'<p>%1$s</p>',
				/* Translators: The post permalink. */
				sprintf( __( '%s not updated for Coral.', 'wp-irving' ), get_the_permalink( $object_id ) )
			)
		);

		remove_filter( 'wp_mail_content_type', [ $this, 'wp_mail_content_type' ] );
	}

	/**
	 * Email content type.
	 *
	 * @return string
	 */
	public function wp_mail_content_type() {
		return 'text/html';
	}

	/**
	 * API request.
	 *
	 * @param string $query GraphQL query.
	 * @return array|null
	 */
	private function api_request( $query ) {
		// Get the coral URL.
		$coral_url = untrailingslashit( Integrations\get_option_value( 'coral', 'url' ) );

		// Get JWT token.
		$token = $this->get_coral_jwt_token();

		// Fire authenticated request to Coral.
		$response = wp_remote_post(
			"{$coral_url}/api/graphql",
			[
				'body'    => wp_json_encode(
					[
						'query' => $query,
					]
				),
				'headers' => [
					'content-type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				],
			]
		);

		// The token was invalid, so delete the stored token for next time.
		if ( 401 === wp_remote_retrieve_response_code( $response ) ) {
			delete_transient( 'wp_irving_coral_jwt_token' );
			return null;
		}

		if ( $response instanceof \WP_Error ) {
			return null;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $response_body['data'] ?? null;
	}

	/**
	 * Update a post's URL in Coral.
	 *
	 * @param int $post_id Post ID.
	 * @return array|\WP_Error
	 */
	private function update_permalink_in_coral( $post_id ) {
		// Get the new permalink.
		$permalink = get_the_permalink( $post_id );

		// Construct the GraphQL query.
		$graphql_query = <<<EOD
mutation {
	updateStory(input: {
		id: {$post_id},
		story: {
			url: "{$permalink}"
		},
		clientMutationId: "updatePath{$permalink}"
	}) {
		story {
			id
			url
		}
	}
}
EOD;

		return $this->api_request( $graphql_query );
	}

	/**
	 * Get token for Coral, either from cache or via API request.
	 *
	 * @return string|null
	 */
	private function get_coral_jwt_token(): ?string {
		// Retrieve the cached token if available.
		$key   = 'wp_irving_coral_jwt_token';
		$token = get_transient( $key );
		if ( ! empty( $token ) ) {
			return $token;
		}

		$coral_url   = untrailingslashit( Integrations\get_option_value( 'coral', 'url' ) );
		$coral_email = Integrations\get_option_value( 'coral', 'admin_email' );
		$coral_pass  = Integrations\get_option_value( 'coral', 'admin_password' );

		// Validate data needed for request.
		if (
			empty( $coral_url )
			|| empty( $coral_email )
			|| empty( $coral_pass )
		) {
			return null;
		}

		// Get JWT token from Coral.
		$response = wp_remote_post(
			"{$coral_url}/api/auth/local",
			[
				'body'    => wp_json_encode(
					[
						'email'    => $coral_email,
						'password' => $coral_pass,
					]
				),
				'headers' => [
					'content-type' => 'application/json',
				],
			]
		);

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Get the token.
		$token = $response_body['token'] ?? '';

		// Bail if token wasn't properly retrieved.
		if ( empty( $token ) ) {
			return null;
		}

		// Cache for 90 days.
		set_transient( $key, $token, 90 * DAY_IN_SECONDS );

		return $token;
	}

	/**
	 * Set up cron for fetching comment counts, if enabled.
	 */
	public function comment_count_cron_setup() {
		// Bail if cron is not enabled.
		if ( ! ( $this->options[ $this->option_key ]['use_cron'] ?? false ) ) {
			return;
		}

		// Add a custom cron interval.
		add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );

		// Add hook for the cron job.
		add_action( $this->comment_count_cron_hook, [ $this, 'cron_exec' ] );

		// Schedule the cron job.
		if ( ! wp_next_scheduled( $this->comment_count_cron_hook ) ) {
			wp_schedule_event( time(), '15min', $this->comment_count_cron_hook );
		}

		// If on VIP, have cron actions use the jobs system.
		if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
			add_filter(
				'wpcom_vip_passthrough_cron_to_jobs',
				function( $filters ) {
					$filters[] = $this->comment_count_cron_hook;
					return $filters;
				}
			);
		}
	}

	/**
	 * Add a custom cron schedule.
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public function cron_schedules( $schedules ) {
		$schedules['15min'] = [
			'interval' => 15 * 60,
			'display'  => __( 'Once every 15 minutes', 'wp-irving' ),
		];
		return $schedules;
	}

	/**
	 * Cron action.
	 *
	 * @param int $limit Number of active stories to fetch, between 0 and 25.
	 *                   Optional, default 25.
	 */
	public function cron_exec( $limit = 25 ) {
		// Get the time of the last cron run.
		$last_run = get_option( $this->cron_timestamp_key, 0 );

		// Determine how many active stories to fetch. The API maximum is 25
		// (with the option to fetch all by setting the limit to 0).
		$limit = min( max( $limit, 0 ), 25 );

		// Override and fetch all stories if this is the first run.
		$limit = ( 0 === $last_run ) ? 0 : $limit;

		// Attempt to fetch comment count data.
		$data = $this->fetch_active_story_comment_counts( $limit );

		// Bail if there's no data.
		if ( null === $data ) {
			return;
		}

		$count = 0;
		foreach ( $data['activeStories'] as $story ) {
			// Increment the counter.
			$count++;

			// Get the WordPress post for this story.
			$post = $this->get_post_from_story( $story );

			if ( ! $post ) {
				continue;
			}

			// Save the comment count.
			update_post_meta( $post->ID, 'coral_comment_count', $story['commentCounts']['totalPublished'] ?? 0 );

			// If this story's most recent comment is older than the last cron
			// run, we are done processing new data and can stop.
			if ( strtotime( $story['lastCommentedAt'] ) < $last_run ) {
				break;
			}
		}

		// If we reached the end of the stories, then too few were fetched initially.
		if ( ( count( $data['activeStories'] ) === $count ) && ( 0 !== $limit ) ) {
			// Execute again, this time fetching all.
			$this->cron_exec( 0 );
			return;
		}

		// Update the time for the current cron run.
		update_option( $this->cron_timestamp_key, time() );
	}

	/**
	 * Get a post object from a story.
	 *
	 * @param array $story Story data.
	 * @return \WP_Post|null Post object, or null if no post was found.
	 */
	private function get_post_from_story( $story ): ?\WP_Post {
		// If the story ID is a WordPress post ID, use that.
		if ( is_numeric( $story['id'] ) ) {
			$post = get_post( $story['id'] );
			if ( $post instanceof \WP_Post ) {
				return $post;
			}
		}

		// Get the post ID from the URL.
		$post_id = function_exists( 'wpcom_vip_url_to_postid' )
			? wpcom_vip_url_to_postid( $story['url'] )
			: url_to_postid( $story['url'] ); /* phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid */

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if ( $post instanceof \WP_Post ) {
				return $post;
			}
		}

		return null;
	}

	/**
	 * Fetch the Coral comment counts for the stories most recently commented on.
	 *
	 * @param int $limit Number of active stories to fetch. Optional, default 25.
	 * @return array|null
	 */
	private function fetch_active_story_comment_counts( $limit = 25 ) {
		// Construct the GraphQL query.
		$graphql_query = <<<EOD
query {
	activeStories (limit: {$limit}) {
		id
		url
		commentCounts {
			totalPublished
		}
		lastCommentedAt
	}
}
EOD;

		return $this->api_request( $graphql_query );
	}
}
