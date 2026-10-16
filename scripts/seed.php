<?php
// Executado apenas pelo ambiente local. A importação é idempotente e preserva edições.
function fort_seed_local() {
    if (get_option('fort_local_seed_version')) return;
    update_option('blogname', 'Fort Metais');
    update_option('blogdescription', 'Design e qualidade para todos os ambientes.');
    update_option('timezone_string', 'America/Sao_Paulo');
    update_option('blog_public', 0);
    update_option('permalink_structure', '/%postname%/');
    update_option('default_comment_status', 'closed');
    $pages = array(
        'inicio' => array('Início', ''),
        'institucional' => array('Nossa história', '<p>Desde 1999, a Fort Metais desenvolve soluções em metais sanitários com dedicação à qualidade, à praticidade e ao design.</p><p>Investimos em tecnologia para criar produtos que acompanham a vida das pessoas. Nosso compromisso envolve as relações comerciais, sociais e ambientais, com atenção à durabilidade e ao cuidado em cada detalhe.</p>'),
        'representantes' => array('Encontre um representante', '<p>Converse com nossa equipe comercial para encontrar o atendimento mais próximo da sua região.</p>'),
        'fale-com-a-gente' => array('Vamos conversar', '<p>Conte o que você precisa. Nossa equipe está à disposição para ajudar a escolher os metais para o seu projeto.</p>'),
        'privacidade' => array('Privacidade', '<p>As informações do formulário são registradas para atender à sua solicitação comercial. Envie apenas os dados necessários para o contato.</p><p>Nesta versão de desenvolvimento, as solicitações ficam no painel local do WordPress. Não há envio automático de e-mails nem integração com plataformas de publicidade.</p><p>Para dúvidas sobre o uso das suas informações, entre em contato pelo e-mail contato@fortmetais.com.br.</p>'),
    );
    foreach ($pages as $slug => $data) {
        $existing = get_page_by_path($slug);
        $id = $existing ? $existing->ID : wp_insert_post(array('post_type' => 'page', 'post_name' => $slug, 'post_title' => $data[0], 'post_content' => $data[1], 'post_status' => 'publish'));
        if ($slug === 'inicio') { update_option('show_on_front', 'page'); update_option('page_on_front', $id); }
    }
    $lines = array('opala-black' => 'Opala Black', 'opala-rose' => 'Opala Rose', 'opala-aco-corten' => 'Opala Aço Corten', 'ametista' => 'Ametista');
    foreach ($lines as $slug => $name) if (!term_exists($slug, 'fort_linha')) wp_insert_term($name, 'fort_linha', array('slug' => $slug));
    foreach (array('cozinha' => 'Cozinha', 'banheiro' => 'Banheiro', 'area-gourmet' => 'Área gourmet', 'jardim' => 'Jardim') as $slug => $name) if (!term_exists($slug, 'fort_ambiente')) wp_insert_term($name, 'fort_ambiente', array('slug' => $slug));
    foreach (array('black' => 'Black', 'rose' => 'Rose', 'aco-corten' => 'Aço corten', 'cromado' => 'Cromado') as $slug => $name) if (!term_exists($slug, 'fort_acabamento')) wp_insert_term($name, 'fort_acabamento', array('slug' => $slug));
    $products = array(
        array('Torneira Opala Black para bancada — cuba', 'opala-black', 'black', 'banheiro', 'produto-black-alta.jpg', '508', 'Bancada', '1/4 de volta'),
        array('Torneira Opala Rose para bancada — cuba', 'opala-rose', 'rose', 'banheiro', 'produto-rose-alta.jpg', '458', 'Bancada', '1/4 de volta'),
        array('Torneira Opala Aço Corten para bancada — cuba', 'opala-aco-corten', 'aco-corten', 'banheiro', 'produto-corten-alta.jpg', '484', 'Bancada', '1/4 de volta'),
        array('Torneira Ametista de parede com arejador', 'ametista', 'cromado', 'cozinha', 'produto-ametista.png', '56', 'Parede', ''),
        array('Torneira Opala Black bica móvel Slim', 'opala-black', 'black', 'cozinha', 'produto-black-movel.jpg', '502', 'Bancada', '1/4 de volta'),
        array('Torneira Opala Aço Corten para bancada', 'opala-aco-corten', 'aco-corten', 'banheiro', 'produto-corten-baixa.jpg', '482', 'Bancada', '1/4 de volta'),
        array('Torneira Opala Rose para bancada', 'opala-rose', 'rose', 'banheiro', 'produto-rose-baixa.jpg', '460', 'Bancada', '1/4 de volta'),
    );
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    foreach ($products as $order => $product) {
        $id = wp_insert_post(array('post_type' => 'fort_produto', 'post_status' => 'publish', 'post_title' => $product[0], 'post_name' => sanitize_title($product[0]), 'post_content' => '<p>Uma peça Fort Metais para compor seu ambiente com qualidade, praticidade e personalidade.</p><p>Fale com nossa equipe para consultar a referência comercial, as medidas e a disponibilidade deste produto para o seu projeto.</p>', 'post_excerpt' => 'Design que valoriza o ambiente e praticidade em cada detalhe.', 'menu_order' => $order));
        wp_set_object_terms($id, $product[1], 'fort_linha');
        wp_set_object_terms($id, $product[2], 'fort_acabamento');
        wp_set_object_terms($id, $product[3], 'fort_ambiente');
        update_post_meta($id, '_fort_legacy_id', $product[5]);
        update_post_meta($id, '_fort_instalacao', $product[6]);
        update_post_meta($id, '_fort_acionamento', $product[7]);
        update_post_meta($id, '_fort_conexao', '1/2”');
        update_post_meta($id, '_fort_destaque', $order < 4 ? '1' : '0');
        $image = get_template_directory() . '/assets/images/' . $product[4];
        $uploads = wp_upload_dir();
        $dest = $uploads['path'] . '/' . $product[4];
        wp_mkdir_p($uploads['path']);
        if (is_file($image) && copy($image, $dest)) {
            $mime = wp_check_filetype($dest);
            $attachment = wp_insert_attachment(array('post_title' => $product[0], 'post_mime_type' => $mime['type'], 'post_status' => 'inherit'), $dest, $id);
            $size = getimagesize($dest);
            if ($size) wp_update_attachment_metadata($attachment, array('width' => $size[0], 'height' => $size[1], 'file' => _wp_relative_upload_path($dest), 'sizes' => array()));
            update_post_meta($attachment, '_wp_attachment_image_alt', $product[0]);
            set_post_thumbnail($id, $attachment);
        }
    }
    flush_rewrite_rules();
    update_option('fort_local_seed_version', '0.1.0');
}
