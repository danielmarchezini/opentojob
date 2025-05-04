<?php
// Incluir arquivos necessários
// Usar caminhos absolutos para evitar problemas
$root_path = realpath(__DIR__ . '/..');

// Verificar se o arquivo config.php existe
if (file_exists($root_path . '/config/config.php')) {
    require_once $root_path . '/config/config.php';
} elseif (file_exists($root_path . '/includes/config.php')) {
    require_once $root_path . '/includes/config.php';
} else {
    die('Arquivo de configuração não encontrado!');
}

// Incluir os arquivos com os nomes corretos (com letras maiúsculas)
require_once $root_path . '/includes/Database.php';
require_once $root_path . '/includes/Auth.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está sendo executado via linha de comando
$is_cli = (php_sapi_name() === 'cli');

// Se não estiver em CLI, verificar se o usuário está logado e é um administrador
if (!$is_cli) {
    if ((!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') && 
        (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin')) {
        // Redirecionar para a página de login com mensagem
        $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
        $_SESSION['flash_type'] = "danger";
        header("Location: " . SITE_URL . "/admin");
        exit;
    }
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Função para executar consulta SQL com tratamento de erro
function executarSQL($db, $sql, $descricao) {
    try {
        $db->query($sql);
        echo "<p class='text-success'>✓ $descricao</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Erro ao $descricao: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Iniciar HTML
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Estrutura de Vagas - OpenToJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 30px; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Atualizar Estrutura de Vagas</h1>
        <div class="card">
            <div class="card-header">
                <h5>Progresso da Atualização</h5>
            </div>
            <div class="card-body">
<?php
// 1. Criar tabela de tipos de contrato
$sql_criar_tabela_tipos_contrato = "
CREATE TABLE IF NOT EXISTS tipos_contrato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
)";
executarSQL($db, $sql_criar_tabela_tipos_contrato, "criar tabela tipos_contrato");

// 2. Criar tabela de regimes de trabalho
$sql_criar_tabela_regimes_trabalho = "
CREATE TABLE IF NOT EXISTS regimes_trabalho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
)";
executarSQL($db, $sql_criar_tabela_regimes_trabalho, "criar tabela regimes_trabalho");

// 3. Criar tabela de níveis de experiência
$sql_criar_tabela_niveis_experiencia = "
CREATE TABLE IF NOT EXISTS niveis_experiencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
)";
executarSQL($db, $sql_criar_tabela_niveis_experiencia, "criar tabela niveis_experiencia");

// 4. Inserir valores padrão para tipos de contrato
$tipos_contrato = [
    ['nome' => 'CLT', 'descricao' => 'Contrato de trabalho regido pela CLT (Consolidação das Leis do Trabalho)'],
    ['nome' => 'PJ', 'descricao' => 'Contrato como Pessoa Jurídica'],
    ['nome' => 'Estágio', 'descricao' => 'Contrato de estágio para estudantes'],
    ['nome' => 'Freelancer', 'descricao' => 'Trabalho por projeto ou demanda específica'],
    ['nome' => 'Temporário', 'descricao' => 'Contrato por tempo determinado'],
    ['nome' => 'Aprendiz', 'descricao' => 'Contrato de aprendizagem para jovens'],
    ['nome' => 'Trainee', 'descricao' => 'Programa de desenvolvimento profissional para recém-formados'],
    ['nome' => 'Terceirizado', 'descricao' => 'Contratação via empresa terceirizada']
];

echo "<h6>Inserindo tipos de contrato:</h6>";
foreach ($tipos_contrato as $tipo) {
    try {
        // Verificar se já existe
        $existe = $db->fetch("SELECT id FROM tipos_contrato WHERE nome = ?", [$tipo['nome']]);
        if (!$existe) {
            $db->insert('tipos_contrato', [
                'nome' => $tipo['nome'],
                'descricao' => $tipo['descricao']
            ]);
            echo "<p class='text-success'>✓ Tipo de contrato '{$tipo['nome']}' inserido</p>";
        } else {
            echo "<p class='text-warning'>⚠ Tipo de contrato '{$tipo['nome']}' já existe</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Erro ao inserir tipo de contrato '{$tipo['nome']}': " . $e->getMessage() . "</p>";
    }
}

// 5. Inserir valores padrão para regimes de trabalho
$regimes_trabalho = [
    ['nome' => 'Presencial', 'descricao' => 'Trabalho realizado integralmente no local da empresa'],
    ['nome' => 'Remoto', 'descricao' => 'Trabalho realizado integralmente à distância (home office)'],
    ['nome' => 'Híbrido', 'descricao' => 'Combinação de trabalho presencial e remoto'],
    ['nome' => 'Flexível', 'descricao' => 'Horários flexíveis conforme necessidade'],
    ['nome' => 'Semi-presencial', 'descricao' => 'Predominantemente presencial com alguns dias remotos'],
    ['nome' => 'Por turnos', 'descricao' => 'Trabalho organizado em turnos específicos']
];

echo "<h6>Inserindo regimes de trabalho:</h6>";
foreach ($regimes_trabalho as $regime) {
    try {
        // Verificar se já existe
        $existe = $db->fetch("SELECT id FROM regimes_trabalho WHERE nome = ?", [$regime['nome']]);
        if (!$existe) {
            $db->insert('regimes_trabalho', [
                'nome' => $regime['nome'],
                'descricao' => $regime['descricao']
            ]);
            echo "<p class='text-success'>✓ Regime de trabalho '{$regime['nome']}' inserido</p>";
        } else {
            echo "<p class='text-warning'>⚠ Regime de trabalho '{$regime['nome']}' já existe</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Erro ao inserir regime de trabalho '{$regime['nome']}': " . $e->getMessage() . "</p>";
    }
}

// 6. Inserir valores padrão para níveis de experiência
$niveis_experiencia = [
    ['nome' => 'Estágio', 'descricao' => 'Para estudantes em formação'],
    ['nome' => 'Júnior', 'descricao' => 'Profissionais com até 2 anos de experiência'],
    ['nome' => 'Pleno', 'descricao' => 'Profissionais com 2 a 5 anos de experiência'],
    ['nome' => 'Sênior', 'descricao' => 'Profissionais com mais de 5 anos de experiência'],
    ['nome' => 'Especialista', 'descricao' => 'Profissionais com conhecimento avançado em áreas específicas'],
    ['nome' => 'Trainee', 'descricao' => 'Recém-formados em programas de desenvolvimento'],
    ['nome' => 'Coordenador', 'descricao' => 'Profissionais com experiência em coordenação de equipes'],
    ['nome' => 'Gerente', 'descricao' => 'Profissionais com experiência em gestão de áreas ou projetos'],
    ['nome' => 'Diretor', 'descricao' => 'Profissionais com experiência em direção estratégica']
];

echo "<h6>Inserindo níveis de experiência:</h6>";
foreach ($niveis_experiencia as $nivel) {
    try {
        // Verificar se já existe
        $existe = $db->fetch("SELECT id FROM niveis_experiencia WHERE nome = ?", [$nivel['nome']]);
        if (!$existe) {
            $db->insert('niveis_experiencia', [
                'nome' => $nivel['nome'],
                'descricao' => $nivel['descricao']
            ]);
            echo "<p class='text-success'>✓ Nível de experiência '{$nivel['nome']}' inserido</p>";
        } else {
            echo "<p class='text-warning'>⚠ Nível de experiência '{$nivel['nome']}' já existe</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='text-danger'>✗ Erro ao inserir nível de experiência '{$nivel['nome']}': " . $e->getMessage() . "</p>";
    }
}

// 7. Importar valores existentes da tabela vagas
echo "<h6>Importando valores existentes da tabela vagas:</h6>";

// Importar tipos de contrato existentes
try {
    $tipos_existentes = $db->fetchAll("SELECT DISTINCT tipo_contrato FROM vagas WHERE tipo_contrato IS NOT NULL AND tipo_contrato != ''");
    foreach ($tipos_existentes as $tipo) {
        if (!empty($tipo['tipo_contrato'])) {
            $existe = $db->fetch("SELECT id FROM tipos_contrato WHERE nome = ?", [$tipo['tipo_contrato']]);
            if (!$existe) {
                $db->insert('tipos_contrato', [
                    'nome' => $tipo['tipo_contrato'],
                    'descricao' => 'Importado da tabela vagas'
                ]);
                echo "<p class='text-success'>✓ Tipo de contrato '{$tipo['tipo_contrato']}' importado da tabela vagas</p>";
            }
        }
    }
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao importar tipos de contrato existentes: " . $e->getMessage() . "</p>";
}

// Importar regimes de trabalho existentes
try {
    $regimes_existentes = $db->fetchAll("SELECT DISTINCT regime_trabalho FROM vagas WHERE regime_trabalho IS NOT NULL AND regime_trabalho != ''");
    foreach ($regimes_existentes as $regime) {
        if (!empty($regime['regime_trabalho'])) {
            $existe = $db->fetch("SELECT id FROM regimes_trabalho WHERE nome = ?", [$regime['regime_trabalho']]);
            if (!$existe) {
                $db->insert('regimes_trabalho', [
                    'nome' => $regime['regime_trabalho'],
                    'descricao' => 'Importado da tabela vagas'
                ]);
                echo "<p class='text-success'>✓ Regime de trabalho '{$regime['regime_trabalho']}' importado da tabela vagas</p>";
            }
        }
    }
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao importar regimes de trabalho existentes: " . $e->getMessage() . "</p>";
}

// Importar níveis de experiência existentes
try {
    $niveis_existentes = $db->fetchAll("SELECT DISTINCT nivel_experiencia FROM vagas WHERE nivel_experiencia IS NOT NULL AND nivel_experiencia != ''");
    foreach ($niveis_existentes as $nivel) {
        if (!empty($nivel['nivel_experiencia'])) {
            $existe = $db->fetch("SELECT id FROM niveis_experiencia WHERE nome = ?", [$nivel['nivel_experiencia']]);
            if (!$existe) {
                $db->insert('niveis_experiencia', [
                    'nome' => $nivel['nivel_experiencia'],
                    'descricao' => 'Importado da tabela vagas'
                ]);
                echo "<p class='text-success'>✓ Nível de experiência '{$nivel['nivel_experiencia']}' importado da tabela vagas</p>";
            }
        }
    }
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao importar níveis de experiência existentes: " . $e->getMessage() . "</p>";
}

// 8. Adicionar colunas de ID para as novas tabelas na tabela vagas
echo "<h6>Adicionando colunas de ID para as novas tabelas na tabela vagas:</h6>";

// Verificar se as colunas já existem
$colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'tipo_contrato_id'");
if (empty($colunas)) {
    executarSQL($db, "ALTER TABLE vagas ADD COLUMN tipo_contrato_id INT", "adicionar coluna tipo_contrato_id");
}

$colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'regime_trabalho_id'");
if (empty($colunas)) {
    executarSQL($db, "ALTER TABLE vagas ADD COLUMN regime_trabalho_id INT", "adicionar coluna regime_trabalho_id");
}

$colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'nivel_experiencia_id'");
if (empty($colunas)) {
    executarSQL($db, "ALTER TABLE vagas ADD COLUMN nivel_experiencia_id INT", "adicionar coluna nivel_experiencia_id");
}

// 9. Atualizar os IDs na tabela vagas com base nos valores existentes
echo "<h6>Atualizando IDs na tabela vagas:</h6>";

// Atualizar tipo_contrato_id
try {
    $db->query("
        UPDATE vagas v
        JOIN tipos_contrato tc ON v.tipo_contrato = tc.nome
        SET v.tipo_contrato_id = tc.id
        WHERE v.tipo_contrato IS NOT NULL AND v.tipo_contrato != ''
    ");
    echo "<p class='text-success'>✓ Coluna tipo_contrato_id atualizada com sucesso</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao atualizar tipo_contrato_id: " . $e->getMessage() . "</p>";
}

// Atualizar regime_trabalho_id
try {
    $db->query("
        UPDATE vagas v
        JOIN regimes_trabalho rt ON v.regime_trabalho = rt.nome
        SET v.regime_trabalho_id = rt.id
        WHERE v.regime_trabalho IS NOT NULL AND v.regime_trabalho != ''
    ");
    echo "<p class='text-success'>✓ Coluna regime_trabalho_id atualizada com sucesso</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao atualizar regime_trabalho_id: " . $e->getMessage() . "</p>";
}

// Atualizar nivel_experiencia_id
try {
    $db->query("
        UPDATE vagas v
        JOIN niveis_experiencia ne ON v.nivel_experiencia = ne.nome
        SET v.nivel_experiencia_id = ne.id
        WHERE v.nivel_experiencia IS NOT NULL AND v.nivel_experiencia != ''
    ");
    echo "<p class='text-success'>✓ Coluna nivel_experiencia_id atualizada com sucesso</p>";
} catch (PDOException $e) {
    echo "<p class='text-danger'>✗ Erro ao atualizar nivel_experiencia_id: " . $e->getMessage() . "</p>";
}

// 10. Adicionar chaves estrangeiras (opcional, pode ser feito posteriormente)
echo "<h6>Adicionando chaves estrangeiras (opcional):</h6>";
echo "<p class='text-warning'>⚠ As chaves estrangeiras não foram adicionadas para evitar problemas com dados existentes. Isso pode ser feito manualmente mais tarde.</p>";

?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Próximos Passos</h5>
            </div>
            <div class="card-body">
                <p>A estrutura do banco de dados foi atualizada com sucesso. Agora você pode:</p>
                <ol>
                    <li>Gerenciar os tipos de contrato, regimes de trabalho e níveis de experiência através do painel administrativo</li>
                    <li>Utilizar os novos campos nos formulários de vagas</li>
                </ol>
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/?route=painel_admin" class="btn btn-primary">Voltar para o Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin" class="btn btn-success">Ir para Gerenciar Vagas</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
