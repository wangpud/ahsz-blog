@echo off
chcp 65001 >nul
title AHSZ 极简博客 - 预览服务器
echo ============================================
echo   AHSZ 极简博客 · 服务器（监听所有网卡）
echo ============================================
echo.
echo   本机访问:     http://127.0.0.1:8092/
echo   局域网访问:   http://192.168.1.29:8092/
echo   后台地址:     http://127.0.0.1:8092/admin/index.php?m=login
echo   账号: admin  密码: admin888
echo.
echo   提示: 局域网地址以当前机器IP为准，IP变化后请更新上面的地址。
echo   手机/其他设备需与本机在同一局域网，且防火墙放行 8092 端口。
echo.
echo   按 Ctrl+C 停止服务器
echo ============================================
echo.
D:\WorkBuddy\php83\php.exe -S 0.0.0.0:8092 -t D:\WorkBuddy\ahsz-blog D:\WorkBuddy\ahsz-blog\router.php
pause
