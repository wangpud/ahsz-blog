<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
/** 文章卡片组件，需传入 $post 数组 */
/** 优先用列表页批量预加载的数据，未预加载时才逐篇查库 */
$cid = (int)($post['category_id'] ?? 0);
$cat = array_key_exists($cid, $GLOBALS['category_map'] ?? [])
    ? ($GLOBALS['category_map'][$cid] ?: null)
    : get_category($cid);
$pid = (int)($post['id'] ?? 0);
$tags = $GLOBALS['post_tags_map'][$pid] ?? null;
if ($tags === null) $tags = post_tags($pid);
$cover = post_cover_image($post);
?>
<article class="card post-card animate-in">
  <div class="post-cover">
    <?php if ($cover !== ''): ?>
      <img class="cover-img" src="<?= e($cover) ?>" alt="<?= e($post['title'] ?? '') ?>" loading="lazy">
    <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
        <path d="M4 5h16v14H4z"/><circle cx="9" cy="10" r="2"/><path d="m4 17 5-4 4 3 3-2 4 4"/>
      </svg>
    <?php endif; ?>
    <span class="cover-text"><?= e($cat['name'] ?? '随笔') ?></span>
  </div>
  <div class="post-body">
    <div class="post-meta">
      <span class="cate">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/></svg>
        <?= e($cat['name'] ?? '未分类') ?>
      </span>
      <span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
        <?= fmt_date((int)$post['created_at']) ?>
      </span>
      <span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        <?= (int)$post['views'] ?>
      </span>
    </div>
    <h2 class="post-title"><a href="<?= e(post_link((int)$post['id'])) ?>"><?= e($post['title']) ?></a></h2>
    <p class="post-excerpt"><?= e($post['summary']) ?></p>
    <div class="post-foot">
      <?php foreach ($tags as $t): ?>
        <a class="tag" href="<?= e(tag_link((int)$t['id'])) ?>">#<?= e($t['name']) ?></a>
      <?php endforeach; ?>
      <a class="read-more" href="<?= e(post_link((int)$post['id'])) ?>">阅读全文
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</article>
