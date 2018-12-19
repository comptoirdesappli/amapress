<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @return WP_Term[]|WP_Error
 */
function amapress_get_amap_roles() {
	return get_terms( array(
		'hide_empty' => false,
		'taxonomy'   => AmapressUser::AMAP_ROLE,
		'fields'     => 'all',
	) );
}

function amapress_get_amap_roles_editor() {
	$terms = amapress_get_amap_roles();
	usort( $terms, function ( $a, $b ) {
		/** @var WP_Term $a */
		/** @var WP_Term $b */
		return strcmp( $a->name, $b->name );
	} );

	$lieux = Amapress::get_lieux();

	$all_users = [];
//	foreach ( $lieux as $lieu ) {
//		$all_users[ $lieu->ID ] = [];
	/** @var WP_User $user */
	foreach (
		get_users_cached( array(
//				'amapress_lieu' => $lieu->ID,
			'fields' => 'all_with_meta',
		) ) as $user
	) {
		$all_users[ $user->ID ] = sprintf( '%s (%s)', $user->display_name, $user->user_email );
	}
//	}

	$ret = '<p>Pour éditer les rôles au sein du collectif utiliser le tableau ci-dessous.</p>';
	$ret .= '<p>Pour donner des droits d\'accès aux membres du collectif, modifier directement son profil utilisateur en le recherchant depuis le bandeau du site ou depuis la <a href="' . admin_url( 'users.php' ) . '">liste des utilisateurs</a></p>';
	$ret .= '<p>Pour modifier les référents producteurs, utiliser l\'onglet <a href="' . admin_url( 'admin.php?page=amapress_collectif&tab=amapress_edit_ref_prods' ) . '" class="button button-secondary">Référents producteurs</a></p>';

	$members_no_desc = array_map( function ( $user ) {
		$amapien = AmapressUser::getBy( $user );

		return AMapress::makeLink( $amapien->getEditLink(), $amapien->getDisplayName() . ' (' . $amapien->getEmail() . ')', true, true );
	}, get_users( [
		'amapress_role' => 'collectif_no_amap_role',
	] ) );
	if ( ! empty( $members_no_desc ) ) {
		$ret .= '<p><strong>Les membres suivants du collectif n\'ont pas de rôles</strong>. Il est souhaitable de les associer à un rôle descriptif :<br/>
' . implode( ', ', $members_no_desc ) . '</p>';
	}

	$ret .= '<table id="amap_role_editor_table" class="table display nowrap" width="100%" style="table-layout: auto" cellspacing="0">
<thead><tr><th>Rôle</th>';
	foreach ( $lieux as $lieu ) {
		$ret .= '<th>Amapiens de ' . esc_html( $lieu->getTitle() ) . '</th>';
	}
	$ret .= '</tr></thead><tbody>';
	foreach ( $terms as $term ) {
		$user_ids = get_objects_in_term( $term->term_id, $term->taxonomy );

		$ret .= '<tr><td>' . esc_html( $term->name ) . '</td>';
		foreach ( $lieux as $lieu ) {
			$lieu_user_ids = array();
			foreach ( $user_ids as $user_id ) {
				$user_lieux = AmapressUsers::get_user_lieu_ids( $user_id );
				if ( ! empty( $user_lieux ) && ! in_array( $lieu->ID, $user_lieux ) ) {
					continue;
				}

				$lieu_user_ids[] = $user_id;
			}
			$ret .= '<td><select id="amapress_amap_roles_' . $term->term_id . '_' . $lieu->getID() . '" name="amapress_amap_roles[' . $term->term_id . '][' . $lieu->getID() . '][]" class="autocomplete" multiple>' . tf_parse_select_options( $all_users, $lieu_user_ids, false ) . '</select></td>';
		}
		$ret .= '</tr>';
	}
	$ret .= '</tbody></table>';
	$ret .= '<script type="text/javascript">
jQuery(function() {
   $(\'select.autocomplete\').select2({
            allowClear: true,
            multiple: true,
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function (data) {
                return jQuery("<span>" + data.text + "</span>");
            },
            templateSelection: function (data) {
                return jQuery("<span>" + data.text + "</span>");
            },
            width: \'auto\',
        }); 
});
</script>';

	return $ret;
}

function amapress_save_amap_role_editor() {
	if ( isset( $_POST['amapress_amap_roles'] ) && is_array( $_POST['amapress_amap_roles'] ) ) {
		foreach ( $_POST['amapress_amap_roles'] as $role_id => $lieu_new_user_ids ) {
			$new_user_ids = [];
			foreach ( $lieu_new_user_ids as $user_ids ) {
				$new_user_ids = array_merge( $new_user_ids, $user_ids );
			}
			$new_user_ids = array_map( 'intval', $new_user_ids );
			$old_user_ids = get_objects_in_term( $role_id, AmapressUser::AMAP_ROLE );
			foreach ( $old_user_ids as $user_id ) {
				if ( ! in_array( $user_id, $new_user_ids ) ) {
					wp_remove_object_terms( $user_id, $role_id, AmapressUser::AMAP_ROLE );
				}
			}
			foreach ( $new_user_ids as $user_id ) {
				if ( ! in_array( $user_id, $old_user_ids ) ) {
					wp_add_object_terms( $user_id, $role_id, AmapressUser::AMAP_ROLE );
				}
			}
		}
	}
}

function amapress_get_referent_prods_grid() {
	$refs  = AmapressContrats::getReferentProducteursAndLieux( 'all' );
	$lieux = Amapress::get_lieux();
	$prods = Amapress::get_producteurs();
	usort( $prods, function ( $a, $b ) {
		/** @var AmapressProducteur $a */
		/** @var AmapressProducteur $b */
		return strcmp( $a->getTitle(), $b->getTitle() );
	} );

	$ret = '<table class="table display nowrap">
<thead>
<tr><th>Producteur</th>
<th>Contrat</th>
<th>Modèle</th>';
	foreach ( $lieux as $lieu ) {
		$ret .= '<th>' . esc_html( $lieu->getTitle() ) . '</th>';
	}
	$ret .= '</tr></thead><tbody>';

	foreach ( $prods as $prod ) {
		$contrats = AmapressContrats::get_contrats( $prod->getID(), false );
		usort( $contrats, function ( $a, $b ) {
			/** @var AmapressContrat $a */
			/** @var AmapressContrat $b */
			return strcmp( $a->getTitle(), $b->getTitle() );
		} );

		foreach ( $contrats as $contrat ) {
			$contrat_instances = AmapressContrats::get_active_contrat_instances_by_contrat( $contrat->getID() );
			usort( $contrat_instances, function ( $a, $b ) {
				/** @var AmapressContrat_instance $a */
				/** @var AmapressContrat_instance $b */
				return strcmp( $a->getTitle(), $b->getTitle() );
			} );

			foreach ( $contrat_instances as $contrat_instance ) {
				$ret .= '<tr>';
				$ret .= '<td>' . esc_html( $prod->getTitle() ) . '<br/>' . Amapress::makeButtonLink( $prod->getAdminEditLink(), 'Modifier ses référents', true, true ) . '</td>';
				$ret .= '<td>' . esc_html( $contrat->getTitle() ) . '<br/>' . Amapress::makeButtonLink( $contrat->getAdminEditLink(), 'Editer sa présentation', true, true ) . '</td>';
				$ret .= '<td>' . esc_html( $contrat_instance->getTitle() ) . '<br/>' . Amapress::makeButtonLink( $contrat_instance->getAdminEditLink(), 'Editer le modèle de contrat', true, true ) . '</td>';

				foreach ( $lieux as $lieu ) {
					$ret .= '<td>';

					$user_links = [];
					$user_ids   = [];
					foreach (
						from( $refs )->where( function ( $a ) use ( $lieu, $contrat_instance, $contrat, $prod ) {
							return $a['lieu'] == $lieu->ID
							       && $a['producteur'] == $prod->ID
							       && in_array( $contrat->ID, $a['contrat_ids'] )
							       && in_array( $contrat_instance->ID, $a['contrat_instance_ids'] );
						} ) as $ref
					) {
						$user = AmapressUser::getBy( $ref['ref_id'] );
						if ( $user && ! in_array( $user->ID, $user_ids ) ) {
							$user_ids[]   = $user->ID;
							$user_links[] = Amapress::makeLink( admin_url( 'user-edit.php?user_id=' . $user->ID ),
								sprintf( '%s (%s)', $user->getDisplayName(), $user->getEmail() ) );
						}
					}
					$ret .= implode( ', ', $user_links );
					$ret .= '</td>';
				}
				$ret .= '</tr>';
			}
		}
	}
	$ret .= '</tbody></table>';

	return $ret;
}

/**
 * Alters the User query
 * to return a different list based on query vars on users.php
 *
 * @param WP_User_Query $query
 */
function amapress_amap_role_user_query( $query ) {
	global $wpdb;
	$args       = array(
		'object_type' => array( 'user' ),
	);
	$taxonomies = get_taxonomies( $args, "names" );
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $query->query_vars[ $taxonomy ] ) ) {
			$term_ids = array();
			if ( '*' == $query->query_vars[ $taxonomy ] ) {
				$terms    = array_map(
					function ( $t ) {
						return $t->term_id;
					}, get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				) );
				$term_ids = get_objects_in_term( $terms, $taxonomy );
			} else {
				$term = get_term_by( 'slug', esc_attr( $query->query_vars[ $taxonomy ] ), $taxonomy );
				if ( $term ) {
					$term_ids = get_objects_in_term( $term->term_id, $taxonomy );
				}
			}
			if ( ! isset( $ids ) || empty( $ids ) ) {
				$ids = $term_ids;
			} else {
				$ids = array_intersect( $ids, $term_ids );
			}
		}
	}
	if ( isset( $ids ) ) {
		$ids_sql            = amapress_prepare_in_sql( wp_parse_id_list( $ids ) );
		$query->query_where .= " AND $wpdb->users.ID IN ($ids_sql)";
	}
}

function amapress_amap_role_table_query_args( $args ) {
	$taxonomies = get_taxonomies( array(
		'object_type'       => array( 'user' ),
		'show_admin_column' => true
	), "objects" );
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $_GET[ $taxonomy->name ] ) ) {
			$args[ $taxonomy->name ] = $_GET[ $taxonomy->name ];
		}
	}

	return $args;
}

/**
 * Fix a bug with highlighting the parent menu item
 * By default, when on the edit taxonomy page for a user taxonomy, the Posts tab is highlighted
 * This will correct that bug
 */
function amapress_amap_role_parent_menu( $parent = '' ) {
	global $pagenow;

	// If we're editing one of the user taxonomies
	// We must be within the users menu, so highlight that
	if ( ! empty( $_GET['taxonomy'] ) && $pagenow == 'edit-tags.php' && $_GET['taxonomy'] == AmapressUser::AMAP_ROLE ) {
		$parent = 'users.php';
	}

	return $parent;
}

/**
 * Add each of the taxonomies to the Users menu
 * They will behave in the same was as post taxonomies under the Posts menu item
 * Taxonomies will appear in alphabetical order
 */
function amapress_amap_role_admin_menu() {
	$key      = AmapressUser::AMAP_ROLE;
	$taxonomy = get_taxonomy( $key );
	add_users_page(
		$taxonomy->labels->menu_name,
		$taxonomy->labels->menu_name,
		$taxonomy->cap->manage_terms,
		"edit-tags.php?taxonomy={$key}"
	);
}

add_action( 'init', function () {
	add_filter( 'users_list_table_query_args', 'amapress_amap_role_table_query_args' );
	add_action( 'pre_user_query', 'amapress_amap_role_user_query', 12 );
	add_action( 'admin_menu', 'amapress_amap_role_admin_menu' );
	add_filter( 'parent_file', 'amapress_amap_role_parent_menu' );
} );

