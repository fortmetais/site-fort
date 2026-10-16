<?php
/** Optional, manually configured product finishes and illustrative photographs. */
defined('ABSPATH') || exit;

function fort_finish_definitions() {
    return array(
        'cromado' => array('label' => 'Cromado', 'color' => '#c5cbd1'),
        'black' => array('label' => 'Black', 'color' => '#17191c'),
        'aco-corten' => array('label' => 'Aço Corten', 'color' => '#78513f'),
        'rose' => array('label' => 'Rose', 'color' => '#c88969'),
        'grafite' => array('label' => 'Grafite', 'color' => '#565b62'),
    );
}

/** Accept absolute HTTP(S) URLs, including the local WordPress media library. */
function fort_finish_image_url($value) {
    if (!is_string($value)) return '';
    $value = trim($value);
    if ($value === '' || !in_array(strtolower((string) wp_parse_url($value, PHP_URL_SCHEME)), array('http', 'https'), true)) return '';
    $url = esc_url_raw($value, array('http', 'https'));
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host']) || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), array('http', 'https'), true) || !filter_var($url, FILTER_VALIDATE_URL)) return '';
    return $url;
}

/** Keep configured rows even when disabled so drafts can be completed later. */
function fort_sanitize_finish_variants($raw) {
    if (!is_array($raw)) return array();
    $clean = array();
    foreach (fort_finish_definitions() as $key => $definition) {
        if (!isset($raw[$key]) || !is_array($raw[$key])) continue;
        $row = $raw[$key];
        $clean[$key] = array(
            'image' => isset($row['image']) ? fort_finish_image_url($row['image']) : '',
            'reference' => isset($row['reference']) && is_string($row['reference']) ? sanitize_text_field($row['reference']) : '',
            'enabled' => isset($row['enabled']) && is_scalar($row['enabled']) && (string) $row['enabled'] === '1' ? '1' : '0',
            'demo' => isset($row['demo']) && is_scalar($row['demo']) && (string) $row['demo'] === '1' ? '1' : '0',
        );
    }
    return $clean;
}

/** Public options contain only deliberately enabled finishes with a valid photo. */
function fort_product_finishes($product_id) {
    $product_id = absint($product_id);
    if (!$product_id || get_post_type($product_id) !== 'fort_produto') return array();
    $rows = fort_sanitize_finish_variants(get_post_meta($product_id, '_fort_finish_variants', true));
    $finishes = array();
    foreach (fort_finish_definitions() as $key => $definition) {
        if (!isset($rows[$key]) || $rows[$key]['enabled'] !== '1' || $rows[$key]['image'] === '') continue;
        $finishes[$key] = array(
            'label' => $definition['label'],
            'color' => $definition['color'],
            'image' => $rows[$key]['image'],
            'reference' => $rows[$key]['reference'],
            'demo' => $rows[$key]['demo'] === '1',
        );
    }
    return $finishes;
}

/** Shared validation for links and quotation requests: never trust a finish label. */
function fort_product_finish($product_id, $key) {
    if (!is_string($key) || !array_key_exists($key, fort_finish_definitions())) return null;
    $finishes = fort_product_finishes($product_id);
    return isset($finishes[$key]) ? $finishes[$key] : null;
}

function fort_product_model_title($product_id) {
    if (function_exists('fort_product_versions') && fort_product_versions($product_id)) return fort_product_versions_title($product_id);
    $model = get_post_meta($product_id, '_fort_variants_title', true);
    return fort_product_finishes($product_id) && is_string($model) && trim($model) !== '' ? $model : get_the_title($product_id);
}

add_action('add_meta_boxes_fort_produto', function () {
    add_meta_box('fort-product-finishes', 'Variações de acabamento', 'fort_product_finishes_metabox', 'fort_produto', 'normal', 'default');
});

function fort_product_finishes_metabox($post) {
    wp_nonce_field('fort_save_finishes', 'fort_finishes_nonce');
    $rows = fort_sanitize_finish_variants(get_post_meta($post->ID, '_fort_finish_variants', true));
    $model_title = get_post_meta($post->ID, '_fort_variants_title', true);
    if (!is_string($model_title)) $model_title = '';
    echo '<p>Cadastre os acabamentos deste modelo. Cada opção só aparece no site quando estiver habilitada e tiver uma URL válida de imagem. Nenhuma variação é criada automaticamente.</p>';
    echo '<p><label for="fort-variants-title"><strong>Nome do modelo sem acabamento (opcional)</strong></label><br><input id="fort-variants-title" class="widefat" type="text" name="_fort_variants_title" value="' . esc_attr($model_title) . '"><br><span class="description">Usado no título da ficha quando há variações. Deixe vazio para manter o título cadastrado do produto.</span></p>';
    echo '<p>Envie cada foto em <a href="' . esc_url(admin_url('media-new.php')) . '" target="_blank" rel="noopener noreferrer">Mídia → Adicionar arquivo de mídia</a>, copie a URL do arquivo e cole no acabamento correspondente. A referência comercial é opcional e deve ser preenchida somente quando confirmada.</p>';
    echo '<p class="description">Marque <strong>Simulação para teste</strong> nas fotos geradas ou recoloridas para avaliação. O site informará: “Simulação de acabamento para teste. Confirme disponibilidade com a Fort.”</p>';
    echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr><th scope="col">Acabamento</th><th scope="col">Habilitar</th><th scope="col">Imagem e referência</th><th scope="col">Simulação para teste</th></tr></thead><tbody>';
    foreach (fort_finish_definitions() as $key => $definition) {
        $row = isset($rows[$key]) ? $rows[$key] : array('image' => '', 'reference' => '', 'enabled' => '0', 'demo' => '0');
        $prefix = '_fort_finish_variants[' . $key . ']';
        $id = 'fort-finish-' . $key;
        echo '<tr><th scope="row">' . esc_html($definition['label']) . '</th>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($prefix . '[enabled]') . '" value="1" ' . checked($row['enabled'], '1', false) . '> <span class="screen-reader-text">Habilitar ' . esc_html($definition['label']) . '</span></label></td>';
        echo '<td><p><label for="' . esc_attr($id . '-image') . '">URL da imagem — ' . esc_html($definition['label']) . '</label><br><input class="widefat" id="' . esc_attr($id . '-image') . '" type="url" name="' . esc_attr($prefix . '[image]') . '" value="' . esc_attr($row['image']) . '" placeholder="https://"></p>';
        echo '<p><label for="' . esc_attr($id . '-reference') . '">Referência — ' . esc_html($definition['label']) . ' (opcional)</label><br><input class="widefat" id="' . esc_attr($id . '-reference') . '" type="text" name="' . esc_attr($prefix . '[reference]') . '" value="' . esc_attr($row['reference']) . '"></p></td>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($prefix . '[demo]') . '" value="1" ' . checked($row['demo'], '1', false) . '> Simulação <span class="screen-reader-text">— ' . esc_html($definition['label']) . '</span></label></td></tr>';
    }
    echo '</tbody></table></div><p class="description">Desabilitar uma opção preserva sua imagem e referência para futuras edições. Essas variações não alteram as linhas e classificações já cadastradas.</p>';
}

add_action('save_post_fort_produto', function ($id, $post) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($id) || wp_is_post_revision($id) || $post->post_status === 'auto-draft' || !current_user_can('edit_post', $id)) return;
    if (!isset($_POST['fort_finishes_nonce']) || !is_string($_POST['fort_finishes_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fort_finishes_nonce'])), 'fort_save_finishes')) return;
    if (isset($_POST['_fort_finish_variants']) && is_array($_POST['_fort_finish_variants'])) {
        update_post_meta($id, '_fort_finish_variants', fort_sanitize_finish_variants(wp_unslash($_POST['_fort_finish_variants'])));
    }
    if (isset($_POST['_fort_variants_title']) && is_string($_POST['_fort_variants_title'])) {
        update_post_meta($id, '_fort_variants_title', sanitize_text_field(wp_unslash($_POST['_fort_variants_title'])));
    }
}, 10, 2);
