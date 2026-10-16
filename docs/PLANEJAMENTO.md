# Planejamento do redesign da Fort Metais

Atualizado em 14 de outubro de 2026.

## Situação da implementação local

A primeira versão local foi implementada com tema `fort-metais` e plugin `fort-catalogo`. O site está em `http://localhost:8080/`, com painel em `/wp-admin/`. Inclui sete produtos de amostra, busca, filtros, páginas de produto, cadastro de representantes e registro de orçamentos no painel. Veja [como usar o ambiente local](COMO_USAR_LOCAL.md).

O ambiente usa WordPress Playground com PHP 8.3 e SQLite persistente em `.runtime/wordpress`, aproveitando o Node.js disponível no computador. A hospedagem futura usará o banco suportado pelo WordPress na Hostinger, com migração do conteúdo.

O projeto vai modernizar o site da Fort Metais, facilitar a consulta de produtos e direcionar os visitantes ao atendimento comercial. A equipe precisa cadastrar e atualizar produtos por um painel. Para esse cenário, a recomendação é desenvolver um tema próprio para WordPress e selecionar **WordPress** na Hostinger quando chegar a etapa de hospedagem.

## Requisitos confirmados

| Item | Definição |
| --- | --- |
| Site atual | https://www.fortmetais.com.br/ |
| Operação do conteúdo | A equipe atualizará os produtos por um painel administrativo. |
| Objetivo da primeira versão | Catálogo com contato e solicitação de orçamento. |
| Referência visual | HTML `fort_metais_modernizado.html` fornecido pelo usuário. |
| Etapa atual | Primeira versão WordPress local implementada em `C:\desenvolvimento\site_fort`. |

## Escolha na Hostinger

As opções da imagem definem como o site será construído e executado. A escolha entre elas é diferente da escolha do plano de hospedagem.

| Opção | Adequação ao projeto |
| --- | --- |
| **WordPress** | **Recomendada.** Oferece a base para a equipe editar conteúdo e para desenvolver um catálogo com identidade visual própria. |
| Site PHP/HTML | Adequado a um site com código próprio. O HTML isolado precisaria de um sistema adicional para oferecer o painel solicitado. |
| Web app Node.js | Viável com uma aplicação e um painel próprios, mas acrescentaria desenvolvimento e manutenção para atender às mesmas necessidades iniciais. |
| Criador com IA | Adequado a um fluxo centrado no construtor visual. Para este projeto, a proposta é manter o código do tema e os cadastros sob controle do projeto local. |

A Hostinger documenta WordPress, código PHP/HTML e aplicações Node.js como tipos de site disponíveis, conforme o plano. A disponibilidade de aplicações Node.js depende de um plano compatível. [Tipos de site na Hostinger](https://www.hostinger.com/support/1583214-how-to-add-a-website-in-hostinger/)

O plano já contratado, seus recursos, backups e ambiente de testes serão conferidos antes da publicação. O planejamento atual não exige contratação nem mudança de plano.

## Arquitetura proposta

O WordPress atenderá o site público e o painel no mesmo projeto. O tema `fort-metais` concentrará apresentação, páginas e componentes visuais. O plugin `fort-catalogo` concentrará os cadastros, campos e classificações dos produtos e representantes.

Manter os tipos de conteúdo em um plugin permite preservar sua estrutura quando o tema for trocado, conforme a orientação oficial do WordPress. [Tipos de conteúdo em plugins](https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/)

| Parte | Proposta |
| --- | --- |
| Visual público | Tema próprio a partir do HTML recebido, com componentes reutilizáveis. |
| Conteúdo institucional | Páginas e blocos editáveis, com padrões de layout para a equipe. |
| Catálogo | Tipo de conteúdo de produtos, campos específicos e classificações. |
| Representantes | Cadastro por região ou UF, com contatos comerciais revisados. |
| Estilos e ícones | Arquivos locais, com dependências versionadas e CSS compilado. |
| Interações | JavaScript para menu, busca, filtros e recursos que precisarem dele. |
| Dados | Banco do WordPress e biblioteca de mídia. |
| Orçamentos | Chamada por produto para o canal comercial confirmado pela empresa. |

O escopo inicial terá cadastro próprio de catálogo. Carrinho, estoque, pagamento e frete ficam fora dessa versão. Se vendas diretas entrarem no roteiro do negócio, a estrutura dos produtos deve ser revista antes da importação em massa.

## Páginas e navegação

Os endereços abaixo representam a organização proposta. A implementação deve preservar os endereços atuais sempre que possível; cada alteração terá um redirecionamento correspondente.

| Área | Conteúdo e comportamento |
| --- | --- |
| Início | Apresentação, linhas, produtos em destaque, institucional e chamada comercial. |
| Institucional | História, estrutura, qualidade e diferenciais confirmados. |
| Produtos | Busca e filtros por linha, ambiente e acabamento, com paginação. |
| Detalhe do produto | Fotos, referência, especificações, arquivos e solicitação de orçamento. |
| Linhas e ambientes | Listagens dos produtos relacionados, com descrição editável. |
| Acessórios e kits | Produtos classificados e acessíveis pela navegação. |
| Representantes | Consulta por UF ou região e contato alternativo quando não houver representante. |
| Notícias | Conteúdo existente relevante, preservado na migração. |
| Contato | Dados da empresa, canais comerciais e formulário, se adotado. |
| Catálogos | Download do arquivo vigente, com nome e edição claros. |
| Privacidade | Informação correspondente aos dados efetivamente coletados. |

O site atual exibe opções de idioma PT, EN e ES. A abrangência e o conteúdo dessas versões precisam ser levantados antes de definir a migração dos idiomas. [Navegação atual](https://www.fortmetais.com.br/)

## Cadastro e operação pela equipe

Os seguintes campos são uma proposta para validar com uma amostra real do catálogo.

| Cadastro | Campos previstos |
| --- | --- |
| Produto | Nome, referência ou código, endereço da página, resumo, descrição e situação de publicação. |
| Classificações | Linha, ambiente, tipo de produto e acabamento. |
| Mídia | Foto principal, galeria, textos alternativos e ficha técnica ou manual, quando disponível. |
| Informações técnicas | Medidas, instalação e demais especificações aplicáveis ao produto. |
| Destaques | Sinalização de lançamento, destaque e ordem de apresentação. |
| Representante | Nome comercial, UF ou região atendida, telefone e canal de contato. |
| Dados gerais | Logotipo, contatos, redes sociais, catálogo vigente e links dos portais. |

As relações entre linhas, acabamentos e variações serão validadas com os códigos reais, para evitar produtos duplicados e classificações inconsistentes.

O fluxo editorial proposto é cadastrar como rascunho, preencher informações e fotos, revisar e publicar. A equipe terá acesso aos cadastros necessários; a administração técnica cuidará das configurações, atualizações e permissões.

Critério central: uma pessoa da equipe deve conseguir criar e atualizar um produto completo pelo painel e ver a alteração no site sem editar arquivos de código.

## Direção visual

O HTML recebido orientará a composição da página inicial: imagem de abertura, destaque para linhas e produtos, espaços amplos e chamadas comerciais visíveis. O logotipo, as cores finais e as fotografias serão alinhados aos materiais oficiais da empresa.

As fotos de ambientes e produtos do modelo são ilustrativas. O catálogo público deve usar imagens correspondentes aos itens cadastrados. Os textos serão adaptados ao português do Brasil, com atenção a nomes técnicos, dados institucionais e datas.

A navegação terá prioridade no celular. Menu, filtros e botões devem ser utilizáveis por toque e por teclado. Os cards de produtos terão links reais, e os botões de orçamento carregarão a referência do item para o atendimento.

## Ambiente local

A pasta do projeto estava vazia no início do levantamento. Node.js 24.13.0 e Git foram encontrados. PHP, MySQL, Docker e WP CLI não foram encontrados no PATH; essa verificação não determina se existem instalações fora dele. O WordPress local foi configurado com o Playground, usando os recursos do projeto.

Uma alternativa futura para Windows é usar **Local**, que reúne os serviços necessários para desenvolver WordPress localmente. [Instalação oficial do Local](https://localwp.com/help-docs/getting-started/installing-local/)

O ambiente deve acompanhar os requisitos recomendados do WordPress e as versões disponíveis na hospedagem. A referência atual recomenda PHP 8.3 ou superior e MySQL 8.0 ou MariaDB 10.11 ou superior. [Requisitos do WordPress](https://wordpress.org/about/requirements/)

A estrutura prevista de código é:

```text
site_fort/
  README.md
  docs/
  referencias/
  wp-content/
    themes/fort-metais/
    plugins/fort-catalogo/
```

Os documentos, referências, tema, plugin e scripts de execução estão na pasta do projeto. O ambiente local usa os diretórios do tema e do plugin como fonte. Banco, biblioteca de mídia e configuração local ficam em `.runtime`, fora do versionamento.

## Etapas e critérios de conclusão

| Etapa | Trabalho | Critério de conclusão |
| --- | --- | --- |
| 1. Conteúdo e migração | Levantar páginas, produtos, imagens, arquivos, idiomas e endereços existentes; confirmar como exportar os dados. | Inventário de conteúdo e relação inicial entre URLs antigas e novas. |
| 2. Base local | Preparar WordPress, versionamento do código, tema e plugin, com pequena amostra real de produtos. | Site e painel funcionando localmente; equipe consegue editar um produto. |
| 3. Página inicial | Adaptar o HTML, aplicar identidade oficial e concluir menu, rodapé e chamadas. | Página validada em celular e computador, com destinos reais. |
| 4. Catálogo e atendimento | Concluir listagens, filtros, detalhe, representantes, downloads e orçamento. | Jornada completa desde a busca de um produto até o contato comercial. |
| 5. Migração e qualidade | Importar conteúdo, revisar imagens e textos, preparar redirecionamentos e conferir acessibilidade e desempenho. | Conteúdo reconciliado com a origem e verificações registradas. |
| 6. Homologação e publicação | Testar em ambiente separado, validar com a equipe, fazer backup e publicar na Hostinger. | Catálogo, contatos, URLs e painel conferidos após a publicação. |

O primeiro marco de desenvolvimento será **página inicial e cadastro de produtos funcionando localmente**. A amostra terá produtos de linhas e acabamentos diferentes para validar os campos antes da migração completa.

## Verificações para a entrega

- A equipe cadastra, revisa e publica produtos sem alterar código.
- Busca encontra nome e referência, combina filtros e informa quando não há resultados.
- Fotografias e especificações correspondem aos produtos, com referências preservadas.
- Menu e filtros funcionam no celular e por teclado, com foco visível e rótulos claros.
- Páginas não apresentam rolagem horizontal indevida, imagens quebradas ou links provisórios.
- Orçamento identifica o produto; formulário, se incluído, confirma o recebimento apenas após entrega bem sucedida ao serviço de envio.
- Formulários locais usam captura de e-mail de teste; a entrega real é verificada na homologação com o canal comercial definido.
- Títulos, descrições, URLs canônicas e sitemap refletem o conteúdo publicado.
- URLs antigas continuam válidas ou redirecionam para páginas equivalentes, com os arquivos do catálogo preservados.
- Imagens são dimensionadas e otimizadas; estilos de produção são gerados localmente.
- Desempenho é medido em páginas representativas para orientar correções antes da publicação.
- Backups de arquivos e banco permitem restaurar a versão anterior; atualização de produto aparece corretamente após o tratamento de cache.

## Informações para levantar na próxima etapa

| Informação | Finalidade |
| --- | --- |
| Sistema e acesso administrativo atuais, com possibilidade de exportação | Definir o método de migração e preservar códigos e conteúdo. |
| Quantidade de produtos e arquivos disponíveis | Dimensionar importação, revisão e prazo. |
| Logotipo, cores oficiais, fotos e catálogo vigente | Substituir os elementos ilustrativos do modelo. |
| Canal e responsável por receber orçamentos | Definir contato direto, formulário e roteamento por região. |
| Pessoas que editam e publicam | Ajustar permissões e fluxo editorial. |
| Abrangência das versões EN e ES | Evitar perda de páginas e decidir a operação das traduções. |
| Plano e recursos atuais da Hostinger | Definir homologação, backups e publicação sem estimar custos por suposição. |

A duração total será estimada após conhecer o volume de conteúdo e sua forma de exportação. A continuidade imediata começa pelo inventário e pela base local, seguindo esta ordem de entregas.
