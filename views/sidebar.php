<?php
/** 前台侧栏：站点名片 + 分类 + 标签云 */
$siteName = opt('site_name', APP_NAME);
$siteSubtitle = opt('site_subtitle', '记录生活，留住点滴');
$siteDesc = opt('site_desc', '一个关于生活、山水与文字的安静角落。');
$author = opt('site_author', 'AHSZ');
$stats = site_stats();
$cats = get_categories();
$tags = get_tags();
?>
<aside class="sidebar">
  <!-- 站点名片 -->
  <div class="card profile animate-in">
    <div class="profile-cover"></div>
    <div class="profile-avatar"><?= e(mb_substr($author, 0, 1, 'UTF-8')) ?></div>
    <h2><?= e($siteName) ?></h2>
    <div class="subtitle"><?= e($siteSubtitle) ?></div>
    <div class="underline"></div>
    <div class="desc"><?= e($siteDesc) ?></div>
    <div class="profile-stats">
      <div><b><?= $stats['posts'] ?></b><span>文章</span></div>
      <div><b><?= $stats['categories'] ?></b><span>分类</span></div>
      <div><b><?= $stats['tags'] ?></b><span>标签</span></div>
    </div>
  </div>

  <!-- 分类 -->
  <div class="card widget animate-in">
    <div class="widget-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      文章分类
    </div>
    <div class="cat-list">
      <?php foreach ($cats as $c): ?>
      <a href="<?= e(cate_link((int)$c['id'])) ?>">
        <span><?= e($c['name']) ?></span>
        <span class="count"><?= (int)$c['post_count'] ?></span>
      </a>
      <?php endforeach; ?>
      <?php if (!$cats): ?><div style="color:var(--muted);font-size:.85rem;padding:.5rem;">暂无分类</div><?php endif; ?>
    </div>
  </div>

  <!-- 标签云 -->
  <div class="card widget animate-in">
    <div class="widget-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
      标签云
    </div>
    <div class="tag-cloud">
      <?php foreach ($tags as $t): ?>
      <a href="<?= e(tag_link((int)$t['id'])) ?>"><?= e($t['name']) ?><span class="c"><?= (int)$t['post_count'] ?></span></a>
      <?php endforeach; ?>
    </div>
  </div>
</aside>
