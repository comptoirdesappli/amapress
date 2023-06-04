<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressRecette extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_recette';
	const POST_TYPE = 'recette';
	const CATEGORY = 'amps_recette_category';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_recette_photo');
//    }

	public function getContent_model() {
		return $this->getCustomAsString( 'amapress_recette_content_model' );
	}

	public function getProduits() {
		return $this->getCustomAsEntityArray( 'amapress_recette_produits', 'AmapressProduit' );
	}

	public function getCategories() {
		$this->ensure_init();
		$terms = get_the_terms( $this->ID, self::CATEGORY );
		if ( empty( $terms ) ) {
			return '';
		}
		$term_names = array_map( function ( $t ) {
			/** @var WP_Term $t */
			return $t->name;
		}, $terms );

		return implode( ', ', $term_names );
	}

	public function getCategoriesWithLinks() {
		$this->ensure_init();
		$terms = get_the_terms( $this->ID, self::CATEGORY );
		if ( empty( $terms ) ) {
			return '';
		}
		$term_names = array_map( function ( $t ) {
			/** @var WP_Term $t */
			return '<a href="' . esc_attr( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
		}, $terms );

		return implode( ', ', $term_names );
	}
}
