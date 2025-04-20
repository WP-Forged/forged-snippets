<?php
function sf_output_scripts($location) {
    if ( is_admin() ) return;
    $scripts = array_filter( sf_get_scripts(), function($s) use($location){ return !empty($s['status']) && $s['location']===$location; } );
    if ( ! $scripts ) return;
    usort( $scripts, fn($a,$b)=> (int)$a['priority'] - (int)$b['priority'] );
    foreach ( $scripts as $s ) {
        if ( isset($s['code']) && trim($s['code'])!=='' ) {
            echo "\n\n", $s['code'], "\n\n";
        }
    }
}
add_action('wp_head',   fn()=>sf_output_scripts('header'), 10);
add_action('wp_body_open',fn()=>sf_output_scripts('body'),   10);
add_action('wp_footer', fn()=>sf_output_scripts('footer'),  10);
