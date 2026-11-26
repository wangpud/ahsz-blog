<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
$siteName = opt('site_name', APP_NAME); ?>
<!-- 返回顶部 -->
<button class="to-top" id="to-top" title="返回顶部">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 14 6-6 6 6"/></svg>
</button>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> <a href="<?= link_to() ?>"><?= e($siteName) ?></a> · 用心记录每一刻</p>
  <p style="margin-top:.3rem;opacity:.7;">Powered by AHSZ 极简博客 · 简洁，方能致远</p>
  <?php if (opt('icp', '')): ?><p style="margin-top:.3rem;opacity:.7;"><?= e(opt('icp')) ?></p><?php endif; ?>
</footer>

<script src="/assets/app.js"></script>
</body>
</html>
