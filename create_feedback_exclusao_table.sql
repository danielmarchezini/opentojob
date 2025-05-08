-- Criar tabela para armazenar feedback de exclusão de contas
CREATE TABLE IF NOT EXISTS `feedback_exclusao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_usuario` enum('talento','empresa','admin') NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `feedback` text,
  `data_exclusao` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna data_exclusao à tabela usuarios se não existir
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `data_exclusao` datetime DEFAULT NULL;
