<!-- Banner de Consentimento de Cookies -->
<div id="cookie-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-content">
        <div class="cookie-banner-text">
            <h3>Utilizamos cookies</h3>
            <p>Utilizamos cookies e tecnologias semelhantes para melhorar sua experiência em nosso site, personalizar conteúdo e anúncios, fornecer recursos de mídia social e analisar nosso tráfego. Ao clicar em "Aceitar todos", você concorda com o uso de todos os cookies. Você pode gerenciar suas preferências clicando em "Configurações de cookies".</p>
        </div>
        <div class="cookie-banner-buttons">
            <button id="cookie-settings-open" class="btn btn-outline-light">Configurações de cookies</button>
            <button id="reject-all-cookies" class="btn btn-outline-light">Rejeitar todos</button>
            <button id="accept-all-cookies" class="btn btn-accent">Aceitar todos</button>
        </div>
    </div>
</div>

<!-- Modal de Configurações de Cookies -->
<div id="cookie-settings-modal" class="cookie-modal" style="display: none;">
    <div class="cookie-modal-content">
        <div class="cookie-modal-header">
            <h2>Configurações de Cookies</h2>
            <button id="close-cookie-modal" class="cookie-modal-close">&times;</button>
        </div>
        <div class="cookie-modal-body">
            <p>Utilizamos cookies para melhorar sua experiência em nosso site. Você pode escolher quais tipos de cookies deseja permitir. Os cookies essenciais são necessários para o funcionamento do site e não podem ser desativados.</p>
            
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div class="cookie-category-title">
                        <h3>Cookies Essenciais</h3>
                        <p>Necessários para o funcionamento básico do site. O site não pode funcionar corretamente sem esses cookies.</p>
                    </div>
                    <div class="cookie-category-toggle">
                        <input type="checkbox" id="cookie-essential" checked disabled>
                        <label for="cookie-essential">Sempre ativo</label>
                    </div>
                </div>
            </div>
            
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div class="cookie-category-title">
                        <h3>Cookies de Preferências</h3>
                        <p>Permitem que o site lembre de suas preferências e configurações para uma melhor experiência.</p>
                    </div>
                    <div class="cookie-category-toggle">
                        <input type="checkbox" id="cookie-preferences">
                        <label for="cookie-preferences">Ativar/Desativar</label>
                    </div>
                </div>
            </div>
            
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div class="cookie-category-title">
                        <h3>Cookies Analíticos</h3>
                        <p>Ajudam-nos a entender como os visitantes interagem com o site, permitindo melhorar a estrutura, navegação e conteúdo.</p>
                    </div>
                    <div class="cookie-category-toggle">
                        <input type="checkbox" id="cookie-analytics">
                        <label for="cookie-analytics">Ativar/Desativar</label>
                    </div>
                </div>
            </div>
            
            <div class="cookie-category">
                <div class="cookie-category-header">
                    <div class="cookie-category-title">
                        <h3>Cookies de Marketing</h3>
                        <p>Utilizados para rastrear visitantes em diferentes sites e exibir anúncios mais relevantes.</p>
                    </div>
                    <div class="cookie-category-toggle">
                        <input type="checkbox" id="cookie-marketing">
                        <label for="cookie-marketing">Ativar/Desativar</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="cookie-modal-footer">
            <button id="save-cookie-settings" class="btn btn-accent">Salvar preferências</button>
        </div>
    </div>
</div>

<style>
/* Estilos para o Banner de Cookies */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: rgba(33, 37, 41, 0.95);
    color: #fff;
    z-index: 9999;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
}

.cookie-banner-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.cookie-banner-text {
    margin-bottom: 15px;
}

.cookie-banner-text h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.25rem;
}

.cookie-banner-text p {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.cookie-banner-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Estilos para o Modal de Configurações de Cookies */
.cookie-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cookie-modal-content {
    background-color: #fff;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.cookie-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cookie-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--primary-color);
}

.cookie-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.cookie-modal-body {
    padding: 20px;
}

.cookie-category {
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    overflow: hidden;
}

.cookie-category-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background-color: #f8f9fa;
}

.cookie-category-title {
    flex: 1;
}

.cookie-category-title h3 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.cookie-category-title p {
    margin: 0;
    font-size: 0.9rem;
    color: #6c757d;
}

.cookie-category-toggle {
    padding-left: 15px;
}

.cookie-modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    text-align: right;
}

@media (min-width: 768px) {
    .cookie-banner-content {
        flex-direction: row;
        align-items: center;
    }
    
    .cookie-banner-text {
        flex: 1;
        margin-bottom: 0;
        margin-right: 20px;
    }
    
    .cookie-banner-buttons {
        justify-content: flex-end;
    }
}
</style>
