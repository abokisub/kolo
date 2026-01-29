# Deploy Frontend Script
# This script builds the React application and moves it to the Laravel public folder.

Write-Host "--- Starting React Build ---" -ForegroundColor Cyan

# 1. Build React
# Check if we are already in the frontend folder
if ((Split-Path -Leaf (Get-Location)) -eq "frontend") {
    Write-Host "Detected: Already in frontend folder."
} else {
    cd frontend
}
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "Build failed! Check the errors above." -ForegroundColor Red
    exit $LASTEXITCODE
}

Write-Host "--- Copying files to Laravel public folder ---" -ForegroundColor Cyan

# 2. Copy build assets to public
# Use Copy-Item with -Recurse and -Force to overwrite
Copy-Item -Path "build\*" -Destination "..\public\" -Recurse -Force

# 3. Handle index.html -> index.blade.php
# Read the index.html from build
$content = Get-Content -Path "build\index.html" -Raw

# Optional: Add any Blade directives if needed (e.g., @csrf)
# For now, we just save it as index.blade.php
$content | Out-File -FilePath "..\resources\views\index.blade.php" -Encoding utf8

# 4. Clean up public/index.html (important so Laravel's web.php takes precedence)
if (Test-Path "..\public\index.html") {
    Remove-Item -Path "..\public\index.html" -Force
}

cd ..

Write-Host "--- Deployment Complete! ---" -ForegroundColor Green
Write-Host "Static files are now in the /public folder."
Write-Host "Laravel will serve the dashboard through /resources/views/index.blade.php."
