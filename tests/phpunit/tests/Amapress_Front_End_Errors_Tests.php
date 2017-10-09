<?php

class Amapress_Front_End_Errors_Tests extends Amapress_UnitTestCase {
	public function shortcode_DoesNot_NoticeOrThrow_Provider() {
		$this->create_amap();
		$ret = [];
		global $all_amapress_shortcodes;

		foreach ( $this->users as $user_id ) {
			foreach ( $all_amapress_shortcodes as $tag => $func ) {
				$ret["User $user_id / $tag"] = [ $user_id, $tag, $func ];
			}
		}

		return $ret;
	}

	/**
	 * @dataProvider shortcode_DoesNot_NoticeOrThrow_Provider
	 */
	public function testShortcode_DoesNot_NoticeOrThrow( $user_id, $tag, $func ) {
		$this->loginUser( $user_id );

		$func_dump = var_export( $func, true );
		$this->assertTrue( is_callable( $func, false ), "$func_dump is not callable" );
		do_shortcode( "[$tag]" );
	}

	public function post_DoesNot_NoticeOrThrow_Provider() {
		$this->create_amap();

		$ret = [];
		foreach ( $this->users as $user_id ) {
			foreach ( get_post_types() as $post_type ) {
				$posts = get_posts( [
					'posts_per_page' => - 1,
					'post_type'      => $post_type,
				] );
				foreach ( $posts as $post ) {
					$ret["User $user_id / $post->post_title"] = [ $user_id, $post ];
				}
			}
			$posts = get_posts( [
				'posts_per_page' => - 1,
				'post_type'      => AmapressContrat::INTERNAL_POST_TYPE,
			] );
			foreach ( $posts as $post ) {
				$ret["User $user_id / $post->post_title"] = [ $user_id, get_permalink( $post ) . '/details/' ];
			}
			$posts = get_posts( [
				'posts_per_page' => - 1,
				'post_type'      => AmapressDistribution::INTERNAL_POST_TYPE,
			] );
			foreach ( $posts as $post ) {
				$ret["User $user_id / $post->post_title"] = [ $user_id, get_permalink( $post ) . '/liste-emargement/' ];
			}
		}

		return $ret;
	}

	/**
	 * @dataProvider post_DoesNot_NoticeOrThrow_Provider
	 */
	public function testPost_DoesNot_NoticeOrThrow( $user_id, $post_or_url ) {
		$this->loginUser( $user_id );
		if ( is_string( $post_or_url ) ) {
			$this->call_url( $post_or_url );
		} else {
			$this->call_post( $post_or_url );
		}
	}
}

// EOF
