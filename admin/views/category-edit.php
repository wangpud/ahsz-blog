<?php $isEdit = (bool)$cat; ?>
<form method="post" action="index.php?m=cate-save">
  <input type="hidden" name="id" value="<?= $isEdit ? (int)$cat['id'] : 0 ?>">
  <div class="form-card">
    <div class="form-grid">
      <div class="form-row">
        <label>分类名称 *</label>
        <input class="input" name="name" value="<?= e($cat['name'] ?? '') ?>" required>
      </div>
      <div class="form-row">
        <label>别名（英文，留空自动生成）</label>
        <input class="input" name="alias" value="<?= e($cat['alias'] ?? '') ?>" placeholder="例如：life">
      </div>
    </div>
    <div class="form-row">
      <label>分类描述</label>
      <textarea name="description" style="min-height:80px;" placeholder="简单描述这个分类"><?= e($cat['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <label>排序（数字越小越靠前）</label>
      <input class="input" type="number" name="sort_order" value="<?= (int)($cat['sort_order'] ?? 0) ?>">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit?'保存修改':'新建分类' ?></button>
      <a href="index.php?m=categories" class="btn btn-ghost">取消</a>
    </div>
  </div>
</form>
