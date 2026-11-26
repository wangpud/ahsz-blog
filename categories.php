<?php
/**
 * AHSZ 极简博客 - 分类页（独立 PHP 页面，自包含）
 * · 无参数：/categories.php 或 /categories        → 分类总览（卡片网格）
 * · 带分类：/categories.php?id=1 或 /categories/1 → 该分类的文章列表
 */
require_once __DIR__ . '/inc/config.php';
if (!db_is_installed()) { require __DIR__ . '/inc/need-install.php'; exit; }
db_init();
require_once __DIR__ . '/inc/cache.php';

header('Cache-Control: public, max-age=60');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($cachedHtml = page_cache_get()) !== null) {
    header('X-Page-Cache: HIT');
    echo $cachedHtml;
    exit;
}
header('X-Page-Cache: MISS');
ob_start();

$db = db();
$active = 'cate';

// 解析分类 ID：优先 GET 参数，其次路径 /categories/{id}
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$catId = 0;
if (isset($_GET['id']) && preg_match('/^\d+$/', (string)$_GET['id'])) {
    $catId = (int)$_GET['id'];
} elseif (preg_match('#^/categories/(\d+)/?$#', $uriPath, $m)) {
    $catId = (int)$m[1];
}
$cat = $catId > 0 ? get_category($catId) : null;

// 分类配色与图标（总览卡片用）
$tileColors = [
  'oklch(0.62 0.17 150)', 'oklch(0.6 0.19 250)', 'oklch(0.62 0.18 30)',
  'oklch(0.6 0.17 320)', 'oklch(0.58 0.16 200)', 'oklch(0.64 0.16 90)'
];
$tileIcons = [
  '<path d="M4 5h16v14H4z"/><circle cx="9" cy="10" r="2"/><path d="m4 17 5-4 4 3 3-2 4 4"/>',
  '<path d="M4 19V5M4 19h16"/><path d="m6 15 4-5 3 3 5-6"/>',
  '<path d="M12 3 4 8v8l8 5 8-5V8z"/><path d="M4 8l8 5 8-5M12 13v8"/>',
  '<path d="M12 3a6 6 0 0 0-6 6c0 4 6 12 6 12s6-8 6-12a6 6 0 0 0-6-6z"/><circle cx="12" cy="9" r="2"/>',
  '<path d="M4 20 20 4"/><circle cx="8" cy="8" r="4"/><circle cx="16" cy="16" r="4"/>',
  '<path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="5"/>'
];

// 分类文章列表数据
if ($cat) {
    $pageTitle = $cat['name'];
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPage = max(1, (int)opt('per_page', '6'));
    $cid = (int)$cat['id'];
    $total = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status=1 AND category_id={$cid}")->fetchColumn();
    [$pager, $offset] = paginate($total, $page, $perPage, '/categories/' . $cid);
    $posts = $db->query("SELECT * FROM posts WHERE status=1 AND category_id={$cid} ORDER BY created_at DESC LIMIT {$offset},{$perPage}")->fetchAll();
    preload_post_meta($posts);
} else {
    $pageTitle = '文章分类';
    $cats = get_categories();
}
?>
<?php include __DIR__ . '/views/header.php'; ?>
<div class="layout">
  <?php include __DIR__ . '/views/sidebar.php'; ?>
  <main class="main">
  <?php if ($cat): ?>
    <!-- 本分类文章列表 -->
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <?= e($cat['name']) ?></h1>
      <a href="/categories" class="btn btn-ghost btn-sm" style="background:var(--btn-bg);padding:.4rem .8rem;border-radius:.5rem;font-size:.82rem;">← 所有分类</a></div>
    <?php if ($cat['description']): ?>
      <div class="card cat-intro" style="padding:1rem 1.4rem;margin-bottom:1rem;color:var(--muted);font-size:.9rem;"><?= e($cat['description']) ?></div>
    <?php endif; ?>
    <div class="cat-count-bar" style="color:var(--muted);font-size:.85rem;margin-bottom:.6rem;">共 <?= $total ?> 篇文章</div>
    <?php foreach ($posts as $post): include __DIR__ . '/views/post-card.php'; endforeach; ?>
    <?php if (!$posts): ?>
      <div class="card empty"><p>这个分类下还没有文章。</p></div>
    <?php endif; ?>
    <?= $pager ?>
  <?php else: ?>
    <!-- 分类总览 -->
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      文章分类</h1><span class="count">共 <?= count($cats) ?> 个分类</span></div>
    <div class="cate-grid">
      <?php foreach ($cats as $i => $c): ?>
        <?php
          $color = $tileColors[$i % count($tileColors)];
          $icon = $tileIcons[$i % count($tileIcons)];
          $href = '/categories/' . (int)$c['id'];
        ?>
        <div class="card cate-tile animate-in" style="--tile-color:<?= $color ?>;">
          <div class="tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?= $icon ?></svg></div>
          <h3><a href="<?= e($href) ?>"><?= e($c['name']) ?></a></h3>
          <div class="tile-desc"><?= e($c['description'] ?: '这个分类下的所有文章。') ?></div>
          <div class="tile-foot">
            <span class="tile-count"><?= (int)$c['post_count'] ?> 篇文章</span>
            <a class="tile-arrow" href="<?= e($href) ?>">查看
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$cats): ?>
      <div class="card empty"><p>还没有分类，去后台创建几个吧。</p></div>
    <?php endif; ?>
  <?php endif; ?>
  </main>
</div>
<?php
include __DIR__ . '/views/footer.php';
$html = ob_get_clean();
page_cache_set($html);
echo $html;
