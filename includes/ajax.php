<?php
add_action( 'wp_ajax_sf_toggle', 'sf_toggle_ajax' );
function sf_toggle_ajax() {
    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    if ( ! $id || ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'sf_toggle_'.$id ) ) {
        wp_send_json_error('Invalid nonce');
    }
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permission denied');
    $scripts = sf_get_scripts();
    if ( ! isset($scripts[$id]) ) wp_send_json_error('Not found');
    $scripts[$id]['status'] = $scripts[$id]['status'] ? 0 : 1;
    $scripts[$id]['last_updated_date'] = current_time('mysql');
    sf_save_scripts($scripts);
    wp_send_json_success(['new_status'=>$scripts[$id]['status']]);
}
