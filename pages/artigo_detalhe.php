<?php
// Verificar se temos um slug ou um ID
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Registrar informações para depuração
error_log('Acessando artigo - Slug: ' . $slug . ', ID: ' . $id);

if (empty($slug) && $id <= 0) {
    echo '<div class="alert alert-danger">Artigo não encontrado. Parâmetros inválidos.</div>';
    exit;
}

// Buscar artigo pelo slug ou ID
$db = Database::getInstance();

if (!empty($slug)) {
    // Buscar pelo slug
    $artigo = $db->fetch("
        SELECT a.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome
        FROM artigos_blog a
        LEFT JOIN categorias_blog c ON a.categoria_id = c.id
        LEFT JOIN usuarios u ON a.autor_id = u.id
        WHERE a.slug = :slug AND a.status = 'publicado'
    ", ['slug' => $slug]);
    
    error_log('Busca por slug: ' . ($artigo ? 'Artigo encontrado' : 'Artigo não encontrado'));
} else {
    // Buscar pelo ID (para visualização administrativa)
    $artigo = $db->fetch("
        SELECT a.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome
        FROM artigos_blog a
        LEFT JOIN categorias_blog c ON a.categoria_id = c.id
        LEFT JOIN usuarios u ON a.autor_id = u.id
        WHERE a.id = :id
    ", ['id' => $id]);
    
    error_log('Busca por ID: ' . ($artigo ? 'Artigo encontrado' : 'Artigo não encontrado'));
}

if (!$artigo) {
    echo '<div class="alert alert-danger">Artigo não encontrado.</div>';
    exit;
}

// Incrementar visualizações
$db->update('artigos_blog', 
    ['visualizacoes' => $artigo['visualizacoes'] + 1], 
    'id = :id', 
    ['id' => $artigo['id']]
);

// Buscar tags do artigo
$tags = $db->fetchAll("SELECT tag FROM tags_blog WHERE artigo_id = :artigo_id", ['artigo_id' => $artigo['id']]);

// Buscar artigos relacionados
$artigosRelacionados = [];
if (!empty($tags)) {
    $tagsList = array_column($tags, 'tag');
    $tagsStr = "'" . implode("','", $tagsList) . "'";
    
    $artigosRelacionados = $db->fetchAll("
        SELECT DISTINCT a.id, a.titulo, a.slug, a.data_publicacao, a.imagem_destaque
        FROM artigos_blog a
        JOIN tags_blog t ON a.id = t.artigo_id
        WHERE a.id != :artigo_id AND a.status = 'publicado' AND t.tag IN ($tagsStr)
        ORDER BY a.data_publicacao DESC
        LIMIT 3
    ", ['artigo_id' => $artigo['id']]);
}

// Se não houver artigos relacionados por tags, buscar pela mesma categoria
if (empty($artigosRelacionados) && !empty($artigo['categoria_id'])) {
    $artigosRelacionados = $db->fetchAll("
        SELECT a.id, a.titulo, a.slug, a.data_publicacao, a.imagem_destaque
        FROM artigos_blog a
        WHERE a.id != :artigo_id AND a.status = 'publicado' AND a.categoria_id = :categoria_id
        ORDER BY a.data_publicacao DESC
        LIMIT 3
    ", ['artigo_id' => $artigo['id'], 'categoria_id' => $artigo['categoria_id']]);
}
?>

<div class="article-header">
    <div class="container">
        <div class="article-meta">
            <?php if (!empty($artigo['categoria_nome'])): ?>
            <span class="article-category">
                <a href="<?php echo SITE_URL; ?>/?route=blog&categoria=<?php echo $artigo['categoria_slug']; ?>"><?php echo $artigo['categoria_nome']; ?></a>
            </span>
            <?php endif; ?>
            <span class="article-date"><?php echo formatDate($artigo['data_publicacao']); ?></span>
        </div>
        <h1 class="article-title"><?php echo $artigo['titulo']; ?></h1>
        <div class="article-author">
            <span>Por <?php echo $artigo['autor_nome']; ?></span>
        </div>
    </div>
</div>

<div class="container article-container">
    <div class="article-main">
        <?php if (!empty($artigo['imagem_destaque'])): ?>
        <div class="article-featured-image">
            <img src="<?php echo SITE_URL; ?>/uploads/blog/<?php echo $artigo['imagem_destaque']; ?>" alt="<?php echo $artigo['titulo']; ?>">
        </div>
        <?php endif; ?>
        
        <div class="article-content">
            <?php echo $artigo['conteudo']; ?>
        </div>
        
        <div class="article-tags">
            <?php foreach ($tags as $tag): ?>
                <a href="<?php echo SITE_URL; ?>/?route=blog&tag=<?php echo urlencode($tag['tag']); ?>" class="article-tag"><?php echo $tag['tag']; ?></a>
            <?php endforeach; ?>
        </div>
        
        <div class="article-share">
            <span>Compartilhar:</span>
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/?route=artigo&slug=' . $artigo['slug']); ?>" target="_blank" class="share-button facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/?route=artigo&slug=' . $artigo['slug']); ?>&text=<?php echo urlencode($artigo['titulo']); ?>" target="_blank" class="share-button twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/?route=artigo&slug=' . $artigo['slug']); ?>" target="_blank" class="share-button linkedin">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($artigo['titulo'] . ' - ' . SITE_URL . '/?route=artigo&slug=' . $artigo['slug']); ?>" target="_blank" class="share-button whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
        </div>
        
        <?php if (!empty($artigosRelacionados)): ?>
        <div class="related-articles">
            <h3>Artigos relacionados</h3>
            <div class="related-grid">
                <?php foreach ($artigosRelacionados as $relacionado): ?>
                <div class="related-article">
                    <?php if (!empty($relacionado['imagem_destaque'])): ?>
                    <div class="related-image">
                        <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $relacionado['slug']; ?>">
                            <img src="<?php echo SITE_URL; ?>/uploads/blog/<?php echo $relacionado['imagem_destaque']; ?>" alt="<?php echo $relacionado['titulo']; ?>">
                        </a>
                    </div>
                    <?php endif; ?>
                    <h4 class="related-title">
                        <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $relacionado['slug']; ?>"><?php echo $relacionado['titulo']; ?></a>
                    </h4>
                    <div class="related-date"><?php echo formatDate($relacionado['data_publicacao']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="article-sidebar">
        <div class="sidebar-widget">
            <h3 class="widget-title">Categorias</h3>
            <ul class="categories-list">
                <?php
                // Buscar categorias
                $categorias = $db->fetchAll("
                    SELECT c.*, COUNT(a.id) as artigos_count
                    FROM categorias_blog c
                    LEFT JOIN artigos_blog a ON c.id = a.categoria_id AND a.status = 'publicado'
                    GROUP BY c.id
                    ORDER BY c.nome
                ");
                
                foreach ($categorias as $categoria):
                ?>
                <li class="category-item">
                    <a href="<?php echo SITE_URL; ?>/?route=blog&categoria=<?php echo $categoria['slug']; ?>" class="category-link">
                        <?php echo $categoria['nome']; ?>
                        <span class="category-count"><?php echo $categoria['artigos_count']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="sidebar-widget">
            <h3 class="widget-title">Artigos populares</h3>
            <ul class="popular-posts">
                <?php
                // Buscar artigos populares
                $artigosPopulares = $db->fetchAll("
                    SELECT a.id, a.titulo, a.slug, a.data_publicacao, a.visualizacoes, a.imagem_destaque
                    FROM artigos_blog a
                    WHERE a.status = 'publicado' AND a.id != :artigo_id
                    ORDER BY a.visualizacoes DESC
                    LIMIT 5
                ", ['artigo_id' => $artigo['id']]);
                
                foreach ($artigosPopulares as $popular):
                ?>
                <li class="popular-post-item">
                    <?php if (!empty($popular['imagem_destaque'])): ?>
                    <div class="post-thumbnail">
                        <img src="<?php echo SITE_URL; ?>/uploads/blog/<?php echo $popular['imagem_destaque']; ?>" alt="<?php echo $popular['titulo']; ?>">
                    </div>
                    <?php endif; ?>
                    <div class="post-info">
                        <h4 class="post-title">
                            <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $popular['slug']; ?>"><?php echo $popular['titulo']; ?></a>
                        </h4>
                        <div class="post-meta">
                            <span class="post-date"><?php echo formatDate($popular['data_publicacao']); ?></span>
                            <span class="post-views"><?php echo $popular['visualizacoes']; ?> views</span>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="sidebar-widget">
            <h3 class="widget-title">Tags</h3>
            <div class="tags-cloud">
                <?php
                // Buscar tags populares
                $tagsPopulares = $db->fetchAll("
                    SELECT tag, COUNT(*) as count
                    FROM tags_blog
                    GROUP BY tag
                    ORDER BY count DESC
                    LIMIT 15
                ");
                
                foreach ($tagsPopulares as $tag):
                ?>
                <a href="<?php echo SITE_URL; ?>/?route=blog&tag=<?php echo urlencode($tag['tag']); ?>" class="tag-link"><?php echo $tag['tag']; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.article-header {
    background-color: var(--primary-color);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.article-meta {
    margin-bottom: 15px;
}

.article-category a {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    margin-right: 10px;
}

.article-date {
    font-size: 0.9rem;
    opacity: 0.8;
}

.article-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.article-author {
    font-size: 1.1rem;
    opacity: 0.9;
}

.article-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    margin: 40px auto;
}

@media (min-width: 992px) {
    .article-container {
        grid-template-columns: 2fr 1fr;
    }
}

.article-main {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 30px;
}

.article-featured-image {
    margin: -30px -30px 30px;
}

.article-featured-image img {
    width: 100%;
    height: auto;
    border-radius: 10px 10px 0 0;
}

.article-content {
    line-height: 1.8;
    color: var(--dark-color);
    margin-bottom: 30px;
}

.article-content p {
    margin-bottom: 20px;
}

.article-content h2, .article-content h3 {
    margin-top: 30px;
    margin-bottom: 15px;
}

.article-content ul, .article-content ol {
    margin-bottom: 20px;
    padding-left: 20px;
}

.article-content li {
    margin-bottom: 10px;
}

.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 20px 0;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}

.article-tag {
    background-color: var(--light-gray-color);
    color: var(--gray-color);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.3s;
}

.article-tag:hover {
    background-color: var(--primary-color);
    color: white;
}

.article-share {
    display: flex;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid var(--light-gray-color);
    margin-bottom: 30px;
}

.article-share span {
    margin-right: 15px;
    font-weight: 500;
}

.share-buttons {
    display: flex;
    gap: 10px;
}

.share-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.share-button:hover {
    transform: translateY(-3px);
}

.facebook {
    background-color: #3b5998;
}

.twitter {
    background-color: #1da1f2;
}

.linkedin {
    background-color: #0077b5;
}

.whatsapp {
    background-color: #25d366;
}

.related-articles {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid var(--light-gray-color);
}

.related-articles h3 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--primary-color);
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.related-article {
    background-color: var(--light-gray-color);
    border-radius: 10px;
    overflow: hidden;
}

.related-image img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.related-title {
    padding: 15px;
    font-size: 1.1rem;
    margin: 0;
}

.related-title a {
    color: var(--dark-color);
    text-decoration: none;
}

.related-title a:hover {
    color: var(--primary-color);
}

.related-date {
    padding: 0 15px 15px;
    font-size: 0.9rem;
    color: var(--gray-color);
}

.article-sidebar {
    align-self: start;
}

.sidebar-widget {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 25px;
    margin-bottom: 30px;
}

.widget-title {
    font-size: 1.3rem;
    margin-bottom: 20px;
    color: var(--primary-color);
    position: relative;
    padding-bottom: 10px;
}

.widget-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 2px;
    background-color: var(--primary-color);
}

.categories-list {
    list-style: none;
    padding: 0;
}

.category-item {
    border-bottom: 1px solid var(--light-gray-color);
}

.category-link {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    color: var(--dark-color);
    text-decoration: none;
    transition: color 0.3s;
}

.category-link:hover {
    color: var(--primary-color);
}

.category-count {
    background-color: var(--light-gray-color);
    color: var(--gray-color);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8rem;
}

.popular-posts {
    list-style: none;
    padding: 0;
}

.popular-post-item {
    display: flex;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--light-gray-color);
}

.popular-post-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.post-thumbnail {
    width: 80px;
    height: 80px;
    margin-right: 15px;
    flex-shrink: 0;
}

.post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 5px;
}

.post-info {
    flex: 1;
}

.post-info .post-title {
    font-size: 1rem;
    margin-bottom: 5px;
}

.post-info .post-meta {
    margin-bottom: 0;
    font-size: 0.8rem;
}

.tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tag-link {
    background-color: var(--light-gray-color);
    color: var(--gray-color);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.3s;
}

.tag-link:hover {
    background-color: var(--primary-color);
    color: white;
}
</style>
