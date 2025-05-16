# Correção do Erro de Depreciação do htmlspecialchars() no PHP 8.1+

## Problema

Em produção, estava ocorrendo o seguinte erro:

```
Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in /www/wwwroot/opentojob.com.br/pages/inicio.php on line 277
```

Este erro ocorre porque no PHP 8.1+, passar valores nulos para a função `htmlspecialchars()` é considerado depreciado. A função espera uma string como primeiro parâmetro.

## Solução Implementada

Foram criados dois scripts para corrigir automaticamente este problema em todo o código:

1. `fix_all_htmlspecialchars.php` - Corrige os arquivos na pasta `pages/`
2. `fix_admin_htmlspecialchars.php` - Corrige os arquivos nas pastas `admin/` e `admin/pages/`

A solução implementada foi converter explicitamente os valores para string usando `(string)` antes de passá-los para `htmlspecialchars()`, por exemplo:

```php
// Código original (com problema)
echo htmlspecialchars($variavel);

// Código corrigido
echo htmlspecialchars((string)$variavel);
```

## Resultados da Correção

### Arquivos Corrigidos em `pages/`

Total de 26 arquivos corrigidos:

- avaliar_talento.php (8 ocorrências)
- buscar_talentos.php (9 ocorrências)
- cadastro_empresa.php (4 ocorrências)
- cadastro_talento.php (4 ocorrências)
- candidatar_vaga.php (10 ocorrências)
- contatar_empresa.php (6 ocorrências)
- contatar_talento.php (7 ocorrências)
- contato.php (16 ocorrências)
- demandas.php (11 ocorrências)
- demonstrar_interesse.php (8 ocorrências)
- empresas.php (9 ocorrências)
- entrar.php (1 ocorrência)
- indicar_perfil_linkedin.php (10 ocorrências)
- inicio.php (11 ocorrências)
- mensagens_empresa.php (10 ocorrências)
- mensagens_talento.php (7 ocorrências)
- perfil_empresa.php (22 ocorrências)
- perfil_talento.php (14 ocorrências)
- perfis_linkedin.php (8 ocorrências)
- recuperar_senha.php (1 ocorrência)
- sobre.php (6 ocorrências)
- talentos.php (9 ocorrências)
- vaga_detalhe.php (22 ocorrências)
- vagas.php (16 ocorrências)
- vagas_externas.php (13 ocorrências)
- visualizar_demanda.php (13 ocorrências)

### Arquivos Corrigidos em `admin/` e `admin/pages/`

Total de 41 arquivos corrigidos, incluindo:

- ativar_todos_registros_referencia.php (6 ocorrências)
- debug_selects.php (14 ocorrências)
- processar_newsletter.php (2 ocorrências)
- gerenciar_avaliacoes.php (9 ocorrências)
- gerenciar_contratacoes.php (18 ocorrências)
- gerenciar_vagas.php (10 ocorrências)
- e outros...

## Como Aplicar em Produção

Para aplicar a correção em produção, siga estes passos:

1. Faça backup dos arquivos originais no servidor
2. Faça upload dos arquivos corrigidos para o servidor de produção
3. Teste o site para garantir que o erro foi corrigido e que nenhuma funcionalidade foi afetada

## Prevenção de Problemas Futuros

Para evitar este tipo de erro no futuro, recomenda-se:

1. Sempre verificar se uma variável pode ser nula antes de passá-la para `htmlspecialchars()`
2. Usar `(string)` para converter explicitamente valores para string quando necessário
3. Considerar o uso de uma função auxiliar que já inclua a verificação de nulos, por exemplo:

```php
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
```

## Arquivos de Correção

Os scripts de correção estão disponíveis em:

- `c:\xampp\htdocs\open2w\fix_all_htmlspecialchars.php`
- `c:\xampp\htdocs\open2w\fix_admin_htmlspecialchars.php`

Estes scripts fazem backup dos arquivos originais antes de modificá-los e mostram um relatório detalhado das alterações realizadas.
