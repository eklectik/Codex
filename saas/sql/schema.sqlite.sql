PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS sites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    domain TEXT NOT NULL UNIQUE,
    api_token TEXT NOT NULL,
    mode TEXT NOT NULL DEFAULT 'push',
    theme TEXT NOT NULL DEFAULT 'basic',
    is_active INTEGER NOT NULL DEFAULT 1,
    last_deploy TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    html TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'published',
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, slug),
    FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE TRIGGER IF NOT EXISTS pages_updated_at AFTER UPDATE ON pages
BEGIN
    UPDATE pages SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    html TEXT NOT NULL,
    tags TEXT,
    status TEXT NOT NULL DEFAULT 'published',
    published_at TEXT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, slug),
    FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE TRIGGER IF NOT EXISTS posts_updated_at AFTER UPDATE ON posts
BEGIN
    UPDATE posts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    location TEXT NOT NULL,
    items TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, location),
    FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE TRIGGER IF NOT EXISTS menus_updated_at AFTER UPDATE ON menus
BEGIN
    UPDATE menus SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TABLE IF NOT EXISTS deploy_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    payload TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE
);

INSERT OR IGNORE INTO sites (id, name, domain, api_token, mode, theme, is_active, created_at)
VALUES (1, 'Demo Site', 'demo.local', '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef', 'push', 'basic', 1, CURRENT_TIMESTAMP);

INSERT OR IGNORE INTO pages (site_id, slug, title, html, status, updated_at)
VALUES (1, 'accueil', 'Bienvenue', '<h1>Bienvenue</h1><p>Site démo.</p>', 'published', CURRENT_TIMESTAMP);

INSERT OR IGNORE INTO posts (site_id, slug, title, html, tags, status, published_at, updated_at)
VALUES (1, 'hello-world', 'Hello World', '<p>Ceci est un premier article.</p>', 'seo,pbn', 'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT OR IGNORE INTO menus (site_id, location, items, updated_at)
VALUES (1, 'primary', '[{"label":"Accueil","href":"/"},{"label":"Blog","href":"/blog"}]', CURRENT_TIMESTAMP);
