<?php
// Incluir arquivo de configuração
require_once 'config/config.php';
require_once 'includes/Database.php';

// Obter instância do banco de dados
$db = Database::getInstance();

// ID do talento a corrigir
$talento_id = isset($_GET['id']) ? (int)$_GET['id'] : 5;

// Verificar o talento no banco de dados
$talento = $db->fetch("
    SELECT u.id, u.nome, u.foto_perfil, u.tipo, u.status
    FROM usuarios u
    WHERE u.id = :id
", ['id' => $talento_id]);

echo "<h1>Cópia da Imagem do Talento ID: {$talento_id}</h1>";

if (!$talento) {
    echo "<p style='color: red;'>Erro: Usuário não encontrado!</p>";
    exit;
}

// Verificar diretórios
$diretorio_perfil = __DIR__ . '/uploads/perfil';
$diretorio_talentos = __DIR__ . '/uploads/talentos';

// Criar diretórios se não existirem
if (!file_exists($diretorio_perfil)) {
    mkdir($diretorio_perfil, 0755, true);
    echo "<p>Diretório /uploads/perfil/ criado.</p>";
}

if (!file_exists($diretorio_talentos)) {
    mkdir($diretorio_talentos, 0755, true);
    echo "<p>Diretório /uploads/talentos/ criado.</p>";
}

// Informações do talento
echo "<h2>Informações do Talento</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><td>{$talento['id']}</td></tr>";
echo "<tr><th>Nome</th><td>{$talento['nome']}</td></tr>";
echo "<tr><th>Foto Perfil</th><td>" . ($talento['foto_perfil'] ? $talento['foto_perfil'] : "Não definida") . "</td></tr>";
echo "</table>";

// Copiar a imagem da página de edição
echo "<h2>Cópia da Imagem</h2>";

if (!empty($talento['foto_perfil'])) {
    $foto = $talento['foto_perfil'];
    $caminho_perfil = $diretorio_perfil . '/' . $foto;
    
    // Verificar se a imagem já existe no diretório de perfil
    if (file_exists($caminho_perfil)) {
        echo "<p>A imagem já existe no diretório /uploads/perfil/</p>";
        echo "<img src='uploads/perfil/{$foto}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
    } else {
        // Tentar copiar a imagem da página de edição
        $imagem_encontrada = false;
        
        // Verificar se existe no diretório de talentos
        $caminho_talentos = $diretorio_talentos . '/' . $foto;
        if (file_exists($caminho_talentos)) {
            // Copiar a imagem para o diretório de perfil
            if (copy($caminho_talentos, $caminho_perfil)) {
                echo "<p style='color: green;'>Imagem copiada com sucesso de /uploads/talentos/ para /uploads/perfil/</p>";
                echo "<img src='uploads/perfil/{$foto}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
                $imagem_encontrada = true;
            } else {
                echo "<p style='color: red;'>Erro ao copiar a imagem de /uploads/talentos/ para /uploads/perfil/</p>";
            }
        }
        
        // Se não encontrou a imagem, usar a imagem da página de edição
        if (!$imagem_encontrada) {
            // Usar a imagem que aparece na página de edição
            echo "<p>Usando a imagem que aparece na página de edição do talento...</p>";
            
            // Verificar se existe uma imagem padrão no sistema
            $imagem_padrao_fonte = __DIR__ . '/assets/img/default-avatar.jpg';
            if (file_exists($imagem_padrao_fonte)) {
                // Copiar a imagem padrão para o diretório de perfil
                if (copy($imagem_padrao_fonte, $caminho_perfil)) {
                    echo "<p style='color: green;'>Imagem padrão copiada com sucesso para /uploads/perfil/</p>";
                    echo "<img src='uploads/perfil/{$foto}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
                } else {
                    echo "<p style='color: red;'>Erro ao copiar a imagem padrão para /uploads/perfil/</p>";
                }
            } else {
                // Criar um arquivo de imagem simples
                $img = imagecreatetruecolor(200, 200);
                $bg_color = imagecolorallocate($img, 0, 123, 255); // Cor azul
                $text_color = imagecolorallocate($img, 255, 255, 255); // Cor branca
                imagefill($img, 0, 0, $bg_color);
                
                // Adicionar a primeira letra do nome do talento
                $letra = strtoupper(substr($talento['nome'], 0, 1));
                imagestring($img, 5, 90, 90, $letra, $text_color);
                
                // Salvar a imagem
                imagejpeg($img, $caminho_perfil);
                imagedestroy($img);
                
                echo "<p style='color: green;'>Imagem criada com sucesso em /uploads/perfil/</p>";
                echo "<img src='uploads/perfil/{$foto}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
            }
        }
    }
} else {
    echo "<p>O talento não tem uma foto de perfil definida no banco de dados.</p>";
    
    // Criar uma foto padrão
    $novo_nome = 'default-avatar.jpg';
    $default_path = $diretorio_perfil . '/' . $novo_nome;
    
    // Verificar se existe uma imagem padrão no sistema
    $imagem_padrao_fonte = __DIR__ . '/assets/img/default-avatar.jpg';
    if (file_exists($imagem_padrao_fonte)) {
        // Copiar a imagem padrão para o diretório de perfil
        if (copy($imagem_padrao_fonte, $default_path)) {
            echo "<p style='color: green;'>Imagem padrão copiada com sucesso para /uploads/perfil/</p>";
        } else {
            echo "<p style='color: red;'>Erro ao copiar a imagem padrão para /uploads/perfil/</p>";
        }
    } else {
        // Criar um arquivo de imagem simples
        $img = imagecreatetruecolor(200, 200);
        $bg_color = imagecolorallocate($img, 0, 123, 255); // Cor azul
        $text_color = imagecolorallocate($img, 255, 255, 255); // Cor branca
        imagefill($img, 0, 0, $bg_color);
        
        // Adicionar a primeira letra do nome do talento
        $letra = strtoupper(substr($talento['nome'], 0, 1));
        imagestring($img, 5, 90, 90, $letra, $text_color);
        
        // Salvar a imagem
        imagejpeg($img, $default_path);
        imagedestroy($img);
        
        echo "<p style='color: green;'>Imagem padrão criada com sucesso em /uploads/perfil/</p>";
    }
    
    // Atualizar o banco de dados
    $db->execute("UPDATE usuarios SET foto_perfil = :foto WHERE id = :id", [
        'foto' => $novo_nome,
        'id' => $talento_id
    ]);
    
    echo "<p style='color: green;'>Banco de dados atualizado com a foto padrão: {$novo_nome}</p>";
    echo "<img src='uploads/perfil/{$novo_nome}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
}

// Verificar permissões do diretório
echo "<h2>Verificação de Permissões</h2>";
echo "<p>Permissões do diretório /uploads/perfil/: " . substr(sprintf('%o', fileperms($diretorio_perfil)), -4) . "</p>";
echo "<p>O diretório /uploads/perfil/ é gravável: " . (is_writable($diretorio_perfil) ? "Sim" : "Não") . "</p>";

// Verificar se a imagem está acessível via URL
echo "<h2>Teste de Acesso à Imagem</h2>";
echo "<p>Teste de acesso à imagem via URL:</p>";

if (!empty($talento['foto_perfil'])) {
    $foto = $talento['foto_perfil'];
    echo "<p>URL da imagem: " . SITE_URL . "/uploads/perfil/{$foto}</p>";
    echo "<img src='" . SITE_URL . "/uploads/perfil/{$foto}' style='max-width: 200px; max-height: 200px;' alt='Foto de perfil'>";
} else {
    echo "<p>O talento não tem uma foto de perfil definida no banco de dados.</p>";
}

// Link para visualizar o perfil
echo "<p><a href='?route=perfil_talento&id={$talento_id}' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Visualizar Perfil do Talento</a></p>";
?>
