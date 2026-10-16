<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body id="inicio" <?php body_class('reference-presentation'); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header reference-header" id="site-header"><div class="container header-inner">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Fort Metais — início"><img src="<?php echo esc_url(fort_asset('logo.png')); ?>" alt="Fort Metais Sanitários" width="250" height="200"></a>
    <nav class="main-nav" aria-label="Navegação principal" id="main-nav">
        <a href="<?php echo esc_url(home_url('/#inicio')); ?>">Início</a>
        <a href="<?php echo esc_url(home_url('/#institucional')); ?>">Institucional</a>
        <a href="<?php echo esc_url(fort_catalog_url()); ?>" <?php if (is_post_type_archive('fort_produto') || is_singular('fort_produto')) echo 'aria-current="page"'; ?>>Produtos</a>
        <a href="<?php echo esc_url(home_url('/ambientes/')); ?>" <?php if (is_post_type_archive('fort_cena') || is_singular('fort_cena')) echo 'aria-current="page"'; ?>>Ambientes</a>
        <a href="<?php echo esc_url(home_url('/representantes/')); ?>">Representantes</a>
    </nav>
    <div class="header-actions">
        <?php get_template_part('parts/language-switcher'); ?>
        <form class="reference-inline-search" action="<?php echo esc_url(fort_catalog_url()); ?>" method="get"><button type="submit" aria-label="Buscar produtos"><?php echo fort_icon('search'); ?></button><label class="screen-reader-text" for="desktop-search">Pesquisar produtos</label><input type="search" id="desktop-search" name="q" placeholder="Ex: banheiro, torneira..."></form>
        <button class="icon-button search-toggle" aria-label="Abrir busca de produtos" aria-expanded="false" aria-controls="header-search"><?php echo fort_icon('search'); ?></button>
        <button class="icon-button menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="main-nav"><?php echo fort_icon('menu'); ?></button>
    </div>
</div><form class="header-search container" id="header-search" action="<?php echo esc_url(fort_catalog_url()); ?>" method="get" hidden><label for="busca-header">O que você procura?</label><div class="search-input"><input type="search" id="busca-header" name="q" placeholder="Busque pelo nome ou referência do produto"><button class="button button-small" type="submit">Buscar <?php echo fort_icon('search'); ?></button></div></form></header>
