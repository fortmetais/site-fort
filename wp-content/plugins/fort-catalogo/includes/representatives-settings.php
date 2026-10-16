<?php
/** Editable content for the public representatives page. */
defined('ABSPATH') || exit;

function fort_representatives_defaults() {
    return array(
        'intro_title' => 'Seja um representante Fort Metais',
        'intro_text' => 'Leve qualidade, design e praticidade a novos clientes. Conheça as vantagens de representar a Fort Metais e converse com nossa equipe sobre as oportunidades na sua região.',
        'benefits_title' => 'Vantagens de representar a Fort',
        'benefit_portfolio_title' => 'Portfólio de produtos',
        'benefit_portfolio_text' => 'Linhas de metais sanitários que unem qualidade, funcionalidade e design para diferentes ambientes.',
        'benefit_catalog_title' => 'Catálogos digitais',
        'benefit_catalog_text' => 'Imagens e especificações dos produtos para apoiar a apresentação aos seus clientes.',
        'benefit_prices_title' => 'Listas de preços',
        'benefit_prices_text' => 'Informações organizadas sobre os produtos e as variações de acabamento para suas negociações.',
        'benefit_samples_title' => 'Amostras de produtos',
        'benefit_samples_text' => 'Conheça de perto os acabamentos e apresente a qualidade dos produtos aos seus clientes.',
        'requirements_title' => 'O que você precisa para representar a Fort',
        'requirement_cnpj_title' => 'CNPJ ativo',
        'requirement_cnpj_text' => 'Possuir CNPJ ativo para atuar como representante comercial.',
        'requirement_experience_title' => 'Experiência em vendas',
        'requirement_experience_text' => 'Ter experiência na área comercial e no relacionamento com clientes.',
        'requirement_car_title' => 'Carro próprio',
        'requirement_car_text' => 'Ter veículo próprio para visitar clientes nas cidades da região atendida.',
        'contact_title' => 'Vamos construir essa parceria?',
        'contact_text' => 'Fale com nossa equipe para conhecer as oportunidades e tirar suas dúvidas sobre a representação Fort Metais.',
        'contact_button' => 'Quero ser representante',
        'whatsapp_message' => 'Olá! Tenho interesse em ser representante da Fort Metais. Gostaria de saber mais sobre as oportunidades na minha região.',
    );
}

function fort_representatives_content($key) {
    $defaults = fort_representatives_defaults();
    if (!array_key_exists($key, $defaults)) return '';
    $saved = get_option('fort_representatives_options', array());
    if (!is_array($saved) || !array_key_exists($key, $saved) || !is_scalar($saved[$key])) return $defaults[$key];
    return (string) $saved[$key];
}

function fort_representatives_sanitize_options($input) {
    $output = array();
    $previous = get_option('fort_representatives_options', array());
    if (!is_array($previous)) $previous = array();
    if (!is_array($input)) $input = array();
    foreach (fort_representatives_defaults() as $key => $default) {
        $value = array_key_exists($key, $previous) && is_scalar($previous[$key]) ? (string) $previous[$key] : $default;
        if (array_key_exists($key, $input) && is_scalar($input[$key])) $value = (string) $input[$key];
        $output[$key] = substr($key, -5) === '_text' || $key === 'whatsapp_message'
            ? sanitize_textarea_field($value)
            : sanitize_text_field($value);
    }
    return $output;
}

add_action('admin_init', function () {
    register_setting('fort_representatives_settings', 'fort_representatives_options', array(
        'type' => 'array',
        'sanitize_callback' => 'fort_representatives_sanitize_options',
        'default' => fort_representatives_defaults(),
    ));
});

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=fort_representante',
        'Conteúdo da página de representantes',
        'Conteúdo da página',
        'manage_options',
        'fort-representantes-conteudo',
        'fort_representatives_settings_page'
    );
});

function fort_representatives_settings_page() {
    if (!current_user_can('manage_options')) return;
    $groups = array(
        'Apresentação da oportunidade' => array(
            'intro_title' => 'Título da apresentação',
            'intro_text' => 'Texto da apresentação',
        ),
        'Vantagens' => array(
            'benefits_title' => 'Título da seção de vantagens',
            'benefit_portfolio_title' => 'Portfólio: título',
            'benefit_portfolio_text' => 'Portfólio: descrição',
            'benefit_catalog_title' => 'Catálogos: título',
            'benefit_catalog_text' => 'Catálogos: descrição',
            'benefit_prices_title' => 'Listas de preços: título',
            'benefit_prices_text' => 'Listas de preços: descrição',
            'benefit_samples_title' => 'Amostras: título',
            'benefit_samples_text' => 'Amostras: descrição',
        ),
        'Requisitos' => array(
            'requirements_title' => 'Título da seção de requisitos',
            'requirement_cnpj_title' => 'CNPJ: título',
            'requirement_cnpj_text' => 'CNPJ: descrição',
            'requirement_experience_title' => 'Experiência: título',
            'requirement_experience_text' => 'Experiência: descrição',
            'requirement_car_title' => 'Veículo: título',
            'requirement_car_text' => 'Veículo: descrição',
        ),
        'Convite para contato' => array(
            'contact_title' => 'Título do convite',
            'contact_text' => 'Texto do convite',
            'contact_button' => 'Texto do botão',
            'whatsapp_message' => 'Mensagem inicial do WhatsApp',
        ),
    );
    ?>
    <div class="wrap">
        <h1>Conteúdo da página de representantes</h1>
        <p>Edite os textos de apresentação, vantagens, requisitos e convite para contato.</p>
        <p><a href="<?php echo esc_url(home_url('/representantes/')); ?>">Ver página de representantes</a></p>
        <p>Os nomes, estados, regiões e contatos dos representantes são alterados nos <a href="<?php echo esc_url(admin_url('edit.php?post_type=fort_representante')); ?>">cadastros individuais de representantes</a>. O número de WhatsApp do convite é o configurado em <a href="<?php echo esc_url(admin_url('options-general.php?page=fort-metais')); ?>">Configurações → Fort Metais</a>.</p>
        <?php settings_errors('fort_representatives_options'); ?>
        <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
            <?php settings_fields('fort_representatives_settings'); ?>
            <?php foreach ($groups as $heading => $fields) : ?>
                <h2><?php echo esc_html($heading); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                    <?php foreach ($fields as $key => $label) :
                        $id = 'fort-representatives-' . $key;
                        $multiline = substr($key, -5) === '_text' || $key === 'whatsapp_message';
                        ?>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <?php if ($multiline) : ?>
                                    <textarea class="large-text" id="<?php echo esc_attr($id); ?>" name="fort_representatives_options[<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea(fort_representatives_content($key)); ?></textarea>
                                <?php else : ?>
                                    <input class="large-text" type="text" id="<?php echo esc_attr($id); ?>" name="fort_representatives_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(fort_representatives_content($key)); ?>">
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <?php submit_button('Salvar alterações'); ?>
        </form>
    </div>
    <?php
}
