-- Adicionar coluna status à tabela newsletter_inscritos
ALTER TABLE newsletter_inscritos ADD COLUMN status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo';

-- Atualizar todos os registros existentes para status 'ativo'
UPDATE newsletter_inscritos SET status = 'ativo';

-- Comentário para documentação
-- Esta coluna é usada para controlar se um inscrito da newsletter está ativo ou inativo
-- Os inscritos ativos recebem as newsletters, os inativos não
