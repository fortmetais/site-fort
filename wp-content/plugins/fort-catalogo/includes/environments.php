<?php
/** Environments and manually confirmed products shown in their photographs. */
defined('ABSPATH') || exit;

function fort_register_scenes() {
    register_post_type('fort_cena', array(
        'labels' => array(
            'name' => 'Ambientes',
            'singular_name' => 'Ambiente',
            'add_new_item' => 'Adicionar ambiente',
            'edit_item' => 'Editar ambiente',
            'new_item' => 'Novo ambiente',
            'view_item' => 'Ver ambiente',
            'all_items' => 'Todos os ambientes',
            'search_items' => 'Buscar ambientes',
            'not_found' => 'Nenhum ambiente encontrado',
            'not_found_in_trash' => 'Nenhum ambiente na lixeira',
            'featured_image' => 'Foto principal do ambiente',
            'set_featured_image' => 'Definir foto do ambiente',
            'remove_featured_image' => 'Remover foto do ambiente',
            'use_featured_image' => 'Usar como foto do ambiente',
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-format-gallery',
        'has_archive' => 'ambientes',
        'rewrite' => array('slug' => 'inspiracao', 'with_front' => false),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'),
    ));
}
add_action('init', 'fort_register_scenes');

add_action('init', function () {
    foreach (array('fort_linha', 'fort_ambiente') as $taxonomy) {
        register_taxonomy_for_object_type($taxonomy, 'fort_cena');
    }
}, 20);

add_filter('use_block_editor_for_post_type', function ($enabled, $type) {
    return $type === 'fort_cena' ? false : $enabled;
}, 10, 2);

/* Update persisted rewrite rules once, without flushing on public requests. */
add_action('admin_init', function () {
    if (get_option('fort_scene_schema_version') !== '1' && current_user_can('manage_options')) {
        flush_rewrite_rules(false);
        update_option('fort_scene_schema_version', '1', false);
    }
});

/** Validate stored or submitted items; relationships never come from image names. */
function fort_scene_sanitize_items($raw) {
    if (!is_array($raw)) return array();
    $clean = array();
    $seen = array();
    foreach (array_slice($raw, 0, 8) as $item) {
        if (!is_array($item) || !isset($item['product_id']) || !is_scalar($item['product_id'])) continue;
        $identifier = (string) $item['product_id'];
        if (!ctype_digit($identifier)) continue;
        $product_id = absint($identifier);
        if (!$product_id || isset($seen[$product_id]) || get_post_type($product_id) !== 'fort_produto' || get_post_status($product_id) !== 'publish') continue;
        foreach (array('x', 'y') as $axis) {
            if (isset($item[$axis]) && !is_scalar($item[$axis])) continue 2;
        }
        $position = array();
        foreach (array('x', 'y') as $axis) {
            $value = isset($item[$axis]) && is_scalar($item[$axis]) && is_numeric($item[$axis]) ? (float) $item[$axis] : 50.0;
            if (!is_finite($value)) $value = 50.0;
            $position[$axis] = round(max(0.0, min(100.0, $value)), 2);
        }
        $clean[] = array('product_id' => $product_id, 'x' => $position['x'], 'y' => $position['y']);
        $seen[$product_id] = true;
    }
    return $clean;
}

/** Only public, valid products may be shown on public environment pages. */
function fort_scene_items($scene_id) {
    return fort_scene_sanitize_items(get_post_meta(absint($scene_id), '_fort_cena_items', true));
}

add_action('add_meta_boxes_fort_cena', function () {
    add_meta_box('fort-scene-details', 'Foto e produtos deste ambiente', 'fort_scene_metabox', 'fort_cena', 'normal', 'high');
});

function fort_scene_metabox($post) {
    wp_nonce_field('fort_save_scene', 'fort_scene_nonce');
    $image_url = get_post_meta($post->ID, '_fort_cena_image_url', true);
    if (!is_string($image_url)) $image_url = '';
    $illustrative = !metadata_exists('post', $post->ID, '_fort_cena_illustrative') || get_post_meta($post->ID, '_fort_cena_illustrative', true) === '1';
    $items = fort_scene_items($post->ID);
    $products = get_posts(array(
        'post_type' => 'fort_produto',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    echo '<p>Defina a foto principal em <strong>Foto principal do ambiente</strong>, na lateral do editor. Use o título para nomear o ambiente, o resumo para a galeria e o conteúdo para sua apresentação.</p>';
    echo '<p><label for="fort-scene-image"><strong>URL alternativa da foto (opcional)</strong></label><br><input type="url" class="widefat" id="fort-scene-image" name="_fort_cena_image_url" value="' . esc_attr($image_url) . '" placeholder="https://"><br><span class="description">Usada somente se não houver uma foto principal selecionada.</span></p>';
    echo '<p><label><input type="checkbox" name="_fort_cena_illustrative" value="1" ' . checked($illustrative, true, false) . '> Imagem ilustrativa / ambientação digital</label><br><span class="description">Mantenha marcado para imagens geradas ou modificadas com IA e outras ambientações ilustrativas. Essa informação será apresentada junto à foto.</span></p>';
    echo '<hr><h3>Produtos presentes na imagem</h3>';
    echo '<p>Vincule somente os produtos cuja presença na foto foi conferida pela equipe. A escolha de uma linha ou de uma foto não vincula produtos automaticamente. Deixe as linhas sem produto vazias.</p>';
    echo '<p><a class="button" href="' . esc_url(admin_url('post-new.php?post_type=fort_produto')) . '" target="_blank" rel="noopener noreferrer">Cadastrar novo produto</a></p>';
    echo '<p class="description">Se o produto ainda não existir, salve este ambiente como rascunho e abra o cadastro acima. Informe nome, código comercial, linha, acabamento, ambiente de uso, instalação, acionamento, conexão e foto. Revise os dados antes de publicar; depois recarregue este editor para selecionar o produto.</p>';
    echo '<p>As posições indicam o centro do marcador sobre a foto: <strong>X</strong> vai da esquerda (0%) à direita (100%); <strong>Y</strong> vai do topo (0%) à base (100%). Use 50% / 50% para começar e ajuste após visualizar o ambiente.</p>';
    if (!$products) echo '<p><strong>Ainda não há produtos publicados para vincular.</strong> O ambiente pode ser salvo sem produtos até a conferência do catálogo.</p>';
    echo '<table class="widefat striped"><thead><tr><th scope="col">Produto confirmado</th><th scope="col">X (%)</th><th scope="col">Y (%)</th></tr></thead><tbody>';
    for ($index = 0; $index < 8; $index++) {
        $item = isset($items[$index]) ? $items[$index] : array('product_id' => 0, 'x' => 50, 'y' => 50);
        $select_id = 'fort-scene-product-' . $index;
        echo '<tr><td><label class="screen-reader-text" for="' . esc_attr($select_id) . '">Produto ' . esc_html((string) ($index + 1)) . '</label><select class="widefat" id="' . esc_attr($select_id) . '" name="_fort_cena_items[' . esc_attr((string) $index) . '][product_id]"><option value="">Sem produto / remover vínculo</option>';
        foreach ($products as $product) {
            $code = get_post_meta($product->ID, '_fort_codigo', true);
            $label = $product->post_title . (is_scalar($code) && (string) $code !== '' ? ' — ' . (string) $code : ' — código não informado');
            echo '<option value="' . esc_attr((string) $product->ID) . '" ' . selected($item['product_id'], $product->ID, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td>';
        foreach (array('x', 'y') as $axis) {
            $axis_id = 'fort-scene-' . $axis . '-' . $index;
            echo '<td><label class="screen-reader-text" for="' . esc_attr($axis_id) . '">' . esc_html(strtoupper($axis)) . ' do produto ' . esc_html((string) ($index + 1)) . '</label><input id="' . esc_attr($axis_id) . '" name="_fort_cena_items[' . esc_attr((string) $index) . '][' . esc_attr($axis) . ']" type="number" min="0" max="100" step="0.1" class="small-text" value="' . esc_attr((string) $item[$axis]) . '"></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table><p class="description">Até 8 produtos por ambiente. Para remover um vínculo, selecione “Sem produto / remover vínculo”. Produtos repetidos são considerados uma única vez.</p>';
}

add_action('save_post_fort_cena', function ($id, $post) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($id) || wp_is_post_revision($id) || $post->post_status === 'auto-draft' || !current_user_can('edit_post', $id)) return;
    if (!isset($_POST['fort_scene_nonce']) || !is_string($_POST['fort_scene_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fort_scene_nonce'])), 'fort_save_scene')) return;
    if (isset($_POST['_fort_cena_image_url']) && is_string($_POST['_fort_cena_image_url'])) {
        update_post_meta($id, '_fort_cena_image_url', esc_url_raw(wp_unslash($_POST['_fort_cena_image_url']), array('http', 'https')));
    }
    if (!isset($_POST['_fort_cena_illustrative']) || is_string($_POST['_fort_cena_illustrative'])) {
        $illustrative = isset($_POST['_fort_cena_illustrative']) && $_POST['_fort_cena_illustrative'] === '1';
        update_post_meta($id, '_fort_cena_illustrative', $illustrative ? '1' : '0');
    }
    if (isset($_POST['_fort_cena_items']) && is_array($_POST['_fort_cena_items'])) {
        update_post_meta($id, '_fort_cena_items', fort_scene_sanitize_items(wp_unslash($_POST['_fort_cena_items'])));
    }
}, 10, 2);
