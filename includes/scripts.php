<?php
function sf_get_scripts() {
    $scripts = get_option( SF_OPTION, array() );
    return is_array( $scripts ) ? $scripts : array();
}

function sf_save_scripts( $scripts ) {
    update_option( SF_OPTION, $scripts );
}
