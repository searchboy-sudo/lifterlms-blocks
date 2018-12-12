<?php
/**
 * Handle post migration to the block editor.
 *
 * @package  LifterLMS_Blocks/Classes
 * @since    1.0.0
 * @version  [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle post migration to the block editor.
 */
class LLMS_Blocks_Migrate {

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @version  [version]
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'migrate_post' ), 2 );
		add_action( 'wp', array( $this, 'remove_template_hooks' ) );

	}

	/**
	 * Retrieve the block template by post type.
	 *
	 * @param   string $post_type wp post type.
	 * @return  string
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function get_template( $post_type ) {

		if ( 'course' === $post_type ) {
			ob_start();

			?><!-- wp:llms/course-information /-->

<!-- wp:llms/instructors /-->

<!-- wp:llms/pricing-table /-->

<!-- wp:llms/course-progress -->
<div class="wp-block-llms-course-progress">[lifterlms_course_progress]</div>
<!-- /wp:llms/course-progress -->

<!-- wp:llms/course-continue-button -->
<div class="wp-block-llms-course-continue-button" style="text-align:center">[lifterlms_course_continue_button]</div>
<!-- /wp:llms/course-continue-button -->

<!-- wp:llms/course-syllabus /-->
			<?php

			return ob_get_clean();

		}

		if ( 'lesson' === $post_type ) {
			ob_start();

			?>
			<!-- wp:llms/lesson-progression /-->

<!-- wp:llms/lesson-navigation /-->
			<?php

			return ob_get_clean();
		}

		return '';

	}

	/**
	 * Migrate posts created prior to the block editor to have default LifterLMS templates
	 *
	 * @return  void
	 * @since   1.0.0
	 * @version [version]
	 */
	public function migrate_post() {

		global $pagenow, $post;

		if ( 'post.php' !== $pagenow || ! is_object( $post ) || ! in_array( $post->post_type, array( 'course', 'lesson' ), true ) ) {
			return;
		}

		// Already Migrated.
		if ( llms_parse_bool( get_post_meta( $post->ID, '_llms_blocks_migrated', true ) ) ) {
			return;
		}

		// Already Has blocks.
		if ( has_blocks( $post->post_content ) ) {
			update_post_meta( $post->ID, '_llms_blocks_migrated', 'yes' );
			return;
		}

		// Update the post.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_content' => $post->post_content . "\r\r" . $this->get_template( $post->post_type ),
			),
			array(
				'ID' => $post->ID,
			),
			array( '%s' ),
			array( '%d' )
		);

		// Save migration state.
		update_post_meta( $post->ID, '_llms_blocks_migrated', 'yes' );

		// Reload.
		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => $post->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		exit;

	}

	/**
	 * Removes core template action hooks from posts which have been migrated to the block editor
	 *
	 * @return  void
	 * @since   [version]
	 * @version [version]
	 */
	public function remove_template_hooks() {

		if ( ! llms_parse_bool( get_post_meta( get_the_ID(), '_llms_blocks_migrated', true ) ) ) {
			return;
		}

		// Coure Information.
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_meta_wrapper_start', 5 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_length', 10 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_difficulty', 20 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_course_tracks', 25 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_course_categories', 30 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_course_tags', 35 );
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_meta_wrapper_end', 50 );

		// Course Syllabus.
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_single_syllabus', 90 );

		// Instructors.
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_course_author', 40 );

		// Lesson Navigation.
		remove_action( 'lifterlms_single_lesson_after_summary', 'lifterlms_template_lesson_navigation', 20 );

		// Lesson Progression.
		remove_action( 'lifterlms_single_lesson_after_summary', 'lifterlms_template_complete_lesson_link', 10 );

		// Pricing Table.
		remove_action( 'lifterlms_single_course_after_summary', 'lifterlms_template_pricing_table', 60 );
		remove_action( 'lifterlms_single_membership_after_summary', 'lifterlms_template_pricing_table', 10 );

	}

}

return new LLMS_Blocks_Migrate();
