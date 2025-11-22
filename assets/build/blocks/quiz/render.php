<?php
/**
 * Quiz block render callback.
 *
 * Renders an interactive quiz block with question, options, and answer validation.
 *
 * @package Blank_Plugin
 * @param array $attributes Block attributes.
 * @return string Rendered block HTML.
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_array( $attributes ) ) {
	return '';
}

$blank_plugin_question        = isset( $attributes['question'] ) ? esc_html( $attributes['question'] ) : '';
$blank_plugin_options         = isset( $attributes['options'] ) ? (array) $attributes['options'] : array();
$blank_plugin_correct_answer  = isset( $attributes['correctAnswer'] ) ? esc_html( $attributes['correctAnswer'] ) : '';
$blank_plugin_current_user_id = get_current_user_id();
$blank_plugin_question_hash   = md5( $blank_plugin_question );
$blank_plugin_user_answer     = $blank_plugin_current_user_id ? get_user_meta( $blank_plugin_current_user_id, 'quiz_answer_' . $blank_plugin_question_hash, true ) : '';
$blank_plugin_result          = '';

// Check if user has submitted an answer.
if ( $blank_plugin_user_answer ) {
	$blank_plugin_is_correct = $blank_plugin_current_user_id && ( get_user_meta( $blank_plugin_current_user_id, 'quiz_correct_' . $blank_plugin_question_hash, true ) === '1' );
	$blank_plugin_message    = $blank_plugin_is_correct ?
		esc_html__( 'Correct!', 'blank-plugin' ) :
		/* translators: %s: Correct answer */
		sprintf( esc_html__( 'Incorrect. The correct answer is %s.', 'blank-plugin' ), $blank_plugin_correct_answer );
	$blank_plugin_result = '<div class="quiz-result"><p>' .
		/* translators: %s: User's answer */
		sprintf( esc_html__( 'Your answer: %s.', 'blank-plugin' ), esc_html( $blank_plugin_user_answer ) ) .
		' ' . $blank_plugin_message . '</p></div>';
}
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
	<div class="quiz-block">
		<h3><?php echo wp_kses_post( $blank_plugin_question ); ?></h3>
		<?php if ( ! $blank_plugin_user_answer && $blank_plugin_current_user_id ) : ?>
			<form class="quiz-form" data-question="<?php echo esc_attr( $blank_plugin_question ); ?>"
				data-correct-answer="<?php echo esc_attr( $blank_plugin_correct_answer ); ?>">
				<?php foreach ( $blank_plugin_options as $blank_plugin_option ) : ?>
					<label>
						<input type="radio" name="quiz_answer" value="<?php echo esc_attr( $blank_plugin_option ); ?>" required>
						<?php echo esc_html( $blank_plugin_option ); ?>
					</label><br>
				<?php endforeach; ?>
				<button type="submit" name="submit_quiz"><?php esc_html_e( 'Submit', 'blank-plugin' ); ?></button>
			</form>
		<?php elseif ( ! $blank_plugin_current_user_id ) : ?>
			<p><?php esc_html_e( 'Please log in to answer this quiz.', 'blank-plugin' ); ?></p>
		<?php endif; ?>
		<?php echo wp_kses_post( $blank_plugin_result ); ?>
	</div>
</div>
