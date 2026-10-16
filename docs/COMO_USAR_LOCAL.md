# Como usar o WordPress local da Fort Metais

O [site local](http://localhost:8080/) funciona enquanto o servidor estiver aberto. O [painel administrativo](http://localhost:8080/wp-admin/) permite manter o catálogo sem editar arquivos de código.

## Abrir e encerrar

Abra `INICIAR_FORT.cmd`, na raiz do projeto, ou execute `npm run dev` nessa pasta. Aguarde a mensagem com os endereços do site e do painel.

Para encerrar, pressione `Ctrl+C` na janela do servidor. Execute o mesmo arquivo para reabrir. Os produtos, imagens, configurações e orçamentos são preservados.

O ambiente utiliza Node.js e o WordPress Playground oficial, com PHP 8.3 e SQLite local. O servidor fica restrito a este computador. Em uma nova cópia do projeto, execute `npm ci` antes da primeira inicialização.

## Acessar o painel

Use os dados do arquivo `.runtime/acesso-local.json`. A conta de desenvolvimento tem usuário `admin` e uma senha gerada na primeira execução. Esse arquivo pertence ao ambiente local e fica fora do versionamento.

## Cadastrar produtos

1. Abra **Produtos > Adicionar produto**.
2. Informe o título e a descrição.
3. Selecione linha, ambiente e acabamento. Novas classificações podem ser criadas nos respectivos cadastros do menu Produtos.
4. Em **Imagem destacada**, escolha uma imagem da biblioteca ou envie a foto do produto.
5. Preencha a referência e as especificações confirmadas em **Informações do produto**.
6. Marque a opção de destaque para exibir o item na página inicial.
7. Salve como rascunho para revisar ou use **Publicar** para exibir no site local. Em um produto existente, use **Atualizar**.

Os identificadores do site antigo foram preservados como dados de migração. Eles não foram apresentados como códigos comerciais. As referências devem ser preenchidas a partir do cadastro oficial da empresa.

## Variações de cor do produto

O produto [Torneira Opala para bancada — cuba](http://localhost:8080/produtos/torneira-opala-black-para-bancada-cuba/) está configurado para testar cinco bolinhas de acabamento: Cromado, Black, Aço Corten, Rose e Grafite. A seleção atualiza a foto, a especificação, o endereço da página e os links de orçamento e WhatsApp. O orçamento registra o acabamento escolhido e sua referência, quando informada.

Em **Produtos > Editar produto > Variações de acabamento**, informe o nome do modelo sem acabamento, habilite as cores e preencha a URL da foto e a referência comercial de cada uma. As fotos estão disponíveis na biblioteca **Mídia**. Desabilitar uma cor preserva seus dados, mas retira a opção da ficha. Produtos sem variações continuam com sua foto e acabamento originais.

Black utiliza a foto original. As outras quatro fotos são simulações de acabamento para testar a funcionalidade, identificadas como tal no site e no orçamento. Substitua pelas fotos oficiais e desmarque **Simulação para teste** quando o acabamento e a disponibilidade forem confirmados. Não foram inventadas referências comerciais.

Cada acabamento tem sua própria referência comercial. Quando o campo da opção estiver vazio, a ficha, o formulário, o WhatsApp e o registro do orçamento exibem **a confirmar**; a referência principal do produto não é reutilizada para outra cor.

## Versões com arejador ou articulador

A torneira Jade possui as opções **Com arejador** e **Com articulador**. Os botões trocam a foto, a versão, a referência e os links comerciais. A seleção fica no endereço da ficha e é registrada no orçamento.

Em **Produtos > Editar a torneira Jade > Variações da bica**, habilite cada versão, informe a URL da foto e sua referência própria. O campo **Nome do modelo sem a versão da bica** evita que o título fique preso a uma opção. **Versão exibida inicialmente** define a seleção padrão.

**Com arejador** usa a foto original e a referência **F-013161**. **Com articulador** tem referência **a confirmar**, conforme solicitado, e uma imagem ilustrativa criada a partir da foto e da referência do acessório. A imagem está em **Mídia** e marcada como **Simulação para teste**; substitua pela foto oficial quando disponível. Deixe referências pendentes vazias: o site mostra **a confirmar**, sem cadastrar esse texto como código.

As cores foram configuradas na Opala e as versões da bica na Jade. O cadastro atual trabalha com essas opções separadas; combinações de cor e bica no mesmo produto exigirão referências e fotos específicas.

## Idiomas do site

O seletor com o ícone de globo no menu superior oferece **Português (BR)**, **English** e **Español**. Português brasileiro é o padrão na primeira visita. A escolha é lembrada neste navegador e acompanha a navegação, filtros, produtos e pedidos de orçamento. Ao trocar o idioma dentro de um produto, a cor ou a versão selecionada é preservada.

Os conteúdos atuais foram traduzidos, incluindo página inicial, institucional, compromissos, representantes, ambientes, produtos, especificações, formulários, avisos e mensagens prontas do WhatsApp. Marcas, códigos comerciais, números de telefone e endereços continuam com os dados originais. A área administrativa permanece em português.

Em **Configurações > Idiomas e traduções**, procure o trecho original ou sua tradução, revise inglês e espanhol e use **Salvar traduções desta página**. A tela mostra 25 trechos por página; salvar uma página preserva as demais. Limpar um campo restaura a tradução de base. **Adicionar tradução** permite cadastrar o original em português e suas versões para novos textos.

Novos conteúdos ou textos alterados em português precisam de tradução cadastrada nessa área. Copie cada trecho exatamente, incluindo a pontuação; trechos separados por destaques ou quebras de linha podem ter entradas próprias. O site utiliza um catálogo de traduções local, sem serviço externo de tradução automática. Enquanto um novo trecho não tiver tradução, o original em português é preservado.

As traduções iniciais ficam em `wp-content/plugins/fort-catalogo/includes/translations/en.php` e `es.php`. Ajustes feitos no painel são preservados no banco local. Os links traduzidos usam `?lang=en` ou `?lang=es`, mantendo os endereços anteriores. O idioma do atendimento aparece no registro do orçamento.

Com o servidor aberto, `npm run check:i18n` verifica os três idiomas, a preferência lembrada, navegação, filtros, busca por termos em inglês e espanhol, campos dos formulários, variações e redirecionamentos. A auditoria dos conteúdos atuais também conferiu 34 rotas em cada idioma estrangeiro.

## Atualizar a apresentação e os contatos

Em **Configurações > Fort Metais**, altere título e descrição da abertura, imagem principal, telefone, WhatsApp, e-mail, endereço e as URLs dos catálogos PDF por idioma.

Para escolher uma imagem, envie o arquivo em **Mídia**, copie sua URL e preencha o campo correspondente.

O conteúdo institucional fica em **Páginas > Nossa história**. A composição visual da página inicial e seus cards de linhas são definidos no tema desta primeira versão.

### Catálogos PDF por idioma

1. Envie os PDFs completos em **Mídia > Adicionar arquivo de mídia**, ou abra os arquivos já cadastrados na biblioteca. Copie a **URL do arquivo**, sem usar o endereço de uma miniatura ou de uma página de anexo.
2. Abra **Configurações > Fort Metais**.
3. Em **URL do catálogo PDF — Português (BR)**, cole a URL do arquivo em português.
4. Em **URL do catálogo PDF — English**, cole a URL do arquivo em inglês.
5. **URL do catálogo PDF — Español (opcional)** pode ficar vazio até existir uma versão oficial em espanhol.
6. Clique em **Salvar alterações** e confira os botões na abertura da página inicial e em **Acessos Rápidos**, no rodapé.

Os arquivos completos fornecidos para este projeto são **Catalogo 2024 fort.pdf**, em português, com **124 páginas** e **35.586.756 bytes**, e **Catálogo Fort Inglês.pdf**, em inglês, com **124 páginas** e **80.430.063 bytes**. Confira o nome e a quantidade de páginas ao selecionar ou substituir os arquivos na biblioteca.

| Idioma selecionado no site | Arquivo exibido |
| --- | --- |
| Português (BR) | PDF configurado no campo Português (BR). |
| English | PDF configurado no campo English. |
| Español, com PDF próprio configurado | PDF configurado no campo Español. |
| Español, sem PDF próprio | PDF em português, com o botão identificado como **Catálogo en portugués**. |

O idioma da página não traduz o conteúdo de um PDF. A identificação em espanhol informa que o documento disponível está em português. Se não houver um arquivo configurado para o idioma, nem a alternativa em português no caso do espanhol, o botão permite **solicitar o catálogo pelo formulário**. Os PDFs são abertos em outra aba, onde o navegador permite ler ou baixar o arquivo completo.

## Consultar orçamentos e representantes

O formulário registra as solicitações em **Orçamentos**, com nome, e-mail, telefone, cidade, mensagem e produto de interesse. Os registros são privados e acessíveis pelo painel. O ambiente local não envia e-mail; a integração de notificações será definida antes da publicação.

Em **Representantes**, adicione nome comercial, UF, região atendida, telefone e e-mail. Publique o cadastro para que ele apareça na consulta por estado. Enquanto uma UF não tiver cadastros, a página oferece contato com a central comercial.

Em **Representantes > Conteúdo da página**, edite a apresentação para novos representantes, os quatro cards de vantagens, os três requisitos, o convite final e a mensagem inicial do WhatsApp. O número de destino fica em **Configurações > Fort Metais > WhatsApp com país e DDD**. O botão abre a conversa para a pessoa confirmar o envio no WhatsApp.

## Pop-up de atendimento pelo WhatsApp

O botão azul flutuante abre uma janela com o personagem da Fort, uma mensagem de boas-vindas e **Conversar pelo WhatsApp**. A janela aparece somente ao clicar. Use o X, a tecla Escape, o próprio botão flutuante ou um clique fora da janela para fechá-la. A conversa é aberta em outra aba quando o visitante escolhe o botão dentro da janela.

O número de destino continua em **Configurações > Fort Metais > WhatsApp com país e DDD**. O conteúdo acompanha Português (BR), English e Español. As traduções podem ser revisadas em **Configurações > Idiomas e traduções**.

O título e a mensagem originais ficam no tema, em `parts/whatsapp-widget.php`; o personagem sem caixa fica em `assets/images/mascote-atendimento.png`. O registro da imagem e das referências de composição fica em `referencias/atendimento-whatsapp.json`.

## Fotos e produtos nos ambientes

A página [Ambientes](http://localhost:8080/ambientes/) reúne fotos de inspiração. Ao abrir uma foto, os marcadores numerados identificam os produtos presentes no ambiente; o link **Conheça o produto** abre a ficha do item no catálogo.

1. Abra **Ambientes > Adicionar ambiente**.
2. Informe o título, o resumo para a galeria e o conteúdo de apresentação do ambiente.
3. Em **Foto principal do ambiente**, selecione uma foto da biblioteca ou envie o arquivo por **Mídia**. A URL alternativa é opcional e só é usada quando não há foto principal selecionada.
4. Selecione a linha e o tipo de ambiente, como banheiro ou cozinha, nas classificações do cadastro.
5. Mantenha **Imagem ilustrativa / ambientação digital** marcado para imagens geradas ou modificadas com IA e outras ambientações ilustrativas. Essa indicação aparece junto à foto.
6. Em **Produtos presentes na imagem**, escolha os produtos cuja presença foi confirmada pela equipe. É possível vincular até oito produtos publicados, sem repetições.
7. Ajuste os marcadores com **X (%)**, da esquerda para a direita, e **Y (%)**, do topo para a base. Os valores vão de 0 a 100; 50 / 50 posiciona o marcador no centro da foto.
8. Salve como rascunho, confira a prévia e publique. Em um ambiente existente, use **Atualizar**. Para remover um vínculo, escolha **Sem produto / remover vínculo**.

Se o produto ainda não existir, salve o ambiente como rascunho e use **Cadastrar novo produto**. Revise e publique o produto, depois recarregue o editor do ambiente para selecioná-lo. As fotos e as linhas não associam produtos automaticamente.

Antes de cadastrar cada novo ambiente, peça à pessoa responsável a referência comercial, o nome completo e os atributos dos produtos visíveis na foto. Compare essas informações com os cadastros existentes para reaproveitar apenas o modelo correspondente. Uma linha ou aparência parecida não confirma que seja o mesmo produto.

Use títulos padronizados que comecem por “Torneira”, mantenha o código no campo **Referência / código comercial** e registre o nome comercial completo na descrição. Preencha instalação, acionamento, conexão, acabamento e demais características somente quando confirmados. Não estime medidas, pressão, vazão, materiais ou outras especificações ausentes.

Há cinco ambientes publicados: Opala Black com a referência **F-400137**, Rubi Aço Corten com **F-400135**, Ametista de bancada com **F-350002**, Opala Rose com **F-400146** e uma ambientação Opala Black Slim que reutiliza o produto já cadastrado. As quatro referências novas têm fotos oficiais, com suas fontes registradas em `referencias/produtos-ambientes-origem.json`.

## Arquivos e persistência

| Caminho | Conteúdo |
| --- | --- |
| `wp-content/themes/fort-metais/` | Tema e recursos visuais. |
| `wp-content/plugins/fort-catalogo/` | Cadastros, campos e registro de orçamentos. |
| `.runtime/wordpress/` | Instalação WordPress persistente. |
| `.runtime/wordpress/wp-content/database/.ht.sqlite` | Banco local. |
| `.runtime/wordpress/wp-content/uploads/` | Imagens e arquivos enviados pelo painel. |
| `.runtime/acesso-local.json` | Conta de desenvolvimento. |
| `.runtime/backups/` | Cópia do banco feita antes do teste de reinício. |

Para copiar o ambiente completo, encerre o servidor e preserve a pasta `.runtime` junto com o código. O banco e as imagens enviados pelo painel não entram automaticamente no versionamento do código.

## Verificações

Com o servidor aberto, execute `npm run check`. O comando verifica início, catálogo, filtros, busca, estado sem resultados, ficha de produto, orçamento com item selecionado, páginas institucionais, painel, resposta 404, rejeição de nonce inválido e recursos locais.

Com o produto de teste configurado, `npm run check:finishes` confere as cinco fotos, a seleção por URL, os links comerciais e a rejeição de acabamentos inválidos. Esse teste não registra orçamentos válidos. A seleção também foi conferida no navegador, por toque e teclado, e uma solicitação local de demonstração Rose foi registrada no painel.

A inicialização verifica a sintaxe dos arquivos PHP. A amostra é importada apenas na primeira execução, preservando as alterações posteriores.

Use `npm run check:versions` para conferir as duas versões da Jade, suas fotos e referências, os links de orçamento e WhatsApp, o tratamento de opções inválidas e as referências pendentes das cores da Opala. O teste não cria orçamentos válidos. Uma solicitação local de demonstração com articulador foi registrada e conferida no painel.

## Continuidade e publicação

Os sete produtos da amostra inicial foram preservados. O catálogo também inclui o cadastro Jade e as quatro referências confirmadas para os ambientes, com fotos oficiais. Ainda é necessário importar o catálogo completo, revisar os textos e as especificações pendentes com a empresa, cadastrar representantes e anexar os demais arquivos oficiais.

Na Hostinger, o tema e o plugin serão instalados em WordPress com PHP e MySQL ou MariaDB. O banco SQLite do Playground não é uma cópia diretamente compatível com MySQL; o conteúdo será migrado por exportação e importação, com conferência de imagens, campos e classificações.

As páginas antigas, idiomas, notícias, portais e redirecionamentos serão reconciliados durante a migração completa. A publicação inclui revisão de privacidade, configuração de envio de e-mail, backups e testes na hospedagem.
