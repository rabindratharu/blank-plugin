<?php
/**
 * Register Meta Boxes
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;
use Blank_Plugin\Inc\Utils;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Register meta boxes class.
 *
 * Handles registration of custom meta boxes for product reviews.
 *
 * @since 1.0.0
 */
class Meta_Boxes {

	use Singleton;

	/**
	 * Meta field keys
	 */
	const PRODUCT_NAME_FIELD  = 'review_item';
	const RATING_FIELD        = 'reviewer_rating';
	const REVIEWER_NAME_FIELD = 'reviewer_name';
	const NONCE_FIELD         = 'met_box_nonce';
	const POST_TYPE           = 'review';

	/**
	 * Private constructor to prevent direct object creation.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up action hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'init', array( $this, 'register_meta' ), 20 );
		add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post_meta_data' ) );
	}

	/**
	 * Register post meta.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_meta() {
		$post_meta = array(
			self::PRODUCT_NAME_FIELD  => array(
				'type'        => 'integer',
				'description' => __( 'The ID of the product being reviewed', 'blank-plugin' ),
			),
			self::RATING_FIELD        => array(
				'type'        => 'number',
				'description' => __( 'The rating given in the review (1-5)', 'blank-plugin' ),
			),
			self::REVIEWER_NAME_FIELD => array(
				'type'        => 'string',
				'description' => __( 'The name of the reviewer', 'blank-plugin' ),
			),
		);

		foreach ( $post_meta as $meta_key => $args ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $args['type'],
					'description'       => $args['description'],
					'sanitize_callback' => array( $this, 'sanitize_meta_data' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Sanitizes meta data based on the object type and meta key.
	 *
	 * @param mixed  $meta_value Value to sanitize.
	 * @param string $meta_key Meta key to sanitize.
	 * @param string $object_type Object type to sanitize.
	 * @return mixed Sanitized value.
	 */
	public function sanitize_meta_data( $meta_value, $meta_key, $object_type ) {
		if ( self::POST_TYPE !== $object_type ) {
			return $meta_value;
		}

		switch ( $meta_key ) {
			case self::PRODUCT_NAME_FIELD:
				return absint( $meta_value );
			case self::RATING_FIELD:
				$rating = absint( $meta_value );
				return ( $rating >= 1 && $rating <= 5 ) ? $rating : '';
			case self::REVIEWER_NAME_FIELD:
				return sanitize_text_field( $meta_value );
			default:
				return $meta_value;
		}
	}

	/**
	 * Add custom meta box for product reviews.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_custom_meta_box() {
		add_meta_box(
			'blank_plugin_meta_box',
			esc_html__( 'Review Details', 'blank-plugin' ),
			array( $this, 'render_meta_box_content' ),
			self::POST_TYPE,
			'normal',
			'high',
			array( '__back_compat_meta_box' => true )
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box_content( $post ) {
		// Verify post type.
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Get current values with proper sanitization.
		$product_name  = get_post_meta( $post->ID, self::PRODUCT_NAME_FIELD, true );
		$rating        = get_post_meta( $post->ID, self::RATING_FIELD, true );
		$reviewer_name = get_post_meta( $post->ID, self::REVIEWER_NAME_FIELD, true );

		// Get posts for dropdown.
		$products = Utils::get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Security field.
		wp_nonce_field(
			basename( __FILE__ ),
			self::NONCE_FIELD
		);
		?>
<div class="blank-plugin-meta-box-container">
	<!-- Product Selection -->
	<div class="blank-plugin-meta-box-field">
		<label for="<?php echo esc_attr( self::PRODUCT_NAME_FIELD ); ?>">
			<?php esc_html_e( 'Review Item', 'blank-plugin' ); ?>
		</label>
		<?php if ( ! empty( $products ) ) : ?>
		<select name="<?php echo esc_attr( self::PRODUCT_NAME_FIELD ); ?>"
			id="<?php echo esc_attr( self::PRODUCT_NAME_FIELD ); ?>" class="blank-plugin-select-field">
			<option value="">
				<?php esc_html_e( 'Select a Item', 'blank-plugin' ); ?>
			</option>
			<?php foreach ( $products as $product_id => $product_title ) : ?>
			<option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $product_name, $product_id ); ?>>
				<?php echo esc_html( $product_title ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php else : ?>
		<p class="blank-plugin-no-products">
			<?php esc_html_e( 'No items found', 'blank-plugin' ); ?>
		</p>
		<?php endif; ?>
	</div>

	<!-- Rating Selection -->
	<div class="blank-plugin-meta-box-field">
		<label for="<?php echo esc_attr( self::RATING_FIELD ); ?>">
			<?php esc_html_e( 'Rating (1-5)', 'blank-plugin' ); ?>
		</label>
		<select name="<?php echo esc_attr( self::RATING_FIELD ); ?>" id="<?php echo esc_attr( self::RATING_FIELD ); ?>"
			class="blank-plugin-select-field">
			<option value="">
				<?php esc_html_e( 'Select Rating', 'blank-plugin' ); ?>
			</option>
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
			<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
				<?php
					/* translators: 1: Star rating number, 2: 's' for plural if rating is more than 1 */
					printf(
						esc_html__( '%1$d Star %2$s', 'blank-plugin' ),
						esc_html( $i ),
						esc_html( $i > 1 ? 's' : '' )
					);
				?>
			</option>
			<?php endfor; ?>
		</select>
	</div>

	<!-- Reviewer Name -->
	<div class="blank-plugin-meta-box-field">
		<label for="<?php echo esc_attr( self::REVIEWER_NAME_FIELD ); ?>">
			<?php esc_html_e( 'Reviewer\'s Name', 'blank-plugin' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( self::REVIEWER_NAME_FIELD ); ?>"
			id="<?php echo esc_attr( self::REVIEWER_NAME_FIELD ); ?>" value="<?php echo esc_attr( $reviewer_name ); ?>"
			class="blank-plugin-text-field">
	</div>
</div>
		<?php
	}

	/**
	 * Save post meta data when the post is saved.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_post_meta_data( int $post_id ) {
		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify post type.
		if ( ! isset( $_POST['post_type'] ) || self::POST_TYPE !== $_POST['post_type'] ) {
			return;
		}

		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
				basename( __FILE__ )
			)
		) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save Review Item.
		if ( isset( $_POST[ self::PRODUCT_NAME_FIELD ] ) ) {
			update_post_meta(
				$post_id,
				self::PRODUCT_NAME_FIELD,
				absint( $_POST[ self::PRODUCT_NAME_FIELD ] )
			);
		}

		// Save Rating (1-5 only).
		if ( isset( $_POST[ self::RATING_FIELD ] ) ) {
			$rating = absint( $_POST[ self::RATING_FIELD ] );
			if ( $rating >= 1 && $rating <= 5 ) {
				$rating_value = $rating;
			} else {
				$rating_value = '';
			}
			update_post_meta(
				$post_id,
				self::RATING_FIELD,
				$rating_value
			);
		}

		// Save Reviewer's Name.
		if ( isset( $_POST[ self::REVIEWER_NAME_FIELD ] ) ) {
			update_post_meta(
				$post_id,
				self::REVIEWER_NAME_FIELD,
				sanitize_text_field( wp_unslash( $_POST[ self::REVIEWER_NAME_FIELD ] ) )
			);
		}
	}
}
