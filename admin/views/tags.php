<div class="panel-card">
  <div class="panel-head">
    <h2>标签管理（共 <?= count($tags) ?> 个）</h2>
    <span class="meta" style="color:var(--muted);font-size:.8rem;">标签由文章自动汇总生成</span>
  </div>
  <div style="padding:1.5rem;display:flex;flex-wrap:wrap;gap:.8rem;">
    <?php foreach ($tags as $t): ?>
    <div style="display:flex;align-items:center;gap:.5rem;padding:.55rem .8rem;border-radius:.6rem;background:var(--input);">
      <a href="/tag/<?= (int)$t['id'] ?>" target="_blank" style="font-weight:700;">#<?= e($t['name']) ?></a>
      <span class="badge pill"><?= (int)$t['post_count'] ?> 篇</span>
      <a href="index.php?m=tag-delete&id=<?= (int)$t['id'] ?>" onclick="return confirm('删除标签？文章不受影响。')" style="color:var(--danger);font-size:.8rem;" title="删除">✕</a>
    </div>
    <?php endforeach; ?>
    <?php if (!$tags): ?><div class="empty-row" style="width:100%;">暂无标签</div><?php endif; ?>
  </div>
</div>
