<?php
defined('ABSPATH') || exit;
$benefits = array('portfolio' => 'grid', 'catalog' => 'download', 'prices' => 'check', 'samples' => 'award');
$requirements = array('cnpj', 'experience', 'car');
?>
<section class="fort-partnership" id="seja-representante" aria-labelledby="partnership-title">
    <div class="fort-partnership-intro">
        <span class="eyebrow">CRESÇA COM A FORT</span>
        <h2 id="partnership-title"><?php echo esc_html(fort_representatives_content('intro_title')); ?></h2>
        <p><?php echo nl2br(esc_html(fort_representatives_content('intro_text'))); ?></p>
    </div>
    <section class="fort-partnership-block" aria-labelledby="benefits-title">
        <h3 class="fort-partnership-section-title" id="benefits-title"><?php echo esc_html(fort_representatives_content('benefits_title')); ?></h3>
        <div class="fort-benefits-grid">
            <?php foreach ($benefits as $key => $icon): ?>
                <article class="fort-benefit-card">
                    <div class="fort-benefit-icon"><?php echo fort_icon($icon); ?></div>
                    <h4><?php echo esc_html(fort_representatives_content('benefit_' . $key . '_title')); ?></h4>
                    <p><?php echo nl2br(esc_html(fort_representatives_content('benefit_' . $key . '_text'))); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="fort-partnership-block" aria-labelledby="requirements-title">
        <h3 class="fort-partnership-section-title" id="requirements-title"><?php echo esc_html(fort_representatives_content('requirements_title')); ?></h3>
        <ul class="fort-requirements-grid">
            <?php foreach ($requirements as $key): ?>
                <li>
                    <span class="fort-requirement-check"><?php echo fort_icon('check'); ?></span>
                    <div><h4><?php echo esc_html(fort_representatives_content('requirement_' . $key . '_title')); ?></h4>
                    <p><?php echo nl2br(esc_html(fort_representatives_content('requirement_' . $key . '_text'))); ?></p></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <div class="fort-partnership-contact">
        <div><h3><?php echo esc_html(fort_representatives_content('contact_title')); ?></h3><p><?php echo nl2br(esc_html(fort_representatives_content('contact_text'))); ?></p></div>
        <a class="button" href="<?php echo esc_url(fort_whatsapp(fort_representatives_content('whatsapp_message'))); ?>" target="_blank" rel="noopener noreferrer"><?php echo fort_icon('chat'); ?> <?php echo esc_html(fort_representatives_content('contact_button')); ?></a>
    </div>
</section>
