# JHN Drive - Port 8088 Server Launcher
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "  JHN Drive - Minimalist Dark Mode Cloud Storage" -ForegroundColor White
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "  Dedicated Port: 8088" -ForegroundColor Green
Write-Host "  Local URL:      http://localhost:8088" -ForegroundColor Yellow
Write-Host "  LAN Server:     http://0.0.0.0:8088" -ForegroundColor Yellow
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host ""

php artisan serve --host=0.0.0.0 --port=8088
