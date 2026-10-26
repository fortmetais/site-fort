<?php
/**
 * Plugin Name: Fort Catálogo
 * Description: Produtos, classificações, representantes e solicitações de orçamento da Fort Metais.
 * Version: 0.2.0
 * Requires PHP: 8.1
 * Text Domain: fort-catalogo
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/includes/representatives-settings.php';
require_once __DIR__ . '/includes/environments.php';
require_once __DIR__ . '/includes/product-finishes.php';
require_once __DIR__ . '/includes/product-versions.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/i18n-admin.php';
require_once __DIR__ . '/includes/news.php';

function fort_register_catalog() {
    register_post_type('fort_produto', array(
        'labels' => array('name' => 'Produtos', 'singular_name' => 'Produto', 'add_new_item' => 'Adicionar produto', 'edit_item' => 'Editar produto', 'search_items' => 'Buscar produtos', 'not_found' => 'Nenhum produto encontrado'),
        'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-products',
        'has_archive' => 'produtos', 'rewrite' => array('slug' => 'produtos', 'with_front' => false),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'),
    ));
    foreach (array('fort_linha' => array('Linhas', 'Linha', 'nossas-linhas'), 'fort_ambiente' => array('Ambientes', 'Ambiente', 'ambientes'), 'fort_acabamento' => array('Acabamentos', 'Acabamento', 'acabamentos')) as $key => $labels) {
        register_taxonomy($key, 'fort_produto', array(
            'labels' => array('name' => $labels[0], 'singular_name' => $labels[1]),
            'public' => true, 'hierarchical' => true, 'show_in_rest' => true,
            'show_admin_column' => true, 'rewrite' => array('slug' => $labels[2], 'with_front' => false),
        ));
    }
    register_post_type('fort_representante', array(
        'labels' => array('name' => 'Representantes', 'singular_name' => 'Representante', 'add_new_item' => 'Adicionar representante'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => true,
        'menu_icon' => 'dashicons-location-alt', 'supports' => array('title', 'editor'),
    ));
    register_post_type('fort_orcamento', array(
        'labels' => array('name' => 'Orçamentos', 'singular_name' => 'Solicitação', 'edit_item' => 'Consultar solicitação'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false,
        'menu_icon' => 'dashicons-email-alt', 'supports' => array('title'),
        'capabilities' => array('create_posts' => 'do_not_allow'), 'map_meta_cap' => true,
    ));
}
add_action('init', 'fort_register_catalog');
add_filter('use_block_editor_for_post_type', function ($enabled, $type) { return in_array($type, array('fort_produto', 'fort_representante'), true) ? false : $enabled; }, 10, 2);
register_activation_hook(__FILE__, function () { fort_register_catalog(); flush_rewrite_rules(); });

function fort_product_fields() {
    return array('_fort_codigo' => 'Referência / código comercial', '_fort_medidas' => 'Dimensões', '_fort_instalacao' => 'Instalação', '_fort_acionamento' => 'Acionamento', '_fort_conexao' => 'Conexão', '_fort_ficha' => 'URL da ficha técnica ou manual');
}
add_action('add_meta_boxes', function () {
    add_meta_box('fort-details', 'Informações do produto', 'fort_product_metabox', 'fort_produto', 'normal', 'high');
    add_meta_box('fort-representative', 'Região e contato', 'fort_representative_metabox', 'fort_representante');
    add_meta_box('fort-request', 'Dados da solicitação', 'fort_request_metabox', 'fort_orcamento');
});
function fort_product_metabox($post) {
    wp_nonce_field('fort_save_fields', 'fort_fields_nonce');
    echo '<p>Preencha somente especificações confirmadas. A foto principal é cadastrada em <strong>Imagem destacada</strong>.</p>';
    foreach (fort_product_fields() as $key => $label) {
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br><input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . ($key === '_fort_ficha' ? 'url' : 'text') . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '"></p>';
    }
    echo '<p><label><input type="checkbox" name="_fort_destaque" value="1" ' . checked(get_post_meta($post->ID, '_fort_destaque', true), '1', false) . '> Exibir nos destaques da página inicial</label></p>';
    echo '<p><label><input type="checkbox" name="_fort_lancamento" value="1" ' . checked(get_post_meta($post->ID, '_fort_lancamento', true), '1', false) . '> Identificar como lançamento</label></p>';
}
function fort_representative_metabox($post) {
    wp_nonce_field('fort_save_fields', 'fort_fields_nonce');
    foreach (array('_fort_uf' => 'UF atendida (exemplo: PR)', '_fort_regiao' => 'Cidades ou região atendida', '_fort_telefone' => 'Telefone', '_fort_email' => 'E-mail') as $key => $label) {
        echo '<p><label><strong>' . esc_html($label) . '</strong><br><input class="widefat" name="' . esc_attr($key) . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '"></label></p>';
    }
}
function fort_request_metabox($post) {
    foreach (array('_fort_nome' => 'Nome', '_fort_email' => 'E-mail', '_fort_telefone' => 'Telefone', '_fort_cidade' => 'Cidade / UF', '_fort_produto_nome' => 'Produto', '_fort_acabamento' => 'Acabamento selecionado', '_fort_acabamento_chave' => 'Identificador do acabamento', '_fort_acabamento_referencia' => 'Referência do acabamento', '_fort_acabamento_simulacao' => 'Imagem de simulação', '_fort_versao' => 'Versão selecionada', '_fort_versao_chave' => 'Identificador da versão', '_fort_versao_referencia' => 'Referência da versão', '_fort_versao_simulacao' => 'Imagem da versão — simulação', '_fort_mensagem' => 'Mensagem', '_fort_consentimento' => 'Consentimento', '_fort_idioma' => 'Idioma da solicitação') as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        if ($key === '_fort_idioma') $value = array('pt-br' => 'Português (BR)', 'en' => 'English', 'es' => 'Español')[$value] ?? $value;
        $reference_types = array('_fort_acabamento_referencia' => '_fort_acabamento_chave', '_fort_versao_referencia' => '_fort_versao_chave');
        if ($value === '' && isset($reference_types[$key]) && get_post_meta($post->ID, $reference_types[$key], true) !== '') $value = 'a confirmar';
        echo '<p><strong>' . esc_html($label) . '</strong><br>' . nl2br(esc_html($value)) . '</p>';
    }
}
add_action('save_post', function ($id) {
    if (!isset($_POST['fort_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fort_fields_nonce'])), 'fort_save_fields') || !current_user_can('edit_post', $id) || wp_is_post_autosave($id) || wp_is_post_revision($id)) return;
    if (get_post_type($id) === 'fort_produto') {
        foreach (fort_product_fields() as $key => $label) {
            $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            update_post_meta($id, $key, $key === '_fort_ficha' ? esc_url_raw($value) : sanitize_text_field($value));
        }
        foreach (array('_fort_destaque', '_fort_lancamento') as $key) update_post_meta($id, $key, isset($_POST[$key]) ? '1' : '0');
    }
    if (get_post_type($id) === 'fort_representante') {
        foreach (array('_fort_uf', '_fort_regiao', '_fort_telefone', '_fort_email') as $key) {
            $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
            if ($key === '_fort_uf') $value = strtoupper(substr($value, 0, 2));
            if ($key === '_fort_email') $value = sanitize_email($value);
            update_post_meta($id, $key, $value);
        }
    }
});

function fort_options_defaults() {
    return array('telefone' => '(44) 3425-8600', 'whatsapp' => '554434258600', 'email' => 'contato@fortmetais.com.br', 'endereco' => 'R. Martin Afonso de Souza, 315 — Loanda, PR', 'hero_titulo' => 'Desde 1999, inspirando inovação e praticidade.', 'hero_texto' => 'Oferecemos produtos em metais com alta qualidade, profissionalismo e confiança, promovendo soluções para a vida e o crescimento sustentável.', 'catalogo' => '', 'catalogo_en' => '', 'catalogo_es' => '', 'hero_imagem' => '', 'instagram' => '');
}
function fort_option($key) { $all = wp_parse_args(get_option('fort_options', array()), fort_options_defaults()); return isset($all[$key]) ? $all[$key] : ''; }
add_action('admin_menu', function () { add_options_page('Fort Metais', 'Fort Metais', 'manage_options', 'fort-metais', 'fort_settings_page'); });
add_action('admin_init', function () {
    register_setting('fort_settings', 'fort_options', array('sanitize_callback' => function ($input) {
        $clean = array();
        foreach (fort_options_defaults() as $key => $default) {
            $value = isset($input[$key]) ? $input[$key] : '';
            $clean[$key] = in_array($key, array('catalogo', 'catalogo_en', 'catalogo_es', 'hero_imagem', 'instagram'), true) ? esc_url_raw($value) : ($key === 'email' ? sanitize_email($value) : sanitize_textarea_field($value));
            if ($key === 'whatsapp') $clean[$key] = preg_replace('/\D/', '', $value);
        }
        return $clean;
    }));
});
function fort_settings_page() {
    echo '<div class="wrap"><h1>Fort Metais</h1><p>Edite os contatos e a apresentação da página inicial. Os produtos, linhas e ambientes têm seus próprios cadastros no menu.</p><form method="post" action="options.php">';
    settings_fields('fort_settings');
    echo '<table class="form-table">';
    $labels = array('hero_titulo' => 'Título da abertura', 'hero_texto' => 'Descrição da abertura', 'hero_imagem' => 'URL da imagem da abertura', 'telefone' => 'Telefone', 'whatsapp' => 'WhatsApp com país e DDD', 'email' => 'E-mail', 'endereco' => 'Endereço', 'catalogo' => 'URL do catálogo PDF — Português (BR)', 'catalogo_en' => 'URL do catálogo PDF — English', 'catalogo_es' => 'URL do catálogo PDF — Español (opcional)', 'instagram' => 'Instagram');
    $descriptions = array(
        'catalogo' => 'Arquivo em Português (BR), usado no site em português. Também é a alternativa para o espanhol quando não houver um catálogo em espanhol.',
        'catalogo_en' => 'Arquivo em inglês, usado quando o site estiver em English.',
        'catalogo_es' => 'Opcional. Sem um arquivo em espanhol, o site exibirá o catálogo em Português (BR), identificado como tal.',
    );
    foreach ($labels as $key => $label) {
        echo '<tr><th><label for="fort-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><textarea rows="' . (str_starts_with($key, 'hero_t') ? '3' : '1') . '" class="large-text" id="fort-' . esc_attr($key) . '" name="fort_options[' . esc_attr($key) . ']">' . esc_textarea(fort_option($key)) . '</textarea>';
        if (isset($descriptions[$key])) echo '<p class="description">' . esc_html($descriptions[$key]) . '</p>';
        echo '</td></tr>';
    }
    echo '</table>'; submit_button('Salvar alterações'); echo '</form></div>';
}

add_filter('manage_fort_produto_posts_columns', function ($cols) { $cols['fort_ref'] = 'Referência'; $cols['fort_featured'] = 'Destaque'; return $cols; });
add_action('manage_fort_produto_posts_custom_column', function ($col, $id) { if ($col === 'fort_ref') echo esc_html(get_post_meta($id, '_fort_codigo', true) ?: 'A definir'); if ($col === 'fort_featured') echo get_post_meta($id, '_fort_destaque', true) === '1' ? 'Sim' : '—'; }, 10, 2);

function fort_handle_quote() {
    $back = home_url('/fale-com-a-gente/');
    if (!isset($_POST['fort_quote_nonce']) || !is_string($_POST['fort_quote_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fort_quote_nonce'])), 'fort_quote')) wp_die(fort_t('A sessão do formulário expirou. Volte e tente novamente.'), fort_t('Solicitação inválida'), array('response' => 403));
    $read = function ($key) { return isset($_POST[$key]) && is_scalar($_POST[$key]) ? trim(wp_unslash((string) $_POST[$key])) : ''; };
    $name = sanitize_text_field($read('nome')); $email = sanitize_email($read('email'));
    $phone = sanitize_text_field($read('telefone')); $message = sanitize_textarea_field($read('mensagem'));
    if ($read('website') !== '' || strlen($name) < 2 || strlen($name) > 120 || !is_email($email) || strlen($phone) < 8 || strlen($phone) > 40 || strlen($message) > 5000 || $read('consentimento') !== '1') { wp_safe_redirect(add_query_arg('envio', 'invalido', $back)); exit; }
    $product = absint($read('produto'));
    if ($product && (get_post_type($product) !== 'fort_produto' || get_post_status($product) !== 'publish')) $product = 0;
    $finish_key = $read('acabamento');
    $finish = $finish_key !== '' && $product ? fort_product_finish($product, $finish_key) : null;
    // Validate the enabled option against this product before rate limiting or saving.
    if ((isset($_POST['acabamento']) && !is_scalar($_POST['acabamento'])) || ($finish_key !== '' && !$finish)) { wp_safe_redirect(add_query_arg('envio', 'invalido', $back)); exit; }
    $version_key = $read('versao');
    $version = $version_key !== '' && $product ? fort_product_version($product, $version_key) : null;
    if ((isset($_POST['versao']) && !is_scalar($_POST['versao'])) || ($version_key !== '' && !$version)) { wp_safe_redirect(add_query_arg('envio', 'invalido', $back)); exit; }
    $rate_key = 'fort_quote_' . md5(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'local');
    if (get_transient($rate_key)) { wp_safe_redirect(add_query_arg('envio', 'aguarde', $back)); exit; }
    $id = wp_insert_post(array('post_type' => 'fort_orcamento', 'post_status' => 'private', 'post_title' => $name . ' — ' . wp_date('d/m/Y H:i')), true);
    if (is_wp_error($id) || !$id) { wp_safe_redirect(add_query_arg('envio', 'erro', $back)); exit; }
    foreach (array('_fort_nome' => $name, '_fort_email' => $email, '_fort_telefone' => $phone, '_fort_cidade' => sanitize_text_field($read('cidade')), '_fort_produto' => $product, '_fort_produto_nome' => $product ? fort_product_model_title($product) : 'Contato geral', '_fort_acabamento' => $finish ? $finish['label'] : '', '_fort_acabamento_chave' => $finish ? $finish_key : '', '_fort_acabamento_referencia' => $finish ? $finish['reference'] : '', '_fort_acabamento_simulacao' => $finish && $finish['demo'] ? 'Simulação para teste' : '', '_fort_versao' => $version ? $version['label'] : '', '_fort_versao_chave' => $version ? $version_key : '', '_fort_versao_referencia' => $version ? $version['reference'] : '', '_fort_versao_simulacao' => $version && $version['demo'] ? 'Simulação para teste' : '', '_fort_mensagem' => $message, '_fort_consentimento' => wp_date('c'), '_fort_idioma' => fort_language()) as $key => $value) update_post_meta($id, $key, $value);
    set_transient($rate_key, 1, 30);
    wp_safe_redirect(add_query_arg('envio', 'sucesso', $back)); exit;
}
add_action('admin_post_fort_orcamento', 'fort_handle_quote');
add_action('admin_post_nopriv_fort_orcamento', 'fort_handle_quote');
