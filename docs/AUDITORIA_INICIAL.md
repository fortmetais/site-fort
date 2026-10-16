# Auditoria inicial do site Fort Metais

Levantamento de 14 de outubro de 2026 para orientar o redesign e a migração de conteúdo. O HTML recebido é uma referência de apresentação; os cadastros e fluxos precisam ser implementados para atender à operação da equipe.

## Conteúdo atual a preservar

| Área | Observação e consequência para o projeto |
| --- | --- |
| Página inicial | Apresenta a empresa, lançamentos, linhas, produtos em destaque e acessos a portais. Revisar a hierarquia e conferir os destinos desses acessos. |
| Produtos | O catálogo organiza a consulta por linhas e ambientes. Preservar essas classificações e a navegação de acessórios e kits. |
| Representantes | A página oferece consulta por estado e informações para novos representantes. Conferir contatos e dados de cobertura antes da importação. |
| Contato | Há dados comerciais e formulário. Definir o destinatário e testar a entrega no fluxo futuro. |

Fontes: [página inicial](https://www.fortmetais.com.br/), [produtos](https://fortmetais.com.br/produtos), [representantes](https://fortmetais.com.br/representantes) e [contato](https://fortmetais.com.br/fale-com-a-gente).

A navegação também apresenta notícias, download de catálogo e opções PT, EN e ES. A migração deve incluir o levantamento dessas áreas, seu conteúdo e arquivos. A quantidade total de produtos e a tecnologia do painel atual precisam ser identificadas antes de escolher o método de importação.

## Endereços já identificados

| Endereço | Tratamento previsto |
| --- | --- |
| `/` | Preservar a função de página inicial. |
| `/produtos` | Preservar a entrada do catálogo. |
| `/representantes` | Preservar a entrada da consulta comercial. |
| `/fale-com-a-gente` | Preservar o endereço ou redirecionar se o nome mudar. |
| `/produtos/12-torneira-opala-bancada-aco-corten-fica-cuba-arejador-14-de-volta-p-484` | Exemplo de URL de produto encontrada na página inicial; incluir no inventário completo. |

O inventário deve registrar endereço antigo, destino novo, referência do produto e arquivos associados. É necessário conferir as versões com e sem `www` e definir uma versão canônica, com redirecionamento consistente. A ficha do produto de exemplo não pôde ser lida pela ferramenta de pesquisa; seus campos serão conferidos no levantamento detalhado.

## HTML modelo

O arquivo foi preservado em `referencias/fort_metais_modernizado.original.html`. A cópia tem o mesmo conteúdo binário do arquivo fornecido em Downloads.

| Ponto encontrado no arquivo | Ação prevista |
| --- | --- |
| Abertura ampla, grade de linhas, destaques e seção institucional | Usar como referência para a composição do tema. |
| Logotipo representado por texto | Aplicar o arquivo oficial e conferir suas versões para fundos claros e escuros. |
| Fotos externas de ambientes usadas em cards de produtos | Substituir por fotos reais e corretamente associadas a cada produto. |
| Links `href="#"` e cards visuais sem links | Conectar a páginas, arquivos ou canais reais; retirar elementos que não tiverem função definida. |
| Busca somente com campos visuais | Implementar a consulta ao catálogo e os estados de resultado vazio. |
| Botões de catálogo, produtos e conversa sem comportamento completo | Definir os destinos e implementar as ações. |
| Ícones de favoritos sem comportamento | Retirar do primeiro escopo, salvo necessidade comercial definida. |
| `lang="pt-PT"`, textos como “Contactos” e “Fale Connosco” | Ajustar para `pt-BR` e revisar o texto da interface. |
| Ano de rodapé 2024 e chamada com “26 anos” | Revisar com a empresa; preferir “Desde 1999” após validar a informação institucional. |
| Tailwind carregado por script de CDN | Gerar o CSS de produção no projeto. |
| Lucide carregado com `@latest` | Fixar a dependência e incluir os recursos usados pelo tema. |
| Menu móvel alterna apenas a classe de visibilidade | Incluir nome acessível, estado de expansão, tratamento de foco e fechamento. |
| Busca e links de ícones sem rótulos explícitos | Incluir rótulos, foco visível e navegação por teclado. |
| Recursos de SEO e imagens ainda incompletos | Definir metadados, imagem social, URLs, tamanhos de imagem e carregamento adequado. |

A documentação do Tailwind destina o Play CDN ao desenvolvimento. O tema usará um arquivo CSS gerado antes da publicação. [Orientação oficial do Tailwind](https://tailwindcss.com/docs/installation/play-cdn)

## Prioridades

1. Validar os dados do catálogo e as rotinas de quem vai mantê-lo.
2. Estruturar o painel e a página de produto com uma amostra real.
3. Adaptar o visual da página inicial e concluir a navegação no celular.
4. Conectar busca, filtros e orçamento ao conteúdo cadastrado.
5. Conferir a migração de URLs, imagens, documentos e demais páginas antes da publicação.

Este levantamento cobre a estrutura de conteúdo encontrada nas páginas públicas e a leitura do código do modelo. A avaliação visual em navegador, as medições de desempenho e os testes funcionais serão feitos sobre a implementação local e a homologação.
