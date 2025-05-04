/**
 * Funções auxiliares para o painel administrativo
 */

/**
 * Função para processar a resposta da API e extrair os dados do item
 * @param {Object} data Dados retornados pela API
 * @param {string} itemType Tipo de item (artigo, vaga, talento, etc.)
 * @returns {Object|null} Dados do item ou null se não encontrado
 */
function processApiResponse(data, itemType) {
    console.log(`Processando resposta para ${itemType}:`, data);
    
    // Verificar se a resposta foi bem-sucedida
    if (!data || !data.success) {
        const errorMsg = data && data.message ? data.message : 'Erro desconhecido';
        console.error(`Erro na resposta da API: ${errorMsg}`);
        return null;
    }
    
    // Verificar diferentes estruturas de resposta
    let item = null;
    
    // Formato 1: data.data.itemType (ex: data.data.artigo)
    if (data.data && data.data[itemType]) {
        item = data.data[itemType];
    } 
    // Formato 2: data.itemType (ex: data.artigo)
    else if (data[itemType]) {
        item = data[itemType];
    } 
    // Formato 3: data.data (quando o item está diretamente em data.data)
    else if (data.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
        item = data.data;
    }
    // Formato 4: Quando o item está em outra propriedade com nome diferente
    else if (data.data) {
        // Tentar encontrar uma propriedade que possa conter o item
        for (const key in data.data) {
            if (typeof data.data[key] === 'object' && !Array.isArray(data.data[key])) {
                console.log(`Tentando usar dados de data.data.${key}`);
                item = data.data[key];
                break;
            }
        }
        
        // Se ainda não encontrou, usar o primeiro item de um array se for um array
        if (!item && Array.isArray(data.data) && data.data.length > 0) {
            console.log('Tentando usar o primeiro item do array data.data');
            item = data.data[0];
        }
    }
    
    if (!item) {
        console.error(`Dados do ${itemType} não encontrados na resposta`);
        return null;
    }
    
    console.log(`Dados do ${itemType} extraídos:`, item);
    return item;
}

/**
 * Função para exibir mensagem de erro no modal
 * @param {string} formId ID do formulário onde exibir o erro
 * @param {string} errorMessage Mensagem de erro
 */
function showErrorInModal(formId, errorMessage) {
    const form = document.getElementById(formId);
    if (form) {
        form.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                ${errorMessage}
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        `;
    } else {
        alert(errorMessage);
    }
}

/**
 * Função para formatar data
 * @param {string} dateString Data em formato ISO
 * @returns {string} Data formatada
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const options = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', options);
    } catch (e) {
        return dateString;
    }
}

/**
 * Função para formatar texto com quebras de linha
 * @param {string} text Texto a ser formatado
 * @returns {string} HTML com quebras de linha
 */
function formatTextWithLineBreaks(text) {
    if (!text) return '';
    return text.replace(/\n/g, '<br>');
}

/**
 * Função para formatar salário
 * @param {number} min Salário mínimo
 * @param {number} max Salário máximo
 * @returns {string} Salário formatado
 */
function formatSalario(min, max) {
    const formatNumber = (num) => {
        if (!num) return 'N/A';
        return new Intl.NumberFormat('pt-BR', { 
            style: 'currency', 
            currency: 'BRL' 
        }).format(num);
    };
    
    if (min && max && min === max) {
        return formatNumber(min);
    } else if (min && max) {
        return `${formatNumber(min)} - ${formatNumber(max)}`;
    } else if (min) {
        return `A partir de ${formatNumber(min)}`;
    } else if (max) {
        return `Até ${formatNumber(max)}`;
    } else {
        return 'Não informado';
    }
}

/**
 * Função para obter HTML de badge de status
 * @param {string} status Status do item
 * @param {string} tipo Tipo de item (vaga, artigo, etc.)
 * @returns {string} HTML do badge
 */
function getStatusBadgeHTML(status, tipo) {
    let badge = '';
    
    if (tipo === 'vaga') {
        if (status === 'aberta') {
            badge = '<span class="badge badge-success">Aberta</span>';
        } else if (status === 'fechada') {
            badge = '<span class="badge badge-secondary">Fechada</span>';
        } else if (status === 'pendente') {
            badge = '<span class="badge badge-warning">Pendente</span>';
        } else {
            badge = `<span class="badge badge-info">${status}</span>`;
        }
    } else if (tipo === 'artigo') {
        if (status === 'publicado') {
            badge = '<span class="badge badge-success">Publicado</span>';
        } else if (status === 'rascunho') {
            badge = '<span class="badge badge-secondary">Rascunho</span>';
        } else {
            badge = `<span class="badge badge-info">${status}</span>`;
        }
    } else {
        badge = `<span class="badge badge-info">${status}</span>`;
    }
    
    return badge;
}
