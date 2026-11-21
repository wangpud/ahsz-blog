<div class="panel-card">
  <div class="panel-head">
    <h2>文章列表（共 <?= count($posts) ?> 篇）</h2>
    <div class="tools">
      <input class="input" id="filter" placeholder="筛选标题…" oninput="filterRows()">
      <a href="index.php?m=post-edit" class="btn btn-primary btn-sm">+ 写文章</a>
    </div>
  </div>
  <table id="postTable">
    <thead><tr><th>标题</th><th>分类</th><th>状态</th><th>阅读</th><th>发布时间</th><th style="width:150px;">操作</th></tr></thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td><a class="title" href="index.php?m=post-edit&id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
        <td><span class="badge pill"><?= e($p['cname'] ?? '未分类') ?></span></td>
        <td><span class="badge <?= $p['status']?'green':'gray' ?>"><?= $p['status']?'已发布':'草稿' ?></span><?php if($p['is_top']): ?> <span class="badge pill">置顶</span><?php endif; ?></td>
        <td><?= (int)$p['views'] ?></td>
        <td class="meta"><?= fmt_date((int)$p['created_at'], 'Y-m-d') ?></td>
        <td>
          <div class="row-actions">
            <a href="index.php?m=post-edit&id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm">编辑</a>
            <a href="/post/<?= (int)$p['id'] ?>" target="_blank" class="btn btn-ghost btn-sm">查看</a>
            <a href="index.php?m=post-delete&id=<?= (int)$p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定删除这篇文章吗？')">删除</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$posts): ?><tr><td colspan="6" class="empty-row">暂无文章</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<script>
function filterRows(){
  var q=document.getElementById('filter').value.toLowerCase();
  document.querySelectorAll('#postTable tbody tr').forEach(function(tr){
    tr.style.display = tr.textContent.toLowerCase().indexOf(q)>-1 ? '' : 'none';
  });
}
</script>
