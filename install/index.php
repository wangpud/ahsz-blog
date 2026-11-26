<?php
/**
 * AHSZ 极简博客 - 独立安装向导
 * 完全自包含：不使用前台布局 / 样式 / 导航，安装成功后自动锁定。
 * 安装只初始化空站点（数据库表 + 默认配置 + 管理员），不填充任何演示数据。
 */
require_once __DIR__ . '/../inc/schema.php';

$root = dirname(__DIR__);
$dataDir = $root . '/data';
$lockFile = $root . '/data/install.lock';
$configFile = $root . '/data/db.config.php';

/** HTML 转义 */
function ins_h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** JSON 响应（测试连接用） */
function ins_json(bool $ok, string $msg): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/** 表单取值 */
function ins_val(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? (string)$_POST[$key] : $default;
}

/** 校验数据库名：仅字母开头，字母数字下划线 */
function ins_valid_dbname(string $s): bool { return (bool)preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $s); }

/** 页面公共样式（三页共用） */
function ins_css(): string {
    return <<<'CSS'
:root{--pri:#5a6df0;--pri2:#7c8cff;--ink:#20242e;--mut:#6b7280;--line:#e6e8f0;--bg:#f5f6fa}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--bg);color:var(--ink);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif}
code{background:#f2f3f8;padding:2px 7px;border-radius:5px;color:#48506a;font-size:.86rem}
.center-body{display:grid;place-items:center;padding:40px 16px}
.form-body{padding:40px 16px}
/* 居中结果页 */
.b{max-width:520px;width:calc(100% - 40px);background:#fff;border:1px solid var(--line);border-radius:18px;padding:42px;box-shadow:0 16px 44px rgba(30,40,80,.07);text-align:center}
.b .i{width:62px;height:62px;margin:0 auto 18px;border-radius:18px;background:#eef1ff;display:grid;place-items:center;color:var(--pri)}
.b.ok .i{border-radius:50%;background:#e9f8ef;color:#16a34a}
.b h1{font-size:1.35rem;margin:0 0 12px}
.b p{color:var(--mut);line-height:1.9;font-size:.94rem;margin:0 0 16px}
.b .list{background:#f8f9fc;border-radius:12px;padding:16px 20px;font-size:.9rem;line-height:2;text-align:left}
.b .list b{color:#374151}
.b .tag{color:#16a34a;font-weight:700}
.b .warn{margin-top:18px;font-size:.82rem;color:#b45309;line-height:1.7}
/* 表单页 */
.wrap{max-width:760px;margin:0 auto}
.head{text-align:center;margin-bottom:26px}
.logo{width:58px;height:58px;margin:0 auto 14px;border-radius:16px;background:linear-gradient(135deg,var(--pri2),var(--pri));display:grid;place-items:center;color:#fff;box-shadow:0 10px 24px rgba(90,109,240,.28)}
.head h1{font-size:1.5rem;margin:0 0 8px}
.head p{color:var(--mut);font-size:.9rem;margin:0}
.steps{display:flex;gap:8px;justify-content:center;margin:22px 0 26px}
.step{font-size:.8rem;color:var(--mut);background:#fff;border:1px solid var(--line);padding:7px 14px;border-radius:20px}
.step.on{color:#fff;background:var(--pri);border-color:var(--pri)}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:28px;box-shadow:0 12px 36px rgba(30,40,80,.06)}
.card h2{font-size:1rem;margin:0 0 18px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.row label{font-size:.85rem;font-weight:600;color:#374151}
.hint{font-size:.76rem;color:#9ca3af}
input{width:100%;padding:11px 13px;border:1px solid #d9dce6;border-radius:9px;font-size:.92rem;outline:none;transition:.15s;font-family:inherit}
input:focus{border-color:var(--pri);box-shadow:0 0 0 3px rgba(90,109,240,.12)}
.env{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px}
.env div{font-size:.82rem;background:#f8f9fc;border-radius:8px;padding:9px 12px;display:flex;justify-content:space-between}
.ok{color:#16a34a;font-weight:700}
.bad{color:#dc2626;font-weight:700}
.actions{display:flex;gap:12px;align-items:center;margin-top:8px}
button{border:0;border-radius:9px;padding:12px 24px;font-size:.92rem;font-weight:600;cursor:pointer;font-family:inherit}
.primary{background:var(--pri);color:#fff;box-shadow:0 6px 16px rgba(90,109,240,.22)}
.primary:hover{background:#4d5fe0}
.ghost{background:#eef0f7;color:#4b5268}
.ghost:hover{background:#e4e7f1}
.alert{padding:12px 15px;border-radius:9px;margin-bottom:18px;font-size:.87rem;line-height:1.6}
.alert.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
.alert.note{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8}
#test-msg{font-size:.82rem;color:var(--mut)}
.foot{text-align:center;color:#9ca3af;font-size:.78rem;margin-top:22px}
@media(max-width:560px){.grid,.env{grid-template-columns:1fr}.card{padding:20px}}
CSS;
}

/** 页面骨架（三页共用） */
function ins_head(string $title, string $bodyClass = ''): void {
    ?><!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= ins_h($title) ?> - AHSZ 极简博客</title><style><?= ins_css() ?></style></head>
<body class="<?= ins_h($bodyClass) ?>"><?php
}
function ins_foot(): void { ?></body></html><?php }

/** 连接 MySQL 服务器（不指定库） */
function ins_connect_server(string $host, int $port, string $user, string $pass): PDO {
    return new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5, PDO::ATTR_EMULATE_PREPARES => false]
    );
}

/** 确保数据库存在，返回是否为新建 */
function ins_ensure_database(PDO $pdo, string $dbname): bool {
    $st = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
    $st->execute([$dbname]);
    if ($st->fetchColumn()) return false;
    $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    return true;
}

/** 读取并校验数据库参数：成功返回参数数组，失败返回错误字符串 */
function ins_db_params() {
    $h = trim(ins_val('db_host', '127.0.0.1'));
    $p = (int)ins_val('db_port', '3306');
    $n = trim(ins_val('db_name', 'ahsz_blog'));
    $u = trim(ins_val('db_user', 'root'));
    $pw = isset($_POST['db_pass']) ? (string)$_POST['db_pass'] : '';
    if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $h)) return '主机地址格式不正确';
    if ($p < 1 || $p > 65535) return '端口必须在 1-65535 之间';
    if (!ins_valid_dbname($n)) return '数据库名只能以字母开头，且只包含字母、数字和下划线';
    if ($u === '') return '数据库用户名不能为空';
    return ['h' => $h, 'p' => $p, 'n' => $n, 'u' => $u, 'pw' => $pw];
}

/* ---------- 已安装 / 已锁定 ---------- */
if (is_file($lockFile) || is_file($configFile)) {
    ins_head('安装向导已锁定', 'center-body');
    ?>
    <div class="b">
      <div class="i"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div>
      <h1>安装向导已锁定</h1>
      <p>站点已完成安装，本向导不再开放。如需重装，请删除 <code>data/install.lock</code> 与 <code>data/db.config.php</code>。</p>
      <p class="warn">删除配置会断开当前站点的数据库连接。</p>
    </div>
    <?php
    ins_foot();
    exit;
}

/* ---------- 环境检测 ---------- */
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
$env = [
    'php' => PHP_VERSION_ID >= 80000,
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'writable' => is_dir($dataDir) && is_writable($dataDir),
];
$envOk = $env['php'] && $env['pdo_mysql'] && $env['mbstring'] && $env['writable'];

/* ---------- 测试连接 ---------- */
if (($_POST['action'] ?? '') === 'test') {
    $r = ins_db_params();
    if (!is_array($r)) ins_json(false, $r);
    try {
        $pdo = ins_connect_server($r['h'], $r['p'], $r['u'], $r['pw']);
        $created = ins_ensure_database($pdo, $r['n']);
        ins_json(true, $created ? "连接成功，已创建数据库 {$r['n']}" : "连接成功，数据库 {$r['n']} 已存在");
    } catch (Throwable $e) {
        ins_json(false, '连接失败：' . $e->getMessage());
    }
}

/* ---------- 执行安装 ---------- */
$error = '';
if (($_POST['do'] ?? '') === 'install' && $envOk) {
    $r = ins_db_params();
    $au = trim(ins_val('admin_user', 'admin'));
    $ap = isset($_POST['admin_pass']) ? (string)$_POST['admin_pass'] : '';
    $ap2 = isset($_POST['admin_pass2']) ? (string)$_POST['admin_pass2'] : '';
    $site = trim(ins_val('site_name', 'AHSZ 极简博客'));

    if (!is_array($r)) {
        $error = $r;
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $au)) {
        $error = '管理员账号需为 3-30 位字母、数字或下划线';
    } elseif (strlen($ap) < 6 || $ap !== $ap2) {
        $error = '管理员密码至少 6 位，且两次输入必须一致';
    } elseif ($site === '' || mb_strlen($site) > 100) {
        $error = '网站名称不能为空且不超过 100 字';
    } else {
        try {
            $server = ins_connect_server($r['h'], $r['p'], $r['u'], $r['pw']);
            $created = ins_ensure_database($server, $r['n']);
            $pdo = new PDO(
                "mysql:host={$r['h']};port={$r['p']};dbname={$r['n']};charset=utf8mb4",
                $r['u'], $r['pw'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
            );
            foreach (db_schema_statements() as $sql) { $pdo->exec($sql); }

            $optStmt = $pdo->prepare("INSERT INTO `options`(`opt_key`,`opt_value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `opt_value`=VALUES(`opt_value`)");
            foreach (db_default_options($site) as $k => $v) { $optStmt->execute([$k, $v]); }

            if (!(int)$pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn()) {
                $pdo->prepare("INSERT INTO `admins`(`username`,`password`,`created_at`) VALUES(?,?,?)")
                    ->execute([$au, md5($ap), time()]);
            }

            $cfg = [
                'host' => $r['h'], 'port' => $r['p'], 'dbname' => $r['n'],
                'username' => $r['u'], 'password' => $r['pw'], 'charset' => 'utf8mb4',
            ];
            if (file_put_contents($configFile, "<?php\nreturn " . var_export($cfg, true) . ";\n", LOCK_EX) === false) {
                throw new RuntimeException('无法写入 data/db.config.php，请检查 data 目录权限');
            }
            file_put_contents($lockFile, date('Y-m-d H:i:s'), LOCK_EX);

            ins_head('安装完成', 'center-body');
            ?>
            <div class="b ok">
              <div class="i"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 13 4 4L19 7"/></svg></div>
              <h1>安装完成</h1>
              <p>数据库已初始化为空站点，安装向导已自动锁定。</p>
              <div class="list">
                <div><b>数据库：</b><?= ins_h($r['n']) ?> <span class="tag"><?= $created ? '（新建）' : '（已存在）' ?></span></div>
                <div><b>管理员：</b><?= ins_h($au) ?></div>
              </div>
              <div class="warn">站点暂无任何文章与分类，请自行访问前台 <code>/</code> 与后台 <code>/admin/</code> 开始发布内容。</div>
            </div>
            <?php
            ins_foot();
            exit;
        } catch (Throwable $e) {
            $error = '安装失败：' . $e->getMessage();
        }
    }
}

/* ---------- 表单页 ---------- */
$dbHost = ins_val('db_host', '127.0.0.1');
$dbPort = ins_val('db_port', '3306');
$dbName = ins_val('db_name', 'ahsz_blog');
$dbUser = ins_val('db_user', 'root');
$siteName = ins_val('site_name', 'AHSZ 极简博客');
$adminUser = ins_val('admin_user', 'admin');

ins_head('安装向导', 'form-body');
?>
<div class="wrap">
  <div class="head">
    <div class="logo"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg></div>
    <h1>AHSZ 极简博客 · 安装向导</h1>
    <p>本页独立于网站前台，安装完成后自动锁定</p>
  </div>
  <div class="steps"><span class="step on">1. 环境检测</span><span class="step on">2. 填写配置</span><span class="step">3. 完成安装</span></div>

  <div class="card">
    <h2>运行环境</h2>
    <div class="env">
      <div><span>PHP 8.0+</span><span class="<?= $env['php'] ? 'ok' : 'bad' ?>"><?= $env['php'] ? '通过' : '不通过' ?></span></div>
      <div><span>PDO MySQL 扩展</span><span class="<?= $env['pdo_mysql'] ? 'ok' : 'bad' ?>"><?= $env['pdo_mysql'] ? '通过' : '不通过' ?></span></div>
      <div><span>mbstring 扩展</span><span class="<?= $env['mbstring'] ? 'ok' : 'bad' ?>"><?= $env['mbstring'] ? '通过' : '不通过' ?></span></div>
      <div><span>data 目录可写</span><span class="<?= $env['writable'] ? 'ok' : 'bad' ?>"><?= $env['writable'] ? '通过' : '不通过' ?></span></div>
    </div>

    <?php if ($error || !$envOk): ?>
    <div class="alert err"><?= $error ?: '环境检测未通过，请启用所需 PHP 扩展并确保 data 目录可写。' ?></div>
    <?php endif; ?>

    <form id="install-form" method="post" action="">
      <h2>数据库配置</h2>
      <div class="grid">
        <div class="row"><label>数据库主机</label><input name="db_host" value="<?= ins_h($dbHost) ?>" required></div>
        <div class="row"><label>端口</label><input name="db_port" type="number" value="<?= ins_h($dbPort) ?>" required></div>
        <div class="row"><label>数据库名</label><input name="db_name" value="<?= ins_h($dbName) ?>" placeholder="ahsz_blog" required><div class="hint">不存在时会尝试自动创建（需账号有建库权限）</div></div>
        <div class="row"><label>数据库用户名</label><input name="db_user" value="<?= ins_h($dbUser) ?>" required></div>
        <div class="row"><label>数据库密码</label><input name="db_pass" type="password" value="" autocomplete="new-password"><div class="hint">留空表示无密码</div></div>
      </div>

      <h2>站点与管理账号</h2>
      <div class="grid">
        <div class="row"><label>网站名称</label><input name="site_name" value="<?= ins_h($siteName) ?>" required></div>
        <div class="row"><label>管理员账号</label><input name="admin_user" value="<?= ins_h($adminUser) ?>" required></div>
        <div class="row"><label>管理员密码</label><input name="admin_pass" type="password" required></div>
        <div class="row"><label>确认密码</label><input name="admin_pass2" type="password" required></div>
      </div>

      <div class="alert note">安装会创建空站点（不含任何文章 / 分类 / 标签），并在 <code>data/</code> 下写入 <code>db.config.php</code> 与 <code>install.lock</code>。前者保存数据库账号，请勿公开提交。</div>

      <div class="actions">
        <button type="submit" name="do" value="install" class="primary" <?= $envOk ? '' : 'disabled' ?>>开始安装</button>
        <button type="button" id="test-btn" class="ghost" <?= $envOk ? '' : 'disabled' ?>>测试数据库连接</button>
        <span id="test-msg"></span>
      </div>
    </form>
  </div>
  <div class="foot">AHSZ 极简博客 · 独立安装向导</div>
</div>

<script>
document.getElementById('test-btn').addEventListener('click', function () {
  var btn = this, msg = document.getElementById('test-msg');
  var fd = new FormData(document.getElementById('install-form'));
  fd.append('action', 'test');
  btn.disabled = true; msg.textContent = '正在连接…';
  fetch(location.href, {method: 'POST', body: fd})
    .then(function (r) { return r.json(); })
    .then(function (j) { msg.textContent = j.msg; msg.style.color = j.ok ? '#16a34a' : '#dc2626'; })
    .catch(function () { msg.textContent = '请求失败'; msg.style.color = '#dc2626'; })
    .finally(function () { btn.disabled = false; });
});
</script>
<?php ins_foot(); ?>
