# GitHub e publicação em um domínio temporário

O projeto tem duas partes que precisam acompanhar a publicação: o código do tema/plugin e os dados administrados pelo WordPress. O GitHub guarda a primeira parte; o banco e a biblioteca de mídia seguem em um pacote privado de migração.

## Instalação atual e próximas atualizações

O site temporário está em **https://lime-lion-509099.hostingersite.com/**. A migração inicial foi concluída em 16/10/2026 por um importador privado testado, que remapeou os vínculos entre conteúdos e preservou a conta administrativa da hospedagem. O utilitário foi removido após a conferência.

O repositório `fortmetais/site-fort`, branch `main`, possui implantação automática ativa nesse domínio. Um push atualiza o código do tema e do plugin. Produtos, posts e mídias cadastrados na Hostinger continuam no banco e nos uploads da hospedagem; não reaplique o banco local para atualizar o layout.

Na versão 0.2.0, o plugin prepara a página/categoria Notícias e traz a notícia histórica confirmada da Linha Safira uma única vez, sem substituir edições posteriores. Novas matérias são criadas pelo WordPress, conforme [o guia de publicação](COMO_PUBLICAR_NOTICIAS.md).

As instruções de migração abaixo são referência para preparar uma nova instalação completa.

## Repositório

- HTTPS: `https://github.com/fortmetais/site-fort.git`
- SSH alternativo: `git@github.com:fortmetais/site-fort.git`

O repositório deve conter o tema `wp-content/themes/fort-metais`, o plugin `wp-content/plugins/fort-catalogo`, as traduções, os scripts de desenvolvimento e a documentação. O `.gitignore` exclui `.runtime`, dependências instaladas, senhas, configurações privadas, bancos, exportações e backups. A captura `referencias/site-atual.html` também é mantida apenas localmente por conter um identificador de sessão do site antigo.

Fotos que fazem parte do tema, incluindo o personagem do atendimento, são recursos do código. Imagens e PDFs enviados pela biblioteca Mídia ficam nos uploads do WordPress e precisam acompanhar a migração separadamente.

No domínio temporário atual, os pushes para `main` acionam a implantação automática. Em outra instalação sem esse vínculo, o envio ao GitHub sozinho não publica o site. Atualizações de código devem preservar o banco e os uploads utilizados pela equipe.

## O que já existe e deve ser preservado

Inventário conferido em 16/10/2026: 12 produtos publicados, 5 ambientes, 26 anexos cadastrados e 4 orçamentos privados. A biblioteca física contém 85 arquivos, incluindo os catálogos em português e inglês. Quantidades podem crescer com novos cadastros.

Além dos conteúdos, precisamos preservar:

- Linhas, ambientes, acabamentos, referências, especificações e variações de produto.
- Fotos dos produtos, PDFs, imagens dos ambientes e posições dos marcadores.
- Vínculos entre ambientes e produtos, e entre orçamentos e produtos.
- Contatos e apresentação (`fort_options`), conteúdo de representantes (`fort_representatives_options`) e revisões das traduções (`fort_i18n_overrides`).
- Páginas, imagem principal, links permanentes, usuários e permissões.

Os marcadores dos ambientes guardam IDs de produtos em dados serializados. A importação precisa preservar esses IDs ou remapear todos os vínculos; exportar somente os textos não garante essa associação.

## Preparar a migração

1. Criar um backup privado consistente do SQLite, dos uploads, do tema e do plugin. Manter o original local preservado.
2. Preparar e testar a conversão do banco em uma cópia. O ambiente local usa SQLite; a instalação WordPress da hospedagem utilizará MySQL/MariaDB. Um arquivo SQLite não é um dump MySQL importável.
3. Transferir as tabelas de conteúdo WordPress para um esquema MySQL nativo, preservando IDs, metadados e opções serializadas. As tabelas internas do adaptador SQLite não pertencem ao destino.
4. Copiar os uploads mantendo os caminhos dos arquivos e seus vínculos no banco.
5. Substituir `http://localhost:8080` pelo endereço temporário com uma ferramenta compatível com dados serializados, primeiro em modo de simulação. Alterar também `home` e `siteurl`.

O exportador padrão XML/WXR inclui conteúdos, mas não as opções completas do site nem os arquivos físicos da biblioteca. Ele não é suficiente sozinho para este projeto. Ainda é necessário validar a conversão SQLite → MySQL antes de importar na hospedagem.

## Criar o site temporário na Hostinger

1. Conferir se o plano possui uma vaga para um site separado.
2. Em **Sites > Criar/Adicionar site**, selecionar **WordPress** e começar com uma instalação vazia.
3. Na escolha do endereço, selecionar **Usar domínio temporário**. A Hostinger gera o endereço; o endereço efetivo depende da conta.
4. Instalar o tema e o plugin Fort, transferir os uploads e importar o banco já validado para MySQL.
5. Conferir HTTPS, acesso administrativo e links permanentes no novo endereço.

O site temporário deve ser uma instalação separada do site público atual. Configurar o ambiente de revisão para não ser indexado pelos buscadores; caso o acesso deva ser restrito à equipe, aplicar também proteção de acesso, pois impedir indexação não torna o site privado.

## Conferir antes de conectar o domínio definitivo

- Produtos e variações: fotos, referências, arejador/articulador e opções pendentes.
- Ambientes: fotos, marcadores e links para os produtos corretos.
- Português, inglês e espanhol: textos, menus e mensagens comerciais.
- Catálogos PDF: português e inglês, inclusive os links do rodapé.
- Institucional, compromissos, representantes e dados de contato.
- Pop-up do personagem e destino do WhatsApp.
- Formulário: registro do orçamento e integração de envio de e-mail, ainda pendente no ambiente local.
- Ausência de links e imagens dependentes de `localhost`.

Depois da revisão, conectar o domínio definitivo, atualizar os endereços de forma compatível com serialização e conferir redirecionamentos das URLs antigas. Preservar um backup anterior à troca.

## Referências oficiais

- [Adicionar WordPress e escolher domínio temporário na Hostinger](https://www.hostinger.com/support/6640339-how-to-add-a-wordpress-website-in-hostinger/)
- [Adicionar sites e conferir disponibilidade do plano](https://www.hostinger.com/support/1583214-how-to-add-a-website-in-hostinger/)
- [Enviar código local para o GitHub](https://docs.github.com/en/migrations/importing-source-code/using-the-command-line-to-import-source-code/adding-locally-hosted-code-to-github)
- [Conteúdo do exportador WordPress](https://developer.wordpress.org/cli/commands/export/)
- [Trocar URLs preservando dados serializados](https://developer.wordpress.org/cli/commands/search-replace/)
