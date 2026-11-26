<?php
/**
 * MySQL 建表语句（供安装向导调用，不依赖任何配置）
 * 引擎 InnoDB，字符集 utf8mb4，时间戳用 INT UNSIGNED 存储 Unix 秒。
 */

/** 返回建表 SQL 列表，按依赖顺序执行 */
function db_schema_statements(): array {
    return [
        "CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL COMMENT '分类名',
            `alias` VARCHAR(100) NOT NULL COMMENT '唯一标识',
            `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '分类描述',
            `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
            `created_at` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_categories_alias` (`alias`),
            KEY `idx_categories_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `posts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `alias` VARCHAR(255) NOT NULL DEFAULT '',
            `summary` TEXT,
            `content` LONGTEXT,
            `category_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `cover` VARCHAR(500) NOT NULL DEFAULT '',
            `views` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_top` TINYINT NOT NULL DEFAULT 0,
            `status` TINYINT NOT NULL DEFAULT 1,
            `created_at` INT UNSIGNED NOT NULL,
            `updated_at` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_posts_status_time` (`status`,`created_at`),
            KEY `idx_posts_home` (`status`,`is_top`,`created_at`),
            KEY `idx_posts_category` (`category_id`),
            KEY `idx_posts_cate_time` (`status`,`category_id`,`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `tags` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `alias` VARCHAR(100) NOT NULL DEFAULT '',
            `created_at` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_tags_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `post_tags` (
            `post_id` INT UNSIGNED NOT NULL,
            `tag_id` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`post_id`,`tag_id`),
            KEY `idx_post_tags_tag` (`tag_id`),
            CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_post_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `options` (
            `opt_key` VARCHAR(100) NOT NULL,
            `opt_value` TEXT,
            PRIMARY KEY (`opt_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(100) NOT NULL,
            `password` CHAR(32) NOT NULL COMMENT 'md5',
            `created_at` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_admins_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** 默认站点配置（安装时写入 options 表） */
function db_default_options(string $siteName = ''): array {
    return [
        'site_name' => $siteName !== '' ? $siteName : 'AHSZ 极简博客',
        'site_subtitle' => '记录生活，留住点滴',
        'site_desc' => '一个安静的角落，记录生活与思考。',
        'site_tags' => '极简,博客,生活,随笔',
        'site_keywords' => 'AHSZ,博客,极简',
        'site_author' => 'admin',
        'per_page' => '6',
        'theme_hue' => '250',
        'icp' => '',
    ];
}
