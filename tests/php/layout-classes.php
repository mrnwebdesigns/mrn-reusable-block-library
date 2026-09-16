<?php
// phpcs:ignoreFile -- Standalone WordPress stub harness for reusable layout-class regression coverage.
/**
 * Focused checks for reusable Layout Class fields and template output.
 *
 * Run with:
 * php tests/php/layout-classes.php
 */

define( 'ABSPATH', __DIR__ );

class WP_Post {
	public $ID = 42;
	public $post_name = 'test-block';
	public $post_title = 'Test Block';
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9_\-]+/', '-', $value );

	return trim( (string) $value, '-' );
}

function sanitize_html_class( $value ) {
	return preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $value );
}

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}

function add_shortcode( $tag, $callback ) {
	return true;
}

function apply_filters( $hook_name, $value, ...$args ) {
	return $value;
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

require_once dirname( __DIR__, 2 ) . '/mrn-reusable-block-library.php';

function mrn_rbl_layout_class_test_assert( $condition, $message ) {
	if ( $condition ) {
		return;
	}

	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function mrn_rbl_layout_class_test_assert_same( $expected, $actual, $message ) {
	if ( $expected === $actual ) {
		return;
	}

	fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
	exit( 1 );
}

$fields = mrn_rbl_ensure_layout_class_field(
	array(
		array(
			'key'  => 'field_test_anchor',
			'name' => 'anchor',
			'type' => 'text',
		),
		array(
			'key'  => 'field_test_background',
			'name' => 'background_color',
			'type' => 'select',
		),
	)
);

mrn_rbl_layout_class_test_assert_same(
	array( 'anchor', 'layout_class', 'background_color' ),
	array_column( $fields, 'name' ),
	'Reusable Layout Class is inserted directly after Anchor ID.'
);
mrn_rbl_layout_class_test_assert_same( 'layout', mrn_rbl_get_main_config_field_group_key( $fields[1] ), 'Reusable Layout Class belongs to Basic Setting.' );
mrn_rbl_layout_class_test_assert_same(
	array( 'base', 'hero-row', 'featured' ),
	mrn_rbl_merge_layout_classes( array( 'base', 'hero-row' ), array( 'layout_class' => '.hero-row, .featured' ) ),
	'Reusable classes are normalized and deduplicated.'
);

$templates = array(
	'basic-block.php',
	'content-grid.php',
	'content-lists.php',
	'cta.php',
	'faq.php',
	'generic-block.php',
	'partners.php',
	'search-form.php',
);
foreach ( $templates as $template ) {
	$source = file_get_contents( dirname( __DIR__, 2 ) . '/templates/' . $template );
	mrn_rbl_layout_class_test_assert( false !== strpos( (string) $source, 'mrn_rbl_merge_layout_classes' ), "{$template} merges Layout Class onto its outer element." );
}

$context = array(
	'post'            => new WP_Post(),
	'fields'          => array( 'layout_class' => '.generic-feature, highlighted' ),
	'suppress_anchor' => true,
);
ob_start();
include dirname( __DIR__, 2 ) . '/templates/generic-block.php';
$markup = (string) ob_get_clean();
mrn_rbl_layout_class_test_assert( false !== strpos( $markup, 'class="mrn-reusable-block mrn-reusable-block--generic generic-feature highlighted"' ), 'The generic outer section renders every custom class.' );

echo "Reusable layout-class tests passed.\n";
