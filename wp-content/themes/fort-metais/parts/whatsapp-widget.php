<?php
defined('ABSPATH') || exit;
$widget_translate = function ($text) { return function_exists('fort_t') ? fort_t($text) : $text; };
$widget_whatsapp_url = fort_whatsapp('Olá! Quero conhecer os produtos da Fort Metais.');
$widget_mascot_path = get_template_directory() . '/assets/images/mascote-atendimento.png';
$widget_mascot_url = add_query_arg('v', is_file($widget_mascot_path) ? filemtime($widget_mascot_path) : '1', fort_asset('mascote-atendimento.png'));
?>
<div class="fort-whatsapp-widget" id="fort-whatsapp-widget">
    <section class="fort-whatsapp-panel" id="fort-whatsapp-panel" role="dialog" aria-modal="false" aria-labelledby="fort-whatsapp-title" hidden>
        <div class="fort-whatsapp-mascot"><img src="<?php echo esc_url($widget_mascot_url); ?>" alt="Personagem da Fort Metais dando boas-vindas" width="240" height="360" loading="lazy" decoding="async"></div>
        <div class="fort-whatsapp-bubble">
            <button class="fort-whatsapp-close" type="button" aria-label="Fechar atendimento"><?php echo fort_icon('close'); ?></button>
            <span class="fort-whatsapp-eyebrow">FORT METAIS</span>
            <h2 id="fort-whatsapp-title">Olá! Como podemos ajudar?</h2>
            <p class="fort-whatsapp-message">Tire suas dúvidas sobre nossos produtos ou encontre a solução para o seu projeto.</p>
            <a class="fort-whatsapp-cta" href="<?php echo esc_url($widget_whatsapp_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo fort_icon('chat'); ?><span>Conversar pelo WhatsApp</span></a>
            <p class="fort-whatsapp-note">Você será direcionado ao WhatsApp.</p>
        </div>
    </section>
    <a class="floating-contact fort-whatsapp-launcher" id="fort-whatsapp-launcher" href="<?php echo esc_url($widget_whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($widget_translate('Abrir atendimento da Fort')); ?>" aria-haspopup="dialog" aria-expanded="false" aria-controls="fort-whatsapp-panel" data-open-label="<?php echo esc_attr($widget_translate('Abrir atendimento da Fort')); ?>" data-close-label="<?php echo esc_attr($widget_translate('Fechar atendimento da Fort')); ?>">
        <span class="fort-whatsapp-launcher-chat" aria-hidden="true"><?php echo fort_icon('chat'); ?></span>
        <span class="fort-whatsapp-launcher-close" aria-hidden="true"><?php echo fort_icon('close'); ?></span>
    </a>
</div>
