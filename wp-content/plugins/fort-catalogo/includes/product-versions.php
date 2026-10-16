<?php
/** Manually configured spout versions, independent of product finish options. */
defined('ABSPATH') || exit;

function fort_product_version_definitions() {
    return array(
        'arejador' => array('label' => 'Com arejador'),
        'articulador' => array('label' => 'Com articulador'),
    );
}

/** Retain disabled rows so photographs and confirmed references can be completed. */
function fort_sanitize_product_versions($raw) {
    if (!is_array($raw)) return array();
    $clean = array();
    foreach (fort_product_version_definitions() as $key => $definition) {
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

/** A version is selectable only when enabled and linked to a valid image. */
function fort_product_versions($product_id) {
    $product_id = absint($product_id);
    if (!$product_id || get_post_type($product_id) !== 'fort_produto') return array();
    $rows = fort_sanitize_product_versions(get_post_meta($product_id, '_fort_product_versions', true));
    $versions = array();
    foreach (fort_product_version_definitions() as $key => $definition) {
        if (!isset($rows[$key]) || $rows[$key]['enabled'] !== '1' || $rows[$key]['image'] === '') continue;
        $versions[$key] = array(
            'label' => $definition['label'],
            'image' => $rows[$key]['image'],
            'reference' => $rows[$key]['reference'],
            'demo' => $rows[$key]['demo'] === '1',
        );
    }
    return $versions;
}

/** Validate a selected version against the product's enabled options. */
function fort_product_version($product_id, $key) {
    if (!is_string($key) || !array_key_exists($key, fort_product_version_definitions())) return null;
    $versions = fort_product_versions($product_id);
    return isset($versions[$key]) ? $versions[$key] : null;
}

/** Return the default option key, falling back to the first valid enabled row. */
function fort_product_default_version($product_id) {
    $versions = fort_product_versions($product_id);
    $key = get_post_meta(absint($product_id), '_fort_default_version', true);
    return is_string($key) && isset($versions[$key]) ? $key : array_key_first($versions);
}

function fort_product_versions_title($product_id) {
    $model = get_post_meta(absint($product_id), '_fort_versions_title', true);
    return fort_product_versions($product_id) && is_string($model) && trim($model) !== '' ? $model : get_the_title($product_id);
}

add_action('add_meta_boxes_fort_produto', function () {
    add_meta_box('fort-product-versions', 'Variações da bica', 'fort_product_versions_metabox', 'fort_produto', 'normal', 'default');
});

function fort_product_versions_metabox($post) {
    wp_nonce_field('fort_save_versions', 'fort_versions_nonce');
    $rows = fort_sanitize_product_versions(get_post_meta($post->ID, '_fort_product_versions', true));
    $title = get_post_meta($post->ID, '_fort_versions_title', true);
    if (!is_string($title)) $title = '';
    $default = get_post_meta($post->ID, '_fort_default_version', true);
    if (!is_string($default) || !array_key_exists($default, fort_product_version_definitions())) $default = '';
    echo '<p>Cadastre as versões com arejador e com articulador quando disponíveis para este modelo. Estas opções são independentes das variações de acabamento. Nenhuma versão é habilitada automaticamente.</p>';
    echo '<p><label for="fort-versions-title"><strong>Nome do modelo sem a versão da bica (opcional)</strong></label><br><input id="fort-versions-title" class="widefat" type="text" name="_fort_versions_title" value="' . esc_attr($title) . '"><br><span class="description">Usado no título da ficha quando houver versões habilitadas. Deixe vazio para manter o título cadastrado do produto.</span></p>';
    echo '<p>Envie cada foto em <a href="' . esc_url(admin_url('media-new.php')) . '" target="_blank" rel="noopener noreferrer">Mídia → Adicionar arquivo de mídia</a>, copie a URL e cole na versão correspondente. Informe a referência comercial somente após a confirmação pela equipe.</p>';
    echo '<p class="description">Marque <strong>Simulação para teste</strong> nas imagens ilustrativas, geradas ou modificadas para avaliação. A indicação será exibida na ficha do produto.</p>';
    echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr><th scope="col">Versão</th><th scope="col">Habilitar</th><th scope="col">Imagem e referência</th><th scope="col">Simulação para teste</th></tr></thead><tbody>';
    foreach (fort_product_version_definitions() as $key => $definition) {
        $row = isset($rows[$key]) ? $rows[$key] : array('image' => '', 'reference' => '', 'enabled' => '0', 'demo' => '0');
        $prefix = '_fort_product_versions[' . $key . ']';
        $id = 'fort-version-' . $key;
        echo '<tr><th scope="row">' . esc_html($definition['label']) . '</th>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($prefix . '[enabled]') . '" value="1" ' . checked($row['enabled'], '1', false) . '> <span class="screen-reader-text">Habilitar ' . esc_html($definition['label']) . '</span></label></td>';
        echo '<td><p><label for="' . esc_attr($id . '-image') . '">URL da imagem — ' . esc_html($definition['label']) . '</label><br><input class="widefat" id="' . esc_attr($id . '-image') . '" type="url" name="' . esc_attr($prefix . '[image]') . '" value="' . esc_attr($row['image']) . '" placeholder="https://"></p>';
        echo '<p><label for="' . esc_attr($id . '-reference') . '">Referência — ' . esc_html($definition['label']) . ' (opcional)</label><br><input class="widefat" id="' . esc_attr($id . '-reference') . '" type="text" name="' . esc_attr($prefix . '[reference]') . '" value="' . esc_attr($row['reference']) . '"></p></td>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($prefix . '[demo]') . '" value="1" ' . checked($row['demo'], '1', false) . '> Simulação <span class="screen-reader-text">— ' . esc_html($definition['label']) . '</span></label></td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<p><label for="fort-default-version"><strong>Versão exibida inicialmente</strong></label><br><select id="fort-default-version" name="_fort_default_version"><option value="" ' . selected($default, '', false) . '>Primeira versão habilitada</option>';
    foreach (fort_product_version_definitions() as $key => $definition) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($default, $key, false) . '>' . esc_html($definition['label']) . '</option>';
    }
    echo '</select><br><span class="description">A versão escolhida precisa estar habilitada e ter uma URL válida de imagem. Caso contrário, a primeira versão válida será exibida.</span></p>';
    echo '<p class="description">Desabilitar uma versão preserva sua imagem e referência. As versões não alteram as linhas, cores e outras informações do produto.</p>';
}

add_action('save_post_fort_produto', function ($id, $post) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($id) || wp_is_post_revision($id) || $post->post_status === 'auto-draft' || !current_user_can('edit_post', $id)) return;
    if (!isset($_POST['fort_versions_nonce']) || !is_string($_POST['fort_versions_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fort_versions_nonce'])), 'fort_save_versions')) return;
    if (isset($_POST['_fort_product_versions']) && is_array($_POST['_fort_product_versions'])) {
        update_post_meta($id, '_fort_product_versions', fort_sanitize_product_versions(wp_unslash($_POST['_fort_product_versions'])));
    }
    if (isset($_POST['_fort_versions_title']) && is_string($_POST['_fort_versions_title'])) {
        update_post_meta($id, '_fort_versions_title', sanitize_text_field(wp_unslash($_POST['_fort_versions_title'])));
    }
    if (isset($_POST['_fort_default_version']) && is_string($_POST['_fort_default_version'])) {
        $default = wp_unslash($_POST['_fort_default_version']);
        $valid = fort_product_versions($id);
        update_post_meta($id, '_fort_default_version', isset($valid[$default]) ? $default : '');
    }
}, 10, 2);
