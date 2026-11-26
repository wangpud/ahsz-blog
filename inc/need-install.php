<?php
/** 未安装时的友好提示（独立样式，不加载前台布局） */
if (!defined('APP_NAME')) { exit; }
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>尚未安装 - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
<style>
  *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f6f7fb;color:#1f2430;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif}
  .box{background:#fff;border:1px solid #e6e8f0;border-radius:18px;padding:48px 40px;max-width:460px;width:calc(100% - 40px);text-align:center;box-shadow:0 18px 50px rgba(30,40,80,.08)}
  .ic{width:64px;height:64px;margin:0 auto 20px;border-radius:18px;background:linear-gradient(135deg,#7c8cff,#5a6df0);display:grid;place-items:center;color:#fff}
  h1{font-size:1.4rem;margin:0 0 10px}p{color:#6c7280;line-height:1.8;margin:0 0 26px;font-size:.95rem}
  a{display:inline-block;background:#5a6df0;color:#fff;text-decoration:none;padding:12px 30px;border-radius:10px;font-weight:600}
</style>
</head>
<body>
  <div class="box">
    <div class="ic"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg></div>
    <h1>站点尚未安装</h1>
    <p>检测到还没有数据库连接配置。请先通过独立的安装向导创建 MySQL 数据库、初始化数据并设置管理员账号。</p>
    <a href="/install/">前往安装向导</a>
  </div>
</body>
</html>
