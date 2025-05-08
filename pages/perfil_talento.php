<?php
// Incluir arquivo para registrar interações
require_once 'includes/registrar_interacao.php';

// Verificar se o ID do talento foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirecionar para a página de talentos se nenhum ID for fornecido
    header("Location: " . SITE_URL . "/?route=talentos");
    exit;
}

// Obter o ID do talento
$talento_id = (int)$_GET['id'];

// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);

// Verificar se o usuário atual é uma empresa
$is_empresa = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'empresa';

// Verificar se o talento já está nos favoritos da empresa
$is_favorito = false;
if ($is_empresa) {
    $empresa_id = $_SESSION['user_id'];
    try {
        $db = Database::getInstance();
        $is_favorito = $db->fetchColumn("
            SELECT COUNT(*) FROM talentos_favoritos 
            WHERE empresa_id = :empresa_id AND talento_id = :talento_id
        ", [
            'empresa_id' => $empresa_id,
            'talento_id' => $talento_id
        ]) > 0;
    } catch (PDOException $e) {
        // Se a tabela ainda não existir, não é um erro crítico
        error_log("Erro ao verificar favoritos: " . $e->getMessage());
    }
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o talento existe e está ativo
$talento = $db->fetch("
    SELECT u.id, u.nome, u.email, u.data_cadastro, t.foto_perfil, 
           t.profissao, t.nivel, t.experiencia, t.curriculo, t.formacao, t.resumo, t.habilidades,
           t.areas_interesse, t.github, t.portfolio, t.telefone, t.linkedin, t.website,
           t.carta_apresentacao, t.experiencia_profissional, t.formacao
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id AND u.tipo = 'talento' AND u.status = 'ativo'
", [
    'id' => $talento_id
]);

// Se o talento não existir, não estiver ativo ou não tiver perfil público, redirecionar
if (!$talento) {
    $_SESSION['flash_message'] = "Perfil de talento não encontrado ou não disponível publicamente.";
    $_SESSION['flash_type'] = "warning";
    
    // Usar JavaScript para redirecionar em vez de header()
    echo "<script>window.location.href = '" . SITE_URL . "/?route=talentos';</script>";
    exit;
}

// Registrar visualização do perfil se houver um usuário logado
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $talento_id) {
    // Registrar a visualização do perfil
    registrarVisualizacaoPerfil($_SESSION['user_id'], $talento_id);
}

// Verificar se o usuário atual é uma empresa para mostrar informações de contato
$is_empresa = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'empresa';

// Processar habilidades do talento a partir do campo habilidades
$habilidades_array = [];
if (!empty($talento['habilidades'])) {
    // Dividir a string de habilidades por vírgulas
    $habilidades_lista = explode(',', $talento['habilidades']);
    
    // Processar cada habilidade
    foreach ($habilidades_lista as $habilidade) {
        $habilidade = trim($habilidade);
        if (!empty($habilidade)) {
            $habilidades_array[] = [
                'nome' => $habilidade,
                'nivel' => 'Intermediário' // Valor padrão para nível
            ];
        }
    }
}

// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Perfil do Talento</h1>
                <p class="lead">Conheça mais sobre este profissional</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=talentos">Talentos</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($talento['nome']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Voltar para Talentos
                </a>
            </div>
        </div>
    </div>
</div>

<section class="section-perfil-talento py-5">
    <div class="container">
        <div class="row">
            <!-- Coluna da esquerda - Informações principais -->
            <div class="col-lg-4 mb-4">
                <div class="card perfil-card">
                    <div class="card-body text-center">
                        <div class="perfil-avatar mb-3">
                            <?php if (!empty($talento['foto_perfil'])): ?>
                                <?php 
                                $foto_path = __DIR__ . '/../uploads/perfil/' . $talento['foto_perfil'];
                                $foto_url = file_exists($foto_path) ? 
                                    'uploads/perfil/' . $talento['foto_perfil'] : 
                                    'assets/img/default-avatar.jpg'; 
                                ?>
                                <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($talento['nome']); ?>" class="rounded-circle img-fluid" style="width: 180px; height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-primary text-white" style="width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; font-size: 72px;">
                                    <?php echo strtoupper(substr($talento['nome'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-center align-items-center">
                            <h3 class="card-title mb-0"><?php echo htmlspecialchars($talento['nome']); ?></h3>
                            <button type="button" class="btn btn-link text-muted ms-2" style="font-size: 0.8rem; padding: 0;" data-bs-toggle="modal" data-bs-target="#reportarModal" title="Reportar perfil">
                                <i class="fas fa-flag"></i>
                            </button>
                        </div>
                        <h5 class="text-muted mb-1"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissional'); ?></h5>
                        <?php if (!empty($talento['nivel'])): ?>
                        <span class="badge bg-info text-dark mb-3"><?php echo htmlspecialchars($talento['nivel']); ?></span>
                        <?php endif; ?>
                        
                        <div class="perfil-info mb-4">
                            <p class="mb-2">
                                <i class="fas fa-briefcase me-2"></i>
                                <strong>Experiência:</strong> 
                                <?php echo !empty($talento['experiencia']) ? $talento['experiencia'] . ' anos' : 'Não informado'; ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <strong>Membro desde:</strong> 
                                <?php echo date('d/m/Y', strtotime($talento['data_cadastro'])); ?>
                            </p>
                            
                            <?php if ($is_empresa && !empty($talento['email'])): ?>
                                <p class="mb-2">
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>E-mail:</strong> 
                                    <a href="mailto:<?php echo $talento['email']; ?>"><?php echo $talento['email']; ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_empresa): ?>
                            <div class="perfil-acoes">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Ações</h5>
                                    <button id="btn-favoritar" class="btn-favoritar <?php echo $is_favorito ? 'favoritado' : ''; ?>" data-talento-id="<?php echo $talento_id; ?>" data-acao="<?php echo $is_favorito ? 'remover' : 'adicionar'; ?>">
                                        <i class="<?php echo $is_favorito ? 'fas' : 'far'; ?> fa-heart"></i>
                                        <span class="favorito-tooltip"><?php echo $is_favorito ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?></span>
                                    </button>
                                </div>
                                
                                <?php if (!empty($talento['curriculo'])): ?>
                                    <a href="<?php echo SITE_URL; ?>/uploads/curriculos/<?php echo $talento['curriculo']; ?>" class="btn btn-primary btn-lg w-100 mb-3" target="_blank">
                                        <i class="fas fa-file-pdf me-2"></i> BAIXAR CURRÍCULO
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo SITE_URL; ?>/?route=contatar_talento&id=<?php echo $talento['id']; ?>" class="btn btn-accent btn-sm w-100">
                                    <i class="fas fa-envelope me-2"></i> Entrar em Contato
                                </a>
                            </div>
                        <?php elseif (!$is_logged_in): ?>
                            <div class="alert alert-info" role="alert">
                                <small>Para ver informações de contato e currículo, <a href="<?php echo SITE_URL; ?>/?route=entrar">faça login</a> como empresa.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                

            </div>
            
            <!-- Coluna da direita - Apresentação e detalhes -->
            <div class="col-lg-8">
                <?php if (!empty($habilidades_array)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Habilidades</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap">
                            <?php foreach ($habilidades_array as $habilidade): ?>
                                <span class="badge bg-primary me-2 mb-2 p-2">
                                    <?php echo htmlspecialchars($habilidade['nome']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Carta de Apresentação</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($talento['carta_apresentacao'])): ?>
                            <div class="apresentacao">
                                <?php echo nl2br(htmlspecialchars($talento['carta_apresentacao'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Este talento ainda não adicionou uma carta de apresentação ao seu perfil.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Seção de formação acadêmica -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Formação Acadêmica</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($talento['formacao'])): ?>
                            <div class="formacao-academica">
                                <?php echo nl2br(htmlspecialchars($talento['formacao'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p class="mb-0">Este talento ainda não adicionou formação acadêmica ao seu perfil.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Resumo Profissional</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($talento['resumo'])): ?>
                            <div class="apresentacao">
                                <?php 
                                $resumo = htmlspecialchars($talento['resumo']);
                                $resumo_curto = substr($resumo, 0, 300);
                                $tem_mais = strlen($resumo) > 300;
                                ?>
                                <div id="resumo-curto">
                                    <?php echo nl2br($resumo_curto); ?>
                                    <?php if ($tem_mais): ?>
                                        <span id="reticencias">...</span>
                                        <button id="btn-ler-mais" class="btn btn-sm btn-outline-primary mt-2">Ler mais</button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($tem_mais): ?>
                                <div id="resumo-completo" style="display: none;">
                                    <?php echo nl2br($resumo); ?>
                                    <button id="btn-ler-menos" class="btn btn-sm btn-outline-primary mt-2">Ler menos</button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const btnLerMais = document.getElementById('btn-ler-mais');
                                const btnLerMenos = document.getElementById('btn-ler-menos');
                                const resumoCurto = document.getElementById('resumo-curto');
                                const resumoCompleto = document.getElementById('resumo-completo');
                                
                                if (btnLerMais) {
                                    btnLerMais.addEventListener('click', function() {
                                        resumoCurto.style.display = 'none';
                                        resumoCompleto.style.display = 'block';
                                    });
                                }
                                
                                if (btnLerMenos) {
                                    btnLerMenos.addEventListener('click', function() {
                                        resumoCompleto.style.display = 'none';
                                        resumoCurto.style.display = 'block';
                                    });
                                }
                            });
                            </script>
                        <?php else: ?>
                            <p class="text-muted">Este talento ainda não adicionou um resumo ao seu perfil.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Seção de experiência profissional -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Experiência Profissional</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($talento['experiencia'])): ?>
                            <div class="experiencia-profissional">
                                <?php echo nl2br(htmlspecialchars($talento['experiencia'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p class="mb-0">Este talento ainda não adicionou experiências profissionais ao seu perfil.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                

                
                <!-- Seção de avaliações e recomendações -->
                <?php
                // Verificar se a tabela de avaliações existe antes de consultar
                $avaliacoes = [];
                $media_pontuacao = 0;
                $total_avaliacoes = 0;
                
                // Verificar se a tabela existe antes de consultar
                try {
                    // Tentar obter avaliações aprovadas e públicas para este talento
                    // Verificar a estrutura da tabela para usar o nome correto da coluna
                    try {
                        $avaliacoes = $db->fetchAll("SELECT * FROM avaliacoes_talentos WHERE talento_id = :talento_id AND status = 'aprovada' AND publica = 1 ORDER BY data_avaliacao DESC", [
                            'talento_id' => $talento_id
                        ]);
                    } catch (Exception $e) {
                        // Se falhar, tente uma consulta alternativa
                        try {
                            $avaliacoes = $db->fetchAll("SELECT * FROM avaliacoes_talentos WHERE talento_id = :talento_id AND publica = 1 ORDER BY data_avaliacao DESC", [
                                'talento_id' => $talento_id
                            ]);
                        } catch (Exception $e2) {
                            // Se ainda falhar, defina como array vazio
                            $avaliacoes = [];
                            error_log("Erro ao buscar avaliações: " . $e2->getMessage());
                        }
                    }
                    
                    // Calcular a média das pontuações
                    $total_avaliacoes = count($avaliacoes);
                    
                    if ($total_avaliacoes > 0) {
                        $soma_pontuacoes = array_sum(array_column($avaliacoes, 'pontuacao'));
                        $media_pontuacao = $soma_pontuacoes / $total_avaliacoes;
                    }
                } catch (PDOException $e) {
                    // Se a tabela não existir, simplesmente continuar sem avaliações
                    $avaliacoes = [];
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Avaliações e Recomendações</h5>
                        <?php if ($is_empresa || Auth::checkUserType('admin')): ?>
                        <a href="<?php echo SITE_URL; ?>/?route=avaliar_talento&id=<?php echo $talento_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-star me-1"></i> Avaliar este Talento
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($avaliacoes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star-half-alt fa-3x text-muted mb-3"></i>
                                <p>Este talento ainda não possui avaliações públicas.</p>
                                <p>Seja o primeiro a avaliar <?php echo htmlspecialchars($talento['nome']); ?>!</p>
                                <a href="<?php echo SITE_URL; ?>/?route=avaliar_talento&id=<?php echo $talento_id; ?>" class="btn btn-outline-primary">
                                    Avaliar Talento
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="avaliacao-sumario mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center">
                                        <div class="pontuacao-media">
                                            <h2 class="mb-0"><?php echo number_format($media_pontuacao, 1); ?></h2>
                                            <div class="estrelas">
                                                <?php
                                                // Exibir estrelas com base na média
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= floor($media_pontuacao)) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    } elseif ($i - $media_pontuacao < 1 && $i - $media_pontuacao > 0) {
                                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-muted"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <p class="text-muted"><?php echo $total_avaliacoes; ?> avaliação(ões)</p>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="distribuicao-pontuacao">
                                            <?php
                                            // Calcular distribuição das pontuações
                                            $distribuicao = array_count_values(array_column($avaliacoes, 'pontuacao'));
                                            
                                            // Garantir que todas as pontuações estejam representadas
                                            for ($i = 5; $i >= 1; $i--) {
                                                if (!isset($distribuicao[$i])) {
                                                    $distribuicao[$i] = 0;
                                                }
                                                
                                                $porcentagem = $total_avaliacoes > 0 ? ($distribuicao[$i] / $total_avaliacoes) * 100 : 0;
                                            ?>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-2">
                                                        <?php echo $i; ?> <i class="fas fa-star text-warning small"></i>
                                                    </div>
                                                    <div class="col-8">
                                                        <div class="progress" style="height: 10px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $porcentagem; ?>%" aria-valuenow="<?php echo $porcentagem; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2 text-end">
                                                        <?php echo $distribuicao[$i]; ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="avaliacoes-lista">
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <div class="avaliacao-item mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($avaliacao['nome_avaliador']); ?></h5>
                                                <?php if (!empty($avaliacao['linkedin_avaliador'])): ?>
                                                    <a href="<?php echo htmlspecialchars($avaliacao['linkedin_avaliador']); ?>" target="_blank" class="text-primary">
                                                        <i class="fab fa-linkedin"></i> Ver perfil no LinkedIn
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="avaliacao-data text-muted">
                                                <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="avaliacao-pontuacao mb-2">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $avaliacao['pontuacao']) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-muted"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="avaliacao-texto">
                                            <?php echo nl2br(htmlspecialchars($avaliacao['avaliacao'])); ?>
                                        </div>
                                        
                                        <?php if (!$is_last): ?>
                                            <hr class="my-4">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Seção para empresas logadas -->
                <?php if ($is_empresa): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Ações para Empresas</h5>
                    </div>
                    <div class="card-body">
                        <p>Como empresa cadastrada, você pode:</p>
                        <ul>
                            <li>Entrar em contato diretamente com este talento</li>
                            <li>Convidar para entrevistas</li>
                            <li>Baixar o currículo completo</li>
                        </ul>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <a href="<?php echo SITE_URL; ?>/?route=contatar_talento&id=<?php echo $talento['id']; ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-envelope me-2"></i> Enviar Mensagem
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="<?php echo SITE_URL; ?>/?route=convidar_entrevista&id=<?php echo $talento['id']; ?>" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-calendar-check me-2"></i> Convidar para Entrevista
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript para processar a ação de favoritar/desfavoritar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnFavoritar = document.getElementById('btn-favoritar');
    
    if (btnFavoritar) {
        btnFavoritar.addEventListener('click', function() {
            const talentoId = this.getAttribute('data-talento-id');
            const acao = this.getAttribute('data-acao');
            
            // Enviar solicitação AJAX para favoritar/desfavoritar
            const formData = new FormData();
            formData.append('talento_id', talentoId);
            formData.append('acao', acao);
            
            fetch('<?php echo SITE_URL; ?>/ajax/favoritar_talento.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Atualizar o botão
                    if (data.is_favorito) {
                        btnFavoritar.classList.add('favoritado');
                        btnFavoritar.setAttribute('data-acao', 'remover');
                        btnFavoritar.querySelector('i').classList.remove('far');
                        btnFavoritar.querySelector('i').classList.add('fas');
                        btnFavoritar.querySelector('.favorito-tooltip').textContent = 'Remover dos favoritos';
                    } else {
                        btnFavoritar.classList.remove('favoritado');
                        btnFavoritar.setAttribute('data-acao', 'adicionar');
                        btnFavoritar.querySelector('i').classList.remove('fas');
                        btnFavoritar.querySelector('i').classList.add('far');
                        btnFavoritar.querySelector('.favorito-tooltip').textContent = 'Adicionar aos favoritos';
                    }
                    
                    // Exibir mensagem de sucesso
                    alert(data.message);
                } else {
                    // Exibir mensagem de erro
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.');
            });
        });
    }
});
</script>

<style>
.perfil-card {
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.perfil-avatar {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    overflow: hidden;
}

.perfil-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    font-weight: bold;
}

/* Estilo para destacar o botão de download do currículo */
.btn-primary.btn-lg {
    font-weight: bold;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.btn-primary.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.perfil-info {
    text-align: left;
}

.apresentacao {
    line-height: 1.6;
}

.btn-block {
    display: block;
    width: 100%;
}
/* Estilos para o botão de favoritar */
.btn-favoritar {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    font-size: 1.5rem;
    color: #ff5252;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
    padding: 5px;
    border-radius: 50%;
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-favoritar:hover {
    background-color: #fff0f0;
    color: #ff0000;
    transform: scale(1.1);
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

.btn-favoritar.favoritado {
    background-color: #fff0f0;
    color: #ff0000;
    border-color: #ff0000;
}

.btn-favoritar .favorito-tooltip {
    position: absolute;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
    margin-bottom: 5px;
}

.btn-favoritar:hover .favorito-tooltip {
    opacity: 1;
    visibility: visible;
}
</style>

</section>

<!-- Modal de Reportar -->
<div class="modal fade" id="reportarModal" tabindex="-1" aria-labelledby="reportarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportarModalLabel">Reportar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/pages/processar_reporte.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="usuario_reportado_id" value="<?php echo $talento_id; ?>">
                    <input type="hidden" name="tipo_usuario_reportado" value="talento">
                    
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo do reporte</label>
                        <select class="form-select" id="motivo" name="motivo" required>
                            <option value="">Selecione um motivo</option>
                            <option value="Informações falsas">Informações falsas</option>
                            <option value="Conteúdo inadequado">Conteúdo inadequado</option>
                            <option value="Spam ou propaganda">Spam ou propaganda</option>
                            <option value="Comportamento inadequado">Comportamento inadequado</option>
                            <option value="Violação de termos">Violação de termos</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do problema</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Descreva o problema em detalhes..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i> Sua denúncia será analisada pela nossa equipe e tratada com confidencialidade. Agradecemos sua contribuição para manter a plataforma segura.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Enviar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>
