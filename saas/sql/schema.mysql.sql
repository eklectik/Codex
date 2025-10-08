CREATE TABLE IF NOT EXISTS sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    domain VARCHAR(191) NOT NULL UNIQUE,
    api_token CHAR(64) NOT NULL,
    mode ENUM('push','pull') NOT NULL DEFAULT 'push',
    theme VARCHAR(64) NOT NULL DEFAULT 'basic',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_deploy DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    slug VARCHAR(191) NOT NULL,
    title VARCHAR(191) NOT NULL,
    html MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_slug (site_id, slug),
    CONSTRAINT fk_pages_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    slug VARCHAR(191) NOT NULL,
    title VARCHAR(191) NOT NULL,
    html MEDIUMTEXT NOT NULL,
    tags VARCHAR(255) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    published_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_post_slug (site_id, slug),
    CONSTRAINT fk_posts_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    location VARCHAR(64) NOT NULL,
    items JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_site_location (site_id, location),
    CONSTRAINT fk_menus_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deploy_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id INT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL,
    payload MEDIUMTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sites (name, domain, api_token, mode, theme, is_active, created_at)
SELECT 'Demo Site', 'demo.local', '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef', 'push', 'basic', 1, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM sites WHERE domain = 'demo.local');

SET @demo_site_id := (SELECT id FROM sites WHERE domain = 'demo.local' LIMIT 1);

INSERT INTO pages (site_id, slug, title, html, status, updated_at)
SELECT @demo_site_id, 'accueil', 'Bienvenue', '<h1>Bienvenue</h1><p>Site démo.</p>', 'published', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE site_id = @demo_site_id AND slug = 'accueil');

INSERT INTO posts (site_id, slug, title, html, tags, status, published_at, updated_at)
SELECT @demo_site_id, 'hello-world', 'Hello World', '<p>Ceci est un premier article.</p>', 'seo,pbn', 'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE site_id = @demo_site_id AND slug = 'hello-world');

INSERT INTO menus (site_id, location, items, updated_at)
SELECT @demo_site_id, 'primary', JSON_ARRAY(JSON_OBJECT('label','Accueil','href','/'), JSON_OBJECT('label','Blog','href','/blog')), CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE site_id = @demo_site_id AND location = 'primary');
