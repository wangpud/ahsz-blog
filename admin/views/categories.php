<div class="panel-card">
  <div class="panel-head">
    <h2>分类管理（共 <?= count($cats) ?> 个）</h2>
    <a href="index.php?m=category-edit" class="btn btn-primary btn-sm">+ 新建分类</a>
  </div>
  <table>
    <thead><tr><th>分类名</th><th>别名</th><th>描述</th><th>文章数</th><th>排序</th><th style="width:150px;">操作</th></tr></thead>
    <tbody>
      <?php foreach ($cats as $c): ?>
      <tr>
        <td class="title"><?= e($c['name']) ?></td>
        <td class="meta"><?= e($c['alias']) ?></td>
        <td class="meta"><?= e(mb_substr($c['description'],0,30,'UTF-8')) ?></td>
        <td><?= (int)$c['cnt'] ?></td>
        <td><?= (int)$c['sort_order'] ?></td>
        <td>
          <div class="row-actions">
            <a href="index.php?m=category-edit&id=<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm">编辑</a>
            <a href="index.php?m=cate-delete&id=<?= (int)$c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('删除分类？该分类下文章将变为未分类。')">删除</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$cats): ?><tr><td colspan="6" class="empty-row">暂无分类</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
