<?php get_header(); while (have_posts()): the_post();
$scene_id = get_the_ID();
$items = fort_scene_items($scene_id);
$image = fort_scene_image_url($scene_id);
$illustrative = get_post_meta($scene_id, '_fort_cena_illustrative', true) !== '0';
?>
<main id="conteudo" class="fort-environment-single">
    <section class="page-intro"><div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><a href="<?php echo esc_url(home_url('/ambientes/')); ?>">Ambientes</a><?php echo fort_icon('chevron'); ?><span><?php the_title(); ?></span></div>
        <span class="eyebrow"><?php echo esc_html(fort_term_name($scene_id, 'fort_ambiente')); ?><?php if ($line = fort_term_name($scene_id, 'fort_linha')) echo ' · ' . esc_html($line); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()): ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
    </div></section>
    <section class="section container fort-scene-detail">
        <div class="fort-scene-layout<?php echo $items ? '' : ' fort-scene-without-items'; ?>">
            <div>
                <figure class="fort-scene-figure">
                    <div class="fort-scene-image">
                        <?php if ($image): ?><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="1536" height="1024" fetchpriority="high"><?php endif; ?>
                        <?php if ($image) foreach ($items as $index => $item): ?>
                            <a class="fort-scene-pin" href="#scene-product-<?php echo esc_attr($item['product_id']); ?>" style="left:<?php echo esc_attr($item['x']); ?>%;top:<?php echo esc_attr($item['y']); ?>%" aria-label="<?php echo esc_attr('Identificar produto ' . ($index + 1) . ': ' . get_the_title($item['product_id'])); ?>"><?php echo esc_html($index + 1); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($illustrative): ?><figcaption>Imagem ilustrativa de ambientação.</figcaption><?php endif; ?>
                </figure>
                <?php if (get_the_content()): ?><div class="prose fort-scene-description"><?php the_content(); ?></div><?php endif; ?>
            </div>
            <?php if ($items): ?><aside class="fort-scene-items" aria-labelledby="scene-items-title">
                <span class="eyebrow">OS DETALHES FAZEM A DIFERENÇA</span><h2 id="scene-items-title">Produtos neste ambiente</h2>
                <p class="fort-scene-items-help">Selecione um número na foto para identificar a peça.</p>
                <ol class="fort-scene-product-list">
                    <?php foreach ($items as $index => $item): $product_id = $item['product_id']; ?>
                        <li class="fort-scene-product" id="scene-product-<?php echo esc_attr($product_id); ?>">
                            <div class="fort-scene-product-image"><span class="fort-scene-item-number"><?php echo esc_html($index + 1); ?></span><?php echo get_the_post_thumbnail($product_id, 'medium', array('loading' => 'lazy', 'alt' => get_the_title($product_id))); ?></div>
                            <div><span class="eyebrow"><?php echo esc_html(fort_term_name($product_id, 'fort_linha')); ?></span><h3><?php echo esc_html(get_the_title($product_id)); ?></h3>
                            <?php if ($code = get_post_meta($product_id, '_fort_codigo', true)): ?><p class="fort-scene-product-code">Referência <?php echo esc_html($code); ?></p><?php endif; ?>
                            <a class="text-link" href="<?php echo esc_url(get_permalink($product_id)); ?>">Conheça o produto <?php echo fort_icon('arrow'); ?></a></div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </aside><?php endif; ?>
        </div>
        <?php if (!$items && current_user_can('edit_post', $scene_id)): ?><div class="fort-scene-admin-note"><p>Para identificar as peças nesta foto, vincule os produtos confirmados em <a href="<?php echo esc_url(get_edit_post_link($scene_id)); ?>">Editar ambiente → Produtos presentes na imagem</a>. Este aviso aparece somente para quem pode editar o ambiente.</p></div><?php endif; ?>
        <div class="fort-scene-contact"><div><h2>Esse ambiente combina com o seu projeto?</h2><p>Converse com a Fort e descubra as possibilidades para o seu espaço.</p></div><a class="button" href="<?php echo esc_url(fort_whatsapp('Olá! Gostaria de informações sobre os metais deste ambiente: ' . get_the_title() . '. ' . get_permalink())); ?>" target="_blank" rel="noopener noreferrer">Falar com a Fort <?php echo fort_icon('chat'); ?></a></div>
        <a class="text-link" href="<?php echo esc_url(home_url('/ambientes/')); ?>"><?php echo fort_icon('grid'); ?> Explorar outros ambientes</a>
    </section>
</main>
<?php endwhile; get_footer(); ?>
