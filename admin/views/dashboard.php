<div class="stats-grid">
  <div class="stat"><div class="ic" style="background:oklch(.6 .18 var(--hue))"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8"/></svg></div><div><div class="num"><?= $stats['posts'] ?></div><div class="lbl">文章总数</div></div></div>
  <div class="stat"><div class="ic" style="background:oklch(.62 .16 calc(var(--hue) + 40))"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div><div><div class="num"><?= $stats['categories'] ?></div><div class="lbl">分类数</div></div></div>
  <div class="stat"><div class="ic" style="background:oklch(.6 .17 calc(var(--hue) + 80))"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/></svg></div><div><div class="num"><?= $stats['tags'] ?></div><div class="lbl">标签数</div></div></div>
  <div class="stat"><div class="ic" style="background:oklch(.58 .15 calc(var(--hue) + 120))"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></div><div><div class="num"><?= number_format($stats['views']) ?></div><div class="lbl">总阅读量</div></div></div>
</div>

<div class="panel-card">
  <div class="panel-head"><h2>最近文章</h2><a href="index.php?m=posts" class="btn btn-ghost btn-sm">查看全部</a></div>
  <table>
    <thead><tr><th>标题</th><th>分类</th><th>阅读</th><th>发布时间</th></tr></thead>
    <tbody>
      <?php foreach ($recent as $p): ?>
      <tr>
        <td><a class="title" href="index.php?m=post-edit&id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
        <td><span class="badge pill"><?= e($p['cname'] ?? '未分类') ?></span></td>
        <td><?= (int)$p['views'] ?></td>
        <td class="meta"><?= fmt_date((int)$p['created_at'], 'Y-m-d') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="4" class="empty-row">还没有文章，<a href="index.php?m=post-edit" style="color:var(--primary);">写一篇</a></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
