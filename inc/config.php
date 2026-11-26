<?php
/**
 * AHSZ 极简博客 - 全局配置
 * 全新独立博客程序，无插件/模块/应用/页面概念
 * 数据库：MySQL（连接参数由 install 安装向导写入 data/db.config.php）
 */
define('APP_NAME', 'AHSZ 极简博客');
define('APP_VER', '2.0');
define('DB_CONFIG_FILE', __DIR__ . '/../data/db.config.php');
define('INSTALL_LOCK_FILE', __DIR__ . '/../data/install.lock');

// 动态站点地址：跟随访问时的主机（支持 localhost / 127.0.0.1 / 局域网IP / 域名）
$_host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8092';
if (!preg_match('/^[\w.\-:]+$/', $_host)) { $_host = '127.0.0.1:8092'; }
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('SITE_URL', $_scheme . '://' . $_host . '/');

// 时区
date_default_timezone_set('Asia/Shanghai');

// 错误处理：不向访客显示错误详情（避免泄露绝对路径/SQL），但记录到日志便于排查
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 输出压缩：HTML 经 gzip 后通常减少 60%~70% 传输体积
if (extension_loaded('zlib') && !headers_sent() && PHP_SAPI !== 'cli'
    && stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
    ob_start('ob_gzhandler');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
