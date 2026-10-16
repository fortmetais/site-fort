<?php
$language = function_exists('fort_language') ? fort_language() : 'pt-br';
$languages = array(
    'pt-br' => array('short' => 'PT-BR', 'label' => 'Português (BR)', 'tag' => 'pt-BR'),
    'en' => array('short' => 'EN', 'label' => 'English', 'tag' => 'en'),
    'es' => array('short' => 'ES', 'label' => 'Español', 'tag' => 'es'),
);
$language_label = function_exists('fort_t') ? fort_t('Idioma do site') : 'Idioma do site';
?>
<nav class="fort-language-switcher" aria-label="<?php echo esc_attr($language_label); ?>" translate="no">
    <details>
        <summary aria-label="<?php echo esc_attr($language_label . ': ' . $languages[$language]['label']); ?>"><?php echo fort_icon('globe'); ?><span><?php echo esc_html($languages[$language]['short']); ?></span><?php echo fort_icon('chevron'); ?></summary>
        <ul>
            <?php foreach ($languages as $key => $choice): ?>
                <li><a href="<?php echo esc_url(function_exists('fort_language_url') ? fort_language_url($key) : add_query_arg('lang', $key)); ?>" data-fort-language="<?php echo esc_attr($key); ?>" lang="<?php echo esc_attr($choice['tag']); ?>" hreflang="<?php echo esc_attr($choice['tag']); ?>" <?php if ($language === $key) echo 'aria-current="true"'; ?>><?php echo esc_html($choice['label']); ?><?php if ($language === $key) echo fort_icon('check'); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </details>
</nav>
