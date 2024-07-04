<?php
defined( 'ABSPATH' ) || exit;

$user = get_current_user();

do_action('castors_before_edit_membership_form'); ?>

<form class="castors-EditMembershipForm edit-membership" action="" method="post" <?php do_action('castors_edit_membership_form_tag'); ?> >

	<?php do_action('castors_edit_membership_form_start'); ?>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="member_num"><?php esc_html_e("N° d'adhérent", 'castors'); ?></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="member_num" id="member_num" value="<?php echo esc_attr($user->member_num); ?>" disabled />
	</p>
	<div class="clear"></div>
	<p class="arkabase-field-desc"><em><?php esc_html_e("Ce numéro unique vous est attribué lors de votre première adhésion aux Castors. Vous pouvez le mentionner dans toutes vos correspondances avec l'association pour faciliter nos échanges.", 'castors' ); ?></em></p>

	<p>
		<?php wp_nonce_field( 'save_membership_details', 'save-membership-details-nonce' ); ?>
		<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="save_membership_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
		<input type="hidden" name="action" value="save_membership_details" />
	</p>

	<?php do_action( 'castors_edit_membership_form_end' ); ?>
</form>

<?php do_action( 'castors_after_edit_membership_form' ); ?>
