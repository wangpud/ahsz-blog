<?php
/**
 * PHP 内置服务器路由脚本
 * 静态文件直接放行；其余请求交给 index.php / admin/index.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// 根目录
if ($uri === '/') { require __DIR__ . '/index.php'; return true; }

// 独立安装向导（与前台完全分离，安装成功后自锁）
if ($uri === '/install' || $uri === '/install/' || $uri === '/install/index.php') {
    require __DIR__ . '/install/index.php';
    return true;
}

// 独立页面：分类页（无扩展名 URL）
// /categories            → 总览
// /categories/{id}       → 该分类的文章列表
if ($uri === '/categories' || $uri === '/categories/' || preg_match('#^/categories/\d+/?$#', $uri)) {
    require __DIR__ . '/categories.php';
    return true;
}

// 敏感路径禁止直接访问：数据库/配置/核心代码/模板/版本控制文件
// 防止 data/blog.db（含管理员哈希）、data/db.config.php、inc/*、views/* 被下载
if (preg_match('#^/(data|inc|views)(/|$)#i', $uri)
    || preg_match('#^/admin/views(/|$)#i', $uri)
    || preg_match('#^/\.(git|env|htaccess)#i', $uri)) {
    http_response_code(404);
    return true;
}

// 已存在的静态文件：加浏览器缓存与 ETag；CSS/JS 直接压缩输出
if (is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['css','js','png','jpg','jpeg','gif','webp','svg','ico','woff2'], true)) {
        $mtime = (int)filemtime($file);
        $etag = '"' . $mtime . '-' . (int)filesize($file) . '"';
        header('Cache-Control: public, max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('X-Content-Type-Options: nosniff');
        $notModified = (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag)
            || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (int)strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime);
        if ($notModified) { http_response_code(304); return true; }

        if (in_array($ext, ['css','js'], true)) {
            header('Content-Type: ' . ($ext === 'css' ? 'text/css' : 'application/javascript') . '; charset=utf-8');
            $body = (string)file_get_contents($file);
            if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
                header('Content-Encoding: gzip');
                $body = gzencode($body, 6);
            }
            header('Content-Length: ' . strlen($body));
            echo $body;
            return true;
        }
    }
    return false;
}

// 后台
if (preg_match('#^/admin(?:/|$)#', $uri)) {
    require __DIR__ . '/admin/index.php';
    return true;
}

// 前台交给 index.php（PATH_INFO 由内置服务器自动设置）
require __DIR__ . '/index.php';
return true;
