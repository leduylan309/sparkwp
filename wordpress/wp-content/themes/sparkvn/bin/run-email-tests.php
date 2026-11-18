<?php
/**
 * CLI runner for Spark email tests.
 *
 * Usage (from container):
 *   php wp-content/themes/sparkvn/bin/run-email-tests.php
 *
 * @package Spark_VN
 */

if ( php_sapi_name() !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

require dirname( __DIR__, 4 ) . '/wp-load.php';
require get_template_directory() . '/inc/test-email-suite.php';

exit( sparkvn_run_email_test_suite() );
