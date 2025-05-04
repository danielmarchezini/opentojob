<?php
/**
 * Classe para gerenciar a exibição de anúncios do Google AdSense
 */
class AdSense {
    private static $instance = null;
    private $db;
    private $config;
    private $posicoes_ativas;
    
    /**
     * Construtor privado para implementar o padrão Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->carregarConfiguracoes();
    }
    
    /**
     * Obtém a instância única da classe
     * @return AdSense
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega as configurações de monetização do banco de dados
     */
    private function carregarConfiguracoes() {
        $this->config = $this->db->fetchRow("SELECT * FROM configuracoes_monetizacao WHERE id = 1");
        
        if (!$this->config) {
            // Configuração padrão se não existir no banco
            $this->config = [
                'ativa' => 0,
                'codigo_adsense' => '',
                'posicoes_ativas' => '{}'
            ];
        }
        
        $this->posicoes_ativas = json_decode($this->config['posicoes_ativas'], true);
        if (!$this->posicoes_ativas) {
            $this->posicoes_ativas = [];
        }
    }
    
    /**
     * Verifica se a monetização está ativa
     * @return bool
     */
    public function isAtiva() {
        return $this->config['ativa'] == 1;
    }
    
    /**
     * Verifica se uma posição específica está ativa
     * @param string $posicao Nome da posição
     * @return bool
     */
    public function isPosicaoAtiva($posicao) {
        if (!$this->isAtiva()) {
            return false;
        }
        
        return isset($this->posicoes_ativas[$posicao]) && $this->posicoes_ativas[$posicao] == 1;
    }
    
    /**
     * Exibe um anúncio em uma posição específica
     * @param string $posicao Nome da posição
     * @param string $formato Formato do anúncio (horizontal, vertical, quadrado)
     * @return string HTML do anúncio
     */
    public function exibirAnuncio($posicao, $formato = 'horizontal') {
        if (!$this->isPosicaoAtiva($posicao)) {
            return '';
        }
        
        $codigo_adsense = $this->config['codigo_adsense'];
        if (empty($codigo_adsense)) {
            return '<div class="adsense-placeholder" data-posicao="' . htmlspecialchars($posicao) . '">
                <div class="adsense-placeholder-inner">
                    <i class="fas fa-ad"></i>
                    <span>Espaço reservado para anúncio</span>
                    <small>(Configure seu código AdSense no painel administrativo)</small>
                </div>
            </div>';
        }
        
        // Definir tamanhos com base no formato
        $tamanhos = $this->getTamanhoAnuncio($formato);
        
        return '<div class="anuncio-container anuncio-' . htmlspecialchars($posicao) . '">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="' . htmlspecialchars($codigo_adsense) . '"
                 data-ad-slot="' . $this->getSlotPorPosicao($posicao) . '"
                 data-ad-format="' . $tamanhos['formato'] . '"
                 ' . $tamanhos['atributos'] . '></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>';
    }
    
    /**
     * Obtém o tamanho do anúncio com base no formato
     * @param string $formato Formato do anúncio
     * @return array Informações de tamanho
     */
    private function getTamanhoAnuncio($formato) {
        switch ($formato) {
            case 'horizontal':
                return [
                    'formato' => 'auto',
                    'atributos' => 'data-full-width-responsive="true"'
                ];
            case 'vertical':
                return [
                    'formato' => 'auto',
                    'atributos' => 'data-ad-format="vertical"'
                ];
            case 'quadrado':
                return [
                    'formato' => 'auto',
                    'atributos' => 'data-ad-format="rectangle"'
                ];
            case 'vaga':
                return [
                    'formato' => 'auto',
                    'atributos' => 'data-ad-format="fluid" data-ad-layout="in-article"'
                ];
            default:
                return [
                    'formato' => 'auto',
                    'atributos' => 'data-full-width-responsive="true"'
                ];
        }
    }
    
    /**
     * Obtém o slot do anúncio com base na posição
     * Nota: Na implementação real, você deve configurar slots específicos do AdSense
     * @param string $posicao Nome da posição
     * @return string ID do slot
     */
    private function getSlotPorPosicao($posicao) {
        // Aqui você pode mapear posições para slots específicos do AdSense
        // Por enquanto, retornamos um valor genérico
        return 'auto';
    }
    
    /**
     * Retorna o código para incluir o script do AdSense no cabeçalho
     * @return string Código HTML
     */
    public function getScriptHeader() {
        if (!$this->isAtiva()) {
            return '';
        }
        
        return '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . 
               htmlspecialchars($this->config['codigo_adsense']) . '" crossorigin="anonymous"></script>';
    }
    
    /**
     * Retorna o CSS para os espaços reservados de anúncios
     * @return string Código CSS
     */
    public function getPlaceholderCSS() {
        return '<style>
            .adsense-placeholder {
                background-color: #f8f9fa;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                padding: 20px;
                margin: 15px 0;
                text-align: center;
                min-height: 90px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .adsense-placeholder-inner {
                color: #6c757d;
            }
            .adsense-placeholder i {
                font-size: 24px;
                display: block;
                margin-bottom: 10px;
            }
            .adsense-placeholder small {
                display: block;
                margin-top: 5px;
                font-size: 12px;
            }
            .anuncio-container {
                margin: 15px 0;
                overflow: hidden;
            }
            .anuncio-vaga {
                margin: 20px 0;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 4px;
            }
        </style>';
    }
}
