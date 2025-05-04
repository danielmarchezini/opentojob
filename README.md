# OpenToJob - Conectando talentos prontos a oportunidades imediatas

OpenToJob é uma plataforma especializada para conectar empresas e talentos profissionais, com foco em profissionais que estão imediatamente disponíveis para novas oportunidades. A plataforma permite que profissionais sinalizem sua disponibilidade para novas oportunidades e que empresas encontrem talentos qualificados que estão ativamente buscando novas posições.

## Requisitos

- PHP 8.1+
- MySQL 8.0+
- Servidor web (Apache, Nginx)
- Extensão PDO para PHP
- Git

## Ambientes

O projeto está configurado para funcionar em dois ambientes:

### Desenvolvimento
- URL: http://localhost/open2w
- Banco de dados: open2w (localhost)
- Usuário BD: root (sem senha)

### Produção
- URL: https://opentojob.com.br
- Banco de dados: sql_opentojob_co
- Usuário BD: sql_opentojob_co

## Instalação em Ambiente de Desenvolvimento

1. Clone o repositório para a pasta do seu servidor web:
   ```
   git clone https://github.com/[seu-usuario]/opentojob.git c:\xampp\htdocs\open2w
   ```
2. Crie um banco de dados MySQL chamado `open2w`
3. Importe o arquivo `database.sql` para criar a estrutura do banco de dados
4. Acesse a aplicação pelo navegador: http://localhost/open2w

## Instalação em Ambiente de Produção

1. Acesse o servidor via SSH
2. Navegue até o diretório web:
   ```
   cd /var/www/html/opentojob.com.br
   ```
3. Clone o repositório:
   ```
   git clone https://github.com/[seu-usuario]/opentojob.git .
   ```
4. Importe o banco de dados se necessário
5. Verifique as permissões dos diretórios de upload:
   ```
   chmod -R 755 uploads/
   ```

## Estrutura do Projeto

```
opentojob/
├── assets/           # Arquivos estáticos (CSS, JS, imagens)
├── config/           # Arquivos de configuração
├── includes/         # Classes e funções do sistema
├── pages/            # Páginas do site
│   ├── admin/        # Páginas da área administrativa
│   ├── empresa/      # Páginas da área da empresa
│   └── talento/      # Páginas da área do talento
├── templates/        # Templates reutilizáveis (header, footer)
├── uploads/          # Diretório para uploads de arquivos
│   ├── curriculos/   # Currículos dos talentos
│   ├── empresas/     # Logos das empresas
│   └── perfil/       # Fotos de perfil
├── index.php         # Arquivo principal (roteador)
├── database.sql      # Estrutura do banco de dados
└── README.md         # Documentação
```

## Fluxo de Trabalho com Git

### Desenvolvimento de Novas Funcionalidades
1. Crie uma nova branch a partir da main:
   ```
   git checkout -b feature/nome-da-funcionalidade
   ```
2. Faça as alterações necessárias
3. Commit e push para o repositório:
   ```
   git add .
   git commit -m "Descrição das alterações"
   git push origin feature/nome-da-funcionalidade
   ```
4. Crie um Pull Request para a branch main

### Atualização do Ambiente de Produção
1. Acesse o servidor via SSH
2. Navegue até o diretório do projeto:
   ```
   cd /var/www/html/opentojob.com.br
   ```
3. Faça pull das alterações da branch main:
   ```
   git pull origin main
   ```

## Tipos de Usuários

### Talentos (Profissionais)
- Podem criar perfil profissional completo
- Podem ativar/desativar status #opentowork
- Podem pesquisar e se candidatar a vagas
- Podem gerenciar candidaturas
- Podem receber mensagens de empresas
- Podem responder a mensagens recebidas via sistema de chat

### Empresas
- Podem criar perfil da empresa
- Podem publicar vagas
- Podem buscar talentos
- Podem enviar mensagens para talentos
- Podem gerenciar candidaturas recebidas
- Podem criar demandas de talentos

### Administradores
- Podem gerenciar todos os usuários
- Podem aprovar/rejeitar cadastros
- Podem gerenciar conteúdo do blog
- Podem visualizar estatísticas
- Podem configurar parâmetros do sistema
