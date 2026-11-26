<?php if (!defined('APP_NAME')) { http_response_code(403); exit; } ?>
<div class="backup-grid">
  <section class="panel-card">
    <div class="panel-head">
      <h2>创建备份</h2>
      <span class="backup-pill">完整数据</span>
    </div>
    <div class="panel-body">
      <p class="backup-desc">将全部数据表的结构与内容导出为 UTF-8 格式的 SQL 文件，可下载到本地留存，也可保存到服务器稍后恢复。</p>
      <div class="backup-stats">
        <?php
        $labels = ['posts' => '文章', 'categories' => '分类', 'tags' => '标签', 'post_tags' => '文章标签', 'options' => '站点设置', 'admins' => '管理员'];
        foreach ($labels as $key => $label): ?>
        <div class="backup-stat"><b><?= (int)($backupStats[$key] ?? 0) ?></b><span><?= e($label) ?></span></div>
        <?php endforeach; ?>
      </div>
      <div class="form-actions">
        <a class="btn btn-primary" href="index.php?m=backup-download&amp;token=<?= e($backupToken) ?>">下载备份文件</a>
        <form method="post" action="index.php?m=backup-create" onsubmit="return confirm('在服务器保存一份完整备份？');">
          <input type="hidden" name="token" value="<?= e($backupToken) ?>">
          <button class="btn btn-ghost" type="submit" <?= $backupWritable ? '' : 'disabled title="备份目录不可写"' ?>>保存到服务器</button>
        </form>
      </div>
      <?php if (!$backupWritable): ?>
        <div class="backup-warn"><b>目录不可写：</b>data/backups 目录没有写入权限，请先检查目录权限后再保存到服务器。</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel-card">
    <div class="panel-head">
      <h2>从文件恢复</h2>
      <span class="backup-pill danger">覆盖当前数据</span>
    </div>
    <div class="panel-body">
      <p class="backup-desc">仅接受由本系统导出的 SQL 备份文件。恢复会先重建数据表再导入数据，当前数据将被覆盖；系统会先自动保存一份回滚备份。</p>
      <form method="post" enctype="multipart/form-data" action="index.php?m=backup-restore-upload" onsubmit="return confirm('警告：恢复将覆盖当前全部数据！确定继续吗？');">
        <input type="hidden" name="token" value="<?= e($backupToken) ?>">
        <div class="form-row">
          <label>SQL 备份文件（.sql，最大 50MB）</label>
          <input class="input" type="file" name="sqlfile" accept=".sql,text/plain" required>
        </div>
        <button class="btn btn-danger" type="submit">上传并恢复</button>
      </form>
      <div class="backup-warn"><b>提示：</b>上传大小同时受 PHP 配置限制。较大的备份文件可放入 <code>data/backups/</code> 目录，然后从下方列表中选择恢复。</div>
    </div>
  </section>
</div>

<section class="panel-card" style="margin-top:1.2rem;">
  <div class="panel-head">
    <h2>服务器备份</h2>
    <span class="backup-pill">共 <?= count($backupFiles) ?> 个文件</span>
  </div>
  <?php if (!$backupFiles): ?>
    <div class="empty-row">暂无备份文件。点击上方“保存到服务器”即可创建一份。</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr><th>文件名</th><th>类型</th><th>大小</th><th>创建时间</th><th style="text-align:right;">操作</th></tr>
      </thead>
      <tbody>
        <?php foreach ($backupFiles as $f): ?>
        <tr>
          <td>
            <span class="backup-file">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
              <?= e($f['name']) ?>
            </span>
          </td>
          <td><?= $f['auto'] ? '<span class="badge pill">恢复前自动备份</span>' : '<span class="badge gray">手动备份</span>' ?></td>
          <td><?= number_format($f['size'] / 1024, 1) ?> KB</td>
          <td><?= date('Y-m-d H:i:s', $f['mtime']) ?></td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;">
              <a class="btn btn-ghost btn-sm" href="index.php?m=backup-file-download&amp;token=<?= e($backupToken) ?>&amp;file=<?= urlencode($f['name']) ?>">下载</a>
              <form method="post" action="index.php?m=backup-restore-file" onsubmit="return confirm('确定使用此备份恢复吗？当前数据将被覆盖！');">
                <input type="hidden" name="token" value="<?= e($backupToken) ?>">
                <input type="hidden" name="file" value="<?= e($f['name']) ?>">
                <button class="btn btn-primary btn-sm" type="submit">恢复</button>
              </form>
              <a class="btn btn-danger btn-sm" href="index.php?m=backup-delete&amp;token=<?= e($backupToken) ?>&amp;file=<?= urlencode($f['name']) ?>" onclick="return confirm('确定删除这个备份文件吗？此操作不可撤销。');">删除</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
