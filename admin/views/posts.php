<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
$cateName = '全部';
if ($onlyTop) { $cateName = '置顶文章'; }
elseif ($cateId === -1) { $cateName = '未分类'; }
elseif ($cateId > 0) {
    $cateName = '未知分类';
    foreach ($cateList as $c) { if ((int)$c['id'] === $cateId) { $cateName = $c['name']; break; } }
}
?>
<div class="panel-card">
  <div class="cate-tabs">
    <a href="index.php?m=posts" class="cate-tab <?= (!$onlyTop && $cateId === 0) ? 'on' : '' ?>">全部 <em><?= (int)$cateCounts[0] ?></em></a>
    <?php foreach ($cateList as $c): $cid = (int)$c['id']; ?>
      <a href="index.php?m=posts&cate=<?= $cid ?>" class="cate-tab <?= (!$onlyTop && $cateId === $cid) ? 'on' : '' ?>"><?= e($c['name']) ?> <em><?= (int)($cateCounts[$cid] ?? 0) ?></em></a>
    <?php endforeach; ?>
    <a href="index.php?m=posts&cate=-1" class="cate-tab <?= (!$onlyTop && $cateId === -1) ? 'on' : '' ?>">未分类 <em><?= (int)$cateCounts[-1] ?></em></a>
    <a href="index.php?m=posts&top=1" class="cate-tab top-tab <?= $onlyTop ? 'on' : '' ?>">置顶文章 <em><?= (int)$topCount ?></em></a>
    <a href="index.php?m=categories" class="cate-tab manage">管理分类 →</a>
  </div>
  <div class="panel-head">
    <h2>文章列表（<?= e($cateName) ?> · 共 <?= (int)$totalPosts ?> 篇）</h2>
    <div class="tools">
      <input class="input" id="filter" placeholder="筛选标题…" oninput="filterRows()">
      <button type="button" id="bulkBtn" class="btn btn-danger btn-sm" disabled onclick="submitBulk()">批量删除 <span id="selCount"></span></button>
      <a href="index.php?m=post-edit" class="btn btn-primary btn-sm">+ 写文章</a>
    </div>
  </div>
  <form id="bulkForm" method="post" action="index.php?m=post-bulk-delete">
  <table id="postTable">
    <thead><tr>
      <th class="chk-col"><input type="checkbox" id="checkAll" title="全选/取消全选" onchange="toggleAll(this.checked)"></th>
      <th>标题</th><th>分类</th><th>状态</th><th>阅读</th><th>发布时间</th><th style="width:150px;">操作</th>
    </tr></thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td class="chk-col"><input type="checkbox" class="row-chk" name="ids[]" value="<?= (int)$p['id'] ?>" onchange="syncCheckAll()"></td>
        <td><a class="title" href="index.php?m=post-edit&id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></td>
        <td><span class="badge pill"><?= e($p['cname'] ?? '未分类') ?></span></td>
        <td>
          <span class="badge <?= $p['status']?'green':'gray' ?>"><?= $p['status']?'已发布':'草稿' ?></span>
          <?php
            $qs = '';
            if ($onlyTop) $qs .= '&top=1';
            elseif (isset($_GET['cate'])) $qs .= '&cate=' . (int)$_GET['cate'];
            if (isset($_GET['p'])) $qs .= '&p=' . max(1, (int)$_GET['p']);
            $topped = (int)$p['is_top'] === 1;
            $hasImage = post_cover_image($p) !== '';
          ?>
          <?php if ($hasImage): ?>
          <span class="has-img" title="已上传图片" aria-label="已上传图片"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></span>
          <?php endif; ?>
          <a href="index.php?m=post-toggle-top&id=<?= (int)$p['id'] ?><?= $qs ?>" class="top-btn <?= $topped ? 'on' : '' ?>" title="<?= $topped ? '点击取消置顶' : '点击置顶' ?>"><?= $topped ? '取消置顶' : '置顶' ?></a>
        </td>
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
      <?php if (!$posts): ?><tr><td colspan="7" class="empty-row">暂无文章</td></tr><?php endif; ?>
    </tbody>
  </table>
  </form>
  <?php if (!empty($pager)): ?>
  <div class="admin-pager"><?= $pager ?></div>
  <?php endif; ?>
</div>
<script>
function visibleRows(){ return [].filter.call(document.querySelectorAll('#postTable tbody tr'), function(tr){ return tr.style.display !== 'none' && tr.querySelector('.row-chk'); }); }
function refreshBulk(){
  var all = [].slice.call(document.querySelectorAll('.row-chk'));
  var checked = all.filter(function(c){ return c.checked; }).length;
  document.getElementById('bulkBtn').disabled = checked === 0;
  document.getElementById('selCount').textContent = checked ? '(' + checked + ')' : '';
  var vis = visibleRows().map(function(tr){ return tr.querySelector('.row-chk'); });
  var head = document.getElementById('checkAll');
  head.checked = vis.length > 0 && vis.every(function(c){ return c.checked; });
  head.indeterminate = vis.some(function(c){ return c.checked; }) && !head.checked;
}
function toggleAll(on){ visibleRows().forEach(function(tr){ tr.querySelector('.row-chk').checked = on; }); refreshBulk(); }
function syncCheckAll(){ refreshBulk(); }
function filterRows(){
  var q = document.getElementById('filter').value.toLowerCase();
  [].slice.call(document.querySelectorAll('#postTable tbody tr')).forEach(function(tr){
    tr.style.display = tr.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
  });
  refreshBulk();
}
function submitBulk(){
  var n = [].filter.call(document.querySelectorAll('.row-chk'), function(c){ return c.checked; }).length;
  if (n && confirm('确定删除选中的 ' + n + ' 篇文章吗？此操作不可恢复。')) {
    document.getElementById('bulkForm').submit();
  }
}
refreshBulk();
</script>
