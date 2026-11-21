<form method="post" action="index.php?m=password-save">
  <div class="form-card">
    <div class="form-row">
      <label>原密码</label>
      <input class="input" type="password" name="old_password" required>
    </div>
    <div class="form-row">
      <label>新密码</label>
      <input class="input" type="password" name="new_password" required>
      <div class="hint">请输入至少 6 位的新密码。</div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">修改密码</button>
    </div>
  </div>
</form>
