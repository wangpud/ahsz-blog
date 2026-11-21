<?php
/**
 * PHP 内置服务器路由脚本
 * 静态文件直接放行；其余请求交给 index.php / admin/index.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// 根目录
if ($uri === '/') { require __DIR__ . '/index.php'; return true; }

// 独立页面：分类页（无扩展名 URL）
// /categories            → 总览
// /categories/{id}       → 该分类的文章列表
if ($uri === '/categories' || $uri === '/categories/' || preg_match('#^/categories/\d+/?$#', $uri)) {
    require __DIR__ . '/categories.php';
    return true;
}

// 已存在的静态文件，放行
if (is_file($file)) { return false; }

// 后台
if (preg_match('#^/admin(?:/|$)#', $uri)) {
    require __DIR__ . '/admin/index.php';
    return true;
}

// 前台交给 index.php（PATH_INFO 由内置服务器自动设置）
require __DIR__ . '/index.php';
return true;
