<?php
$product_id = absint(fort_input('produto'));
if ($product_id && (get_post_type($product_id) !== 'fort_produto' || get_post_status($product_id) !== 'publish')) $product_id = 0;
$finish_key = fort_input('acabamento');
$finish = $product_id && $finish_key !== '' ? fort_product_finish($product_id, $finish_key) : null;
if (!$finish) $finish_key = '';
$version_key = fort_input('versao');
$version = $product_id && $version_key !== '' ? fort_product_version($product_id, $version_key) : null;
if (!$version) $version_key = '';
$product_title = $product_id ? fort_product_model_title($product_id) : '';
$quote_whatsapp_message = $product_id ? 'Olá! Gostaria de solicitar um orçamento para ' . $product_title . '.' : '';
if ($finish) {
    $quote_whatsapp_message .= ' Acabamento: ' . $finish['label'] . '.';
    $quote_whatsapp_message .= ' Referência: ' . ($finish['reference'] !== '' ? $finish['reference'] : 'a confirmar') . '.';
    if ($finish['demo']) $quote_whatsapp_message .= ' Visualizei uma simulação de acabamento para teste e gostaria de confirmar a disponibilidade.';
}
if ($version) {
    $quote_whatsapp_message .= ' Versão: ' . $version['label'] . '.';
    $quote_whatsapp_message .= ' Referência: ' . ($version['reference'] !== '' ? $version['reference'] : 'a confirmar') . '.';
    if ($version['demo']) $quote_whatsapp_message .= ' Visualizei uma simulação de versão para teste e gostaria de confirmar a configuração e a disponibilidade.';
}
$status = fort_input('envio');
$messages = array('sucesso' => 'Sua solicitação foi registrada. Obrigado por entrar em contato com a Fort!', 'invalido' => 'Confira os campos obrigatórios e o consentimento antes de enviar.', 'aguarde' => 'Sua última solicitação já foi registrada. Aguarde alguns instantes antes de enviar outra.', 'erro' => 'Não foi possível registrar sua solicitação. Tente novamente ou use um dos nossos canais de contato.');
?>
<div class="contact-layout"><div class="contact-information"><span class="eyebrow">CONTE COM A GENTE</span><h2>O próximo detalhe<br>começa com uma conversa.</h2><p>Produtos, projetos ou parcerias: escolha o melhor jeito de falar com a Fort.</p><div class="contact-method"><?php echo fort_icon('phone'); ?><div><span>Telefone</span><a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', fort_get('telefone'))); ?>"><?php echo esc_html(fort_get('telefone')); ?></a></div></div><div class="contact-method"><?php echo fort_icon('mail'); ?><div><span>E-mail</span><a href="mailto:<?php echo esc_attr(fort_get('email')); ?>"><?php echo esc_html(fort_get('email')); ?></a></div></div><div class="contact-method"><?php echo fort_icon('pin'); ?><div><span>Onde estamos</span><p><?php echo esc_html(fort_get('endereco')); ?></p></div></div></div>
<form class="contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><h2><?php echo $product_id ? 'Solicite um orçamento' : 'Envie sua mensagem'; ?></h2><?php if (isset($messages[$status])): ?><div class="form-notice <?php echo $status === 'sucesso' ? 'success' : 'error'; ?>" role="status"><?php echo esc_html($messages[$status]); ?></div><?php endif; ?>
<?php if ($product_id): ?>
<div class="selected-product"><span>Produto de interesse</span><strong><?php echo esc_html($product_title); ?></strong>
    <?php if ($finish): ?>
        <p>Acabamento: <strong><?php echo esc_html($finish['label']); ?></strong><br>Referência: <?php echo esc_html($finish['reference'] !== '' ? $finish['reference'] : 'a confirmar'); ?></p>
        <?php if ($finish['demo']): ?><p class="finish-demo-note">Simulação de acabamento para teste. Confirme disponibilidade com a Fort.</p><?php endif; ?>
    <?php endif; ?>
    <?php if ($version): ?>
        <p>Versão: <strong><?php echo esc_html($version['label']); ?></strong><br>Referência: <?php echo esc_html($version['reference'] !== '' ? $version['reference'] : 'a confirmar'); ?></p>
        <?php if ($version['demo']): ?><p class="version-demo-note">Simulação de versão para teste. Confirme a configuração e a disponibilidade com a Fort.</p><?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>
<input type="hidden" name="action" value="fort_orcamento"><input type="hidden" name="produto" value="<?php echo esc_attr($product_id); ?>"><input type="hidden" name="acabamento" value="<?php echo esc_attr($finish_key); ?>"><input type="hidden" name="versao" value="<?php echo esc_attr($version_key); ?>"><?php wp_nonce_field('fort_quote', 'fort_quote_nonce'); ?>
<div class="form-grid"><div><label for="nome">Seu nome <span>*</span></label><input id="nome" name="nome" autocomplete="name" required minlength="2" maxlength="120" placeholder="Como podemos chamar você?"></div><div><label for="email">E-mail <span>*</span></label><input id="email" name="email" type="email" autocomplete="email" required maxlength="160" placeholder="voce@empresa.com.br"></div><div><label for="telefone">Telefone <span>*</span></label><input id="telefone" name="telefone" type="tel" autocomplete="tel" required minlength="8" maxlength="40" placeholder="(00) 00000-0000"></div><div><label for="cidade">Cidade / UF</label><input id="cidade" name="cidade" autocomplete="address-level2" maxlength="160" placeholder="Sua cidade e estado"></div></div><label for="mensagem">Como podemos ajudar?</label><textarea id="mensagem" name="mensagem" rows="4" maxlength="5000" placeholder="Conte um pouco sobre o que você precisa."><?php echo fort_input('assunto') === 'catalogo' ? esc_textarea(fort_t('Gostaria de receber o catálogo atualizado da Fort Metais.')) : ''; ?></textarea><div class="honeypot" aria-hidden="true"><label for="website">Deixe este campo vazio</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div><label class="consent"><input type="checkbox" name="consentimento" value="1" required><span>Concordo com o uso dos meus dados para receber atendimento sobre esta solicitação. <a href="<?php echo esc_url(home_url('/privacidade/')); ?>">Privacidade</a></span></label><button class="button" type="submit">Enviar solicitação <?php echo fort_icon('arrow'); ?></button><p class="form-footnote">Prefere uma conversa? <a href="<?php echo esc_url(fort_whatsapp($quote_whatsapp_message)); ?>" target="_blank" rel="noopener noreferrer">Fale pelo WhatsApp.</a></p></form></div>
