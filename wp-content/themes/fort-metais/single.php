<?php get_header(); while (have_posts()): the_post();
$news_post_id = get_the_ID();
$news_categories = get_the_category();
$news_category_label = 'Notícias';
foreach ($news_categories as $news_category) {
    if (!in_array($news_category->slug, array('noticias', 'uncategorized', 'sem-categoria'), true)) { $news_category_label = $news_category->name; break; }
}
?>
<main id="conteudo" class="fort-news-single">
    <article <?php post_class('fort-news-article'); ?>>
        <header class="page-intro fort-news-article-intro"><div class="container">
            <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><a href="<?php echo esc_url(home_url('/noticias/')); ?>">Notícias</a><?php echo fort_icon('chevron'); ?><span><?php the_title(); ?></span></div>
            <div class="fort-news-article-heading">
                <span class="eyebrow"><?php echo esc_html($news_category_label); ?></span>
                <h1><?php the_title(); ?></h1>
                <div class="fort-news-published"><span>Publicado em</span> <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d/m/Y')); ?></time></div>
                <?php if (has_excerpt()): ?><p class="fort-news-standfirst"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
            </div>
        </div></header>
        <div class="container fort-news-article-main">
            <?php if (has_post_thumbnail()): ?><figure class="fort-news-cover"><?php echo get_the_post_thumbnail($news_post_id, 'full', array('loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async')); ?><?php if (get_the_post_thumbnail_caption()): ?><figcaption><?php echo esc_html(get_the_post_thumbnail_caption()); ?></figcaption><?php endif; ?></figure><?php endif; ?>
            <div class="fort-news-body prose">
                <?php the_content(); ?>
                <?php wp_link_pages(array('before' => '<nav class="fort-news-content-pages" aria-label="Páginas desta notícia">', 'after' => '</nav>', 'link_before' => '<span>', 'link_after' => '</span>')); ?>
            </div>
            <div class="fort-news-back"><a class="text-link" href="<?php echo esc_url(home_url('/noticias/')); ?>"><?php echo fort_icon('grid'); ?> Voltar para Notícias</a></div>
        </div>
    </article>
    <?php
    $related_news = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'category_name' => 'noticias', 'post__not_in' => array($news_post_id), 'posts_per_page' => 2, 'orderby' => array('date' => 'DESC', 'ID' => 'DESC'), 'ignore_sticky_posts' => true, 'no_found_rows' => true));
    if ($related_news->have_posts()): ?>
        <section class="container fort-news-related" aria-labelledby="fort-news-related-title">
            <div class="fort-news-section-heading"><h2 id="fort-news-related-title">Continue a leitura</h2><a class="text-link" href="<?php echo esc_url(home_url('/noticias/')); ?>">Todas as notícias <?php echo fort_icon('arrow'); ?></a></div>
            <div class="fort-news-grid"><?php while ($related_news->have_posts()): $related_news->the_post(); get_template_part('parts/news-card', null, array('heading' => 3)); endwhile; ?></div>
        </section>
    <?php endif; wp_reset_postdata(); ?>
</main>
<?php endwhile; get_footer(); ?>
