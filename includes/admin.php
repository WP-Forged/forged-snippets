<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    add_menu_page(
        'Forged Snippets',
        'Forged Snippets',
        'manage_options',
        'wpforged_snippets',
        'sf_all_snippets_page',
        'dashicons-editor-code'
    );
    add_submenu_page(
        'wpforged_snippets',
        'All Snippets',
        'All Snippets',
        'manage_options',
        'wpforged_snippets',
        'sf_all_snippets_page'
    );
    add_submenu_page(
        'wpforged_snippets',
        'Add New Snippet',
        'Add New Snippet',
        'manage_options',
        'wpforged_snippets_new',
        'sf_script_form_page'
    );
} );

function sf_admin_nav() {
    $links = array(
        'All Snippets'    => admin_url( 'admin.php?page=wpforged_snippets' ),
        'Add New Snippet' => admin_url( 'admin.php?page=wpforged_snippets_new' ),
    );
    echo '<div style="background:#fff;border:1px solid #ddd;padding:12px 15px;border-radius:6px;box-shadow:0 3px 6px rgba(0,0,0,0.1);margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;"><div>';
    $first = true;
    foreach ( $links as $label => $url ) {
        if ( ! $first ) {
            echo '<span style="margin:0 10px;color:#999;">|</span>';
        }
        printf( '<a href="%s" style="font-size:12px;color:#0073aa;text-decoration:none;">%s</a>', esc_url( $url ), esc_html( $label ) );
        $first = false;
    }
    echo '</div><div style="font-size:12px;color:#999;">Forged Snippets by WPForged</div></div>';
}

function sf_all_snippets_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission denied', 'forged-snippets' ) );
    }

    if ( isset( $_GET['action'], $_GET['id'], $_GET['_wpnonce'] ) && $_GET['action'] === 'delete' ) {
        $id = sanitize_text_field( $_GET['id'] );
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'sf_delete_' . $id ) ) {
            $scripts = sf_get_scripts();
            if ( isset( $scripts[ $id ] ) ) {
                unset( $scripts[ $id ] );
                sf_save_scripts( $scripts );
                echo '<div class="notice notice-success is-dismissible"><p>Snippet deleted.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Nonce verification failed. Could not delete snippet.</p></div>';
        }
    }

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['bulk_action'], $_POST['sf_bulk_nonce'] ) && wp_verify_nonce( $_POST['sf_bulk_nonce'], 'sf_bulk_action' ) ) {
        $action  = sanitize_text_field( $_POST['bulk_action'] );
        $ids     = isset( $_POST['bulk_ids'] ) && is_array( $_POST['bulk_ids'] ) ? array_map( 'sanitize_text_field', $_POST['bulk_ids'] ) : array();
        $scripts = sf_get_scripts();
        $count   = 0;
        if ( $action !== '' && ! empty( $ids ) ) {
            foreach ( $ids as $id ) {
                if ( isset( $scripts[ $id ] ) ) {
                    if ( $action === 'delete' ) {
                        unset( $scripts[ $id ] );
                        $count++;
                    } elseif ( $action === 'enable' ) {
                        $scripts[ $id ]['status'] = 1;
                        $scripts[ $id ]['last_updated_date'] = current_time( 'mysql' );
                        $count++;
                    } elseif ( $action === 'disable' ) {
                        $scripts[ $id ]['status'] = 0;
                        $scripts[ $id ]['last_updated_date'] = current_time( 'mysql' );
                        $count++;
                    }
                }
            }
            if ( $count > 0 ) {
                sf_save_scripts( $scripts );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $count ) . ' snippet(s) updated.</p></div>';
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p>No snippets were affected by the bulk action.</p></div>';
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>Please select an action and at least one snippet for bulk operations.</p></div>';
        }
    }

    $scripts = sf_get_scripts();
    $scripts_filtered = array_filter( $scripts, function( $script ) {
        return is_array( $script ) && ! empty( $script['id'] );
    } );
    $scripts_arr = array_values( $scripts_filtered );
    usort( $scripts_arr, function( $a, $b ) {
        $prio_a = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
        $prio_b = isset( $b['priority'] ) ? (int) $b['priority'] : 10;
        return $prio_a - $prio_b;
    } );
    ?>
    <div class="wrap">
        <?php sf_admin_nav(); ?>
        <style>
            /* tighten up first two columns */
            #snippets_table th:first-child,
            #snippets_table td:first-child {
                width: 40px;
                padding: 0;
                text-align: center;
            }
            #snippets_table th:nth-child(2),
            #snippets_table td:nth-child(2) {
                padding: 0 5px;
            }
            /* default cell padding */
            #snippets_table th,
            #snippets_table td {
                vertical-align: middle !important;
                padding: 8px 10px;
            }
            .sf-toggle-btn {
                display:inline-block;
                width:40px;
                height:20px;
                border:1px solid #666;
                border-radius:10px;
                position:relative;
                cursor:pointer;
                background:#ccc;
            }
            .sf-toggle-btn:after {
                content:"";
                position:absolute;
                top:2px;
                left:2px;
                width:16px;
                height:16px;
                border-radius:50%;
                background:#fff;
                transition:left .2s;
            }
            .sf-toggle-btn.active {
                background:#0073aa;
            }
            .sf-toggle-btn.active:after {
                left:22px;
            }
            .sf-form-container {
                background:#fff;
                padding:20px;
                border:1px solid #ccc;
                width:90%;
                margin:20px 0;
            }
        </style>
        <h1>All Snippets</h1>
        <div class="sf-form-container">
            <form method="post">
                <?php wp_nonce_field( 'sf_bulk_action', 'sf_bulk_nonce' ); ?>
                <div style="margin-bottom:10px;">
                    <select name="bulk_action">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                        <option value="enable">Enable Selected</option>
                        <option value="disable">Disable Selected</option>
                    </select>
                    <input type="submit" class="button action" value="Apply">
                </div>
                <table id="snippets_table" class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="sf_select_all"></th>
                            <th>Actions</th>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Author</th>
                            <th>Location</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $scripts_arr ) ) : ?>
                            <?php foreach ( $scripts_arr as $s ) : ?>
                                <?php
                                $id      = esc_attr( $s['id'] );
                                $name    = esc_html( $s['name'] ?? '(No Name)' );
                                $author  = esc_html( $s['author'] ?? 'N/A' );
                                $loc     = esc_html( ucfirst( $s['location'] ?? 'N/A' ) );
                                $created = $s['created_date'] ? esc_html( date_i18n( 'd.m.y g:ia', strtotime( $s['created_date'] ) ) ) : 'N/A';
                                $updated = $s['last_updated_date'] ? esc_html( date_i18n( 'd.m.y g:ia', strtotime( $s['last_updated_date'] ) ) ) : 'N/A';
                                $prio    = esc_html( $s['priority'] ?? 10 );
                                $status  = (int) ( $s['status'] ?? 0 );
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="bulk_ids[]" value="<?php echo $id; ?>"></td>
                                    <td>
                                        <a href="<?php echo admin_url( 'admin.php?page=wpforged_snippets_new&action=edit&id=' . $id ); ?>">Edit</a> |
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wpforged_snippets&action=delete&id=' . $id ), 'sf_delete_' . $id ); ?>" onclick="return confirm('Delete this snippet?');">Delete</a>
                                    </td>
                                    <td><?php echo $name; ?></td>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo $author; ?></td>
                                    <td><?php echo $loc; ?></td>
                                    <td><?php echo $created; ?></td>
                                    <td><?php echo $updated; ?></td>
                                    <td><?php echo $prio; ?></td>
                                    <td><div class="sf-toggle-btn <?php echo $status ? 'active' : 'inactive'; ?>" data-id="<?php echo $id; ?>" data-nonce="<?php echo wp_create_nonce( 'sf_toggle_' . $id ); ?>"></div></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="10">No snippets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script>
        jQuery(function($){
            $('#sf_select_all').click(function(){
                $('input[name="bulk_ids[]"]').prop('checked', this.checked);
            });
            $('.sf-toggle-btn').click(function(){
                var btn = $(this), id = btn.data('id'), nonce = btn.data('nonce');
                $.post(ajaxurl, { action:'sf_toggle', id:id, nonce:nonce }, function(res){
                    if ( res.success ) {
                        btn.toggleClass('active inactive');
                    } else {
                        alert('Error toggling status');
                    }
                }).fail(function(){
                    alert('Server error');
                });
            });
        });
        </script>
    </div>
    <?php
}

function sf_script_form_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission denied', 'forged-snippets' ) );
    }

    $is_edit = isset( $_GET['action'], $_GET['id'] ) && $_GET['action'] === 'edit';
    $scripts = sf_get_scripts();
    $script  = $is_edit && isset( $scripts[ $_GET['id'] ] ) ? $scripts[ $_GET['id'] ] : null;
    if ( $is_edit && ! $script ) {
        echo '<div class="notice notice-error"><p>Error: Snippet not found.</p></div>';
        return;
    }

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['sf_nonce'] ) && wp_verify_nonce( $_POST['sf_nonce'], 'sf_save' ) ) {
        $name     = sanitize_text_field( $_POST['name'] );
        $code     = wp_unslash( $_POST['code'] );
        $location = in_array( $_POST['location'], array( 'header','body','footer' ), true ) ? $_POST['location'] : 'footer';
        $priority = max(1,min(10,intval($_POST['priority'])));
        $status   = isset($_POST['status'])?1:0;
        $now      = current_time('mysql');
        if ( $is_edit ) {
            $id    = sanitize_text_field( $_POST['script_id'] );
            $prev  = $scripts[ $id ];
            $hist  = $prev['history'] ?? array();
            $hist[] = array(
                'code'=>$prev['code'],
                'location'=>$prev['location'],
                'priority'=>$prev['priority'],
                'status'=>$prev['status'],
                'date'=>$prev['last_updated_date'] ?? $prev['created_date'] ?? $now
            );
            $scripts[ $id ] = array_merge( $prev, array(
                'name'=>$name,
                'code'=>$code,
                'location'=>$location,
                'priority'=>$priority,
                'status'=>$status,
                'last_updated_date'=>$now,
                'history'=>array_slice($hist,-10)
            ));
            $message='Snippet updated.';
        } else {
            $new_id = uniqid('sf_');
            $scripts[ $new_id ] = array(
                'id'=>$new_id,
                'name'=>$name,
                'author'=>wp_get_current_user()->display_name,
                'code'=>$code,
                'location'=>$location,
                'priority'=>$priority,
                'status'=>$status,
                'created_date'=>$now,
                'last_updated_date'=>$now,
                'history'=>array()
            );
            $message='Snippet added.';
        }
        sf_save_scripts($scripts);
        if (!headers_sent()) {
            wp_redirect(admin_url('admin.php?page=wpforged_snippets&sf_message='.urlencode($message)));
            exit;
        }
        echo '<div class="notice notice-success"><p>'.esc_html($message).'</p></div>';
        echo '<script>location.href="'.esc_url(admin_url('admin.php?page=wpforged_snippets')).'";</script>';
        exit;
    }

    if ( isset($_GET['sf_message']) ) {
        echo '<div class="notice notice-success"><p>'.esc_html(urldecode($_GET['sf_message'])).'</p></div>';
    }

    $form = array(
        'name'=>$is_edit?$script['name']:'',
        'code'=>$is_edit?$script['code']:'',
        'location'=>$is_edit?$script['location']:'footer',
        'priority'=>$is_edit?(int)$script['priority']:10,
        'status'=>$is_edit?(isset($script['status'])&&$script['status']==1):true
    );

    wp_enqueue_script('wp-code-editor');
    wp_enqueue_code_editor(array('type'=>'text/html'));
    ?>
    <div class="wrap">
        <?php sf_admin_nav(); ?>
        <style>
            .sf-form-container{background:#fff;padding:20px;border:1px solid #ccc;width:90%;margin:20px 0}
            .sf-form-table th{width:150px;text-align:left;padding:8px 10px 8px 0;vertical-align:top}
            .sf-form-table td{padding:5px 0;vertical-align:top}
            .sf-fullwidth{width:100%}
            .CodeMirror{border:1px solid #ddd;min-height:150px}
        </style>
        <h1><?php echo $is_edit?'Edit Snippet':'Add New Snippet'; ?></h1>
        <div class="sf-form-container">
            <form method="post">
                <?php wp_nonce_field('sf_save','sf_nonce'); if($is_edit):?><input type="hidden" name="script_id" value="<?php echo esc_attr($_GET['id']);?>"><?php endif;?>
                <table class="form-table sf-form-table"><tbody>
                    <tr><th><label for="name">Name</label></th><td><input id="name" name="name" type="text" class="regular-text sf-fullwidth" value="<?php echo esc_attr($form['name']);?>" required></td></tr>
                    <tr><th><label for="code">Code</label></th><td><textarea id="code" name="code" class="sf-fullwidth" rows="10"><?php echo esc_textarea($form['code']);?></textarea></td></tr>
                    <tr><th><label for="location">Location</label></th><td><select id="location" name="location" class="sf-fullwidth"><option value="header"<?php selected($form['location'],'header');?>>Header</option><option value="body"<?php selected($form['location'],'body');?>>Body</option><option value="footer"<?php selected($form['location'],'footer');?>>Footer</option></select></td></tr>
                    <tr><th><label for="priority">Priority</label></th><td><select id="priority" name="priority" class="sf-fullwidth"><?php for($i=1;$i<=10;$i++):?><option value="<?php echo $i;?>"<?php selected($form['priority'],$i);?>><?php echo $i;?></option><?php endfor;?></select></td></tr>
                    <tr><th><label for="status">Status</label></th><td><input id="status" name="status" type="checkbox" value="1"<?php checked($form['status']);?>> Active</td></tr>
                    <?php if($is_edit):?>
                    <tr><th>Author</th><td><?php echo esc_html($script['author']);?></td></tr>
                    <tr><th>Created</th><td><?php echo esc_html(date_i18n('F j, Y g:ia',strtotime($script['created_date'])));?></td></tr>
                    <tr><th>Last Updated</th><td><?php echo esc_html(date_i18n('F j, Y g:ia',strtotime($script['last_updated_date'])));?></td></tr>
                    <?php endif;?>
                </tbody></table>
                <?php submit_button($is_edit?'Update Snippet':'Add Snippet');?>
            </form>
        </div>
    </div>
    <script>
    jQuery(function($){
        if(typeof wp!=='undefined'&&wp.codeEditor){
            var cfg=wp.codeEditor.defaultSettings?$.extend({},wp.codeEditor.defaultSettings):{};
            cfg.codemirror=$.extend({},cfg.codemirror,{mode:'htmlmixed',lineNumbers:true,autoCloseTags:true,indentUnit:4,tabSize:4,indentWithTabs:false});
            wp.codeEditor.initialize($('#code'),cfg);
        } else {
            $('#code').css({fontFamily:'monospace',height:'200px'});
        }
    });
    </script>
    <?php
}
