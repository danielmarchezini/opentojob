/**
 * Gerenciador de Cookies para OpenToJob
 * Permite que os usuários gerenciem suas preferências de cookies
 */

class CookieManager {
    constructor() {
        this.cookieConsent = {
            essential: true, // Sempre ativo
            preferences: false,
            analytics: false,
            marketing: false
        };
        
        this.cookieName = 'opentojob_cookie_consent';
        this.cookieDuration = 365; // dias
        
        this.init();
    }
    
    init() {
        // Verificar se já existe consentimento salvo
        const savedConsent = this.getCookieConsent();
        
        if (savedConsent) {
            this.cookieConsent = savedConsent;
            this.applyConsent();
        } else {
            // Mostrar banner de cookies na primeira visita
            this.showCookieBanner();
        }
        
        // Adicionar listener para o botão de configurações de cookies no rodapé
        const cookieSettingsBtn = document.getElementById('cookie-settings-btn');
        if (cookieSettingsBtn) {
            cookieSettingsBtn.addEventListener('click', () => this.showCookieModal());
        }
    }
    
    // Obter consentimento salvo
    getCookieConsent() {
        const consent = this.getCookie(this.cookieName);
        return consent ? JSON.parse(consent) : null;
    }
    
    // Salvar consentimento
    saveConsent() {
        this.setCookie(this.cookieName, JSON.stringify(this.cookieConsent), this.cookieDuration);
        this.applyConsent();
        this.hideCookieBanner();
        this.hideCookieModal();
    }
    
    // Aplicar consentimento (ativar/desativar scripts)
    applyConsent() {
        // Implementar lógica para ativar/desativar scripts com base no consentimento
        if (this.cookieConsent.analytics) {
            this.enableAnalytics();
        } else {
            this.disableAnalytics();
        }
        
        if (this.cookieConsent.marketing) {
            this.enableMarketing();
        } else {
            this.disableMarketing();
        }
    }
    
    // Ativar scripts de analytics
    enableAnalytics() {
        // Implementar ativação de scripts de analytics (Google Analytics, etc.)
        console.log('Analytics cookies enabled');
    }
    
    // Desativar scripts de analytics
    disableAnalytics() {
        // Implementar desativação de scripts de analytics
        console.log('Analytics cookies disabled');
    }
    
    // Ativar scripts de marketing
    enableMarketing() {
        // Implementar ativação de scripts de marketing (Facebook Pixel, etc.)
        console.log('Marketing cookies enabled');
    }
    
    // Desativar scripts de marketing
    disableMarketing() {
        // Implementar desativação de scripts de marketing
        console.log('Marketing cookies disabled');
    }
    
    // Mostrar banner de cookies
    showCookieBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.style.display = 'block';
            
            // Adicionar listeners para os botões
            const acceptAllBtn = document.getElementById('accept-all-cookies');
            const rejectAllBtn = document.getElementById('reject-all-cookies');
            const settingsBtn = document.getElementById('cookie-settings-open');
            
            if (acceptAllBtn) {
                acceptAllBtn.addEventListener('click', () => {
                    this.cookieConsent.preferences = true;
                    this.cookieConsent.analytics = true;
                    this.cookieConsent.marketing = true;
                    this.saveConsent();
                });
            }
            
            if (rejectAllBtn) {
                rejectAllBtn.addEventListener('click', () => {
                    this.cookieConsent.preferences = false;
                    this.cookieConsent.analytics = false;
                    this.cookieConsent.marketing = false;
                    this.saveConsent();
                });
            }
            
            if (settingsBtn) {
                settingsBtn.addEventListener('click', () => {
                    this.hideCookieBanner();
                    this.showCookieModal();
                });
            }
        }
    }
    
    // Ocultar banner de cookies
    hideCookieBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.style.display = 'none';
        }
    }
    
    // Mostrar modal de configurações de cookies
    showCookieModal() {
        const modal = document.getElementById('cookie-settings-modal');
        if (modal) {
            // Atualizar checkboxes com as configurações atuais
            const preferencesCheckbox = document.getElementById('cookie-preferences');
            const analyticsCheckbox = document.getElementById('cookie-analytics');
            const marketingCheckbox = document.getElementById('cookie-marketing');
            
            if (preferencesCheckbox) preferencesCheckbox.checked = this.cookieConsent.preferences;
            if (analyticsCheckbox) analyticsCheckbox.checked = this.cookieConsent.analytics;
            if (marketingCheckbox) marketingCheckbox.checked = this.cookieConsent.marketing;
            
            modal.style.display = 'block';
            
            // Adicionar listeners para os botões
            const saveBtn = document.getElementById('save-cookie-settings');
            const closeBtn = document.getElementById('close-cookie-modal');
            
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    // Atualizar consentimento com base nas seleções
                    if (preferencesCheckbox) this.cookieConsent.preferences = preferencesCheckbox.checked;
                    if (analyticsCheckbox) this.cookieConsent.analytics = analyticsCheckbox.checked;
                    if (marketingCheckbox) this.cookieConsent.marketing = marketingCheckbox.checked;
                    
                    this.saveConsent();
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.hideCookieModal();
                });
            }
        }
    }
    
    // Ocultar modal de configurações de cookies
    hideCookieModal() {
        const modal = document.getElementById('cookie-settings-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // Funções auxiliares para manipulação de cookies
    setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }
    
    getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
        return null;
    }
    
    deleteCookie(name) {
        document.cookie = name + '=; Max-Age=-99999999; path=/';
    }
}

// Inicializar o gerenciador de cookies quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.cookieManager = new CookieManager();
});
