<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class RP4WP_Hook_Related_Save_Words extends RP4WP_Hook {
	protected $tag = 'transition_post_status';
	protected $args = 3;

	public function run( $new_status, $old_status, $post ) {

		$args = array(
	    'public'                => true,
	    '_builtin'              => false
		);
		$output = 'names'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'
		$get_post_types = get_post_types($args,$output,$operator);
		$uncode_related_post_types = array();
		if (($key = array_search('uncodeblock', $get_post_types)) !== false) {
	    unset($get_post_types[$key]);
		}
		$uncode_related_post_types[] = 'post';
		$uncode_related_post_types[] = 'page';
		foreach ($get_post_types as $key => $value) {
			$uncode_related_post_types[] = $key;
		}

		// verify this is not an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( !in_array($post->post_type, $uncode_related_post_types )) {
			return;
		}

		// Post status must be publish
		if ( 'publish' != $new_status ) {
			return;
		}

		// Save Words
		$related_word_manager = new RP4WP_Related_Word_Manager();
		$related_word_manager->save_words_of_post( $post->ID );

	}
}