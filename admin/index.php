<?php
/**
 * AHSZ 极简博客 - 后台单入口
 * 功能：概览 / 文章管理 / 分类管理 / 标签管理 / 站点设置
 * 不含：应用中心、插件管理、模块管理、页面设置
 */
require_once __DIR__ . '/../inc/config.php';
db_init();

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
if ($m === 'password-save') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $st = $db->prepare("SELECT * FROM admins WHERE username=?");
    $st->execute([$_SESSION['admin_user'] ?? 'admin']);
    $admin = $st->fetch();
    if ($admin && $admin['password'] === md5($old) && $new !== '') {
        $db->prepare("UPDATE admins SET password=? WHERE id=?")->execute([md5($new), $admin['id']]);
        $msg = '密码已修改';
    } else {
        $msg = '原密码错误或新密码为空';
    }
    header('Location: index.php?m=password&msg=' . urlencode($msg));
    exit;
}

$msg = $_GET['msg'] ?? '';

// ============ 视图分发 ============
$view = $m;
if (!in_array($m, ['dashboard','posts','post-edit','categories','category-edit','tags','settings','password'])) {
    $view = 'dashboard';
}

// 准备各视图数据
if ($m === 'dashboard') {
    $stats = site_stats();
    $recent = $db->query("SELECT p.*, c.name cname FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
} elseif ($m === 'posts') {
    $posts = $db->query("SELECT p.*, c.name cname FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC")->fetchAll();
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
}

include __DIR__ . '/views/layout-head.php';
include __DIR__ . '/views/' . $view . '.php';
include __DIR__ . '/views/layout-foot.php';
