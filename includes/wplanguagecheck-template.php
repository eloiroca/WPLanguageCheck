<?php
/************************************************************
 * Pintar el modal en el footer (frontend)
 ************************************************************/
function wplanguagecheck_render_modal() {
    if (is_admin()) return;
    include(DIRECTORI_PLUGIN_WPLANGUAGECHECK . 'templates/views/modals/wplanguagecheck-modal.php');
}
add_action('wp_footer', 'wplanguagecheck_render_modal', 100);
