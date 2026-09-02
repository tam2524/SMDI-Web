@echo off
echo ========================================================
echo Setting up Cloudflare Tunnel Service (Auto-Start)
echo ========================================================
echo.

:: Ensure administrator privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Please right-click this file and select "Run as Administrator".
    echo.
    pause
    exit /b 1
)

echo 1. Stopping old service...
cloudflared service uninstall >nul 2>&1

echo 2. Copying configuration to Windows System profile...
if not exist "C:\Windows\System32\config\systemprofile\.cloudflared" (
    mkdir "C:\Windows\System32\config\systemprofile\.cloudflared"
)
copy /Y "C:\Users\USER\.cloudflared\*" "C:\Windows\System32\config\systemprofile\.cloudflared\" >nul

echo 3. Installing background service...
cloudflared service install

echo 4. Starting service...
net start cloudflared

echo.
echo ========================================================
echo Cloudflare Tunnel Service successfully configured!
echo Your site is now permanently accessible at:
echo https://solidmotorcycle.com
echo ========================================================
echo.
pause
