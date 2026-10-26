<?php
get_header();
global $wp_query;
$news_page = max(1, absint(get_query_var('paged')));
?>
<main id="conteudo" class="fort-news-listing">
    <section class="page-intro fort-news-intro"><div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><span>Notícias</span></div>
        <span class="eyebrow">ACONTECE NA FORT</span>
        <h1>Notícias</h1>
        <p>Lançamentos, novidades e histórias que fazem parte da Fort Metais.</p>
    </div></section>
    <div class="section container fort-news-content">
        <?php if (have_posts()): ?>
            <?php if ($news_page === 1): the_post(); get_template_part('parts/news-card', null, array('featured' => true)); endif; ?>
            <?php if (have_posts()): ?>
                <section class="fort-news-recent" aria-labelledby="fort-news-recent-title">
                    <div class="fort-news-section-heading"><h2 id="fort-news-recent-title"><?php echo $news_page === 1 ? 'Últimas notícias' : 'Mais notícias'; ?></h2><p>Da mais recente para a mais antiga.</p></div>
                    <div class="fort-news-grid">
                        <?php while (have_posts()): the_post(); get_template_part('parts/news-card', null, array('heading' => 3)); endwhile; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php if ($wp_query->max_num_pages > 1): ?>
                <nav class="fort-news-pagination" aria-label="Páginas de notícias"><?php echo wp_kses_post(paginate_links(array('current' => $news_page, 'total' => $wp_query->max_num_pages, 'mid_size' => 1, 'prev_text' => 'Anterior', 'next_text' => 'Próxima', 'type' => 'list')) ?: ''); ?></nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="fort-news-empty">
                <span class="fort-news-empty-icon" aria-hidden="true"><?php echo fort_icon('grid'); ?></span>
                <h2>Novidades a caminho.</h2>
                <p>Em breve, acompanhe aqui os lançamentos e as notícias da Fort Metais.</p>
                <a class="button" href="<?php echo esc_url(fort_catalog_url()); ?>">Conheça nossos produtos <?php echo fort_icon('arrow'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
