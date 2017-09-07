<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AmapressProduit extends TitanEntity {
	const INTERNAL_POST_TYPE = 'amps_produit';
	const POST_TYPE = 'produit';
	const CATEGORY = 'amps_produit_category';

	function __construct( $post_id ) {
		parent::__construct( $post_id );
	}

//    public function getPhoto()
//    {
//        return $this->getCustom('amapress_produit_photo');
//    }

	public function getProducteur() {
		return $this->getCustomAsEntity( 'amapress_produit_producteur', 'AmapressProducteur' );
	}

	public function getContent_model() {
		return $this->getCustom( 'amapress_produit_content_model' );
	}

	public function getQuantite_variable() {
		return $this->getCustom( 'amapress_produit_quantite_variable' );
	}

	public function getPrice() {
		return floatval( $this->getCustom( 'amapress_produit_price' ) );
	}

	public function getUnit() {
		return $this->getCustom( 'amapress_produit_unit' );
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
//class AmapressUser_produit_like extends TitanEntity
//{
//    const INTERNAL_POST_TYPE = 'amps_user_plike';
//    const POST_TYPE = 'user_produit_like';
//
//    function __construct($post_id)
//    {
//        parent::__construct($post_id);
//    }
//
//
//    private $user = null;
//
//    public function getUser()
//    {
//        $this->ensure_init();
//        $v = $this->custom['amapress_user_produit_like_user'];
//        if (empty($v)) return null;
//        if ($this->user == null) $this->user = AmapressUser::getBy($v);
//        return $this->user;
//    }
//
//    public function setUser($value)
//    {
//        update_post_meta($this->post->ID, 'amapress_user_produit_like_user', $value);
//        $this->user = null;
//    }
//
//
//    private $produit = null;
//
//    public function getProduit()
//    {
//        $this->ensure_init();
//        $v = $this->custom['amapress_user_produit_like_produit'];
//        if (empty($v)) return null;
//        if ($this->produit == null) $this->produit = AmapressUser::getBy($v);
//        return $this->produit;
//    }
//
//    public function setProduit($value)
//    {
//        update_post_meta($this->post->ID, 'amapress_user_produit_like_produit', $value);
//        $this->produit = null;
//    }
//
//
//    public function getLike()
//    {
//        $this->ensure_init();
//        return floatval($this->custom['amapress_user_produit_like_like']);
//    }
//
//    public function setLike($value)
//    {
//        update_post_meta($this->post->ID, 'amapress_user_produit_like_like', $value);
//    }
//
//}

