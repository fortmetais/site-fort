<?php get_header(); while (have_posts()): the_post(); ?>
<main id="conteudo"><section class="page-intro"><div class="container"><div class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Início</a><?php echo fort_icon('chevron'); ?><span><?php the_title(); ?></span></div><span class="eyebrow">FORT METAIS</span><h1><?php the_title(); ?></h1><div class="intro-copy"><?php if (!is_page('institucional') && !is_page('privacidade')) the_content(); ?></div></div></section>
<section class="section container">
<?php if (is_page('fale-com-a-gente')): get_template_part('parts/contact'); ?>
<?php elseif (is_page('representantes')): get_template_part('parts/representatives'); get_template_part('parts/representative-partnership'); ?>
<?php elseif (is_page('institucional')): ?><div class="about-section"><div class="about-image"><img src="<?php echo esc_url(add_query_arg('v', filemtime(get_template_directory() . '/assets/images/fabrica.png'), fort_asset('fabrica.png'))); ?>" alt="Vista aérea do complexo industrial Fort Metais ao pôr do sol" width="1536" height="1024"></div><div class="prose"><span class="eyebrow">NOSSA ESSÊNCIA</span><h2>Qualidade que atravessa gerações.</h2><?php the_content(); ?><a class="button" href="<?php echo esc_url(fort_catalog_url()); ?>">Conheça nossos produtos <?php echo fort_icon('arrow'); ?></a></div></div>
<?php else: ?><div class="prose prose-narrow"><?php the_content(); ?></div><?php endif; ?>
</section><?php if (is_page('institucional')) get_template_part('parts/commitments'); ?></main><?php endwhile; get_footer(); ?>
