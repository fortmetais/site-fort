<?php
$featured = !empty($args['featured']);
$heading = !empty($args['heading']) && (int) $args['heading'] === 3 ? 'h3' : 'h2';
$category_label = 'Notícias';
foreach (get_the_category() as $news_category) {
    if (!in_array($news_category->slug, array('noticias', 'uncategorized', 'sem-categoria'), true)) { $category_label = $news_category->name; break; }
}
$excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), $featured ? 42 : 28);
?>
<article <?php post_class('fort-news-card' . ($featured ? ' fort-news-featured' : '')); ?>>
    <a class="fort-news-card-photo" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>">
        <?php if (has_post_thumbnail()): ?>
            <?php echo get_the_post_thumbnail(get_the_ID(), $featured ? 'large' : 'medium_large', array('loading' => $featured ? 'eager' : 'lazy', 'decoding' => 'async')); ?>
        <?php else: ?>
            <span class="fort-news-photo-fallback" aria-hidden="true"><?php echo fort_icon('leaf'); ?><span>FORT METAIS</span></span>
        <?php endif; ?>
        <?php if ($featured): ?><span class="fort-news-featured-badge">EM DESTAQUE</span><?php endif; ?>
    </a>
    <div class="fort-news-card-copy">
        <div class="fort-news-meta"><span><?php echo esc_html($category_label); ?></span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d/m/Y')); ?></time></div>
        <<?php echo $heading; ?>><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo $heading; ?>>
        <?php if ($excerpt !== ''): ?><p><?php echo esc_html($excerpt); ?></p><?php endif; ?>
        <a class="text-link fort-news-read" href="<?php the_permalink(); ?>">Ler notícia <?php echo fort_icon('arrow'); ?><span class="screen-reader-text">: <?php the_title(); ?></span></a>
    </div>
</article>
