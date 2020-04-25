<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSave extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'save'           => '',
		'reset'          => '',
		'use_reset'      => false,
		'reset_question' => '',
		'action'         => 'save',
	);

	public function display() {
		if ( ! empty( $this->owner->postID ) ) {
			return;
		}

		if ( empty( $this->settings['save'] ) ) {
			$this->settings['save'] = __( 'Enregistrer', 'amapress' );
		}
		if ( empty( $this->settings['reset'] ) ) {
			$this->settings['reset'] = __( 'Remettre par défaut', 'amapress' );
		}
		if ( empty( $this->settings['reset_question'] ) ) {
			$this->settings['reset_question'] = __( 'Etes-vous sûr de vouloir remettre toutes les options à leurs valeurs par défaut ?', 'amapress' );
		}

		?>
        </tbody>
        </table>

        <p class='submit'>
            <button name="action" value="<?php echo $this->settings['action'] ?>" class="button button-primary">
				<?php echo $this->settings['save'] ?>
            </button>

			<?php
			if ( $this->settings['use_reset'] ) :
				?>
                <button name="action" class="button button-secondary"
                        onclick="javascript: if ( confirm( '<?php echo htmlentities( esc_attr( $this->settings['reset_question'] ) ) ?>' ) ) { jQuery( '#tf-reset-form' ).submit(); } jQuery(this).blur(); return false;">
					<?php echo $this->settings['reset'] ?>
                </button>
				<?php
			endif;
			?>
        </p>

        <table class='form-table tf-form-table'>
            <tbody>
		<?php
	}
}
