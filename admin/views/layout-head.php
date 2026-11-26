<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
$siteName = opt('site_name', APP_NAME);
$m = $m ?? 'dashboard';
$navActive = function ($key) use ($m) {
    $map = ['posts'=>'posts','post-edit'=>'posts','categories'=>'categories','category-edit'=>'categories','tags'=>'tags','settings'=>'settings','password'=>'settings','backup'=>'backup'];
    return (($map[$m] ?? $m) === $key) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>后台管理 - <?= e($siteName) ?></title>
<link rel="stylesheet" href="/assets/admin.css">
<script>
(function(){var t=localStorage.getItem('ahsz-theme')||'auto',h=localStorage.getItem('ahsz-hue');
if(t==='dark'||(t==='auto'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark');
if(h!==null)document.documentElement.style.setProperty('--hue',h);})();
</script>
</head>
<body>
<div class="admin">
  <aside class="side" id="side">
    <div class="side-head">
      <div class="logo"><?= e(mb_substr($siteName,0,1,'UTF-8')) ?></div>
      <div class="t"><b><?= e($siteName) ?></b><small>管理后台</small></div>
    </div>
    <nav class="side-nav">
      <div class="group">内容管理</div>
      <a href="index.php?m=dashboard" class="<?= $m==='dashboard'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>概览</a>
      <a href="index.php?m=posts" class="<?= $navActive('posts') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>文章管理</a>
      <a href="index.php?m=categories" class="<?= $navActive('categories') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>分类管理</a>
      <a href="index.php?m=tags" class="<?= $navActive('tags') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>标签管理</a>
      <div class="group">系统</div>
      <a href="index.php?m=settings" class="<?= $navActive('settings') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9M4.6 9a1.7 1.7 0 0 0-.3-1.9"/></svg>站点设置</a>
      <a href="index.php?m=backup" class="<?= $navActive('backup') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>数据备份</a>
      <a href="index.php?m=password" class="<?= $m==='password'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>修改密码</a>
    </nav>
    <div class="side-foot">
      <a href="/" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>查看网站</a>
      <a href="index.php?m=logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>退出登录</a>
    </div>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.8rem;">
        <button class="icon-btn menubtn" onclick="document.getElementById('side').classList.toggle('open')" style="background:var(--input);border-radius:.5rem;width:2rem;height:2rem;display:none;place-items:center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h1><?= ['dashboard'=>'概览','posts'=>'文章管理','post-edit'=>'编辑文章','categories'=>'分类管理','category-edit'=>'编辑分类','tags'=>'标签管理','settings'=>'站点设置','password'=>'修改密码','backup'=>'数据备份与恢复'][$m] ?? '管理后台' ?></h1>
      </div>
      <div class="user"><span class="av"><?= e(mb_substr($_SESSION['admin_user'] ?? 'A',0,1)) ?></span><?= e($_SESSION['admin_user'] ?? 'admin') ?></div>
    </header>
    <main class="content">
      <?php if (!empty($msg)): $isErr = str_contains((string)$msg, '失败') || str_contains((string)$msg, '错误'); ?><div class="alert" style="background:<?= $isErr ? 'var(--danger-soft)' : 'rgba(46,160,67,.12)' ?>;color:<?= $isErr ? 'var(--danger)' : '#2da44e' ?>;margin-bottom:1rem;"><?= e($msg) ?></div><?php endif; ?>
