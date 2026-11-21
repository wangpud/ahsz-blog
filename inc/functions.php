<?php
/**
 * 公共函数库
 */

/** HTML 转义 */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 生成站点链接（base + 路径） */
function url(string $path = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

/** 前台路由地址（根绝对路径，保证任意层级页面点击都正确） */
function link_to(string $path = ''): string {
    return '/index.php' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/** 文章链接 */
function post_link(int $id): string {
    return link_to('post/' . $id);
}
/** 分类链接 */
function cate_link(int $id): string {
    return '/categories/' . $id;
}
/** 标签链接 */
function tag_link(int $id): string {
    return link_to('tag/' . $id);
}

/** 获取全部分类（带文章数） */
function get_categories(): array {
    return db()->query("
        SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id AND p.status=1) AS post_count
        FROM categories c ORDER BY c.sort_order ASC, c.id ASC
    ")->fetchAll();
}

/** 单个分类 */
function get_category(int $id): ?array {
    $st = db()->prepare("SELECT * FROM categories WHERE id=?");
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/** 全部分签（带文章数） */
function get_tags(): array {
    return db()->query("
        SELECT t.*, (SELECT COUNT(*) FROM post_tags pt JOIN posts p ON p.id=pt.post_id WHERE pt.tag_id=t.id AND p.status=1) AS post_count
        FROM tags t ORDER BY post_count DESC, t.id ASC
    ")->fetchAll();
}

/** 文章的标签 */
function post_tags(int $postId): array {
    $st = db()->prepare("SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id=t.id WHERE pt.post_id=? ORDER BY t.id");
    $st->execute([$postId]);
    return $st->fetchAll();
}

/** 设置文章的标签（传标签名数组） */
function set_post_tags(int $postId, array $tagNames): void {
    $db = db();
    $db->prepare("DELETE FROM post_tags WHERE post_id=?")->execute([$postId]);
    $seen = [];
    foreach ($tagNames as $name) {
        $name = trim((string)$name);
        if ($name === '' || isset($seen[$name])) continue;
        $seen[$name] = 1;
        $st = $db->prepare("SELECT id FROM tags WHERE name=?");
        $st->execute([$name]);
        $tid = $st->fetchColumn();
        if (!$tid) {
            $st2 = $db->prepare("INSERT INTO tags(name,alias,created_at) VALUES(?,?,?)");
            $st2->execute([$name, strtolower($name), time()]);
            $tid = $db->lastInsertId();
        }
        $db->prepare("INSERT OR IGNORE INTO post_tags(post_id,tag_id) VALUES(?,?)")->execute([$postId, $tid]);
    }
    // 清理无文章的空标签
    $db->exec("DELETE FROM tags WHERE id NOT IN (SELECT tag_id FROM post_tags)");
}

/** 摘要：截取纯文本 */
function make_excerpt(string $html, int $len = 120): string {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);
    return mb_substr($text, 0, $len, 'UTF-8');
}

/** 日期格式化 */
function fmt_date(int $ts, string $fmt = 'Y-m-d'): string {
    return date($fmt, $ts);
}

/** 分页：返回 [html, offset, limit] */
function paginate(int $total, int $page, int $perPage, string $baseLink): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    if ($pages <= 1) {
        return ['', $offset, $perPage];
    }
    $sep = (strpos($baseLink, '?') !== false ? '&' : '?') . 'p=';
    $html = '<nav class="pagination" aria-label="分页">';
    $prev = max(1, $page - 1);
    $next = min($pages, $page + 1);
    $html .= '<a class="page-btn ' . ($page <= 1 ? 'disabled' : '') . '" href="' . e($baseLink . $sep . $prev) . '">上一页</a>';
    // 页码窗口
    $from = max(1, $page - 2);
    $to = min($pages, $page + 2);
    if ($from > 1) { $html .= '<a class="page-btn" href="' . e($baseLink . $sep . '1') . '">1</a><span class="page-dots">…</span>'; }
    for ($i = $from; $i <= $to; $i++) {
        $html .= '<a class="page-btn ' . ($i === $page ? 'active' : '') . '" href="' . e($baseLink . $sep . $i) . '">' . $i . '</a>';
    }
    if ($to < $pages) { $html .= '<span class="page-dots">…</span><a class="page-btn" href="' . e($baseLink . $sep . $pages) . '">' . $pages . '</a>'; }
    $html .= '<a class="page-btn ' . ($page >= $pages ? 'disabled' : '') . '" href="' . e($baseLink . $sep . $next) . '">下一页</a>';
    $html .= '</nav>';
    return [$html, $offset, $perPage];
}

/** 后台鉴权检查，未登录跳转 */
function require_login(): void {
    if (empty($_SESSION['admin_logged'])) {
        header('Location: index.php?m=login');
        exit;
    }
}

/** 生成 slug（简单拼音化不做，仅保留安全字符） */
function make_alias(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/** 站点统计 */
function site_stats(): array {
    $db = db();
    return [
        'posts' => (int)$db->query("SELECT COUNT(*) FROM posts WHERE status=1")->fetchColumn(),
        'categories' => (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'tags' => (int)$db->query("SELECT COUNT(*) FROM tags")->fetchColumn(),
        'views' => (int)$db->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn(),
    ];
}
