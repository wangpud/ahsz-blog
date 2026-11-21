<?php
/**
 * 数据库层：全新精简的 SQLite 结构
 * 仅 5 张表：posts(文章) / categories(分类) / tags(标签) / post_tags(关联) / options(站点配置)
 * 无 application / plugin / module / page 相关表
 */

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }
    return $pdo;
}

/**
 * 初始化数据库结构（幂等），返回是否新建
 */
function db_init(): bool {
    $isNew = !file_exists(DB_PATH);
    $db = db();
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        alias TEXT NOT NULL UNIQUE,
        description TEXT NOT NULL DEFAULT '',
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at INTEGER NOT NULL
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        alias TEXT NOT NULL DEFAULT '',
        summary TEXT NOT NULL DEFAULT '',
        content TEXT NOT NULL DEFAULT '',
        category_id INTEGER NOT NULL DEFAULT 0,
        cover TEXT NOT NULL DEFAULT '',
        views INTEGER NOT NULL DEFAULT 0,
        is_top INTEGER NOT NULL DEFAULT 0,
        status INTEGER NOT NULL DEFAULT 1,
        created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        alias TEXT NOT NULL DEFAULT '',
        created_at INTEGER NOT NULL
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
        post_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY(post_id, tag_id),
        FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS options (
        opt_key TEXT PRIMARY KEY,
        opt_value TEXT NOT NULL DEFAULT ''
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )");
    return $isNew;
}

/**
 * 读取站点配置
 */
function opt(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query("SELECT opt_key, opt_value FROM options") as $row) {
                $cache[$row['opt_key']] = $row['opt_value'];
            }
        } catch (Throwable $e) { $cache = []; }
    }
    return $cache[$key] ?? $default;
}

/**
 * 保存站点配置
 */
function opt_set(string $key, string $value): void {
    $st = db()->prepare("INSERT INTO options(opt_key,opt_value) VALUES(?,?) ON CONFLICT(opt_key) DO UPDATE SET opt_value=excluded.opt_value");
    $st->execute([$key, $value]);
}
