-- Adicionar campos de subtítulo e comentários à tabela equipe
ALTER TABLE `equipe` 
ADD COLUMN `subtitulo` varchar(255) DEFAULT NULL COMMENT 'Subtítulo ou breve descrição do membro da equipe' AFTER `profissao`,
ADD COLUMN `comentarios` text DEFAULT NULL COMMENT 'Comentários ou biografia detalhada do membro da equipe' AFTER `subtitulo`;
