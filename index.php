<?php
// Iniciar buffer de saída para evitar erros de 'headers already sent'
ob_start();

// Iniciar sessão
session_start();

// Incluir configurações e funções principais
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/url_helper.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar se é uma URL antiga de vaga com ID e redirecionar para URL com slug
if (isset($_GET['route']) && $_GET['route'] === 'vaga' && isset($_GET['id']) && !isset($_GET['slug'])) {
    $vaga_id = (int)$_GET['id'];
    if ($vaga_id > 0) {
        $db = Database::getInstance();
        try {
            $vaga = $db->fetch("SELECT slug FROM vagas WHERE id = :id AND status = 'aberta'", ['id' => $vaga_id]);
            if ($vaga && !empty($vaga['slug'])) {
                // Redirecionar para a URL com slug
                redirect(url('vaga', ['slug' => $vaga['slug']]));
                exit;
            }
        } catch (Exception $e) {
            // Silenciar erro e continuar com a URL antiga
            error_log("Erro ao buscar slug da vaga: " . $e->getMessage());
        }
    }
}

// Verificar modo de manutenção
$db = Database::getInstance();
try {
    $manutencao = $db->fetch("SELECT valor FROM configuracoes WHERE chave = 'manutencao_ativo'");
    $modo_manutencao = ($manutencao && $manutencao['valor'] == 1);
    
    // Verificar se é uma rota administrativa ou de login
    $route = isset($_GET['route']) ? $_GET['route'] : 'inicio';
    $is_admin_route = (strpos($route, 'admin') !== false || $route === 'entrar' || $route === 'login');
    
    // Se o modo de manutenção estiver ativo e o usuário não for admin e não estiver tentando acessar o painel admin
    if ($modo_manutencao && !Auth::checkUserType('admin') && !$is_admin_route) {
        // Definir cabeçalho HTTP
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 3600'); // 1 hora
        
        // Incluir template de manutenção
        include 'templates/header.php';
        echo '<div class="container-wide maintenance-mode">
                <div class="maintenance-content">
                    <h1>Site em Manutenção</h1>
                    <p>Estamos realizando melhorias em nosso sistema. Por favor, tente novamente mais tarde.</p>
                    <p>Agradecemos sua compreensão.</p>';
        
        // Adicionar link para administradores
        echo '    <p class="mt-4"><small><a href="' . SITE_URL . '/entrar">Acesso administrativo</a></small></p>';
        
        echo '  </div>
              </div>';
        include 'templates/footer.php';
        exit;
    }
} catch (Exception $e) {
    // Em caso de erro, continuar normalmente
    // Registrar erro em log se necessário
    error_log("Erro ao verificar modo de manutenção: " . $e->getMessage());
}

// Definir rota padrão
$route = isset($_GET['route']) ? $_GET['route'] : 'inicio';

// Verificar se é uma rota de API
$is_api_route = (strpos($route, 'api_') === 0);

// Adicionar log para depuração em produção
if ($route === 'vaga') {
    error_log("Rota vaga acessada. Parâmetros: " . json_encode($_GET));
}

// Rotas de API
if (strpos($route, 'api_') === 0) {
    $is_api_route = true;
    header('Content-Type: application/json');
    
    switch ($route) {
        case 'api_salvar_meta_descricao':
            include __DIR__ . '/api/salvar_meta_descricao.php';
            break;
            
        case 'api_salvar_meta_descricoes':
            include __DIR__ . '/api/salvar_meta_descricoes.php';
            break;
            
        case 'gerar_sitemap':
            // Redirecionar para o arquivo sitemap_generator.php
            header('Location: ' . SITE_URL . '/sitemap_generator.php?save=1');
            exit;
            break;
            
        default:
            // API não encontrada
            echo json_encode([
                'success' => false,
                'message' => 'API não encontrada'
            ]);
            break;
    }
    
    // Encerrar execução após processar a API
    exit;
}

// Incluir cabeçalho apenas se não for uma rota de API
if (!$is_api_route) {
    include 'templates/header.php';
}

// Rotear para a página correspondente
switch ($route) {
    // Páginas públicas
    case 'inicio':
        include 'pages/inicio.php';
        break;
    case 'sobre':
        include 'pages/sobre.php';
        break;
    case 'termos':
        include 'pages/termos.php';
        break;
    case 'privacidade':
        include 'pages/privacidade.php';
        break;
    case 'cookies':
        include 'pages/cookies.php';
        break;
    case 'perfis_linkedin':
        include 'pages/perfis_linkedin.php';
        break;
    case 'indicar_perfil_linkedin':
        include 'pages/indicar_perfil_linkedin.php';
        break;
    case 'vagas':
        include 'pages/vagas.php';
        break;
    case 'vagas_externas':
        include 'pages/vagas_externas.php';
        break;
    case 'demandas':
        // Definir página atual para carregar o CSS específico
        $page = 'demandas';
        // Incluir conteúdo
        include 'pages/demandas.php';
        break;
    case 'visualizar_demanda':
        // Definir página atual para carregar o CSS específico
        $page = 'visualizar_demanda';
        // Incluir conteúdo
        include 'pages/visualizar_demanda.php';
        break;
    case 'demonstrar_interesse':
        // Definir página atual para carregar o CSS específico
        $page = 'demonstrar_interesse';
        // Incluir conteúdo
        include 'pages/demonstrar_interesse.php';
        break;
    case 'sobre_procura_se':
        // Definir página atual para carregar o CSS específico
        $page = 'sobre_procura_se';
        // Incluir conteúdo
        include 'pages/sobre_procura_se.php';
        break;
    case 'detalhes_vaga':
    case 'vaga':
    case 'vaga_detalhe':
        // Adicionar log para depuração
        error_log("Processando rota vaga. Parâmetros: " . json_encode($_GET));
        
        // Verificar se temos slug ou id
        if (isset($_GET['slug']) || isset($_GET['id'])) {
            include 'pages/vaga_detalhe.php';
        } else {
            // Redirecionar para a página de vagas se não houver slug ou id
            redirect('vagas');
        }
        break;
    case 'blog':
        include 'pages/blog.php';
        break;
    case 'artigo':
        include 'pages/artigo_detalhe.php';
        break;
    case 'contato':
        include 'pages/contato.php';
        break;
    case 'empresas':
        // Definir página atual para carregar o CSS específico
        $page = 'empresas';
        // Incluir conteúdo
        include 'pages/empresas.php';
        break;
    case 'talentos':
        // Definir página atual para carregar o CSS específico
        $page = 'talentos';
        // Incluir conteúdo
        include 'pages/talentos.php';
        break;
    case 'perfil_empresa':
        // Definir página atual para carregar o CSS específico
        $page = 'perfil_empresa';
        // Incluir conteúdo
        include 'pages/perfil_empresa.php';
        break;
    case 'avaliar_talento':
        // Definir página atual para carregar o CSS específico
        $page = 'avaliar_talento';
        // Incluir conteúdo
        include 'pages/avaliar_talento.php';
        break;
    case 'contato_talento_empresa':
        // Definir página atual para carregar o CSS específico
        $page = 'contato_talento_empresa';
        // Incluir conteúdo
        include 'pages/contato_talento_empresa.php';
        break;
    
    // Autenticação
    case 'login': // Alias para 'entrar' (compatibilidade)
    case 'entrar':
        include 'pages/entrar.php';
        break;
    case 'escolha_cadastro':
        include 'pages/escolha_cadastro.php';
        break;
    case 'cadastro_talento':
        include 'pages/cadastro_talento.php';
        break;
    case 'cadastro_empresa':
        include 'pages/cadastro_empresa.php';
        break;
    case 'sair':
        include 'pages/sair.php';
        break;
    case 'recuperar_senha':
        include 'pages/recuperar_senha.php';
        break;
    case 'redefinir_senha':
        include 'pages/redefinir_senha.php';
        break;
    case 'api_alterar_senha':
        require_once 'api/alterar_senha.php';
        break;
    case 'api_candidatura_detalhe':
        require_once 'api/candidatura_detalhe.php';
        break;
        
    // Admin - Excluir vaga
    case 'admin_excluir_vaga':
        // Verificar se o usuário é administrador
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }
        require_once 'api/excluir_vaga.php';
        break;
    
    // Área do Talento
    case 'painel_talento':
        if (Auth::checkUserType('talento') || Auth::checkUserType('admin')) {
            include 'pages/talento/painel.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'perfil_talento':
        // Verificar se foi especificado um ID de talento na URL
        $talento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (Auth::checkUserType('talento')) {
            // Se for um talento logado e estiver acessando seu próprio perfil (sem ID ou ID igual ao do usuário logado)
            if ($talento_id === 0 || $talento_id == $_SESSION['user_id']) {
                include 'pages/talento/perfil.php';
            } else {
                // Se for um talento visualizando o perfil de outro talento
                include 'pages/perfil_talento.php';
            }
        } else if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            // Se for uma empresa ou administrador, mostrar o perfil público do talento
            include 'pages/perfil_talento.php';
        } else {
            // Se não for talento, empresa ou admin, mostrar página personalizada
            include 'pages/acesso_perfil_talento.php';
        }
        break;
    case 'perfil_talento_editar':
        if (Auth::checkUserType('talento') || Auth::checkUserType('admin')) {
            include 'pages/talento/perfil_editar.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'estatisticas_talento':
        if (Auth::checkUserType('talento') || Auth::checkUserType('admin')) {
            include 'pages/talento/estatisticas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'minhas_candidaturas':
        if (Auth::checkUserType('talento') || Auth::checkUserType('admin')) {
            include 'pages/talento/candidaturas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'mensagens_talento':
        if (Auth::checkUserType('talento') || Auth::checkUserType('admin')) {
            include 'pages/talento/mensagens.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_demandas':
        // Verificar se o usuário está logado e é uma empresa
        if (!Auth::checkUserType('empresa')) {
            header('Location: ' . SITE_URL . '/?route=entrar');
            exit;
        }
        include 'pages/empresa/gerenciar_demandas.php';
        break;
        
    case 'talentos_favoritos':
        // Verificar se o usuário está logado e é uma empresa
        if (!Auth::checkUserType('empresa')) {
            header('Location: ' . SITE_URL . '/?route=entrar');
            exit;
        }
        include 'pages/empresa/talentos_favoritos.php';
        break;
        
    // Área da Empresa
    case 'mensagens_empresa':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/mensagens_empresa.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    // Rota para exclusão de conta de empresa
    case 'excluir_conta_empresa':
        if (Auth::checkUserType('empresa')) {
            include 'pages/empresa/excluir_conta.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'perfil_empresa_editar':
        if (Auth::checkUserType('empresa')) {
            include 'pages/empresa/perfil_editar.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'estatisticas_empresa':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/estatisticas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_vagas':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/gerenciar_vagas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_demandas':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/gerenciar_demandas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'criar_demanda':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/criar_demanda.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'editar_demanda':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/editar_demanda.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'visualizar_interessados':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/visualizar_interessados.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'editar_vaga':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/editar_vaga.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'nova_vaga':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/nova_vaga.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'empresa/candidaturas':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/candidaturas.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'convidar_entrevista':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/convidar_entrevista.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'buscar_talentos':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/empresa/buscar_talentos.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'mensagens_empresa':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/mensagens_empresa.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'contatar_talento':
        if (Auth::checkUserType('empresa') || Auth::checkUserType('admin')) {
            include 'pages/contatar_talento.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'mensagens_talento':
        if (Auth::checkUserType('talento')) {
            include 'pages/mensagens_talento.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    // Rota para exclusão de conta de talento
    case 'excluir_conta_talento':
        if (Auth::checkUserType('talento')) {
            include 'pages/talento/excluir_conta.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'contatar_empresa':
        if (Auth::checkUserType('talento')) {
            include 'pages/contatar_empresa.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    
    // Área do Administrador
    case 'painel_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'dashboard';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/dashboard.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_usuarios':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_usuarios';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_usuarios.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'aprovar_cadastros':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'aprovar_cadastros';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/aprovar_cadastros.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_vagas_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_vagas';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_vagas.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'cadastrar_vaga_externa':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'cadastrar_vaga_externa';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/cadastrar_vaga_externa.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_blog':
    case 'gerenciar_blog_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_blog';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_blog.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_talentos_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_talentos';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_talentos.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_empresas_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_empresas';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_empresas.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'relatorios':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'relatorios';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/relatorios.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_avaliacoes_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_avaliacoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_avaliacoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_usuarios_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_usuarios';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_usuarios.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_vagas_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_vagas';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_vagas.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'processar_vaga_admin':
        if (Auth::checkUserType('admin')) {
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Processar ações de vagas
            include 'admin/pages/processar_vaga.php';
        } else {
            // Retornar erro em formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso restrito. Faça login como administrador.'
            ]);
        }
        break;
        
    case 'aprovar_cadastros_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'aprovar_cadastros';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/aprovar_cadastros.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'cadastrar_vaga_externa_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'cadastrar_vaga_externa';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/cadastrar_vaga_externa.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_blog_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_blog';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_blog.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_depoimentos_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_depoimentos';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_depoimentos.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_equipe_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_equipe';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_equipe.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_emails_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_emails';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_emails.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'processar_email_admin':
        if (Auth::checkUserType('admin')) {
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Processar ações de modelos de e-mail
            include 'admin/pages/processar_email.php';
        } else {
            // Retornar erro em formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso restrito. Faça login como administrador.'
            ]);
        }
        break;
        
    case 'relatorios_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'relatorios';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/relatorios.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'diagnostico_emails':
        if (Auth::checkUserType('admin')) {
            // Incluir a página de diagnóstico diretamente, sem layout
            include 'admin/pages/diagnostico_emails.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'testar_email':
        if (Auth::checkUserType('admin')) {
            // Incluir a página de teste de e-mail
            include 'admin/testar_email.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'configurar_smtp':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configurar_smtp';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/configurar_smtp.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'configuracoes_seo_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configuracoes_seo';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/configuracoes_seo.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'instalar_seo_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'instalar_seo';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/instalar_seo.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'configuracoes_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configuracoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/configuracoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'estatisticas_interacoes':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'estatisticas_interacoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/estatisticas_interacoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'admin/gerenciar_equipe':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_equipe';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'pages/admin/gerenciar_equipe.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_contratacoes':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_contratacoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_contratacoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_reportes':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_reportes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_reportes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    // API Routes
    case 'api_vaga_detalhe':
        include 'api/vaga_detalhe.php';
        break;
        
    case 'api_artigo_detalhe':
        include 'pages/api_artigo_detalhe.php';
        break;
        
    case 'api_talento_detalhe':
        include 'api/talento_detalhe.php';
        break;
        
    case 'api_empresa_detalhe':
        include 'api/empresa_detalhe.php';
        break;
        
    case 'api_vaga_detalhe':
        include 'api/vaga_detalhe.php';
        break;
        
    case 'api_avaliacao_detalhe':
        include 'api/avaliacao_detalhe.php';
        break;
        
    case 'api_alterar_senha':
        include 'api/alterar_senha.php';
        break;
        
    case 'api_mensagem_detalhe':
        include 'api/mensagem_detalhe.php';
        break;
        
    case 'api_salvar_meta_descricao':
        $is_api_route = true;
        include 'api/salvar_meta_descricao.php';
        break;
        
    case 'api_salvar_meta_descricoes':
        $is_api_route = true;
        include 'api/salvar_meta_descricoes.php';
        break;
    
    // Páginas administrativas que precisam de redirecionamento especial
    case 'gerenciar_depoimentos_admin':
    case 'gerenciar_equipe_admin':
    case 'configurar_smtp':
    case 'gerenciar_blog_admin':
    case 'gerenciar_webhooks_admin':
    case 'gerenciar_contratacoes':
    case 'gerenciar_reportes':
    case 'gerenciar_avaliacoes_admin':
    case 'estatisticas_interacoes':
    case 'configuracoes_admin':
    case 'configuracoes_monetizacao_admin':
        include 'pages/admin_redirect.php';
        break;
        
    // Rota para página sobre (URL amigável)
    case 'sobre':
        include 'pages/sobre.php';
        break;
        
    // Rota para testar envio de e-mail
    case 'testar_email':
        include __DIR__ . '/admin/testar_email.php';
        break;
        
    // Rota para configurar SMTP
    case 'configurar_smtp':
        include __DIR__ . '/admin/pages/configurar_smtp.php';
        break;
        
    // Rotas para páginas de FAQ
    case 'faq_empresa':
        include 'pages/faq_empresa.php';
        break;
        
    case 'faq_talento':
        include 'pages/faq_talento.php';
        break;
        
    // Rota para exclusão de dados (LGPD)
    case 'exclusao_dados':
        if (isset($_SESSION['user_id'])) {
            include 'pages/exclusao_dados.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'exclusao_confirmada':
        include 'pages/exclusao_confirmada.php';
        break;
        
    // Rota para informar contratação
    case 'informar_contratacao':
        if (Auth::checkUserType('talento')) {
            include 'pages/talento/informar_contratacao.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    // Rota para gerenciar webhooks
    case 'gerenciar_webhooks_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_webhooks';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/gerenciar_webhooks.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'configuracoes_monetizacao_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configuracoes_monetizacao';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/configuracoes_monetizacao.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_newsletter':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_newsletter';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_newsletter.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'enviar_newsletter':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'enviar_newsletter';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/enviar_newsletter.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_perfis_linkedin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_perfis_linkedin';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/gerenciar_perfis_linkedin.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'adicionar_perfil_linkedin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'adicionar_perfil_linkedin';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/adicionar_perfil_linkedin.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'editar_perfil_linkedin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'editar_perfil_linkedin';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/editar_perfil_linkedin.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
    case 'gerenciar_indicacoes_perfis':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_indicacoes_perfis';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            include 'admin/pages/gerenciar_indicacoes_perfis.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'configuracoes_site_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configuracoes_site';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir página de configurações do site
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/configuracoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_blog_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_blog';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_blog.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_contratacoes':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_contratacoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_contratacoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_reportes':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_reportes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_reportes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_avaliacoes_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_avaliacoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_avaliacoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_cache_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_cache';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_cache.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_talentos_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_talentos';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_talentos.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'gerenciar_usuarios_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'gerenciar_usuarios';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/gerenciar_usuarios.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    case 'configuracoes_admin':
        if (Auth::checkUserType('admin')) {
            // Definir página atual para carregar o CSS específico
            $page = 'configuracoes';
            // Incluir funções administrativas
            include 'admin/includes/admin_functions.php';
            // Incluir cabeçalho, barra lateral e conteúdo
            include 'admin/includes/header.php';
            include 'admin/includes/sidebar.php';
            include 'admin/pages/configuracoes.php';
            include 'admin/includes/footer.php';
        } else {
            include 'pages/acesso_negado.php';
        }
        break;
        
    // Rota padrão (página inicial)
    default:
        include 'pages/inicio.php';
        break;
}

// Carregar rodapé apenas se não for uma rota de API
if (!isset($is_api_route) || !$is_api_route) {
    include 'templates/footer.php';
}

// Liberar o buffer de saída
ob_end_flush();
?>
