<?php
defined( 'ABSPATH' ) || exit;

$user = get_current_user();
$worksite = get_query_var('chantiers');
var_dump($worksite);
do_action('castors_before_worksite_list');

do_action('castors_after_worksite_list');

do_action('castors_before_edit_worksite_form'); ?>

<form class="castors-EditWorksiteForm edit-worksite" action="" method="post" <?php do_action('castors_edit_worksite_form_tag'); ?> >

	<?php do_action('castors_edit_worksite_form_start'); ?>
    WORKSITES
	<?php do_action( 'castors_edit_worksite_form_end' ); ?>
</form>

<?php Castors_User::locationAutocompleteScript('.castors-EditWorksiteForm #location', '.castors-EditWorksiteForm #location-details'); ?>

<?php do_action( 'castors_after_edit_worksite_form' ); ?>
