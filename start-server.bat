@echo off
echo Starting Ubora Laravel server with increased upload limits...
echo.
echo PHP Settings:
echo - upload_max_filesize: 10M
echo - post_max_size: 12M
echo - max_execution_time: 300
echo - memory_limit: 256M
echo.
php -d upload_max_filesize=10M -d post_max_size=12M -d max_execution_time=300 -d memory_limit=256M artisan serve --host=0.0.0.0 --port=8001
pause
