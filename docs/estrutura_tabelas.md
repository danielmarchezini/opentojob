# Estrutura de Tabelas do OpenToJob

Este documento contém a descrição detalhada das tabelas do banco de dados do OpenToJob, com foco especial nas tabelas de avaliações que possuem estruturas específicas.

## Tabelas Principais

### Usuários (`usuarios`)
Tabela central que armazena todos os usuários do sistema.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| nome | VARCHAR(100) | Nome do usuário |
| email | VARCHAR(100) | Email único do usuário |
| senha | VARCHAR(255) | Senha criptografada |
| tipo | ENUM | Tipo de usuário: 'talento', 'empresa', 'admin' |
| status | ENUM | Status: 'pendente', 'ativo', 'inativo', 'bloqueado' |
| data_cadastro | DATETIME | Data de cadastro |
| ultimo_acesso | DATETIME | Data do último acesso |
| token_recuperacao | VARCHAR(100) | Token para recuperação de senha |
| expiracao_token | DATETIME | Data de expiração do token |
| foto_perfil | VARCHAR(255) | Caminho para a foto de perfil |
| telefone | VARCHAR(20) | Número de telefone |
| linkedin | VARCHAR(255) | URL do perfil do LinkedIn |
| website | VARCHAR(255) | URL do site pessoal |
| sobre | TEXT | Descrição sobre o usuário |
| data_atualizacao | DATETIME | Data da última atualização |

### Talentos (`talentos`)
Armazena informações específicas dos usuários do tipo "talento".

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| usuario_id | INT | Referência ao ID na tabela usuarios |
| cpf | VARCHAR(14) | CPF do talento |
| data_nascimento | DATE | Data de nascimento |
| genero | VARCHAR(20) | Gênero |
| endereco | VARCHAR(255) | Endereço completo |
| cidade | VARCHAR(100) | Cidade |
| estado | VARCHAR(2) | Estado (UF) |
| cep | VARCHAR(10) | CEP |
| pais | VARCHAR(50) | País (padrão: Brasil) |
| formacao_academica | TEXT | Formação acadêmica |
| experiencia_profissional | TEXT | Experiência profissional |
| habilidades | TEXT | Habilidades |
| idiomas | TEXT | Idiomas |
| pretensao_salarial | DECIMAL(10,2) | Pretensão salarial |
| disponibilidade | VARCHAR(50) | Disponibilidade |
| curriculo | VARCHAR(255) | Caminho para o currículo |
| carta_apresentacao | TEXT | Carta de apresentação |
| opentowork | TINYINT(1) | Indica se está disponível para trabalho (0/1) |
| opentowork_visibilidade | ENUM | Visibilidade: 'publico', 'privado' |
| linkedin | VARCHAR(255) | URL do LinkedIn (adicionado posteriormente) |
| github | VARCHAR(255) | URL do GitHub (adicionado posteriormente) |
| portfolio | VARCHAR(255) | URL do portfólio (adicionado posteriormente) |

### Empresas (`empresas`)
Armazena informações específicas dos usuários do tipo "empresa".

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| usuario_id | INT | Referência ao ID na tabela usuarios |
| cnpj | VARCHAR(18) | CNPJ da empresa |
| razao_social | VARCHAR(255) | Razão social |
| segmento | VARCHAR(100) | Segmento de atuação |
| tamanho | VARCHAR(50) | Tamanho da empresa |
| endereco | VARCHAR(255) | Endereço completo |
| cidade | VARCHAR(100) | Cidade |
| estado | VARCHAR(2) | Estado (UF) |
| cep | VARCHAR(10) | CEP |
| pais | VARCHAR(50) | País (padrão: Brasil) |
| logo | VARCHAR(255) | Caminho para o logo |
| descricao | TEXT | Descrição da empresa |
| ano_fundacao | INT | Ano de fundação |
| publicar_vagas | TINYINT(1) | Permissão para publicar vagas (0/1) |
| mostrar_perfil | BOOLEAN | Visibilidade do perfil (adicionado posteriormente) |
| descricao_curta | VARCHAR(255) | Descrição curta (adicionado posteriormente) |
| linkedin | VARCHAR(255) | URL do LinkedIn (adicionado posteriormente) |
| site | VARCHAR(255) | URL do site (adicionado posteriormente) |
| facebook | VARCHAR(255) | URL do Facebook (adicionado posteriormente) |
| instagram | VARCHAR(255) | URL do Instagram (adicionado posteriormente) |

### Vagas (`vagas`)
Armazena as vagas de emprego publicadas pelas empresas.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| titulo | VARCHAR(255) | Título da vaga |
| descricao | TEXT | Descrição da vaga |
| empresa_id | INT | Referência ao ID na tabela empresas |
| tipo | ENUM | Tipo: 'interna', 'externa' |
| status | ENUM | Status: 'pendente', 'aberta', 'fechada' |
| tipo_contrato | ENUM | Tipo de contrato: 'clt', 'pj', 'estagio', etc. |
| modelo_trabalho | ENUM | Modelo: 'presencial', 'remoto', 'hibrido' |
| nivel_experiencia | ENUM | Nível: 'estagiario', 'junior', 'pleno', etc. |
| cidade | VARCHAR(100) | Cidade |
| estado | VARCHAR(2) | Estado (UF) |
| pais | VARCHAR(50) | País (padrão: Brasil) |
| salario_min | DECIMAL(10,2) | Salário mínimo |
| salario_max | DECIMAL(10,2) | Salário máximo |
| mostrar_salario | TINYINT(1) | Mostrar salário (0/1) |
| requisitos | TEXT | Requisitos da vaga |
| responsabilidades | TEXT | Responsabilidades da vaga |
| beneficios | TEXT | Benefícios oferecidos |
| data_publicacao | DATETIME | Data de publicação |
| data_expiracao | DATETIME | Data de expiração |
| url_externa | VARCHAR(255) | URL externa (para vagas externas) |
| empresa_externa | VARCHAR(255) | Nome da empresa externa |
| visualizacoes | INT | Número de visualizações |
| candidaturas | INT | Número de candidaturas |
| destaque | TINYINT(1) | Vaga em destaque (0/1) |

## Tabelas de Avaliações

### Avaliações (`avaliacoes`)
Tabela original de avaliações, com estrutura que pode variar.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| talento_id | INT | Referência ao ID do talento avaliado |
| empresa_id | INT (opcional) | Referência ao ID da empresa avaliadora |
| nome_avaliador | VARCHAR(100) | Nome do avaliador |
| linkedin_avaliador | VARCHAR(255) | LinkedIn do avaliador |
| avaliacao | TEXT | Texto da avaliação (pode ser chamado 'texto' ou 'comentario') |
| pontuacao | INT | Pontuação da avaliação (pode ser chamado 'nota') |
| data_avaliacao | DATETIME | Data da avaliação (pode ser chamado 'data_criacao') |
| publica | TINYINT(1) | Indica se a avaliação é pública |
| aprovada | TINYINT(1) | Indica se a avaliação foi aprovada (campo antigo) |
| rejeitada | TINYINT(1) | Indica se a avaliação foi rejeitada (campo antigo) |
| status | ENUM | Status: 'pendente', 'aprovada', 'rejeitada' (campo novo) |
| email_avaliador | VARCHAR(255) | Email do avaliador (opcional) |

### Avaliações de Talentos (`avaliacoes_talentos`)
Tabela específica para avaliações de talentos, com estrutura mais padronizada.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| talento_id | INT | Referência ao ID do talento avaliado |
| empresa_id | INT (opcional) | Referência ao ID da empresa avaliadora |
| nome_avaliador | VARCHAR(255) | Nome do avaliador |
| email_avaliador | VARCHAR(255) | Email do avaliador |
| linkedin_avaliador | VARCHAR(255) | LinkedIn do avaliador |
| pontuacao | DECIMAL(3,1) | Pontuação da avaliação |
| avaliacao | TEXT | Texto da avaliação |
| data_avaliacao | DATETIME | Data da avaliação |
| status | ENUM | Status: 'pendente', 'aprovada', 'rejeitada' |
| publica | TINYINT(1) | Indica se a avaliação é pública |
| aprovada | TINYINT(1) | Indica se a avaliação foi aprovada |
| rejeitada | TINYINT(1) | Indica se a avaliação foi rejeitada |

### Avaliações de Empresas (`avaliacoes_empresas`)
Tabela para avaliações de empresas feitas por talentos.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT | Chave primária |
| empresa_id | INT | Referência ao ID da empresa avaliada |
| talento_id | INT | Referência ao ID do talento avaliador |
| vaga_id | INT (opcional) | Referência à vaga relacionada |
| nota | INT | Nota da avaliação |
| comentario | TEXT | Comentário da avaliação |
| data_avaliacao | DATETIME | Data da avaliação |
| status | ENUM | Status: 'pendente', 'aprovada', 'rejeitada' |

## Observações Importantes

1. **Sincronização entre Tabelas**: Existe um mecanismo de sincronização entre as tabelas `avaliacoes` e `avaliacoes_talentos` para garantir que as avaliações aprovadas apareçam no perfil do talento.

2. **Campos com Nomes Diferentes**: Alguns campos podem ter nomes diferentes entre as tabelas:
   - `pontuacao` (avaliacoes) → `nota` (avaliacoes_talentos)
   - `data_avaliacao` (avaliacoes) → `data_criacao` (em algumas consultas)
   - `avaliacao` (avaliacoes) → pode ser `texto` ou `comentario` em algumas versões

3. **Campos de Status**: Existem duas abordagens para o status das avaliações:
   - Campos separados: `aprovada` (boolean) e `rejeitada` (boolean)
   - Campo único: `status` (enum: 'pendente', 'aprovada', 'rejeitada')

4. **Verificação de Estrutura**: O sistema verifica a existência de colunas antes de realizar operações, garantindo compatibilidade com diferentes versões da estrutura do banco de dados.
