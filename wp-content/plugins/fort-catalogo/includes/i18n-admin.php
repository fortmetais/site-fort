<?php
/** Editorial adjustments for the public site's translation catalogues. */
defined('ABSPATH') || exit;

function fort_i18n_admin_base_catalogs() {
    $catalogs = array('en' => array(), 'es' => array());
    foreach (array_keys($catalogs) as $language) {
        $path = __DIR__ . '/translations/' . $language . '.php';
        if (is_file($path)) {
            $catalog = require $path;
            if (is_array($catalog)) $catalogs[$language] = $catalog;
        }
    }
    return $catalogs;
}

function fort_i18n_admin_overrides() {
    $stored = get_option('fort_i18n_overrides', array());
    if (!is_array($stored)) $stored = array();
    foreach (array('en', 'es') as $language) {
        if (!isset($stored[$language]) || !is_array($stored[$language])) $stored[$language] = array();
    }
    return $stored;
}

function fort_i18n_admin_sources($catalogs, $overrides) {
    $sources = array();
    foreach (array('en', 'es') as $language) {
        foreach (array($catalogs[$language], $overrides[$language]) as $catalog) {
            foreach ($catalog as $source => $translation) {
                if (is_string($source) && $source !== '' && strlen($source) <= 12000 && is_string($translation)) $sources[$source] = true;
            }
        }
    }
    $sources = array_keys($sources);
    sort($sources, SORT_NATURAL | SORT_FLAG_CASE);
    return $sources;
}

function fort_i18n_admin_clean_translation($value) {
    if (!is_string($value) || strlen($value) > 24000 || wp_check_invalid_utf8($value) !== $value) {
        wp_die('A tradução contém caracteres inválidos ou ultrapassa o limite de 24.000 bytes.', 'Não foi possível salvar', array('response' => 400));
    }
    return sanitize_textarea_field(wp_strip_all_tags($value));
}

add_action('admin_menu', function () {
    add_options_page('Idiomas e traduções', 'Idiomas e traduções', 'manage_options', 'fort-idiomas', 'fort_i18n_admin_page');
});

add_action('admin_init', function () {
    if (!isset($_POST['fort_i18n_save'])) return;
    if (!current_user_can('manage_options')) wp_die('Você não tem permissão para editar traduções.', '', array('response' => 403));
    check_admin_referer('fort_i18n_save', 'fort_i18n_nonce');

    $catalogs = fort_i18n_admin_base_catalogs();
    $overrides = fort_i18n_admin_overrides();
    $known_sources = array_fill_keys(fort_i18n_admin_sources($catalogs, $overrides), true);
    $rows = isset($_POST['fort_i18n_rows']) ? wp_unslash($_POST['fort_i18n_rows']) : array();
    if (!is_array($rows) || count($rows) > 25) wp_die('Envie somente uma página de traduções por vez.', '', array('response' => 400));

    // Validate the entire batch before writing anything. Other pages stay intact.
    $clean_rows = array();
    foreach ($rows as $hash => $row) {
        if (!is_string($hash) || !is_array($row) || !isset($row['source']) || !is_string($row['source'])) wp_die('Linha de tradução inválida.', '', array('response' => 400));
        $source = $row['source'];
        if (strlen($source) > 12000 || !hash_equals(hash('sha256', $source), $hash) || !isset($known_sources[$source])) wp_die('A origem de uma tradução mudou. Recarregue a página e tente novamente.', '', array('response' => 400));
        foreach (array('en', 'es') as $language) {
            if (!array_key_exists($language, $row)) wp_die('A página de traduções foi recebida incompleta. Recarregue e tente novamente.', '', array('response' => 400));
            $clean_rows[$source][$language] = fort_i18n_admin_clean_translation($row[$language]);
        }
    }

    $new_source_raw = isset($_POST['fort_i18n_new_source']) ? wp_unslash($_POST['fort_i18n_new_source']) : '';
    $new_source = fort_i18n_admin_clean_translation($new_source_raw);
    if (strlen($new_source) > 12000) wp_die('O texto original ultrapassa o limite de 12.000 bytes.', '', array('response' => 400));
    $new_translations = array();
    foreach (array('en', 'es') as $language) {
        $new_translations[$language] = fort_i18n_admin_clean_translation(isset($_POST['fort_i18n_new_' . $language]) ? wp_unslash($_POST['fort_i18n_new_' . $language]) : '');
    }
    $new_has_translation = $new_translations['en'] !== '' || $new_translations['es'] !== '';
    if ($new_source !== '' && $new_has_translation) {
        // Adding an existing source must not clear its other language unseen.
        if (!isset($clean_rows[$new_source])) $clean_rows[$new_source] = array();
        foreach ($new_translations as $language => $translation) {
            if ($translation !== '') $clean_rows[$new_source][$language] = $translation;
        }
    }

    foreach ($clean_rows as $source => $translations) {
        foreach ($translations as $language => $translation) {
            if ($translation === '' || (isset($catalogs[$language][$source]) && $translation === $catalogs[$language][$source])) {
                unset($overrides[$language][$source]);
            } else {
                $overrides[$language][$source] = $translation;
            }
        }
    }
    update_option('fort_i18n_overrides', $overrides, false);

    $args = array('page' => 'fort-idiomas', 'fort_saved' => '1');
    if (($new_source !== '' && !$new_has_translation) || ($new_source === '' && $new_has_translation)) $args['fort_add_incomplete'] = '1';
    if (isset($_POST['fort_i18n_search']) && is_string($_POST['fort_i18n_search'])) $args['s'] = sanitize_text_field(wp_unslash($_POST['fort_i18n_search']));
    if (isset($_POST['fort_i18n_page'])) $args['paged'] = max(1, absint($_POST['fort_i18n_page']));
    wp_safe_redirect(add_query_arg($args, admin_url('options-general.php')));
    exit;
});

function fort_i18n_admin_page() {
    if (!current_user_can('manage_options')) return;
    $catalogs = fort_i18n_admin_base_catalogs();
    $overrides = fort_i18n_admin_overrides();
    $sources = fort_i18n_admin_sources($catalogs, $overrides);
    $search = isset($_GET['s']) && is_string($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    if ($search !== '') {
        $sources = array_values(array_filter($sources, function ($source) use ($catalogs, $overrides, $search) {
            $text = $source;
            foreach (array('en', 'es') as $language) $text .= "\n" . ($overrides[$language][$source] ?? $catalogs[$language][$source] ?? '');
            return function_exists('mb_stripos') ? mb_stripos($text, $search, 0, 'UTF-8') !== false : stripos($text, $search) !== false;
        }));
    }
    $total = count($sources);
    $pages = max(1, (int) ceil($total / 25));
    $page = min($pages, isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1);
    $visible_sources = array_slice($sources, ($page - 1) * 25, 25);
    $page_url = add_query_arg(array('page' => 'fort-idiomas', 's' => $search, 'paged' => $page), admin_url('options-general.php'));
    $pagination_base = str_replace('999999999', '%#%', add_query_arg('paged', 999999999, $page_url));
    ?>
    <div class="wrap fort-i18n-admin">
        <h1>Idiomas e traduções</h1>
        <p>O site usa <strong>Português (Brasil)</strong> como idioma padrão. Inglês e espanhol partem de um catálogo previamente traduzido. A equipe pode revisar os textos abaixo ou adicionar traduções de novos conteúdos, sem alterar o original em português.</p>
        <p>Esta tela não faz tradução automática nem utiliza serviços pagos. Deixar uma tradução vazia restaura o texto do catálogo base; se ele ainda não existir, o site exibe o original em português.</p>
        <?php if (isset($_GET['fort_saved']) && $_GET['fort_saved'] === '1') : ?>
            <div class="notice notice-success is-dismissible"><p>Traduções desta página salvas.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['fort_add_incomplete']) && $_GET['fort_add_incomplete'] === '1') : ?>
            <div class="notice notice-warning"><p>Para adicionar um novo texto, informe o original em português e pelo menos uma tradução. As demais alterações foram salvas.</p></div>
        <?php endif; ?>
        <form method="get" action="<?php echo esc_url(admin_url('options-general.php')); ?>">
            <input type="hidden" name="page" value="fort-idiomas">
            <p class="search-box"><label class="screen-reader-text" for="fort-i18n-search">Buscar nas traduções</label><input id="fort-i18n-search" type="search" name="s" value="<?php echo esc_attr($search); ?>"><input type="submit" class="button" value="Buscar traduções"></p>
        </form>
        <p class="fort-i18n-count"><?php echo esc_html(number_format_i18n($total)); ?> textos encontrados. Página <?php echo esc_html($page); ?> de <?php echo esc_html($pages); ?>.</p>
        <form method="post" action="<?php echo esc_url($page_url); ?>">
            <?php wp_nonce_field('fort_i18n_save', 'fort_i18n_nonce'); ?>
            <input type="hidden" name="fort_i18n_save" value="1">
            <input type="hidden" name="fort_i18n_search" value="<?php echo esc_attr($search); ?>">
            <input type="hidden" name="fort_i18n_page" value="<?php echo esc_attr($page); ?>">
            <table class="widefat striped fort-i18n-table">
                <thead><tr><th scope="col">Original — Português (Brasil)</th><th scope="col">English</th><th scope="col">Español</th></tr></thead>
                <tbody>
                    <?php foreach ($visible_sources as $source) : $hash = hash('sha256', $source); ?>
                        <tr>
                            <th scope="row"><div class="fort-i18n-source"><?php echo esc_html($source); ?></div><input type="hidden" name="fort_i18n_rows[<?php echo esc_attr($hash); ?>][source]" value="<?php echo esc_attr($source); ?>"></th>
                            <?php foreach (array('en' => 'English', 'es' => 'Español') as $language => $label) :
                                $translation = $overrides[$language][$source] ?? $catalogs[$language][$source] ?? '';
                                $input_id = 'fort-i18n-' . $language . '-' . $hash;
                            ?>
                                <td><label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($label . ': ' . $source); ?></label><textarea class="widefat" id="<?php echo esc_attr($input_id); ?>" name="fort_i18n_rows[<?php echo esc_attr($hash); ?>][<?php echo esc_attr($language); ?>]" rows="3" maxlength="12000" lang="<?php echo esc_attr($language); ?>"><?php echo esc_textarea(is_string($translation) ? $translation : ''); ?></textarea><?php if (isset($overrides[$language][$source])) : ?><span class="description">Personalizado pela equipe</span><?php endif; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$visible_sources) : ?><tr><td colspan="3">Nenhum texto encontrado. Use “Adicionar tradução” para cadastrar um novo original.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <?php submit_button('Salvar traduções desta página'); ?>
            <details class="fort-i18n-add">
                <summary>Adicionar tradução</summary>
                <p>Copie o texto em português exatamente como aparece no site, incluindo pontuação. Traduções de novos textos são usadas quando esse mesmo original aparece no site.</p>
                <p><label for="fort-i18n-new-source"><strong>Original — Português (Brasil)</strong></label><br><textarea class="large-text" id="fort-i18n-new-source" name="fort_i18n_new_source" rows="3" maxlength="6000" lang="pt-BR"></textarea></p>
                <div class="fort-i18n-new-languages">
                    <p><label for="fort-i18n-new-en"><strong>English</strong></label><br><textarea class="large-text" id="fort-i18n-new-en" name="fort_i18n_new_en" rows="3" maxlength="12000" lang="en"></textarea></p>
                    <p><label for="fort-i18n-new-es"><strong>Español</strong></label><br><textarea class="large-text" id="fort-i18n-new-es" name="fort_i18n_new_es" rows="3" maxlength="12000" lang="es"></textarea></p>
                </div>
                <?php submit_button('Salvar e adicionar tradução', 'secondary', 'submit', false); ?>
            </details>
        </form>
        <?php if ($pages > 1) : ?>
            <nav class="tablenav-pages fort-i18n-pagination" aria-label="Páginas de traduções"><?php echo wp_kses_post(paginate_links(array('base' => $pagination_base, 'format' => '', 'current' => $page, 'total' => $pages, 'prev_text' => '« Anterior', 'next_text' => 'Próxima »'))); ?></nav>
        <?php endif; ?>
    </div>
    <style>
        .fort-i18n-admin{max-width:1500px}.fort-i18n-count{clear:both;padding-top:12px}.fort-i18n-table th,.fort-i18n-table td{width:33.333%;vertical-align:top;padding:12px}.fort-i18n-table tbody th{font-weight:400}.fort-i18n-source{white-space:pre-wrap;overflow-wrap:anywhere}.fort-i18n-table textarea{min-height:76px;resize:vertical}.fort-i18n-table .description{display:block;margin-top:4px}.fort-i18n-add{margin:20px 0;padding:18px;background:#fff;border:1px solid #c3c4c7}.fort-i18n-add summary{font-size:16px;font-weight:600;cursor:pointer}.fort-i18n-new-languages{display:grid;grid-template-columns:1fr 1fr;gap:20px}.fort-i18n-pagination{display:flex;gap:12px;margin:24px 0}.fort-i18n-pagination .current{font-weight:700}@media(max-width:782px){.fort-i18n-table thead{display:none}.fort-i18n-table tr{display:block;padding:10px 0;border-bottom:1px solid #c3c4c7}.fort-i18n-table th,.fort-i18n-table td{display:block;width:auto}.fort-i18n-table td label{position:static!important;width:auto;height:auto;clip:auto;clip-path:none;font-weight:600;display:block;margin-bottom:6px}.fort-i18n-new-languages{grid-template-columns:1fr;gap:0}}
    </style>
    <?php
}
