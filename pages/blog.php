<div class="blog-header">
    <div class="container">
        <h1 class="blog-title">Blog Open2W</h1>
        <p class="blog-subtitle">Dicas, tendências e informações sobre o mercado de trabalho</p>
    </div>
</div>

<div class="container blog-container">
    <div class="blog-main">
        <?php
        // Buscar artigos do blog
        $db = Database::getInstance();
        $artigos = $db->fetchAll("
            SELECT a.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome
            FROM artigos_blog a
            LEFT JOIN categorias_blog c ON a.categoria_id = c.id
            LEFT JOIN usuarios u ON a.autor_id = u.id
            WHERE a.status = 'publicado'
            ORDER BY a.data_publicacao DESC
            LIMIT 10
        ");
        
        if (empty($artigos)) {
            echo '<div class="alert alert-info">Nenhum artigo publicado ainda.</div>';
        } else {
            foreach ($artigos as $artigo):
        ?>
            <div class="blog-post">
                <?php if (!empty($artigo['imagem_destaque'])): ?>
                <div class="post-image">
                    <img src="<?php echo SITE_URL; ?>/uploads/blog/<?php echo $artigo['imagem_destaque']; ?>" alt="<?php echo $artigo['titulo']; ?>">
                </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <h2 class="post-title">
                        <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $artigo['slug']; ?>"><?php echo $artigo['titulo']; ?></a>
                    </h2>
                    
                    <div class="post-meta">
                        <span class="post-date">
                            <i class="far fa-calendar-alt"></i> <?php echo formatDate($artigo['data_publicacao']); ?>
                        </span>
                        <span class="post-author">
                            <i class="far fa-user"></i> <?php echo $artigo['autor_nome']; ?>
                        </span>
                        <?php if (!empty($artigo['categoria_nome'])): ?>
                        <span class="post-category">
                            <i class="far fa-folder"></i> 
                            <a href="<?php echo SITE_URL; ?>/?route=blog&categoria=<?php echo $artigo['categoria_slug']; ?>"><?php echo $artigo['categoria_nome']; ?></a>
                        </span>
                        <?php endif; ?>
                        <span class="post-views">
                            <i class="far fa-eye"></i> <?php echo $artigo['visualizacoes']; ?> visualizações
                        </span>
                    </div>
                    
                    <div class="post-excerpt">
                        <?php echo truncateText(strip_tags($artigo['conteudo']), 200); ?>
                    </div>
                    
                    <div class="post-tags">
                        <?php
                        // Buscar tags do artigo
                        $tags = $db->fetchAll("SELECT tag FROM tags_blog WHERE artigo_id = :artigo_id", ['artigo_id' => $artigo['id']]);
                        foreach ($tags as $tag):
                        ?>
                            <a href="<?php echo SITE_URL; ?>/?route=blog&tag=<?php echo urlencode($tag['tag']); ?>" class="post-tag"><?php echo $tag['tag']; ?></a>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $artigo['slug']; ?>" class="read-more">Ler mais <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        <?php
            endforeach;
        }
        ?>
        
        <div class="pagination">
            <ul class="pagination-list">
                <li class="pagination-item"><a href="#"><i class="fas fa-chevron-left"></i></a></li>
                <li class="pagination-item active"><span>1</span></li>
                <li class="pagination-item"><a href="#">2</a></li>
                <li class="pagination-item"><a href="#">3</a></li>
                <li class="pagination-item"><a href="#"><i class="fas fa-chevron-right"></i></a></li>
            </ul>
        </div>
    </div>
    
    <div class="blog-sidebar">
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
            <h3 class="widget-title">Tags populares</h3>
            <div class="tags-cloud">
                <?php
                // Buscar tags populares
                $tags = $db->fetchAll("
                    SELECT tag, COUNT(*) as count
                    FROM tags_blog
                    GROUP BY tag
                    ORDER BY count DESC
                    LIMIT 15
                ");
                
                foreach ($tags as $tag):
                ?>
                <a href="<?php echo SITE_URL; ?>/?route=blog&tag=<?php echo urlencode($tag['tag']); ?>" class="tag-link"><?php echo $tag['tag']; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="sidebar-widget">
            <h3 class="widget-title">Artigos populares</h3>
            <ul class="popular-posts">
                <?php
                // Buscar artigos populares
                $artigosPopulares = $db->fetchAll("
                    SELECT a.id, a.titulo, a.slug, a.data_publicacao, a.visualizacoes, a.imagem_destaque
                    FROM artigos_blog a
                    WHERE a.status = 'publicado'
                    ORDER BY a.visualizacoes DESC
                    LIMIT 5
                ");
                
                foreach ($artigosPopulares as $artigo):
                ?>
                <li class="popular-post-item">
                    <?php if (!empty($artigo['imagem_destaque'])): ?>
                    <div class="post-thumbnail">
                        <img src="<?php echo SITE_URL; ?>/uploads/blog/<?php echo $artigo['imagem_destaque']; ?>" alt="<?php echo $artigo['titulo']; ?>">
                    </div>
                    <?php endif; ?>
                    <div class="post-info">
                        <h4 class="post-title">
                            <a href="<?php echo SITE_URL; ?>/?route=artigo&slug=<?php echo $artigo['slug']; ?>"><?php echo $artigo['titulo']; ?></a>
                        </h4>
                        <div class="post-meta">
                            <span class="post-date"><?php echo formatDate($artigo['data_publicacao']); ?></span>
                            <span class="post-views"><?php echo $artigo['visualizacoes']; ?> views</span>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="sidebar-widget">
            <h3 class="widget-title">Inscreva-se</h3>
            <p>Receba as últimas novidades e artigos diretamente no seu e-mail.</p>
            <form class="subscribe-form">
                <div class="form-group">
                    <input type="email" class="form-control" placeholder="Seu e-mail">
                </div>
                <button type="submit" class="btn btn-primary">Inscrever-se</button>
            </form>
        </div>
    </div>
</div>

<style>
.blog-header {
    background-color: var(--primary-color);
    color: white;
    padding: 60px 0;
    text-align: center;
    margin-bottom: 40px;
}

.blog-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.blog-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
}

.blog-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    margin-bottom: 50px;
}

@media (min-width: 992px) {
    .blog-container {
        grid-template-columns: 2fr 1fr;
    }
}

.blog-post {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    overflow: hidden;
}

.post-image img {
    width: 100%;
    height: auto;
    object-fit: cover;
}

.post-content {
    padding: 25px;
}

.post-title {
    font-size: 1.6rem;
    margin-bottom: 15px;
}

.post-title a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color 0.3s;
}

.post-title a:hover {
    color: var(--primary-color);
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: var(--gray-color);
}

.post-meta span {
    display: flex;
    align-items: center;
}

.post-meta i {
    margin-right: 5px;
}

.post-meta a {
    color: var(--gray-color);
    text-decoration: none;
}

.post-meta a:hover {
    color: var(--primary-color);
}

.post-excerpt {
    margin-bottom: 20px;
    line-height: 1.6;
    color: var(--dark-color);
}

.post-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.post-tag {
    background-color: var(--light-gray-color);
    color: var(--gray-color);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.3s;
}

.post-tag:hover {
    background-color: var(--primary-color);
    color: white;
}

.read-more {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    font-weight: 500;
    text-decoration: none;
}

.read-more i {
    margin-left: 5px;
    transition: transform 0.3s;
}

.read-more:hover i {
    transform: translateX(5px);
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

.subscribe-form .form-group {
    margin-bottom: 15px;
}

.subscribe-form .btn {
    width: 100%;
}
</style>
