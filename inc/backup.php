<?php
/**
 * 数据备份与恢复（MySQL / PDO，不依赖外部命令）
 * 备份：导出本站 6 张表的建表语句与全部数据为 UTF-8 SQL 文件。
 * 恢复：仅接受本站导出的备份文件，逐条白名单校验后执行；恢复前自动生成回滚备份。
 */
if (!defined('APP_NAME')) { http_response_code(403); exit; }

/** 允许备份/恢复的表（严格白名单） */
function backup_tables(): array {
    return ['categories', 'posts', 'tags', 'post_tags', 'options', 'admins'];
}

/** 备份文件存放目录 */
function backup_dir(): string {
    return dirname(__DIR__) . '/data/backups';
}

/** 确保备份目录存在且可写 */
function backup_ensure_dir(): void {
    $dir = backup_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('无法创建备份目录，请检查 data 目录的写入权限。');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('备份目录不可写，请检查 data/backups 目录权限。');
    }
}

/** 备份标识（恢复时校验，拒绝来源不明的文件） */
const BACKUP_MARK = '-- AHSZ-BACKUP';

/** SQL 值转义（MySQL 默认反斜杠转义模式） */
function backup_sql_value($v): string {
    if ($v === null) return 'NULL';
    return "'" . str_replace(
        ["\\", "\0", "\n", "\r", "'", "\"", "\x1a"],
        ["\\\\", "\\0", "\\n", "\\r", "\\'", "\\\"", "\\Z"],
        (string)$v
    ) . "'";
}

/** 生成完整 SQL 备份文本 */
function backup_create_sql(): string {
    $db = db();
    $cfg = db_config();
    $lines = [
        BACKUP_MARK . ' v2.0',
        '-- 生成时间: ' . date('Y-m-d H:i:s'),
        '-- 数据库: ' . (string)($cfg['dbname'] ?? ''),
        '-- 本文件由 AHSZ 极简博客后台导出，仅支持本系统恢复功能',
        '',
        'SET FOREIGN_KEY_CHECKS=0;',
    ];
    foreach (backup_tables() as $table) {
        $lines[] = '';
        $lines[] = '-- 表: ' . $table;
        $row = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        if (!$row || empty($row[1])) {
            throw new RuntimeException("读取表 {$table} 的结构失败");
        }
        // 去掉 SHOW CREATE TABLE 自带的库名前缀，便于恢复到同名库
        $ddl = (string)preg_replace('/^CREATE\s+TABLE\s+`[^`]+`\s*\./i', 'CREATE TABLE ', trim($row[1]));
        $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
        $lines[] = rtrim($ddl, "; \t\n\r\0\x0B") . ';';

        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) continue;
        $cols = array_keys($rows[0]);
        $colSql = implode(', ', array_map(static fn($c) => '`' . $c . '`', $cols));
        $buffer = [];
        foreach ($rows as $r) {
            $vals = [];
            foreach ($cols as $c) $vals[] = backup_sql_value($r[$c] ?? null);
            $buffer[] = '(' . implode(',', $vals) . ')';
            if (count($buffer) >= 100) {
                $lines[] = "INSERT INTO `{$table}` ({$colSql}) VALUES\n" . implode(",\n", $buffer) . ';';
                $buffer = [];
            }
        }
        if ($buffer) {
            $lines[] = "INSERT INTO `{$table}` ({$colSql}) VALUES\n" . implode(",\n", $buffer) . ';';
        }
    }
    $lines[] = '';
    $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
    return implode("\n", $lines) . "\n";
}

/** 生成不重复的备份文件名 */
function backup_unique_filename(string $prefix): string {
    $dir = backup_dir();
    for ($i = 0; $i < 50; $i++) {
        $name = $prefix . '-' . date('Ymd-His') . ($i > 0 ? '-' . $i : '') . '.sql';
        if (!is_file($dir . '/' . $name)) return $name;
    }
    return $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sql';
}

/** 仅允许本程序生成的备份文件名，防止路径穿越与任意文件下载/删除 */
function backup_valid_filename(string $name): bool {
    return (bool)preg_match('/^(ahsz-backup|auto-before-restore)-\d{8}-\d{6}(?:-\d+)?\.sql$/', $name);
}

/** 列出服务器上的备份文件（按时间倒序） */
function backup_list_files(): array {
    $dir = backup_dir();
    if (!is_dir($dir)) return [];
    $list = [];
    foreach (glob($dir . '/*.sql') ?: [] as $file) {
        $name = basename($file);
        if (!backup_valid_filename($name)) continue;
        $list[] = [
            'name' => $name,
            'size' => (int)filesize($file),
            'mtime' => (int)filemtime($file),
            'auto' => str_starts_with($name, 'auto-before-restore'),
        ];
    }
    usort($list, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $list;
}

/** 各表记录数（备份页概览） */
function backup_table_stats(): array {
    $db = db();
    $stats = [];
    foreach (backup_tables() as $t) {
        $stats[$t] = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    }
    return $stats;
}

/** 备份操作 CSRF 令牌 */
function backup_csrf_token(): string {
    if (empty($_SESSION['ahsz_backup_token']) || !is_string($_SESSION['ahsz_backup_token'])) {
        $_SESSION['ahsz_backup_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['ahsz_backup_token'];
}

/** 校验备份操作 CSRF 令牌，失败终止 */
function backup_csrf_check(): void {
    $token = $_POST['token'] ?? $_GET['token'] ?? '';
    if (!is_string($token) || empty($_SESSION['ahsz_backup_token'])
        || !hash_equals((string)$_SESSION['ahsz_backup_token'], $token)) {
        http_response_code(403);
        exit('安全校验失败（表单已过期），请刷新备份页面后重试。');
    }
}

/** 下载 SQL 文件 */
function backup_send_download(string $filename, string $content): void {
    while (ob_get_level() > 0) @ob_end_clean();
    header('Content-Type: application/octet-stream; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $content;
    exit;
}

/** 去掉整行 SQL 注释（导出内容中的换行已转义为 \n，不会误伤数据） */
function backup_strip_comments(string $sql): string {
    $out = [];
    foreach (explode("\n", $sql) as $line) {
        $t = ltrim($line);
        if (str_starts_with($t, '--') || str_starts_with($t, '#')) continue;
        $out[] = $line;
    }
    return implode("\n", $out);
}

/** 按分号切分 SQL，正确跳过引号与转义字符内的分号 */
function backup_split_sql(string $sql): array {
    $statements = [];
    $buf = '';
    $quote = '';
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($quote !== '' && $ch === '\\' && $i + 1 < $len) {
            $buf .= $ch . $sql[$i + 1];
            $i++;
            continue;
        }
        if ($quote !== '') {
            $buf .= $ch;
            if ($ch === $quote) $quote = '';
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') { $quote = $ch; $buf .= $ch; continue; }
        if ($ch === ';') {
            $s = trim($buf);
            if ($s !== '') $statements[] = $s;
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }
    $s = trim($buf);
    if ($s !== '') $statements[] = $s;
    return $statements;
}

/**
 * 从 SQL 文本恢复数据。
 * 仅允许本站备份标识 + 白名单表上的 DROP/CREATE/INSERT 与 SET FOREIGN_KEY_CHECKS。
 * 恢复前自动创建回滚备份（任一环节失败则不会开始恢复）。
 *
 * @return array{create:int,insert:int,drop:int,auto_backup:string}
 */
function backup_restore_sql(string $sql): array {
    if (strpos($sql, BACKUP_MARK) === false) {
        throw new RuntimeException('这不是本系统导出的备份文件（缺少备份标识），已拒绝恢复。');
    }
    if (!mb_check_encoding($sql, 'UTF-8')) {
        throw new RuntimeException('备份文件编码不是 UTF-8，已拒绝恢复。');
    }
    if (strlen($sql) > 50 * 1024 * 1024) {
        throw new RuntimeException('备份文件超过 50MB 上限，已拒绝恢复。');
    }

    $tables = implode('|', backup_tables());
    $counts = ['create' => 0, 'insert' => 0, 'drop' => 0];
    $pending = [];

    // 第一遍：只校验，不执行。任何不支持的语句都会中止整个恢复，避免部分数据被覆盖。
    foreach (backup_split_sql(backup_strip_comments($sql)) as $raw) {
        $s = trim($raw);
        if ($s === '') continue;
        // mysqldump 的 /*! ... */ 元注释语句整条跳过
        if (preg_match('/^\/\*!.*\*\/\s*$/s', $s)) continue;
        // 去掉 /*!40101 SET ... */ 形式的前缀后继续校验
        $s = (string)preg_replace('/^\/\*!\d+\s*/', '', $s);
        $s = rtrim($s);

        // 外键检查：仅接受 0/1，必须执行（否则按依赖顺序 DROP 父表会失败）
        if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01]\s*$/i', $s)) {
            $pending[] = ['sql' => $s, 'kind' => ''];
            continue;
        }
        // 其他 SET / 锁表语句一律跳过，不执行
        if (preg_match('/^SET\s+/i', $s) || preg_match('/^(?:LOCK|UNLOCK)\s+TABLES/i', $s)) continue;

        if (preg_match('/^DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`(' . $tables . ')`\s*$/i', $s, $m)) {
            $pending[] = ['sql' => "DROP TABLE IF EXISTS `{$m[1]}`", 'kind' => 'drop'];
            continue;
        }
        if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`[^`]+`\s*\.\s*)?`(' . $tables . ')`\s*\(/is', $s)) {
            $pending[] = ['sql' => $s, 'kind' => 'create'];
            continue;
        }
        if (preg_match('/^INSERT\s+INTO\s+`(' . $tables . ')`\s+/i', $s)) {
            $pending[] = ['sql' => $s, 'kind' => 'insert'];
            continue;
        }
        // 未知语句一律拒绝执行，避免任何计划外的数据库变更
        throw new RuntimeException('备份文件包含不支持的 SQL 语句，已终止恢复：' . mb_substr($s, 0, 60));
    }

    // 第二遍：全部校验通过后才生成回滚备份并真正执行
    backup_ensure_dir();
    $autoName = backup_unique_filename('auto-before-restore');
    $autoPath = backup_dir() . '/' . $autoName;
    if (file_put_contents($autoPath, backup_create_sql(), LOCK_EX) === false) {
        throw new RuntimeException('恢复前的自动备份写入失败，已终止恢复。');
    }

    $db = db();
    foreach ($pending as $item) {
        $db->exec($item['sql']);
        if ($item['kind'] !== '') $counts[$item['kind']]++;
    }

    $counts['auto_backup'] = $autoName;
    return $counts;
}
