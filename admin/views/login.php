<?php if (!defined('APP_NAME')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台登录 - <?= e(opt('site_name','AHSZ 极简博客')) ?></title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo"><?= e(mb_substr(opt('site_name','A'),0,1,'UTF-8')) ?></div>
    <h1><?= e(opt('site_name','AHSZ 极简博客')) ?></h1>
    <div class="sub">管理后台登录</div>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="index.php?m=do-login">
      <div class="field">
        <label>用户名</label>
        <input class="input" type="text" name="username" value="admin" autocomplete="username" required>
      </div>
      <div class="field">
        <label>密码</label>
        <input class="input" type="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">登录</button>
    </form>
    <div style="text-align:center;margin-top:1.2rem;">
      <a href="/" style="color:var(--muted);font-size:.8rem;">← 返回网站首页</a>
    </div>
  </div>
</div>
</body>
</html>
