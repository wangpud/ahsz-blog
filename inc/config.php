<?php
/**
 * AHSZ 极简博客 - 全局配置
 * 全新独立博客程序，无插件/模块/应用/页面概念
 */
define('APP_NAME', 'AHSZ 极简博客');
define('APP_VER', '1.0');
define('DB_PATH', __DIR__ . '/../data/blog.db');
// 动态站点地址：跟随访问时的主机（支持 localhost / 127.0.0.1 / 局域网IP / 域名）
$_host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8092';
if (!preg_match('/^[\w.\-:]+$/', $_host)) { $_host = '127.0.0.1:8092'; }
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('SITE_URL', $_scheme . '://' . $_host . '/');

// 时区
date_default_timezone_set('Asia/Shanghai');

// 开启错误显示（便于本地调试）
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
