# Site Fort Metais em WordPress

Versão local do redesign de [www.fortmetais.com.br](https://www.fortmetais.com.br/), com tema próprio, catálogo e painel administrativo. Desenvolvida em 14 de outubro de 2026 a partir do HTML fornecido e de imagens públicas da própria empresa.

- **Site:** http://localhost:8080/
- **Painel:** http://localhost:8080/wp-admin/
- **Acesso local:** usuário e senha no arquivo `.runtime/acesso-local.json`.

## Executar

Abra `INICIAR_FORT.cmd` nesta pasta ou execute:

```powershell
npm run dev
```

Mantenha o processo aberto enquanto usa o site. Para encerrar, pressione `Ctrl+C`. Os dados permanecem salvos após o reinício.

Em uma nova cópia do projeto, instale antes as dependências com `npm ci`. O ambiente requer Node.js 22 ou superior e utiliza o WordPress Playground, com PHP 8.3 e SQLite local.

## Recursos disponíveis

- Página inicial adaptada à identidade Fort Metais, com versões para celular e computador.
- Doze produtos cadastrados, com linhas, ambientes, acabamentos e opções de cor ou bica em modelos de teste.
- Cinco ambientes com marcadores associados aos produtos presentes nas fotos.
- Notícias em `/noticias/`, com destaque, paginação e matérias cadastradas em **Posts** na categoria **Notícias**.
- Português (BR), inglês e espanhol, com revisão das traduções pelo painel.
- Catálogos PDF em português e inglês, selecionados conforme o idioma do site.
- Pop-up de atendimento com o personagem da Fort e acesso ao WhatsApp.
- Busca por nome ou referência, filtros combinados e página de produto.
- Cadastro, edição, rascunho e publicação de produtos pelo WordPress.
- Registro de solicitações de orçamento no painel, associado ao produto.
- Cadastros de representantes e consulta por UF, com alternativa de contato comercial.
- Configuração de contatos, título, descrição e imagem da abertura em **Configurações > Fort Metais**.

## Documentação

- [Como usar e manter o ambiente local](docs/COMO_USAR_LOCAL.md)
- [GitHub, dados e publicação em domínio temporário](docs/PUBLICACAO.md)
- [Passo a passo para publicar notícias](docs/COMO_PUBLICAR_NOTICIAS.md)
- [Planejamento do projeto](docs/PLANEJAMENTO.md)
- [Auditoria inicial](docs/AUDITORIA_INICIAL.md)
- [Prévia da página inicial no visual do HTML](docs/previews/inicio-referencia.jpg)
- [Prévia no celular](docs/previews/celular-referencia.jpg)
- [HTML original preservado](referencias/fort_metais_modernizado.original.html)

## Organização

O tema fica em `wp-content/themes/fort-metais` e o plugin em `wp-content/plugins/fort-catalogo`. O WordPress e o banco locais ficam em `.runtime/wordpress`; as imagens cadastradas ficam em sua biblioteca de mídia. Esses dados de execução estão fora do versionamento.

Esta entrega é uma versão local com parte do catálogo. A importação dos demais produtos, a revisão das especificações pendentes, o cadastro dos representantes e a configuração de envio de e-mail são próximas etapas. O formulário atual registra os pedidos no painel; não envia e-mails.

O repositório é `https://github.com/fortmetais/site-fort.git`, branch `main`, e o site temporário está em `https://lime-lion-509099.hostingersite.com/`. A implantação automática na raiz foi desativada porque removia o core WordPress não versionado. Atualizações devem enviar apenas o tema e o plugin. Posts, produtos e imagens cadastrados pelo painel ficam no banco e nos uploads da respectiva instalação WordPress e não são transferidos pelo Git.

Para conferir as páginas e os fluxos principais com o servidor aberto, execute `npm run check`.

Para conferir a seção de notícias, execute `npm run check:news`. A versão 0.2.0 prepara a página e a categoria Notícias uma única vez, preserva as edições posteriores e mantém os posts de exemplo fora dessa seção. A integração com feeds sociais permanece para uma próxima etapa.

## Apresentação baseada no HTML

A página inicial segue a referência enviada: banner de ambiente com texto centralizado, fonte Inter, azul e degradê no título, botões arredondados, quatro cards de linhas em duas fileiras, produtos em destaque, institucional escuro e rodapé em quatro colunas. As fotografias de ambiente são ilustrativas e foram obtidas dos mesmos endereços do HTML. As fotos do catálogo continuam associadas aos produtos reais. Os estilos dessa apresentação ficam em `assets/reference.css`, dentro do tema.
