<?php
// phpcs:ignoreFile -- Standalone WordPress/WPML contract fixture; no runtime writes.
/** Run with: php tests/php/multilingual-resolution.php */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');

class WP_Post {
    public $ID;
    public $post_name = 'test-block';
    public $post_title = 'Test Block';
    public $post_type = 'mrn_reusable_faq';
    public $post_status = 'publish';
    public function __construct($id = 42) { $this->ID = $id; }
}

$source = new WP_Post(10);
$translation = new WP_Post(20);
$posts = array(10 => $source, 20 => $translation);
$metadata = array(20 => array('_items' => array('field_faq_items'), 'items' => array('2')));
$wpml = true;
$mapped = 20;
$can_edit = false;
$query_calls = 0;
$mapping_calls = 0;

function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function sanitize_title($value) { return strtolower(trim((string) $value)); }
function current_user_can(...$args) { return $GLOBALS['can_edit']; }
function get_post($id = null) { return $GLOBALS['posts'][(int) $id] ?? null; }
function get_post_meta($id) { return $GLOBALS['metadata'][(int) $id] ?? array(); }
function has_filter($hook) { return 'wpml_object_id' === $hook && $GLOBALS['wpml']; }
function apply_filters($hook, $value, ...$args) {
    if ('wpml_object_id' === $hook && $GLOBALS['wpml']) {
        ++$GLOBALS['mapping_calls'];
        check($args === array('mrn_reusable_faq', true), 'WPML maps the exact type and retains a missing source.');
        return $GLOBALS['mapped'];
    }
    return $value;
}
function get_posts($query) {
    ++$GLOBALS['query_calls'];
    // Regression: WPML can still constrain this source slug to the active language.
    return $GLOBALS['wpml'] ? array() : array($GLOBALS['source']);
}
function get_page_by_path($slug, $output, $types) {
    check(is_array($types) && in_array('mrn_reusable_faq', $types, true), 'Lookup is restricted to reusable types.');
    return 'shared-faq' === $slug ? $GLOBALS['source'] : null;
}
function check($condition, $message) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $message . "\n"); exit(1); }
}

require dirname(__DIR__, 2) . '/mrn-reusable-block-library.php';

check(mrn_rbl_get_block_post('shared-faq') === $translation, 'Source slug resolves to the saved translation despite a filtered query.');
check($query_calls === 0, 'Multilingual lookup does not depend on a language-filtered query.');
$metadata[20] = array('_edit_lock' => array('123'));
check(mrn_rbl_get_block_post('shared-faq') === $source, 'An unsaved translation shell falls back to source.');
$metadata[20] = array('_items' => array('field_faq_items'), 'items' => array(''));
check(mrn_rbl_get_block_post('shared-faq') === $translation, 'An intentionally cleared saved translation stays empty.');
$mapped = null;
check(mrn_rbl_get_block_post('shared-faq') === $source, 'A missing translation retains source.');
$mapped = 999;
check(mrn_rbl_get_block_post('shared-faq') === $source, 'A missing target record retains source.');
$mapped = 20;
$translation->post_type = 'page';
check(mrn_rbl_get_block_post('shared-faq') === $source, 'Another post type cannot become the block translation.');
$translation->post_type = 'mrn_reusable_faq';
$translation->post_status = 'draft';
$resolved = mrn_rbl_get_block_post('shared-faq');
check($resolved === $translation && !mrn_rbl_can_render_post($resolved), 'Draft translations retain the existing anonymous-render restriction.');
$can_edit = true;
check(mrn_rbl_can_render_post($resolved), 'Authorized editors retain draft-preview access.');
$can_edit = false;
$translation->post_status = 'publish';
$before = $mapping_calls;
check(mrn_rbl_get_block_post('10') === $source && $mapping_calls === $before, 'An explicit numeric ID stays exact.');
check(mrn_rbl_get_block_post('missing') === null && mrn_rbl_get_block_post('') === null, 'Missing and empty slugs remain empty.');
$source->post_status = 'trash';
check(mrn_rbl_get_block_post('shared-faq') === null, 'Trash cannot become a source block.');
$source->post_status = 'publish';
$wpml = false;
check(mrn_rbl_get_block_post('shared-faq') === $source && $query_calls === 1, 'Sites without WPML keep their existing lookup path.');
echo "Reusable multilingual-resolution tests passed.\n";
