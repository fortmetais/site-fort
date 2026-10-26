<?php
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('responsive-embeds');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array('primary' => 'Menu principal'));
});
add_filter('show_admin_bar', '__return_false');
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('fort-style', get_template_directory_uri() . '/assets/site.css', array(), filemtime(get_template_directory() . '/assets/site.css'));
    wp_enqueue_style('fort-reference', get_template_directory_uri() . '/assets/reference.css', array('fort-style'), filemtime(get_template_directory() . '/assets/reference.css'));
    wp_enqueue_style('fort-languages', get_template_directory_uri() . '/assets/languages.css', array('fort-reference'), filemtime(get_template_directory() . '/assets/languages.css'));
    wp_enqueue_style('fort-whatsapp-widget', get_template_directory_uri() . '/assets/whatsapp-widget.css', array('fort-reference'), filemtime(get_template_directory() . '/assets/whatsapp-widget.css'));
    if (is_home() || is_singular('post')) {
        wp_enqueue_style('fort-news', get_template_directory_uri() . '/assets/news.css', array('fort-reference'), filemtime(get_template_directory() . '/assets/news.css'));
    }
    if (is_post_type_archive('fort_cena') || is_singular('fort_cena')) {
        wp_enqueue_style('fort-environments', get_template_directory_uri() . '/assets/environments.css', array('fort-reference'), filemtime(get_template_directory() . '/assets/environments.css'));
    }
    if (is_singular('fort_produto')) {
        wp_enqueue_style('fort-product-finishes', get_template_directory_uri() . '/assets/product-finishes.css', array('fort-reference'), filemtime(get_template_directory() . '/assets/product-finishes.css'));
        wp_enqueue_script('fort-product-finishes', get_template_directory_uri() . '/assets/product-finishes.js', array('fort-site'), filemtime(get_template_directory() . '/assets/product-finishes.js'), true);
    }
    wp_enqueue_script('fort-site', get_template_directory_uri() . '/assets/site.js', array(), filemtime(get_template_directory() . '/assets/site.js'), true);
    wp_enqueue_script('fort-whatsapp-widget', get_template_directory_uri() . '/assets/whatsapp-widget.js', array(), filemtime(get_template_directory() . '/assets/whatsapp-widget.js'), true);
    $translate = function ($text) { return function_exists('fort_t') ? fort_t($text) : $text; };
    wp_localize_script('fort-site', 'fortI18n', array(
        'openMenu' => $translate('Abrir menu'), 'closeMenu' => $translate('Fechar menu'),
        'sending' => $translate('Enviando…'), 'unconfirmed' => $translate('a confirmar'),
        'finishPrefix' => $translate('acabamento') . ' ',
    ));
});
function fort_asset($file) { return get_template_directory_uri() . '/assets/images/' . ltrim($file, '/'); }
function fort_scene_image_url($scene_id) {
    return get_the_post_thumbnail_url($scene_id, 'full') ?: (string) get_post_meta($scene_id, '_fort_cena_image_url', true);
}
function fort_get($key) { return function_exists('fort_option') ? fort_option($key) : ''; }
function fort_catalog_document() {
    $language = function_exists('fort_language') ? fort_language() : 'pt-br';
    $key = array('pt-br' => 'catalogo', 'en' => 'catalogo_en', 'es' => 'catalogo_es')[$language];
    $url = fort_get($key);
    $document_language = $language;
    if (!$url && $language === 'es' && fort_get('catalogo')) { $url = fort_get('catalogo'); $document_language = 'pt-br'; }
    return array('url' => $url, 'language' => $document_language, 'fallback' => $url && $document_language !== $language);
}
function fort_catalog_button_label($context = 'hero') {
    $catalog = fort_catalog_document();
    if (!$catalog['url']) return 'Solicitar Catálogo';
    if ($catalog['fallback']) return 'Catálogo em português';
    return $context === 'footer' ? 'Baixar Catálogo' : 'Catálogo Completo';
}
function fort_whatsapp($message = '') {
    $number = preg_replace('/\D/', '', fort_get('whatsapp'));
    if (!$number) return home_url('/fale-com-a-gente/');
    return 'https://wa.me/' . $number . ($message ? '?text=' . rawurlencode($message) : '');
}
function fort_icon($name, $class = '') {
    $paths = array(
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'external' => '<path d="M6 18 18 6M6 6h12v12"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c5 5 5 13 0 18-5-5-5-13 0-18Z"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6z"/><path d="m8 12 3 3 5-6"/>',
        'leaf' => '<path d="M20 3C8 2 2 9 6 16s16 5 14-13ZM5 21 16 9"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'chat' => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z"/><path d="M8 11h8m-8 4h5"/>',
        'phone' => '<path d="m6 3 4 4-2 3a14 14 0 0 0 6 6l3-2 4 4-2 3C9 22 2 15 3 6Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'award' => '<circle cx="12" cy="8" r="5"/><path d="m8 12-1 9 5-3 5 3-1-9"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'facebook' => '<path d="M14 21v-8h3l.5-4H14V7c0-1.2.5-2 2-2h2V1h-3c-3 0-5 2-5 5v3H7v4h3v8"/>',
    );
    return '<svg class="icon ' . esc_attr($class) . '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}
function fort_term_name($id, $taxonomy) { $terms = get_the_terms($id, $taxonomy); return $terms && !is_wp_error($terms) ? $terms[0]->name : ''; }
function fort_catalog_url($args = array()) { return add_query_arg($args, home_url('/produtos/')); }
function fort_input($key) { return isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : ''; }
function fort_products_query($limit = 12, $extra = array()) {
    $args = array('post_type' => 'fort_produto', 'post_status' => 'publish', 'posts_per_page' => $limit, 'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'));
    return new WP_Query(array_merge($args, $extra));
}
function fort_product_card() { get_template_part('parts/product-card'); }
function fort_catalog_search_terms($search) {
    $language = function_exists('fort_language') ? fort_language() : 'pt-br';
    if ($language === 'pt-br') return array($search);
    $words = array(
        'en' => array('faucet' => 'torneira', 'faucets' => 'torneira', 'tap' => 'torneira', 'taps' => 'torneira', 'deck' => 'bancada', 'countertop' => 'bancada', 'deck-mounted' => 'bancada', 'basin' => 'cuba', 'basins' => 'cuba', 'wall' => 'parede', 'wall-mounted' => 'parede', 'swivel' => 'móvel', 'fixed' => 'fixa', 'spout' => 'bica', 'aerator' => 'arejador', 'chrome' => 'cromad', 'graphite' => 'grafite', 'steel' => 'aço'),
        'es' => array('grifo' => 'torneira', 'grifos' => 'torneira', 'llave' => 'torneira', 'llaves' => 'torneira', 'encimera' => 'bancada', 'lavabo' => 'cuba', 'pared' => 'parede', 'giratorio' => 'móvel', 'giratoria' => 'móvel', 'móvil' => 'móvel', 'fijo' => 'fixa', 'fija' => 'fixa', 'caño' => 'bica', 'aireador' => 'arejador', 'negro' => 'black', 'negra' => 'black', 'acero' => 'aço', 'grafito' => 'grafite', 'rosa' => 'rose'),
    );
    $ignored = $language === 'en' ? array('a', 'an', 'the', 'for', 'with', 'and', 'of', 'on', 'to', 'mounted', 'turn') : array('el', 'la', 'los', 'las', 'un', 'una', 'para', 'con', 'de', 'del', 'y', 'en', 'sobre');
    $tokens = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);
    $terms = array();
    foreach (array_slice($tokens ?: array(), 0, 10) as $token) {
        $key = function_exists('mb_strtolower') ? mb_strtolower($token, 'UTF-8') : strtolower($token);
        if (in_array($key, $ignored, true)) continue;
        $terms[] = $words[$language][$key] ?? $token;
    }
    return $terms ?: array($search);
}
add_filter('posts_where', function ($where, $query) {
    global $wpdb;
    $search = $query->get('fort_catalog_search');
    if ($search && is_string($search)) {
        foreach (fort_catalog_search_terms($search) as $term) {
            $like = '%' . $wpdb->esc_like($term) . '%';
            $where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} fm WHERE fm.post_id = {$wpdb->posts}.ID AND fm.meta_key = '_fort_codigo' AND fm.meta_value LIKE %s))", $like, $like, $like);
        }
    }
    return $where;
}, 10, 2);
add_action('wp_head', function () {
    $description = is_singular(array('fort_produto', 'post')) ? wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 32, '') : (is_home() ? 'Lançamentos, novidades e histórias que fazem parte da Fort Metais.' : 'Conheça as linhas de metais sanitários Fort. Design e qualidade em torneiras para cozinha, banheiro e seus projetos.');
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="theme-color" content="#2563eb">' . "\n";
});
