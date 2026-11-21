/* AHSZ 极简博客 · 交互脚本 */
(function () {
  'use strict';
  var root = document.documentElement;

  // ---- 主题：深浅色 ----
  function applyTheme(mode) {
    if (mode === 'dark') root.classList.add('dark');
    else if (mode === 'light') root.classList.remove('dark');
    else {
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      root.classList.toggle('dark', prefersDark);
    }
    updateThemeChips(mode);
  }
  function savedTheme() { return localStorage.getItem('ahsz-theme') || 'auto'; }
  function updateThemeChips(mode) {
    document.querySelectorAll('.theme-chip').forEach(function (c) {
      c.classList.toggle('active', c.dataset.mode === mode);
    });
  }
  applyTheme(savedTheme());

  // ---- 主题色 hue ----
  var savedHue = localStorage.getItem('ahsz-hue');
  if (savedHue !== null) root.style.setProperty('--hue', savedHue);
  var slider = document.getElementById('hue-slider');
  var hueValue = document.getElementById('hue-value');
  function currentHue() {
    return root.style.getPropertyValue('--hue').trim() || getComputedStyle(root).getPropertyValue('--hue').trim() || '250';
  }
  if (slider) {
    slider.value = currentHue();
    if (hueValue) hueValue.textContent = slider.value;
    slider.addEventListener('input', function () {
      root.style.setProperty('--hue', this.value);
      if (hueValue) hueValue.textContent = this.value;
      localStorage.setItem('ahsz-hue', this.value);
    });
  }

  // ---- 显示设置面板 ----
  var settingBtn = document.getElementById('settings-btn');
  var settingPanel = document.getElementById('settings-panel');
  if (settingBtn && settingPanel) {
    settingBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      settingPanel.classList.toggle('open');
    });
    settingPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { settingPanel.classList.remove('open'); });
  }

  // ---- 深浅色按钮组 ----
  document.querySelectorAll('.theme-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var mode = this.dataset.mode;
      localStorage.setItem('ahsz-theme', mode);
      applyTheme(mode);
    });
  });

  // ---- 返回顶部 ----
  var toTop = document.getElementById('to-top');
  if (toTop) {
    window.addEventListener('scroll', function () {
      toTop.classList.toggle('show', window.scrollY > 400);
    });
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
})();
