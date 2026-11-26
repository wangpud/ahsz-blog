@echo off
chcp 65001 >nul
title AHSZ 极简博客 - 预览服务器
echo ============================================
echo   AHSZ 极简博客 · 服务器（监听所有网卡）
echo ============================================
echo.
echo   本机访问:     http://127.0.0.1:8092/
echo   安装向导:     http://127.0.0.1:8092/install/
echo   后台地址:     http://127.0.0.1:8092/admin/
echo.
echo   首次使用请先访问安装向导，配置 MySQL 并设置管理员账号。
echo   安装向导与前台无关联，安装完成后会自动锁定，不会出现在导航中。
echo.
echo   提示: 局域网地址以当前机器IP为准，手机/其他设备需与本机同局域网，
echo         且防火墙放行 8092 端口。按 Ctrl+C 停止服务器。
echo ============================================
echo.
D:\WorkBuddy\php83\php.exe -d zend_extension="D:\WorkBuddy\php83\ext\php_opcache.dll" -d opcache.enable=1 -d opcache.memory_consumption=32 -d opcache.max_accelerated_files=1000 -d opcache.validate_timestamps=1 -S 0.0.0.0:8092 -t D:\WorkBuddy\ahsz-blog D:\WorkBuddy\ahsz-blog\router.php
pause
