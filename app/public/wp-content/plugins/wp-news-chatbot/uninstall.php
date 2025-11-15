<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
// Remove options
delete_option( 'wpnc_options' );
