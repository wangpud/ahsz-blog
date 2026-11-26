<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }
$isEdit = (bool)$post; ?>
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
      <div class="editor">
        <div class="editor-toolbar" id="edToolbar">
          <button type="button" data-act="bold" title="加粗"><b>B</b></button>
          <button type="button" data-act="italic" title="斜体"><i>I</i></button>
          <button type="button" data-act="h" title="标题">H</button>
          <span class="sep"></span>
          <button type="button" data-act="ul" title="无序列表">≡</button>
          <button type="button" data-act="ol" title="有序列表">1.</button>
          <button type="button" data-act="quote" title="引用">❝</button>
          <button type="button" data-act="code" title="代码块">&lt;/&gt;</button>
          <span class="sep"></span>
          <button type="button" data-act="link" title="插入链接">🔗</button>
          <button type="button" data-act="image" title="上传图片">🖼</button>
          <button type="button" data-act="file" title="上传附件">📎</button>
          <span class="sep"></span>
          <button type="button" data-act="hr" title="分割线">―</button>
          <button type="button" data-act="br" title="换行">↵</button>
        </div>
        <textarea name="content" id="edContent" class="editor-area" style="min-height:300px;" placeholder="在此撰写正文，可使用上方工具栏，或直接编写 HTML。"><?= e($post['content'] ?? '') ?></textarea>
        <div class="editor-status" id="edStatus">支持 HTML · 图片/附件上传后自动插入 · 单文件最大 20MB</div>
      </div>
      <input type="file" id="edFile" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.zip,.rar,.7z" hidden>
      <div id="edPreview" class="ed-preview" hidden></div>
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
<script>
(function(){
  var ta = document.getElementById('edContent');
  var status = document.getElementById('edStatus');
  var fileInput = document.getElementById('edFile');
  var preview = document.getElementById('edPreview');
  var pendingType = 'image';

  function setStatus(msg, isErr){ status.textContent = msg; status.style.color = isErr ? 'var(--danger)' : ''; }
  function restoreSelection(start, end){ ta.focus(); ta.setSelectionRange(start, end); }
  function insert(text, selStart, selEnd){
    var s = ta.selectionStart, e = ta.selectionEnd;
    var before = ta.value.substring(0, s), after = ta.value.substring(e);
    ta.value = before + text + after;
    var caret = selStart !== undefined ? selStart : s + text.length;
    restoreSelection(caret, selEnd !== undefined ? selEnd : caret);
    ta.dispatchEvent(new Event('input'));
  }
  function wrap(open, close, placeholder){
    var s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.substring(s, e) || placeholder;
    insert(open + sel + close, s + open.length, s + open.length + sel.length);
  }
  function line(tag, placeholder){
    var s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.substring(s, e) || placeholder;
    insert('<'+tag+'>'+sel+'</'+tag+'>', s + tag.length + 2, s + tag.length + 2 + sel.length);
  }

  document.getElementById('edToolbar').addEventListener('click', function(ev){
    var btn = ev.target.closest('button[data-act]');
    if (!btn) return;
    var act = btn.dataset.act;
    if (act === 'bold') wrap('<strong>', '</strong>', '加粗文字');
    else if (act === 'italic') wrap('<em>', '</em>', '斜体文字');
    else if (act === 'h') line('h3', '小标题');
    else if (act === 'ul') insert('<ul>\n  <li>列表项一</li>\n  <li>列表项二</li>\n</ul>');
    else if (act === 'ol') insert('<ol>\n  <li>第一项</li>\n  <li>第二项</li>\n</ol>');
    else if (act === 'quote') line('blockquote', '引用内容');
    else if (act === 'code') insert('<pre><code>\n代码内容\n</code></pre>');
    else if (act === 'hr') insert('\n<hr>\n');
    else if (act === 'br') insert('<br>');
    else if (act === 'link') {
      var s = ta.selectionStart, e = ta.selectionEnd;
      var sel = ta.value.substring(s, e) || '链接文字';
      var url = window.prompt('请输入链接地址：', 'https://');
      if (url) insert('<a href="'+url+'" target="_blank" rel="noopener">'+sel+'</a>', s, s + sel.length + url.length + 40);
    }
    else if (act === 'image' || act === 'file') {
      pendingType = act;
      fileInput.accept = (act === 'image') ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.zip,.rar,.7z';
      fileInput.click();
    }
  });

  fileInput.addEventListener('change', function(){
    var f = fileInput.files && fileInput.files[0];
    if (!f) return;
    setStatus('正在上传 ' + f.name + ' …');
    var fd = new FormData();
    fd.append('file', f);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?m=upload', true);
    xhr.upload.onprogress = function(e){ if (e.lengthComputable) setStatus('上传中 ' + Math.round(e.loaded/e.total*100) + '%'); };
    xhr.onload = function(){
      fileInput.value = '';
      var j; try { j = JSON.parse(xhr.responseText); } catch(err){ setStatus('上传失败：服务器返回异常', true); return; }
      if (!j.ok) { setStatus('上传失败：' + j.msg, true); return; }
      var safeName = j.name.replace(/[<>&"]/g, '');
      var html = (pendingType === 'image' && j.type === 'image')
        ? ('<img src="' + j.url + '" alt="' + safeName + '" style="max-width:100%;">')
        : ('<a href="' + j.url + '" target="_blank" rel="noopener">📎 ' + safeName + '</a>');
      insert('\n' + html + '\n');
      setStatus('已上传：' + j.name);
      showPreview(j);
    };
    xhr.onerror = function(){ setStatus('上传失败：网络错误', true); };
    xhr.send(fd);
  });

  function showPreview(j){
    if (j.type !== 'image') { preview.hidden = true; return; }
    preview.hidden = false;
    preview.innerHTML = '<span>上传预览：</span><img src="' + j.url + '" alt="预览">';
  }

  // Ctrl/Cmd + S 快速保存
  ta.addEventListener('keydown', function(e){
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      ta.form.requestSubmit ? ta.form.requestSubmit() : ta.form.submit();
    }
  });
})();
</script>
