<?php
/** Native WordPress posts power the public news section. */
defined('ABSPATH') || exit;

function fort_news_category_id() {
    $term = get_term_by('slug', 'noticias', 'category');
    return $term && !is_wp_error($term) ? (int) $term->term_id : 0;
}

function fort_news_author_id() {
    if (current_user_can('publish_posts')) return get_current_user_id();
    $administrators = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC'));
    return $administrators ? (int) $administrators[0] : 0;
}

function fort_news_setup() {
    if (get_option('fort_news_setup_version') === '1') return true;
    if (!add_option('fort_news_setup_lock', time(), '', false)) {
        if ((int) get_option('fort_news_setup_lock') < time() - 120) delete_option('fort_news_setup_lock');
        return false;
    }
    try {
        $category_id = fort_news_category_id();
        if (!$category_id) {
            $created = wp_insert_term('Notícias', 'category', array('slug' => 'noticias'));
            if (is_wp_error($created)) throw new RuntimeException('Não foi possível preparar a categoria Notícias.');
            $category_id = (int) $created['term_id'];
        }
        $page = get_page_by_path('noticias', OBJECT, 'page');
        $existing_blog = (int) get_option('page_for_posts');
        if ($existing_blog && (!$page || $existing_blog !== (int) $page->ID)) throw new RuntimeException('Já existe outra página de posts configurada. Revise Configurações → Leitura antes de ativar Notícias.');
        if ($page && $page->post_status !== 'publish') throw new RuntimeException('A página Notícias já existe como rascunho ou na lixeira. Revise sua publicação no menu Páginas.');
        if (!$page) {
            $page_id = wp_insert_post(wp_slash(array('post_type' => 'page', 'post_name' => 'noticias', 'post_title' => 'Notícias', 'post_status' => 'publish', 'post_author' => fort_news_author_id(), 'comment_status' => 'closed', 'ping_status' => 'closed')), true);
            if (is_wp_error($page_id)) throw new RuntimeException('Não foi possível criar a página Notícias.');
        } else $page_id = (int) $page->ID;
        update_option('page_for_posts', $page_id);
        $default = get_term((int) get_option('default_category'), 'category');
        if ($default && !is_wp_error($default) && in_array($default->slug, array('uncategorized', 'sem-categoria'), true)) update_option('default_category', $category_id);
        flush_rewrite_rules(false);
        update_option('fort_news_setup_version', '1', false);
        delete_option('fort_news_setup_error');
        return true;
    } catch (Throwable $error) {
        update_option('fort_news_setup_error', $error->getMessage(), false);
        return false;
    } finally { delete_option('fort_news_setup_lock'); }
}

/** One-time, source-attributed historical import; future edits stay in Posts. */
function fort_news_import_legacy() {
    if (get_option('fort_news_legacy_seed_version') === '1') return true;
    $path = __DIR__ . '/data/news-safira.json';
    if (!is_file($path) || !fort_news_category_id()) return false;
    $source = json_decode((string) file_get_contents($path), true);
    if (!is_array($source) || empty($source['title']) || empty($source['content']) || empty($source['sourceUrl']) || empty($source['date'])) return false;
    if (!empty($source['image']) && !is_file(get_template_directory() . '/assets/images/' . sanitize_file_name($source['image']))) return false;
    if (!add_option('fort_news_legacy_seed_lock', time(), '', false)) {
        if ((int) get_option('fort_news_legacy_seed_lock') < time() - 120) delete_option('fort_news_legacy_seed_lock');
        return false;
    }
    try {
        $existing = get_posts(array('post_type' => 'post', 'post_status' => array_keys(get_post_stati()), 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_fort_news_source', 'meta_value' => $source['sourceUrl'], 'suppress_filters' => true));
        if ($existing) { update_option('fort_news_legacy_seed_version', '1', false); return true; }
        $post_id = wp_insert_post(wp_slash(array('post_type' => 'post', 'post_name' => 'lancamento-nova-linha-safira', 'post_title' => $source['title'], 'post_content' => wp_kses_post($source['content']), 'post_excerpt' => sanitize_textarea_field($source['excerpt'] ?? ''), 'post_date' => $source['date'], 'post_date_gmt' => get_gmt_from_date($source['date']), 'post_status' => 'publish', 'post_author' => fort_news_author_id(), 'post_category' => array(fort_news_category_id()), 'comment_status' => 'closed', 'ping_status' => 'closed', 'meta_input' => array('_fort_news_source' => esc_url_raw($source['sourceUrl'])))), true);
        if (is_wp_error($post_id)) throw new RuntimeException('Não foi possível trazer a notícia histórica da Linha Safira.');
        $image_name = isset($source['image']) && is_string($source['image']) ? sanitize_file_name($source['image']) : '';
        $image = $image_name ? get_template_directory() . '/assets/images/' . $image_name : '';
        if ($image && is_file($image) && wp_get_image_mime($image)) {
            $uploads = wp_upload_dir();
            if (empty($uploads['error']) && wp_mkdir_p($uploads['path'])) {
                $filename = wp_unique_filename($uploads['path'], $image_name);
                $destination = trailingslashit($uploads['path']) . $filename;
                if (copy($image, $destination)) {
                    $attachment = wp_insert_attachment(array('post_title' => $source['title'], 'post_mime_type' => wp_get_image_mime($destination), 'post_status' => 'inherit', 'post_author' => fort_news_author_id()), $destination, $post_id, true);
                    if (!is_wp_error($attachment)) {
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $destination));
                        update_post_meta($attachment, '_wp_attachment_image_alt', sanitize_text_field($source['imageAlt'] ?? $source['title']));
                        set_post_thumbnail($post_id, $attachment);
                    }
                }
            }
        }
        update_option('fort_news_legacy_seed_version', '1', false);
        return true;
    } catch (Throwable $error) {
        update_option('fort_news_setup_error', $error->getMessage(), false);
        return false;
    } finally { delete_option('fort_news_legacy_seed_lock'); }
}

function fort_news_bootstrap() {
    if (fort_news_setup()) fort_news_import_legacy();
}
add_action('init', 'fort_news_bootstrap', 20);

function fort_news_main_query($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_home()) return;
    $category = fort_news_category_id();
    $query->set('post_type', 'post');
    $query->set('post_status', 'publish');
    $query->set('posts_per_page', 9);
    $query->set('orderby', array('date' => 'DESC', 'ID' => 'DESC'));
    $query->set('ignore_sticky_posts', true);
    if ($category) $query->set('cat', $category);
    else $query->set('post__in', array(0));
}
add_action('pre_get_posts', 'fort_news_main_query');

add_action('admin_notices', function () {
    if (!current_user_can('edit_posts')) return;
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->base, array('post', 'edit'), true) || $screen->post_type !== 'post') return;
    echo '<div class="notice notice-info"><p>As matérias publicadas na categoria <strong>Notícias</strong> aparecem na página Notícias do site, da mais recente para a mais antiga. Use <strong>Imagem destacada</strong> para a capa e <strong>Resumo</strong> para o texto do card.</p></div>';
    $error = get_option('fort_news_setup_error');
    if ($error && current_user_can('manage_options')) echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
});
