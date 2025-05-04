<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID da empresa logada
$empresa_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter talentos favoritos da empresa
try {
    $favoritos = $db->fetchAll("
        SELECT 
            tf.id as favorito_id,
            tf.data_favoritado,
            tf.notas,
            u.id as talento_id,
            u.nome,
            u.email,
            t.profissao,
            t.nivel,
            t.experiencia,
            t.foto_perfil,
            t.carta_apresentacao
        FROM talentos_favoritos tf
        JOIN usuarios u ON tf.talento_id = u.id
        JOIN talentos t ON u.id = t.usuario_id
        WHERE tf.empresa_id = :empresa_id
        ORDER BY tf.data_favoritado DESC
    ", ['empresa_id' => $empresa_id]);
} catch (PDOException $e) {
    // Se a tabela ainda não existir, inicializar com array vazio
    error_log("Erro ao buscar favoritos: " . $e->getMessage());
    $favoritos = [];
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">Meus Talentos Favoritos</h1>
                <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-outline-primary">
                    <i class="fas fa-search me-2"></i> Buscar Novos Talentos
                </a>
            </div>
            <p class="text-muted mt-2">Gerencie os talentos que você salvou para análise posterior.</p>
        </div>
    </div>
    
    <?php if (empty($favoritos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Você ainda não adicionou nenhum talento aos favoritos.
            <p class="mb-0 mt-2">Explore a <a href="<?php echo SITE_URL; ?>/?route=talentos" class="alert-link">lista de talentos</a> e clique no ícone de coração para adicionar talentos aos seus favoritos.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favoritos as $favorito): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 favorito-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="talento-avatar me-3">
                                    <?php if (!empty($favorito['foto_perfil'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $favorito['foto_perfil']; ?>" alt="<?php echo htmlspecialchars($favorito['nome']); ?>" class="rounded-circle">
                                    <?php else: ?>
                                        <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                            <?php echo strtoupper(substr($favorito['nome'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($favorito['nome']); ?></h5>
                                    <p class="profissao-tag mb-0"><?php echo htmlspecialchars($favorito['profissao'] ?? 'Profissional'); ?></p>
                                    <?php if (!empty($favorito['nivel'])): ?>
                                        <span class="nivel-badge"><?php echo htmlspecialchars($favorito['nivel']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-remover-favorito ms-auto" data-favorito-id="<?php echo $favorito['favorito_id']; ?>" data-talento-id="<?php echo $favorito['talento_id']; ?>">
                                    <i class="fas fa-times"></i>
                                    <span class="tooltip-text">Remover dos favoritos</span>
                                </button>
                            </div>
                            
                            <div class="talento-apresentacao mb-3">
                                <?php if (!empty($favorito['carta_apresentacao'])): ?>
                                    <?php 
                                    // Limitar a carta de apresentação a 150 caracteres
                                    $carta_resumida = substr(strip_tags($favorito['carta_apresentacao']), 0, 150);
                                    if (strlen($favorito['carta_apresentacao']) > 150) {
                                        $carta_resumida .= '...';
                                    }
                                    echo htmlspecialchars($carta_resumida);
                                    ?>
                                <?php else: ?>
                                    <p class="text-muted">Profissional com <?php echo $favorito['experiencia'] ? $favorito['experiencia'] . ' anos de experiência' : 'experiência'; ?> em <?php echo $favorito['profissao'] ? $favorito['profissao'] : 'sua área de atuação'; ?>.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="favorito-info">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i> Favoritado em: <?php echo date('d/m/Y', strtotime($favorito['data_favoritado'])); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $favorito['talento_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                Ver perfil completo
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.talento-avatar {
    width: 60px;
    height: 60px;
    overflow: hidden;
}

.talento-avatar img {
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
    font-size: 1.5rem;
    font-weight: bold;
}

.favorito-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.favorito-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profissao-tag {
    color: var(--accent-color);
    font-weight: 600;
    font-size: 0.95rem;
}

.nivel-badge {
    display: inline-block;
    background-color: #e9f5ff;
    color: #0077cc;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    margin-top: 5px;
}

.talento-apresentacao {
    font-size: 0.9rem;
    line-height: 1.4;
    color: #555;
    height: 80px;
    overflow: hidden;
}

.btn-remover-favorito {
    background: none;
    border: none;
    color: #ccc;
    font-size: 1rem;
    cursor: pointer;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
}

.btn-remover-favorito:hover {
    background-color: #f8d7da;
    color: #dc3545;
}

.btn-remover-favorito .tooltip-text {
    position: absolute;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}

.btn-remover-favorito:hover .tooltip-text {
    opacity: 1;
    visibility: visible;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar evento de clique aos botões de remover favorito
    document.querySelectorAll('.btn-remover-favorito').forEach(button => {
        button.addEventListener('click', function() {
            const talentoId = this.getAttribute('data-talento-id');
            const favoritoId = this.getAttribute('data-favorito-id');
            const card = this.closest('.col-md-6');
            
            if (confirm('Tem certeza que deseja remover este talento dos favoritos?')) {
                // Enviar solicitação AJAX para remover dos favoritos
                const formData = new FormData();
                formData.append('talento_id', talentoId);
                formData.append('acao', 'remover');
                
                fetch('<?php echo SITE_URL; ?>/ajax/favoritar_talento.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remover o card da interface
                        card.remove();
                        
                        // Verificar se ainda há favoritos
                        if (document.querySelectorAll('.favorito-card').length === 0) {
                            // Se não houver mais favoritos, exibir mensagem
                            const container = document.querySelector('.container');
                            const row = document.querySelector('.row:nth-child(2)');
                            
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-info';
                            alertDiv.innerHTML = `
                                <i class="fas fa-info-circle me-2"></i> Você ainda não adicionou nenhum talento aos favoritos.
                                <p class="mb-0 mt-2">Explore a <a href="<?php echo SITE_URL; ?>/?route=talentos" class="alert-link">lista de talentos</a> e clique no ícone de coração para adicionar talentos aos seus favoritos.</p>
                            `;
                            
                            container.replaceChild(alertDiv, row);
                        }
                    } else {
                        // Exibir mensagem de erro
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.');
                });
            }
        });
    });
});
</script>
