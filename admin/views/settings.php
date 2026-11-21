<form method="post" action="index.php?m=settings-save">
  <div class="form-card">
    <div class="form-row">
      <label>网站名称 *</label>
      <input class="input" name="site_name" value="<?= e(opt('site_name')) ?>" required>
      <div class="hint">显示在浏览器标题、导航栏、页脚等位置。</div>
    </div>
    <div class="form-grid">
      <div class="form-row">
        <label>网站副标题</label>
        <input class="input" name="site_subtitle" value="<?= e(opt('site_subtitle')) ?>" placeholder="一句话描述">
      </div>
      <div class="form-row">
        <label>作者名称</label>
        <input class="input" name="site_author" value="<?= e(opt('site_author')) ?>" placeholder="用于头像显示">
      </div>
    </div>
    <div class="form-row">
      <label>网站描述</label>
      <textarea name="site_desc" style="min-height:70px;"><?= e(opt('site_desc')) ?></textarea>
    </div>
    <div class="form-row">
      <label>网站标签</label>
      <input class="input" name="site_tags" value="<?= e(opt('site_tags')) ?>" placeholder="用英文逗号分隔">
      <div class="hint">站点级标签，用英文逗号 , 分隔。例如：极简,博客,生活,随笔</div>
      <?php
        $stags = array_filter(array_map('trim', explode(',', opt('site_tags'))));
        if ($stags): ?><div class="inline-tags"><?php foreach($stags as $s): ?><span class="chip">#<?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="form-row">
      <label>网站关键词（SEO）</label>
      <input class="input" name="site_keywords" value="<?= e(opt('site_keywords')) ?>">
    </div>
    <div class="form-grid">
      <div class="form-row">
        <label>每页文章数</label>
        <input class="input" type="number" name="per_page" value="<?= e(opt('per_page','6')) ?>" min="1" max="50">
      </div>
      <div class="form-row">
        <label>默认主题色（0-360）</label>
        <input class="input" type="number" name="theme_hue" value="<?= e(opt('theme_hue','250')) ?>" min="0" max="360">
      </div>
    </div>
    <div class="form-row">
      <label>ICP 备案号（可选）</label>
      <input class="input" name="icp" value="<?= e(opt('icp')) ?>" placeholder="例如：粤ICP备12345678号">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">保存设置</button>
      <a href="/" target="_blank" class="btn btn-ghost">查看网站</a>
    </div>
  </div>
</form>
