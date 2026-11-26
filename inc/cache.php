<?php
/**
 * 前台页面缓存：把完整 HTML 缓存到文件，命中时跳过数据库查询与模板渲染。
 * 后台任何内容变更都会调用 page_cache_clear() 立即失效。
 */
if (!defined('APP_NAME')) { http_response_code(403); exit; }

/** 缓存目录 */
function page_cache_dir(): string {
    return dirname(__DIR__) . '/data/cache';
}

/** 缓存键：按完整请求 URI（含分页等查询参数）区分 */
function page_cache_key(): string {
    return md5($_SERVER['REQUEST_URI'] ?? '/');
}

/** 读取缓存，未命中或过期返回 null */
function page_cache_get(int $ttl = 300): ?string {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return null;
    $file = page_cache_dir() . '/' . page_cache_key() . '.html';
    if (!is_file($file)) return null;
    if (time() - (int)filemtime($file) > $ttl) { @unlink($file); return null; }
    $html = @file_get_contents($file);
    return $html === false ? null : $html;
}

/** 写入缓存（目录不可写时静默跳过，不影响站点运行） */
function page_cache_set(string $html): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $dir = page_cache_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return;
    @file_put_contents($dir . '/' . page_cache_key() . '.html', $html, LOCK_EX);
}

/** 清空全部页面缓存 */
function page_cache_clear(): void {
    foreach (glob(page_cache_dir() . '/*.html') ?: [] as $f) @unlink($f);
}
