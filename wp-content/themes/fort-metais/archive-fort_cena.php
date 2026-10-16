<?php
get_header();
$selected = array('ambiente' => fort_input('ambiente'), 'linha' => fort_input('linha'));
$taxonomies = array('ambiente' => 'fort_ambiente', 'linha' => 'fort_linha');
$tax_query = array('relation' => 'AND');
foreach ($taxonomies as $param => $taxonomy) {
    if ($selected[$param]) $tax_query[] = array('taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $selected[$param]);
}
$current_page = max(1, absint(get_query_var('paged')));
$scenes = new WP_Query(array('post_type' => 'fort_cena', 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $current_page, 'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'), 'tax_query' => $tax_query));
?>
<main id="conteudo" class="fort-environments">
    <section class="page-intro"><div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><span>Ambientes</span></div>
        <span class="eyebrow">INSPIRAÇÃO FORT METAIS</span>
        <h1>Ambientes que inspiram.</h1>
        <p>Encontre combinações de materiais, cores e metais para transformar o seu espaço.</p>
    </div></section>
    <section class="section container fort-environment-gallery">
        <form class="fort-environment-filters" action="<?php echo esc_url(home_url('/ambientes/')); ?>" method="get" aria-label="Filtrar ambientes">
            <?php foreach (array('ambiente' => 'Tipo de ambiente', 'linha' => 'Linha de produtos') as $param => $label): ?>
                <div><label for="scene-<?php echo esc_attr($param); ?>"><?php echo esc_html($label); ?></label>
                <select name="<?php echo esc_attr($param); ?>" id="scene-<?php echo esc_attr($param); ?>">
                    <option value=""><?php echo $param === 'ambiente' ? 'Todos os ambientes' : 'Todas as linhas'; ?></option>
                    <?php $terms = get_terms(array('taxonomy' => $taxonomies[$param], 'hide_empty' => false));
                    if (!is_wp_error($terms)) foreach ($terms as $term): ?>
                        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected[$param], $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                    <?php endforeach; ?>
                </select></div>
            <?php endforeach; ?>
            <button type="submit" class="button">Filtrar <?php echo fort_icon('search'); ?></button>
            <?php if (array_filter($selected)): ?><a class="text-link" href="<?php echo esc_url(home_url('/ambientes/')); ?>">Limpar filtros <?php echo fort_icon('close'); ?></a><?php endif; ?>
        </form>
        <div class="fort-environment-summary"><p><strong><?php echo esc_html($scenes->found_posts); ?></strong> <?php echo $scenes->found_posts === 1 ? 'ambiente para explorar' : 'ambientes para explorar'; ?></p></div>
        <?php if ($scenes->have_posts()): ?>
            <div class="fort-environment-grid">
                <?php while ($scenes->have_posts()): $scenes->the_post(); $image = fort_scene_image_url(get_the_ID()); ?>
                    <article class="fort-environment-card">
                        <a class="fort-environment-photo" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr('Explorar ' . get_the_title()); ?>">
                            <?php if ($image): ?><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="1536" height="1024" loading="lazy"><?php else: ?><span>Ambiente Fort Metais</span><?php endif; ?>
                            <span class="fort-environment-explore" aria-hidden="true"><?php echo fort_icon('arrow'); ?></span>
                        </a>
                        <div class="fort-environment-caption"><span class="eyebrow"><?php echo esc_html(fort_term_name(get_the_ID(), 'fort_ambiente')); ?><?php if ($line = fort_term_name(get_the_ID(), 'fort_linha')) echo ' · ' . esc_html($line); ?></span>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php if (has_excerpt()): ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                            <a class="text-link" href="<?php the_permalink(); ?>">Explorar ambiente <?php echo fort_icon('arrow'); ?></a>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php if ($scenes->max_num_pages > 1): ?><nav class="pagination" aria-label="Páginas de ambientes"><?php echo wp_kses_post(paginate_links(array('total' => $scenes->max_num_pages, 'current' => $current_page, 'prev_text' => 'Anterior', 'next_text' => 'Próxima', 'add_args' => array_filter($selected))) ?: ''); ?></nav><?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><?php echo fort_icon('search'); ?><h2>Vamos explorar outras possibilidades?</h2><p>Ainda não há ambientes publicados para esta seleção.</p><a class="button" href="<?php echo esc_url(home_url('/ambientes/')); ?>">Ver todos os ambientes <?php echo fort_icon('arrow'); ?></a></div>
        <?php endif; ?>
    </section>
</main>
<?php get_footer(); ?>
