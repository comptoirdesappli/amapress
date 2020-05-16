<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function amapress_tabs_model_editor( $postID, $option ) {
	$conf = $option->getValue();
	if ( ! $conf ) {
		$conf = array();
	}

	$id = $option->getID();

	ob_start();

	echo "<table id='$id-tab' style='width:100%'>";
	echo '<thead><tr><th style="width: 15%">Mod&egrave;le</th><th>Tabs</th></tr></thead>';
	echo '<tbody>';
	foreach ( $conf as $n => $tabconf ) {
		echo '<tr>';
		$name = esc_attr( $tabconf['name'] );
		$cid  = esc_attr( $tabconf['id'] );
		echo "<td>Id: <input type='text' name='{$id}[$n][id]' value='$cid' class='required' /> Nom:<input type='text' name='{$id}[$n][name]' value='$name' class='required' /><span class='btn del-model dashicons dashicons-dismiss' onclick='amapress_{$id}_del_model(this)' /></td>";
		echo "<td><ul>";
		if ( is_array( $tabconf['tabs'] ) ) {
			foreach ( $tabconf['tabs'] as $nn => $txt ) {
				$txt_att = esc_attr( $txt );
				echo "<li class='btn'><input type='text' name='{$id}[$n][tabs][]' value='$txt_att' /><span class='btn del-model-tab dashicons dashicons-dismiss' onclick='amapress_{$id}_del_model_tab(this)' /></li>";
			}
		}
		echo "<li class='btn'><span class='btn add-model-tab dashicons dashicons-plus-alt' data-rid='$n' onclick='amapress_{$id}_add_model_tab(this)'></span> tab</li>";
		echo "</ul></td>";
		echo '</tr>';
	}
	echo '<tr><td><span class="btn add-model dashicons dashicons-plus-alt" onclick="amapress_' . $id . '_add_model(this)" data-max="' . count( $conf ) . '"></span> modèle</td></tr>';
	echo '</tbody>';
	echo '</table>';

	echo "<script type='text/javascript'>//<![CDATA[
    function amapress_{$id}_add_model(e) {
        var max = jQuery(e).data('max') || 1;
        max++;
        jQuery(e).data('max', max);
        var html = '<tr>' +
         '<td>Id:<input type=\'text\' name=\'{$id}['+max+'][id]\' value=\'id'+max+'\' class=\'required\' />Nom: <input type=\'text\' name=\'{$id}['+max+'][name]\' value=\'modèle '+max+'\' class=\'required\' /><span class=\'btn del-model dashicons dashicons-dismiss\' onclick=\'amapress_{$id}_del_model(this)\' /></td>' +
         '<td><ul><li class=\'btn\'><span class=\'btn add-model-tab dashicons dashicons-plus-alt\' data-rid=\''+max+'\' onclick=\'amapress_{$id}_add_model_tab(this)\' /></li></ul>' +
         '</tr>';
        jQuery(html).insertBefore(jQuery(e).closest('tr'));
    };
    function amapress_{$id}_add_model_tab(e) {
        jQuery('<li class=\'btn\'><input type=\'text\' name=\'{$id}['+jQuery(e).data('rid')+'][tabs][]\' value=\'tab\' class=\'required\' /><span class=\'btn del-model-tab dashicons dashicons-dismiss\' onclick=\'amapress_del_model_tab(this)\' /></li>').insertBefore(jQuery(e).closest('li'));
    };
    function amapress_{$id}_del_model(e) {
        jQuery(e).closest('tr').remove();
    };
    function amapress_{$id}_del_model_tab(e) {
        jQuery(e).closest('li').remove();
    };
    //]]>
</script>";

	$ret = ob_get_clean();

	return $ret;
}

function amapress_tabs_model_save( $postId, $opt ) {
	//var_dump($_POST);
	return false;
}

function amapress_tabs_model_get_options( $opt ) {
	$val = Amapress::getOption( $opt->settings['assoc_prop'] );
	if ( empty( $val ) ) {
		$val = $opt->settings['default'];
	}
	if ( empty( $val ) ) {
		$val = array();
	}

	$ret = array();
	foreach ( $val as $v ) {
		$ret[ $v['id'] ] = sprintf( '%s (%s)', $v['name'], $v['id'] );
	}

	return $ret;
}

function amapress_tabs_model_metabox_editor_clean( $value, $wpautop ) {
	if ( $wpautop ) {
		return wpautop( stripslashes( $value ) );
	}

	return stripslashes( $value );
}

function amapress_tabs_model_metabox_editor_save( $postId, $opt ) {
	foreach ( $_REQUEST as $k => $v ) {
		if ( strrpos( $k, $opt->getID() . '_tab_' ) == 0 ) {
			update_post_meta( $postId, $k, $v );
		}
	}
}

function amapress_tabs_model_metabox_editor( $postId, $opt ) {
	$settings       = array(
		'wpautop'         => true,
		'media_buttons'   => true,
		'rows'            => 10,
		'editor_settings' => array(),
		'show_column'     => false,
	);
	$settings       = array_merge( $settings, $opt->settings );
	$editorSettings = array(
		'wpautop'       => $settings['wpautop'],
		'media_buttons' => $settings['media_buttons'],
		'textarea_rows' => $settings['rows'],
	);

	if ( isset( $opt->settings['editor_settings'] ) && is_array( $opt->settings['editor_settings'] ) ) {
		$editorSettings = array_merge( $editorSettings, $opt->settings['editor_settings'] );
	}

	if ( $opt->settings['required'] ) {
		if ( ! empty( $editorSettings['editor_class'] ) ) {
			$editorSettings['editor_class'] = $editorSettings['editor_class'] . ' tinymcerequired';
		} else {
			$editorSettings['editor_class'] = 'tinymcerequired';
		}
	}

	$titan     = TitanFramework::getInstance( 'amapress' );
	$tabs_conf = Amapress::getOption( $opt->settings['tabs_conf'] );
	if ( ! $tabs_conf ) {
		$tabs_conf = array();
	}

	$tabs_default = Amapress::getOption( $opt->settings['tabs_default'] );

	$sel = get_post_meta( $postId, $opt->settings['tabs_model_prop'], true );
	if ( ! $sel ) {
		$sel = $tabs_default;
	}
	if ( ! $sel ) {
		$sel = 'default';
	}

	ob_start();

	$found = false;
	foreach ( $tabs_conf as $conf ) {
		if ( $conf['id'] != $sel ) {
			continue;
		}

		if ( is_array( $conf['tabs'] ) ) {
			foreach ( $conf['tabs'] as $tab ) {
				$parts = explode( '=', $tab, 2 );
				if ( count( $parts ) != 2 ) {
					echo '<div class="error">Erreur de configuration: chaque tab doit avoir la syntaxe id=label. Trouvé ' . $tab . '. <a href="' . admin_url( 'options-general.php?page=amapress_options_page' ) . '">Corriger</a></div>';
					continue;
				}
				echo "<h3>{$parts[1]}</h3>";
				$field_id    = $opt->getID() . '_tab_' . $parts[0];
				$field_value = get_post_meta( $postId, $field_id, true );
				wp_editor( amapress_tabs_model_metabox_editor_clean( $field_value, $settings['wpautop'] ), $field_id, $editorSettings );
			}

			$found = true;
		}
	}

	if ( ! $found ) {
		$field_id    = $opt->getID() . '_tab_default';
		$field_value = get_post_meta( $postId, $field_id, true );
		wp_editor( amapress_tabs_model_metabox_editor_clean( $field_value, $settings['wpautop'] ), $field_id, $editorSettings );
	}

	$ret = ob_get_clean();

	return $ret;
}

function amapress_tabs_model_echo( $tabs_conf_option_name, $postId, $base_field_name, $as_excerpt = false ) {
	$tabs_conf = Amapress::getOption( $tabs_conf_option_name );
	if ( ! $tabs_conf ) {
		$tabs_conf = array();
	}

	$sel = get_post_meta( $postId, $base_field_name . '_model', true );
	if ( ! $sel ) {
		$sel = 'default';
	}

	$parent_id = 'post' . $postId;
	$found     = false;
	foreach ( $tabs_conf as $k => $conf ) {
		if ( $conf['id'] != $sel ) {
			continue;
		}
		if ( ! is_array( $conf['tabs'] ) ) {
			continue;
		}
		?>
        <div>
            <ul class="nav nav-tabs responsive" id="<?php echo $parent_id ?>" role="tablist">
				<?php
				$active  = 1;
				$id_incr = 109;
				foreach ( $conf['tabs'] as $tab ) {
					$parts = explode( '=', $tab, 2 );
					if ( count( $parts ) != 2 ) {
						continue;
					}

					$act   = $active ? 'active' : '';
					$id    = $parent_id . '_' . $parts[0];
					$label = $parts[1];
					if ( ! $as_excerpt ) {
						echo "<li class='$act' role='presentation'><a href='#$id' role='tab' data-toggle='tab'>$label</a></li>";
					}
					$active = 0;
					$id_incr ++;
				}
				?>
            </ul>

            <div class="tab-content responsive">
				<?php
				$active = 1;
				$i      = 0;
				foreach ( $conf['tabs'] as $tab ) {
					$parts       = explode( '=', $tab, 2 );
					$act         = $active ? 'active' : '';
					$id          = $parent_id . '_' . $parts[0];
					$field_id    = $base_field_name . '_tab_' . $parts[0];
					$field_value = get_post_meta( $postId, $field_id, true );
					$cnt         = amapress_tabs_model_metabox_editor_clean( $field_value, true );
					$label       = $parts[1];
					if ( $as_excerpt && ! empty( $cnt ) ) {
						if ( $i ++ > 0 ) {
							echo '<br/>';
						}
						echo "<strong>$label</strong> <br/>";
						echo "<div class='tab-pane $act' id='$id' role='tabpanel'>$cnt<br/></div>";
					} else {
						echo "<div class='tab-pane $act' id='$id' role='tabpanel'>$cnt</div>";
					}
					$active = 0;
				}
				?>
            </div>
        </div>
		<?php
		$found = true;
		break;
	}

	if ( ! $found ) {
		$field_id    = $base_field_name . '_tab_default';
		$field_value = get_post_meta( $postId, $field_id, true );
		echo amapress_tabs_model_metabox_editor_clean( $field_value, true );
	}
}