@echo off
echo Setting up daily auto-backup for SMDI-Web Database...
schtasks /create /tn "SMDI-Web-Database-Backup" /tr "c:\xampp\php\php.exe c:\xampp\htdocs\SMDI-Web\api\cron_backup.php" /sc daily /st 20:00 /f
echo Task scheduled successfully! It will run daily at 8:00 PM.
pause
