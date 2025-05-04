-- Atualizar usuário administrador padrão caso já exista
-- Senha: admin123 (hash MD5)
UPDATE usuarios SET
    nome = 'Administrador Open2W',
    senha = '0192023a7bbd73250516f069df18b500',
    tipo = 'admin',
    status = 'ativo'
WHERE email = 'admin@open2w.com.br';

-- Inserir categorias iniciais para o blog
INSERT INTO categorias_blog (nome, slug, descricao) VALUES 
('Carreira', 'carreira', 'Dicas e orientações para desenvolvimento profissional'),
('Mercado de Trabalho', 'mercado-de-trabalho', 'Análises e tendências do mercado de trabalho'),
('Recrutamento', 'recrutamento', 'Informações sobre processos seletivos e recrutamento'),
('Tecnologia', 'tecnologia', 'Novidades tecnológicas e seu impacto no trabalho'),
('Entrevistas', 'entrevistas', 'Dicas para se destacar em entrevistas de emprego');

-- Inserir artigo de exemplo
INSERT INTO artigos_blog (
    titulo,
    slug,
    conteudo,
    categoria_id,
    autor_id,
    imagem_destaque,
    data_publicacao,
    status,
    meta_descricao,
    meta_keywords
) VALUES (
    'Como se destacar no mercado de trabalho em 2025',
    'como-se-destacar-no-mercado-de-trabalho-em-2025',
    '<p>O mercado de trabalho está em constante evolução, e se manter relevante é um desafio contínuo para profissionais de todas as áreas. Neste artigo, vamos explorar as principais tendências e habilidades que farão a diferença em 2025.</p>
    <h2>Habilidades técnicas em alta</h2>
    <p>Com a crescente digitalização, algumas habilidades técnicas se tornaram essenciais:</p>
    <ul>
        <li>Análise de dados e inteligência artificial</li>
        <li>Desenvolvimento de software e programação</li>
        <li>Cibersegurança</li>
        <li>Conhecimento em ferramentas de colaboração remota</li>
    </ul>
    <h2>Soft skills indispensáveis</h2>
    <p>Além das habilidades técnicas, as competências comportamentais são cada vez mais valorizadas:</p>
    <ul>
        <li>Adaptabilidade e resiliência</li>
        <li>Inteligência emocional</li>
        <li>Pensamento crítico e resolução de problemas complexos</li>
        <li>Criatividade e inovação</li>
        <li>Comunicação eficaz em ambientes remotos e híbridos</li>
    </ul>
    <h2>Construa sua marca pessoal</h2>
    <p>Em um mercado competitivo, sua marca pessoal pode ser o diferencial. Invista em:</p>
    <ul>
        <li>Presença digital profissional</li>
        <li>Networking estratégico</li>
        <li>Compartilhamento de conhecimento</li>
        <li>Projetos pessoais que demonstrem suas habilidades</li>
    </ul>
    <h2>Aprendizado contínuo</h2>
    <p>A capacidade de aprender continuamente é talvez a habilidade mais importante para o futuro do trabalho. Estabeleça uma rotina de aprendizado que inclua:</p>
    <ul>
        <li>Cursos online e certificações</li>
        <li>Leitura de livros e artigos da sua área</li>
        <li>Participação em eventos e conferências</li>
        <li>Mentoria e coaching</li>
    </ul>
    <p>Lembre-se: o mercado valoriza profissionais que demonstram iniciativa e capacidade de adaptação. Mantenha-se atualizado, desenvolva um conjunto diversificado de habilidades e esteja sempre aberto a novas oportunidades.</p>',
    1, -- categoria_id (Carreira)
    1, -- autor_id (Administrador)
    'artigo-destaque-mercado-trabalho.jpg',
    NOW(),
    'publicado',
    'Descubra as principais tendências e habilidades necessárias para se destacar no mercado de trabalho em 2025.',
    'mercado de trabalho, carreira, habilidades, 2025, profissional, emprego'
);

-- Inserir tags para o artigo
INSERT INTO tags_blog (artigo_id, tag) VALUES 
(1, 'carreira'),
(1, 'mercado de trabalho'),
(1, 'habilidades'),
(1, 'desenvolvimento profissional'),
(1, 'tendências');
