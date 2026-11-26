<?php
/**
 * 数据库层：MySQL（PDO）
 * 6 张表：posts / categories / tags / post_tags / options / admins
 * 连接参数由安装向导写入 data/db.config.php
 */

/** 是否已安装（存在数据库连接配置文件） */
function db_is_installed(): bool {
    return is_file(DB_CONFIG_FILE);
}

/** 是否已锁定安装向导 */
function install_is_locked(): bool {
    return is_file(INSTALL_LOCK_FILE);
}

/** 读取数据库配置 */
function db_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        if (!db_is_installed()) {
            throw new RuntimeException('站点尚未安装，请先访问 /install/ 完成安装。');
        }
        $cfg = require DB_CONFIG_FILE;
        if (!is_array($cfg)) {
            throw new RuntimeException('数据库配置文件格式错误。');
        }
        $cfg += [
            'host' => '127.0.0.1',
            'port' => 3306,
            'dbname' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
        ];
    }
    return $cfg;
}

/** 获取 PDO 连接（单例） */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = db_config();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'], (int)$c['port'], $c['dbname'], $c['charset']
        );
        $pdo = new PDO($dsn, $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

/**
 * 兼容旧调用：安装后表已由向导创建，这里只做存在性确认（幂等）。
 */
function db_init(): bool {
    return db_is_installed();
}

/**
 * 读取站点配置
 */
function opt(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query("SELECT opt_key, opt_value FROM `options`") as $row) {
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
    $st = db()->prepare("INSERT INTO `options`(`opt_key`,`opt_value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `opt_value`=VALUES(`opt_value`)");
    $st->execute([$key, $value]);
}
