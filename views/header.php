<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
/**
 * 前台公共头部：banner + 浮动导航 + 设置面板
 * 变量：$active (nav-active), $pageTitle
 */
$active = $active ?? '';
$siteName = opt('site_name', APP_NAME);
$siteSubtitle = opt('site_subtitle', '记录生活，留住点滴');
$hue = (int)opt('theme_hue', '250');
$stats = site_stats();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(!empty($pageTitle) ? $pageTitle . ' - ' : '') . e($siteName) ?></title>
<meta name="description" content="<?= e(opt('site_desc', '')) ?>">
<meta name="keywords" content="<?= e(opt('site_keywords', '')) ?>">
<link rel="stylesheet" href="/assets/style.css">
<script>
  // 尽早应用主题，避免闪烁
  (function(){
    var t = localStorage.getItem('ahsz-theme')||'auto';
    var h = localStorage.getItem('ahsz-hue');
    if(t==='dark'||(t==='auto'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark');}
    if(h!==null){document.documentElement.style.setProperty('--hue',h);}
    else{<?= 'document.documentElement.style.setProperty("--hue", "'.$hue.'");' ?>}
  })();
</script>
</head>
<body>

<!-- 顶部 Banner -->
<div class="banner">
  <span class="banner-shape b1"></span>
  <span class="banner-shape b2"></span>
  <span class="banner-shape b3"></span>
</div>

<!-- 浮动导航 -->
<div class="navbar-wrap">
  <div class="navbar">
    <div class="navbar-inner">
      <a class="nav-brand" href="<?= link_to() ?>">
        <span class="dot"><?= e(mb_substr($siteName, 0, 1, 'UTF-8')) ?></span>
        <span class="brand-text"><?= e($siteName) ?></span>
      </a>
      <nav class="nav-links">
        <a href="<?= link_to() ?>" class="<?= $active==='home'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg><span>首页</span>
        </a>
        <a href="/categories" class="<?= $active==='cate'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>分类</span>
        </a>
        <a href="<?= link_to('tags') ?>" class="<?= $active==='tags'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4 12 22l-9-9V4h9z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg><span>标签</span>
        </a>
        <a href="<?= link_to('archives') ?>" class="<?= $active==='archives'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v13H4z"/><path d="M3 3h18v4H3z"/><path d="M10 11h4"/></svg><span>归档</span>
        </a>
        <a href="<?= link_to('search') ?>" class="<?= $active==='search'?'active':'' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span>搜索</span>
        </a>
      </nav>
      <div class="nav-actions">
        <button class="icon-btn" id="settings-btn" title="显示设置">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 7 19.4a1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 2.6 14H2.5a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 7a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 2.6V2.5a2 2 0 1 1 4 0v.1A1.7 1.7 0 0 0 17 4.6a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.6 1h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1z"/></svg>
        </button>
      </div>
    </div>
    <!-- 显示设置面板 -->
    <div class="panel" id="settings-panel">
      <h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>外观设置</h4>
      <div style="font-size:.8rem;color:var(--muted);margin-bottom:.4rem;">主题色 <span id="hue-value" style="color:var(--primary);font-weight:800;"><?= $hue ?></span></div>
      <input type="range" class="hue-slider" id="hue-slider" min="0" max="360" value="<?= $hue ?>">
      <div class="theme-row">
        <button class="theme-chip" data-mode="light"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>浅色</button>
        <button class="theme-chip" data-mode="dark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>深色</button>
        <button class="theme-chip" data-mode="auto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M8 9H4v6h4z"/></svg>跟随系统</button>
      </div>
    </div>
  </div>
</div>
