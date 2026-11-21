<?php $isEdit = (bool)$post; ?>
<form method="post" action="index.php?m=post-save">
  <input type="hidden" name="id" value="<?= $isEdit ? (int)$post['id'] : 0 ?>">
  <div class="form-card">
    <div class="form-row">
      <label>文章标题 *</label>
      <input class="input" name="title" value="<?= e($post['title'] ?? '') ?>" placeholder="请输入标题" required>
    </div>
    <div class="form-grid">
      <div class="form-row">
        <label>分类</label>
        <select name="category_id">
          <option value="0">未分类</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (($post['category_id'] ?? 0)==$c['id'])?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label>状态</label>
        <select name="status">
          <option value="1" <?= (($post['status'] ?? 1)==1)?'selected':'' ?>>发布</option>
          <option value="0" <?= (($post['status'] ?? 1)==0)?'selected':'' ?>>草稿</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <label>摘要</label>
      <textarea name="summary" style="min-height:70px;" placeholder="留空则自动从正文提取"><?= e($post['summary'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <label>正文内容</label>
      <textarea name="content" style="min-height:260px;" placeholder="支持 HTML 标签，如 &lt;p&gt;段落&lt;/p&gt;"><?= e($post['content'] ?? '') ?></textarea>
      <div class="hint">可直接编写 HTML。提交后前台原样渲染。</div>
    </div>
    <div class="form-row">
      <label>标签（用英文逗号 , 分隔）</label>
      <input class="input" name="tags" value="<?= e(implode(',', $tagNames)) ?>" placeholder="例如：山水,游记,随笔">
      <?php if ($tagNames): ?><div class="inline-tags"><?php foreach($tagNames as $t): ?><span class="chip">#<?= e($t) ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="form-row" style="display:flex;align-items:center;gap:.6rem;">
      <label style="margin:0;display:flex;align-items:center;gap:.5rem;">
        <input type="checkbox" name="is_top" value="1" <?= (($post['is_top'] ?? 0)==1)?'checked':'' ?>> 置顶这篇文章
      </label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit?'保存修改':'发布文章' ?></button>
      <a href="index.php?m=posts" class="btn btn-ghost">取消</a>
    </div>
  </div>
</form>
