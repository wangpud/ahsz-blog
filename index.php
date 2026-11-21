<?php
/**
 * AHSZ 极简博客 - 前台单入口
 * 路由： / 首页 · /post/{id} · /cate/{id} · /tags · /tag/{id} · /archives · /search
 */
require_once __DIR__ . '/inc/config.php';
db_init();

// 解析路由（兼容 router.php：从 REQUEST_URI 提取路径）
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uriPath ?? '', '/');
// 去掉 index.php 前缀（若直接访问 index.php/xxx）
if (stripos($path, 'index.php') === 0) {
    $path = trim(substr($path, strlen('index.php')), '/');
}
$parts = $path !== '' ? explode('/', $path) : [];
$route = $parts[0] ?? 'home';
$arg = isset($parts[1]) ? (int)$parts[1] : 0;
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = max(1, (int)opt('per_page', '6'));

$active = 'home';
$pageTitle = '';
$db = db();

ob_start();

switch ($route) {

  // ---------- 首页 ----------
  case 'home':
    $active = 'home';
    $total = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status=1")->fetchColumn();
    [$pager, $offset] = paginate($total, $page, $perPage, link_to());
    $posts = $db->query("SELECT * FROM posts WHERE status=1 ORDER BY is_top DESC, created_at DESC LIMIT {$offset},{$perPage}")->fetchAll();
    ?>
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>
      最新文章</h1><span class="count">共 <?= $total ?> 篇</span></div>
    <?php foreach ($posts as $post): include __DIR__ . '/views/post-card.php'; endforeach; ?>
    <?php if (!$posts): ?>
      <div class="card empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg><p>还没有文章，去后台写一篇吧。</p></div>
    <?php endif; ?>
    <?= $pager ?>
    <?php
    break;

  // ---------- 分类总览页：已独立为 categories.php，这里做旧链接兼容 ----------
  case 'categories':
    header('Location: /categories', true, 302);
    exit;

  // ---------- 分类文章列表：已整合进独立页 categories.php，旧链接重定向兼容 ----------
  case 'cate':
    if ($arg > 0) { header('Location: /categories/' . $arg, true, 302); exit; }
    header('Location: /categories', true, 302);
    exit;

  // ---------- 标签聚合页 ----------
  case 'tags':
    $active = 'tags';
    $pageTitle = '标签';
    $tags = get_tags();
    ?>
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
      所有标签</h1><span class="count"><?= count($tags) ?> 个</span></div>
    <div class="card" style="padding:1.6rem;">
      <div class="tag-cloud" style="gap:.7rem;">
        <?php foreach ($tags as $t): ?>
          <a href="<?= e(tag_link((int)$t['id'])) ?>" style="font-size:.9rem;padding:.45rem .9rem;">#<?= e($t['name']) ?><span class="c"><?= (int)$t['post_count'] ?></span></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    break;

  // ---------- 单标签文章页 ----------
  case 'tag':
    $active = 'tags';
    $st = $db->prepare("SELECT * FROM tags WHERE id=?");
    $st->execute([$arg]);
    $tag = $st->fetch();
    if (!$tag) { echo '<div class="card empty"><p>标签不存在</p></div>'; break; }
    $pageTitle = '标签：' . $tag['name'];
    $total = (int)$db->query("SELECT COUNT(*) FROM post_tags pt JOIN posts p ON p.id=pt.post_id WHERE pt.tag_id={$arg} AND p.status=1")->fetchColumn();
    [$pager, $offset] = paginate($total, $page, $perPage, link_to('tag/' . $arg));
    $posts = $db->query("SELECT p.* FROM posts p JOIN post_tags pt ON pt.post_id=p.id WHERE pt.tag_id={$arg} AND p.status=1 ORDER BY p.created_at DESC LIMIT {$offset},{$perPage}")->fetchAll();
    ?>
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/></svg>
      #<?= e($tag['name']) ?></h1><span class="count"><?= $total ?> 篇</span></div>
    <?php foreach ($posts as $post): include __DIR__ . '/views/post-card.php'; endforeach; ?>
    <?= $pager ?>
    <?php
    break;

  // ---------- 归档页 ----------
  case 'archives':
    $active = 'archives';
    $pageTitle = '文章归档';
    $groups = $db->query("SELECT * FROM posts WHERE status=1 ORDER BY created_at DESC")->fetchAll();
    $byYear = [];
    foreach ($groups as $p) { $byYear[date('Y', (int)$p['created_at'])][] = $p; }
    ?>
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v13H4z"/><path d="M3 3h18v4H3z"/></svg>
      文章归档</h1><span class="count"><?= count($groups) ?> 篇</span></div>
    <?php foreach ($byYear as $year => $items): ?>
      <div class="card" style="padding:1.4rem 1.6rem;">
        <div style="font-size:1.3rem;font-weight:800;color:var(--primary);margin-bottom:.9rem;"><?= e($year) ?> 年</div>
        <?php foreach ($items as $p): ?>
          <a href="<?= e(post_link((int)$p['id'])) ?>" style="display:flex;justify-content:space-between;gap:1rem;padding:.55rem .4rem;border-radius:.5rem;color:var(--text-2);font-size:.92rem;">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['title']) ?></span>
            <span style="color:var(--muted);flex-shrink:0;font-size:.82rem;"><?= fmt_date((int)$p['created_at'], 'm-d') ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php
    break;

  // ---------- 搜索页 ----------
  case 'search':
    $active = 'search';
    $pageTitle = '搜索';
    $q = trim($_GET['q'] ?? '');
    ?>
    <div class="section-head"><h1>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      搜索文章</h1></div>
    <form class="card search-form" method="get" action="<?= link_to('search') ?>" style="padding:1rem;">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="输入关键词搜索文章…" autofocus>
      <button type="submit">搜索</button>
    </form>
    <?php if ($q !== ''):
      $like = '%' . $q . '%';
      $st = $db->prepare("SELECT * FROM posts WHERE status=1 AND (title LIKE ? OR summary LIKE ? OR content LIKE ?) ORDER BY created_at DESC");
      $st->execute([$like, $like, $like]);
      $results = $st->fetchAll();
      ?>
      <div class="section-head" style="padding:.6rem .4rem 0;"><span class="count">找到 <?= count($results) ?> 条与“<?= e($q) ?>”相关的结果</span></div>
      <?php foreach ($results as $post): include __DIR__ . '/views/post-card.php'; endforeach; ?>
      <?php if (!$results): ?><div class="card empty"><p>没有找到相关文章，换个关键词试试。</p></div><?php endif; ?>
    <?php endif; ?>
    <?php
    break;

  // ---------- 文章详情 ----------
  case 'post':
    $st = $db->prepare("SELECT * FROM posts WHERE id=? AND status=1");
    $st->execute([$arg]);
    $post = $st->fetch();
    if (!$post) { echo '<div class="card empty"><p>文章不存在或已删除</p></div>'; break; }
    // 阅读量 +1
    $db->prepare("UPDATE posts SET views=views+1 WHERE id=?")->execute([$arg]);
    $post['views'] = (int)$post['views'] + 1;
    $cat = get_category((int)$post['category_id']);
    $tags = post_tags((int)$post['id']);
    $pageTitle = $post['title'];
    // 上一篇 / 下一篇
    $prev = $db->query("SELECT id,title FROM posts WHERE status=1 AND created_at < " . (int)$post['created_at'] . " ORDER BY created_at DESC LIMIT 1")->fetch();
    $next = $db->query("SELECT id,title FROM posts WHERE status=1 AND created_at > " . (int)$post['created_at'] . " ORDER BY created_at ASC LIMIT 1")->fetch();
    ?>
    <article class="card post-detail animate-in">
      <div class="post-meta">
        <span class="cate"><?= e($cat['name'] ?? '未分类') ?></span>
        <span><?= fmt_date((int)$post['created_at'], 'Y-m-d H:i') ?></span>
        <span><?= (int)$post['views'] ?> 次阅读</span>
      </div>
      <h1 class="post-title"><?= e($post['title']) ?></h1>
      <div class="post-content"><?= $post['content'] ?></div>
      <?php if ($tags): ?>
      <div class="detail-tags">
        <?php foreach ($tags as $t): ?><a href="<?= e(tag_link((int)$t['id'])) ?>">#<?= e($t['name']) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </article>
    <!-- 上一篇/下一篇 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="card" style="padding:1rem 1.2rem;">
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem;">上一篇</div>
        <?php if ($prev): ?><a href="<?= e(post_link((int)$prev['id'])) ?>" style="font-weight:600;font-size:.92rem;color:var(--primary);"><?= e($prev['title']) ?></a><?php else: ?><span style="color:var(--muted);font-size:.9rem;">没有了</span><?php endif; ?>
      </div>
      <div class="card" style="padding:1rem 1.2rem;text-align:right;">
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem;">下一篇</div>
        <?php if ($next): ?><a href="<?= e(post_link((int)$next['id'])) ?>" style="font-weight:600;font-size:.92rem;color:var(--primary);"><?= e($next['title']) ?></a><?php else: ?><span style="color:var(--muted);font-size:.9rem;">没有了</span><?php endif; ?>
      </div>
    </div>
    <?php
    break;

  default:
    $active = 'home';
    echo '<div class="card empty"><p>页面不存在，<a href="' . link_to() . '" style="color:var(--primary);">返回首页</a></p></div>';
}

$content = ob_get_clean();

// 输出完整页面
include __DIR__ . '/views/header.php';
echo '<div class="layout">';
include __DIR__ . '/views/sidebar.php';
echo '<main class="main">';
echo $content;
echo '</main></div>';
include __DIR__ . '/views/footer.php';
