<?php
/**
 * AHSZ 极简博客 - 后台单入口
 * 功能：概览 / 文章管理 / 分类管理 / 标签管理 / 站点设置
 * 不含：应用中心、插件管理、模块管理、页面设置
 */
require_once __DIR__ . '/../inc/config.php';
if (!db_is_installed()) { require __DIR__ . '/../inc/need-install.php'; exit; }
db_init();
require_once __DIR__ . '/../inc/backup.php';
require_once __DIR__ . '/../inc/cache.php';

$m = $_GET['m'] ?? 'dashboard';

// 登录/登出不鉴权
if ($m === 'login' || $m === 'do-login') {
    if ($m === 'do-login') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $st = db()->prepare("SELECT * FROM admins WHERE username=?");
        $st->execute([$u]);
        $admin = $st->fetch();
        if ($admin && $admin['password'] === md5($p)) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            header('Location: index.php?m=dashboard');
            exit;
        }
        $error = '用户名或密码错误';
        include __DIR__ . '/views/login.php';
        exit;
    }
    if (!empty($_SESSION['admin_logged'])) { header('Location: index.php?m=dashboard'); exit; }
    $error = $_GET['error'] ?? '';
    include __DIR__ . '/views/login.php';
    exit;
}

if ($m === 'logout') {
    session_destroy();
    header('Location: index.php?m=login');
    exit;
}

// 其余页面需登录
require_login();

// 内容变更类操作立即让前台页面缓存失效
$cacheBusting = ['post-save','post-delete','post-bulk-delete','post-toggle-top','cate-save','cate-delete','tag-delete','settings-save','backup-restore-file','backup-restore-upload'];
if (in_array($m, $cacheBusting, true)) page_cache_clear();

$db = db();
$msg = '';

// ============ 动作处理 ============
if ($m === 'post-save') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $tags = $_POST['tags'] ?? '';
    $isTop = (int)($_POST['is_top'] ?? 0);
    $status = (int)($_POST['status'] ?? 1);
    if ($title === '') { die('标题不能为空'); }
    if ($summary === '') { $summary = make_excerpt($content, 140); }
    // 校验分类是否存在，避免写入孤立 category_id
    if ($categoryId > 0) {
        $stc = $db->prepare("SELECT COUNT(*) FROM categories WHERE id=?");
        $stc->execute([$categoryId]);
        if ((int)$stc->fetchColumn() === 0) $categoryId = 0;
    }
    if ($id > 0) {
        $db->prepare("UPDATE posts SET title=?,summary=?,content=?,category_id=?,is_top=?,status=?,updated_at=? WHERE id=?")
           ->execute([$title, $summary, $content, $categoryId, $isTop, $status, time(), $id]);
        set_post_tags($id, explode(',', $tags));
        $msg = '文章已更新';
    } else {
        $db->prepare("INSERT INTO posts(title,alias,summary,content,category_id,is_top,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)")
           ->execute([$title, 'post-' . time(), $summary, $content, $categoryId, $isTop, $status, time(), time()]);
        set_post_tags((int)$db->lastInsertId(), explode(',', $tags));
        $msg = '文章已发布';
    }
    header('Location: index.php?m=posts&msg=' . urlencode($msg));
    exit;
}
if ($m === 'post-delete') {
    $id = (int)($_GET['id'] ?? 0);
    $db->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
    header('Location: index.php?m=posts&msg=' . urlencode('文章已删除'));
    exit;
}
if ($m === 'post-bulk-delete') {
    // 只接受正整数 ID，去重后用预处理批量删除
    $ids = $_POST['ids'] ?? [];
    $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0))) : [];
    $n = 0;
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM post_tags WHERE post_id IN ($in)")->execute($ids);
        $st = $db->prepare("DELETE FROM posts WHERE id IN ($in)");
        $st->execute($ids);
        $n = $st->rowCount();
    }
    header('Location: index.php?m=posts&msg=' . urlencode($n > 0 ? "已批量删除 {$n} 篇文章" : '未选择要删除的文章'));
    exit;
}
if ($m === 'upload') {
    // 附件上传：仅登录用户可用，白名单扩展名 + 大小限制 + 随机文件名，返回 JSON
    header('Content-Type: application/json; charset=utf-8');
    $json = function (bool $ok, array $data = [], string $msg = '') {
        echo json_encode(['ok' => $ok, 'msg' => $msg] + $data, JSON_UNESCAPED_UNICODE);
        exit;
    };
    if (!isset($_FILES['file'])) $json(false, [], '未选择文件');
    $f = $_FILES['file'];
    if (!is_array($f) || $f['error'] !== UPLOAD_ERR_OK) {
        $errs = [1 => '文件超过 PHP 限制', 2 => '文件超过表单限制', 3 => '文件仅部分上传', 4 => '未选择文件', 6 => '缺少临时目录', 7 => '写入失败'];
        $json(false, [], '上传失败：' . ($errs[$f['error']] ?? '未知错误'));
    }
    $maxBytes = 20 * 1024 * 1024; // 单文件上限 20MB
    if ($f['size'] <= 0 || $f['size'] > $maxBytes) $json(false, [], '文件大小须在 1 字节到 20MB 之间');

    // 扩展名白名单（图片与常见文档，禁止 php 等可执行文件）
    $allow = [
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image', 'bmp' => 'image',
        'pdf' => 'file', 'doc' => 'file', 'docx' => 'file', 'xls' => 'file', 'xlsx' => 'file', 'ppt' => 'file', 'pptx' => 'file',
        'txt' => 'file', 'md' => 'file', 'zip' => 'file', 'rar' => 'file', '7z' => 'file', 'mp3' => 'file', 'mp4' => 'file', 'json' => 'file',
    ];
    $orig = (string)($f['name'] ?? '');
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!isset($allow[$ext])) $json(false, [], '不允许的文件类型：' . ($ext === '' ? '无扩展名' : $ext));
    $type = $allow[$ext];

    $dir = dirname(__DIR__) . '/uploads/' . date('Ym');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) $json(false, [], '无法创建上传目录');
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) $json(false, [], '保存文件失败，请检查 uploads 目录权限');
    @chmod($dest, 0644);

    $url = '/uploads/' . date('Ym') . '/' . $name;
    $json(true, ['url' => $url, 'name' => $orig, 'type' => $type, 'size' => (int)$f['size'], 'ext' => $ext]);
}

if ($m === 'post-toggle-top') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $db->prepare("SELECT is_top FROM posts WHERE id=?");
    $st->execute([$id]);
    $cur = $st->fetchColumn();
    if ($cur !== false) {
        $new = (int)$cur === 1 ? 0 : 1;
        $db->prepare("UPDATE posts SET is_top=?, updated_at=? WHERE id=?")->execute([$new, time(), $id]);
        $msg = $new ? '已置顶' : '已取消置顶';
    } else {
        $msg = '文章不存在';
    }
    // 保留来源页的分类/置顶视图/页码，操作后回到列表原位置
    $back = 'index.php?m=posts';
    if (isset($_GET['cate'])) $back .= '&cate=' . (int)$_GET['cate'];
    if (!empty($_GET['top'])) $back .= '&top=' . (int)$_GET['top'];
    if (isset($_GET['p'])) $back .= '&p=' . max(1, (int)$_GET['p']);
    header('Location: ' . $back . '&msg=' . urlencode($msg));
    exit;
}

if ($m === 'cate-save') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $alias = trim($_POST['alias'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if ($name === '') die('分类名不能为空');

    // 生成唯一 alias：去掉占用（含自身），冲突时追加 -2/-3…，彻底避免唯一约束报错
    $aliasTaken = function (string $a) use ($db, $id): bool {
        $st = $db->prepare("SELECT COUNT(*) FROM categories WHERE alias=? AND id<>?");
        $st->execute([$a, $id]);
        return (int)$st->fetchColumn() > 0;
    };
    if ($alias === '') $alias = 'cat-' . date('ymdHis') . '-' . mt_rand(100, 999);
    while ($aliasTaken($alias)) {
        $alias = preg_match('/^(.*)-\d+$/', $alias, $mm) ? $mm[1] . '-' . ((int)substr($alias, strlen($mm[1]) + 1) + 1) : $alias . '-2';
    }

    if ($id > 0) {
        $db->prepare("UPDATE categories SET name=?,alias=?,description=?,sort_order=? WHERE id=?")->execute([$name, $alias, $desc, $sort, $id]);
    } else {
        $db->prepare("INSERT INTO categories(name,alias,description,sort_order,created_at) VALUES(?,?,?,?,?)")->execute([$name, $alias, $desc, $sort, time()]);
    }
    header('Location: index.php?m=categories&msg=' . urlencode('分类已保存'));
    exit;
}
if ($m === 'cate-delete') {
    $id = (int)($_GET['id'] ?? 0);
    // 该分类下的文章移到未分类(0)
    $db->prepare("UPDATE posts SET category_id=0 WHERE category_id=?")->execute([$id]);
    $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    header('Location: index.php?m=categories&msg=' . urlencode('分类已删除'));
    exit;
}
if ($m === 'tag-delete') {
    $id = (int)($_GET['id'] ?? 0);
    $db->prepare("DELETE FROM post_tags WHERE tag_id=?")->execute([$id]);
    $db->prepare("DELETE FROM tags WHERE id=?")->execute([$id]);
    header('Location: index.php?m=tags&msg=' . urlencode('标签已删除'));
    exit;
}
if ($m === 'settings-save') {
    $keys = ['site_name','site_subtitle','site_desc','site_tags','site_keywords','site_author','per_page','theme_hue','icp'];
    foreach ($keys as $k) {
        if (array_key_exists($k, $_POST)) { opt_set($k, trim($_POST[$k])); }
    }
    header('Location: index.php?m=settings&msg=' . urlencode('站点设置已保存'));
    exit;
}
// ============ 数据备份与恢复 ============
if ($m === 'backup-download') {
    backup_csrf_check();
    try {
        backup_send_download(backup_unique_filename('ahsz-backup'), backup_create_sql());
    } catch (Throwable $ex) {
        header('Location: index.php?m=backup&msg=' . urlencode('备份失败：' . $ex->getMessage()));
        exit;
    }
}
if ($m === 'backup-create') {
    backup_csrf_check();
    try {
        backup_ensure_dir();
        $name = backup_unique_filename('ahsz-backup');
        if (file_put_contents(backup_dir() . '/' . $name, backup_create_sql(), LOCK_EX) === false) {
            throw new RuntimeException('写入备份文件失败');
        }
        $msg = '备份已保存到服务器：' . $name;
    } catch (Throwable $ex) {
        $msg = '备份失败：' . $ex->getMessage();
    }
    header('Location: index.php?m=backup&msg=' . urlencode($msg));
    exit;
}
if ($m === 'backup-delete') {
    backup_csrf_check();
    $name = (string)($_GET['file'] ?? '');
    $path = backup_dir() . '/' . $name;
    if (!backup_valid_filename($name) || !is_file($path)) {
        $msg = '备份文件不存在';
    } elseif (@unlink($path)) {
        $msg = '备份已删除：' . $name;
    } else {
        $msg = '删除失败，请检查目录权限';
    }
    header('Location: index.php?m=backup&msg=' . urlencode($msg));
    exit;
}
if ($m === 'backup-file-download') {
    backup_csrf_check();
    $name = (string)($_GET['file'] ?? '');
    $path = backup_dir() . '/' . $name;
    if (!backup_valid_filename($name) || !is_file($path)) { http_response_code(404); exit('备份文件不存在'); }
    backup_send_download($name, (string)file_get_contents($path));
}
if ($m === 'backup-restore-file') {
    backup_csrf_check();
    $name = (string)($_POST['file'] ?? '');
    $path = backup_dir() . '/' . $name;
    try {
        if (!backup_valid_filename($name) || !is_file($path)) throw new RuntimeException('备份文件不存在');
        $r = backup_restore_sql((string)file_get_contents($path));
        $msg = "恢复完成：重建 {$r['create']} 张表，导入 {$r['insert']} 条 INSERT；恢复前数据已备份为 {$r['auto_backup']}";
    } catch (Throwable $ex) {
        $msg = '恢复失败：' . $ex->getMessage();
    }
    header('Location: index.php?m=backup&msg=' . urlencode($msg));
    exit;
}
if ($m === 'backup-restore-upload') {
    backup_csrf_check();
    try {
        if (!isset($_FILES['sqlfile'])) throw new RuntimeException('请选择 SQL 备份文件');
        $f = $_FILES['sqlfile'];
        if (!is_array($f) || ($f['error'] ?? null) !== UPLOAD_ERR_OK) throw new RuntimeException('文件上传失败');
        if ($f['size'] <= 0 || $f['size'] > 50 * 1024 * 1024) throw new RuntimeException('文件大小须在 1 字节到 50MB 之间');
        if (strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION)) !== 'sql') {
            throw new RuntimeException('只接受 .sql 备份文件');
        }
        $r = backup_restore_sql((string)file_get_contents($f['tmp_name']));
        $msg = "恢复完成：重建 {$r['create']} 张表，导入 {$r['insert']} 条 INSERT；恢复前数据已备份为 {$r['auto_backup']}";
    } catch (Throwable $ex) {
        $msg = '恢复失败：' . $ex->getMessage();
    }
    header('Location: index.php?m=backup&msg=' . urlencode($msg));
    exit;
}

if ($m === 'password-save') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $st = $db->prepare("SELECT * FROM admins WHERE username=?");
    $st->execute([$_SESSION['admin_user'] ?? 'admin']);
    $admin = $st->fetch();
    if ($admin && $admin['password'] === md5($old) && strlen($new) >= 6) {
        $db->prepare("UPDATE admins SET password=? WHERE id=?")->execute([md5($new), $admin['id']]);
        $msg = '密码已修改';
    } else {
        $msg = '原密码错误或新密码不足 6 位';
    }
    header('Location: index.php?m=password&msg=' . urlencode($msg));
    exit;
}

$msg = $_GET['msg'] ?? '';

// ============ 视图分发 ============
$view = $m;
if (!in_array($m, ['dashboard','posts','post-edit','categories','category-edit','tags','settings','password','backup'])) {
    $view = 'dashboard';
}

// 准备各视图数据
if ($m === 'dashboard') {
    $stats = site_stats();
    $recent = $db->query("SELECT p.*, c.name cname FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
} elseif ($m === 'posts') {
    // 按分类 / 置顶管理：列表标签条 + 分页（每页 20 篇）
    // cate: 0=全部, -1=未分类, >0=具体分类ID；top=1=只看置顶文章
    $cateId = (int)($_GET['cate'] ?? 0);
    $onlyTop = (int)($_GET['top'] ?? 0) === 1;
    $cateList = get_categories();
    $cateCounts = [0 => 0, -1 => 0];
    foreach ($db->query("SELECT category_id, COUNT(*) cnt FROM posts GROUP BY category_id") as $row) {
        $k = (int)$row['category_id'];
        $cnt = (int)$row['cnt'];
        $cateCounts[0] += $cnt;                       // 全部：累加所有分类
        $cateCounts[$k === 0 ? -1 : $k] = $cnt;       // 未分类用 -1 表示，与筛选参数一致
    }
    $topCount = (int)$db->query("SELECT COUNT(*) FROM posts WHERE is_top=1")->fetchColumn();
    $where = $onlyTop ? 'p.is_top=1' : ($cateId === -1 ? 'p.category_id=0' : ($cateId > 0 ? 'p.category_id=' . $cateId : '1=1'));
    $page = max(1, (int)($_GET['p'] ?? 1));
    $perPageAdmin = 20;
    $totalPosts = (int)$db->query("SELECT COUNT(*) FROM posts p WHERE {$where}")->fetchColumn();
    $baseLink = 'index.php?m=posts' . ($onlyTop ? '&top=1' : ($cateId !== 0 ? '&cate=' . $cateId : ''));
    [$pager, $offset] = paginate($totalPosts, $page, $perPageAdmin, $baseLink);
    $posts = $db->query("SELECT p.*, c.name cname FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE {$where} ORDER BY p.created_at DESC LIMIT {$offset},{$perPageAdmin}")->fetchAll();
} elseif ($m === 'post-edit') {
    $id = (int)($_GET['id'] ?? 0);
    $post = null;
    if ($id > 0) {
        $st = $db->prepare("SELECT * FROM posts WHERE id=?"); $st->execute([$id]); $post = $st->fetch();
    }
    $cats = get_categories();
    $tagNames = $post ? array_column(post_tags((int)$post['id']), 'name') : [];
} elseif ($m === 'categories') {
    $cats = $db->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id) cnt FROM categories c ORDER BY c.sort_order,c.id")->fetchAll();
} elseif ($m === 'category-edit') {
    $id = (int)($_GET['id'] ?? 0);
    $cat = null;
    if ($id > 0) { $st = $db->prepare("SELECT * FROM categories WHERE id=?"); $st->execute([$id]); $cat = $st->fetch(); }
} elseif ($m === 'tags') {
    $tags = get_tags();
} elseif ($m === 'password') {
    // 无额外数据
} elseif ($m === 'backup') {
    $backupFiles = backup_list_files();
    $backupStats = backup_table_stats();
    $backupToken = backup_csrf_token();
    $dir = backup_dir();
    $backupWritable = is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir));
}

include __DIR__ . '/views/layout-head.php';
include __DIR__ . '/views/' . $view . '.php';
include __DIR__ . '/views/layout-foot.php';
