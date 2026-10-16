<?php get_header(); while (have_posts()): the_post();
$product = get_the_ID();
$line = fort_term_name($product, 'fort_linha');
$finishes = function_exists('fort_product_finishes') ? fort_product_finishes($product) : array();
$finish_key = fort_input('acabamento');
if (!isset($finishes[$finish_key])) {
    $original_finish = sanitize_title(fort_term_name($product, 'fort_acabamento'));
    $finish_key = isset($finishes[$original_finish]) ? $original_finish : array_key_first($finishes);
}
$finish = $finishes[$finish_key] ?? null;
$versions = function_exists('fort_product_versions') ? fort_product_versions($product) : array();
$version_key = fort_input('versao');
if (!isset($versions[$version_key])) $version_key = $versions ? fort_product_default_version($product) : null;
$version = $versions[$version_key] ?? null;
$display_title = fort_product_model_title($product);
$active_variant = $version ?: $finish;
$reference = $active_variant ? $active_variant['reference'] : get_post_meta($product, '_fort_codigo', true);
$reference_label = $active_variant && !$reference ? 'a confirmar' : $reference;
$selected_args = array_filter(array('acabamento' => $finish_key, 'versao' => $version_key));
$quote_url = add_query_arg(array_merge(array('produto' => $product), $selected_args), home_url('/fale-com-a-gente/'));
$share_url = $selected_args ? add_query_arg($selected_args, get_permalink()) : get_permalink();
$whatsapp_message = 'Olá! Gostaria de informações sobre ' . $display_title . '.' . ($finish ? ' Acabamento: ' . $finish['label'] . '.' : '') . ($version ? ' Versão: ' . $version['label'] . '.' : '') . ($active_variant ? ' Referência: ' . $reference_label . '.' : '') . ($version && $version['demo'] ? ' Imagem ilustrativa para teste.' : ($finish && $finish['demo'] ? ' Simulação para teste.' : '')) . ' ' . $share_url;
?>
<main id="conteudo" class="product-single"><div class="container">
    <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><a href="<?php echo esc_url(fort_catalog_url()); ?>">Produtos</a><?php echo fort_icon('chevron'); ?><span><?php echo esc_html($line); ?></span></div>
    <div class="product-layout">
        <div class="single-product-image" id="fort-product-image">
            <?php if ($active_variant): ?><img id="fort-product-photo" src="<?php echo esc_url($active_variant['image']); ?>" alt="<?php echo esc_attr($display_title . ($version ? ' — ' : ' — acabamento ') . $active_variant['label']); ?>" width="1000" height="1200" fetchpriority="high">
            <?php elseif (has_post_thumbnail()): the_post_thumbnail('full', array('fetchpriority' => 'high'));
            else: ?><span>Foto em breve</span><?php endif; ?>
        </div>
        <div class="product-copy">
            <span class="eyebrow"><?php echo $finishes ? 'ACABAMENTOS FORT METAIS' : 'LINHA ' . esc_html($line); ?></span>
            <h1><?php echo esc_html($display_title); ?></h1>
            <p class="reference" id="fort-product-reference" <?php if (!$reference_label) echo 'hidden'; ?>>Referência <span><?php echo esc_html($reference_label); ?></span></p>
            <?php if ($finishes): ?>
                <form class="fort-finish-picker" id="fort-finish-picker" action="<?php the_permalink(); ?>" method="get" data-model-title="<?php echo esc_attr($display_title); ?>">
                    <fieldset><legend>Acabamento: <strong id="fort-selected-finish" aria-live="polite"><?php echo esc_html($finish['label']); ?></strong></legend>
                    <div class="fort-finish-options">
                        <?php foreach ($finishes as $key => $option):
                            $option_url = add_query_arg('acabamento', $key, get_permalink());
                            $option_quote = add_query_arg(array('produto' => $product, 'acabamento' => $key), home_url('/fale-com-a-gente/'));
                            $option_message = 'Olá! Gostaria de informações sobre ' . $display_title . '. Acabamento: ' . $option['label'] . '. Referência: ' . ($option['reference'] ?: 'a confirmar') . '.' . ($option['demo'] ? ' Simulação para teste.' : '') . ' ' . $option_url;
                        ?>
                            <label class="fort-finish-option" for="finish-<?php echo esc_attr($key); ?>">
                                <input type="radio" name="acabamento" id="finish-<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($key); ?>" <?php checked($finish_key, $key); ?>
                                    data-image="<?php echo esc_url($option['image']); ?>" data-label="<?php echo esc_attr($option['label']); ?>" data-reference="<?php echo esc_attr($option['reference']); ?>" data-demo="<?php echo $option['demo'] ? '1' : '0'; ?>"
                                    data-quote="<?php echo esc_url($option_quote); ?>" data-whatsapp="<?php echo esc_url(fort_whatsapp($option_message)); ?>">
                                <span class="fort-finish-swatch fort-finish-swatch-<?php echo esc_attr($key); ?>" style="--finish-color:<?php echo esc_attr($option['color']); ?>" aria-hidden="true"></span>
                                <span class="fort-finish-label"><?php echo esc_html($option['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div></fieldset>
                    <noscript><button class="button button-small" type="submit">Ver acabamento</button></noscript>
                </form>
                <p class="fort-finish-demo" id="fort-finish-demo" <?php if (!$finish['demo']) echo 'hidden'; ?>>Simulação de acabamento para teste. Confirme disponibilidade com a Fort.</p>
                <p id="fort-finish-error" class="fort-finish-error" role="status" hidden>Não foi possível carregar a foto deste acabamento. Selecione outra cor ou atualize a página.</p>
            <?php endif; ?>
            <?php if ($versions): ?>
                <form class="fort-version-picker" id="fort-version-picker" action="<?php the_permalink(); ?>" method="get" data-model-title="<?php echo esc_attr($display_title); ?>">
                    <fieldset><legend>Versão: <strong id="fort-selected-version" aria-live="polite"><?php echo esc_html($version['label']); ?></strong></legend>
                    <div class="fort-version-options">
                        <?php foreach ($versions as $key => $option):
                            $option_args = array_filter(array('acabamento' => $finish_key, 'versao' => $key));
                            $option_url = add_query_arg($option_args, get_permalink());
                            $option_quote = add_query_arg(array_merge(array('produto' => $product), $option_args), home_url('/fale-com-a-gente/'));
                            $option_message = 'Olá! Gostaria de informações sobre ' . $display_title . '. Versão: ' . $option['label'] . '. Referência: ' . ($option['reference'] ?: 'a confirmar') . '.' . ($option['demo'] ? ' Imagem ilustrativa para teste.' : '') . ' ' . $option_url;
                        ?>
                            <label class="fort-version-option" for="version-<?php echo esc_attr($key); ?>">
                                <input type="radio" name="versao" id="version-<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($key); ?>" <?php checked($version_key, $key); ?>
                                    data-image="<?php echo esc_url($option['image']); ?>" data-label="<?php echo esc_attr($option['label']); ?>" data-reference="<?php echo esc_attr($option['reference']); ?>" data-demo="<?php echo $option['demo'] ? '1' : '0'; ?>"
                                    data-quote="<?php echo esc_url($option_quote); ?>" data-whatsapp="<?php echo esc_url(fort_whatsapp($option_message)); ?>">
                                <span><?php echo esc_html($option['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div></fieldset>
                    <noscript><button class="button button-small" type="submit">Ver versão</button></noscript>
                </form>
                <p class="fort-finish-demo" id="fort-version-demo" <?php if (!$version['demo']) echo 'hidden'; ?>>Imagem ilustrativa desta versão. Confirme os detalhes com a Fort.</p>
                <p id="fort-version-error" class="fort-finish-error" role="status" hidden>Não foi possível carregar a foto desta versão. Selecione outra opção ou atualize a página.</p>
            <?php endif; ?>
            <div class="product-description"><?php the_content(); ?></div>
            <dl class="specs">
                <?php if ($version): ?><div><dt>Versão</dt><dd id="fort-version-spec"><?php echo esc_html($version['label']); ?></dd></div><?php endif; ?>
                <?php foreach (array('fort_acabamento' => 'Acabamento', 'fort_ambiente' => 'Ambiente') as $taxonomy => $label):
                    $value = $taxonomy === 'fort_acabamento' && $finish ? $finish['label'] : fort_term_name($product, $taxonomy);
                    if ($value): ?><div><dt><?php echo esc_html($label); ?></dt><dd <?php if ($taxonomy === 'fort_acabamento') echo 'id="fort-finish-spec"'; ?>><?php echo esc_html($value); ?></dd></div><?php endif;
                endforeach; ?>
                <?php foreach (array('_fort_instalacao' => 'Instalação', '_fort_acionamento' => 'Acionamento', '_fort_conexao' => 'Conexão', '_fort_medidas' => 'Dimensões') as $key => $label): $value = get_post_meta($product, $key, true); if ($value): ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endif; endforeach; ?>
            </dl>
            <a class="button" id="fort-product-quote" href="<?php echo esc_url($quote_url); ?>">Solicitar orçamento <?php echo fort_icon('arrow'); ?></a>
            <a class="text-link product-whatsapp" id="fort-product-whatsapp" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url(fort_whatsapp($whatsapp_message)); ?>"><?php echo fort_icon('chat'); ?> Conversar pelo WhatsApp</a>
            <?php if ($file = get_post_meta($product, '_fort_ficha', true)): ?><a class="text-link" href="<?php echo esc_url($file); ?>" target="_blank" rel="noopener noreferrer"><?php echo fort_icon('download'); ?> Ficha técnica</a><?php endif; ?>
        </div>
    </div>
    <section class="section"><div class="section-heading"><div><span class="eyebrow">OUTRAS POSSIBILIDADES</span><h2>Complete o seu projeto.</h2></div><a class="text-link" href="<?php echo esc_url(fort_catalog_url()); ?>">Ver catálogo <?php echo fort_icon('arrow'); ?></a></div><div class="product-grid"><?php $related = fort_products_query(4, array('post__not_in' => array($product))); while ($related->have_posts()): $related->the_post(); fort_product_card(); endwhile; wp_reset_postdata(); ?></div></section>
</div></main>
<?php endwhile; get_footer(); ?>
