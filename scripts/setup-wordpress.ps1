#requires -Version 5.1
<#
.SYNOPSIS
    Bootstrap (or repair) the independent local WordPress install for the Perego client site.

.DESCRIPTION
    Perego is a CoreX client site: this repo holds perego-site/ (plugin) + perego-theme/ (theme),
    tracked in git. The WordPress runtime lives in ./wp (gitignored) — its own install, own
    database, own vhost — with the CoreX framework's required plugins + recommended add-ons
    junctioned in read-only from the framework checkout (-FrameworkRoot), plus this repo's own
    perego-site/perego-theme junctioned in as the active plugin/theme. Idempotent; safe to re-run.

    Real symlinks (mklink /D) need elevation; this uses directory JUNCTIONS (mklink /J), which do
    not, matching C:\wamp64\www\corex\scripts\setup-wordpress.ps1.

.EXAMPLE
    powershell -File .\scripts\setup-wordpress.ps1
#>
[CmdletBinding()]
param(
    [string]$SiteUrl        = 'http://perego.local',
    [string]$Title          = 'Perego Creative Studio',
    [string]$AdminUser      = 'admin',
    [string]$AdminEmail     = 'admin@example.com',
    [string]$AdminPassword  = 'changeme',
    [string]$DbName         = 'perego',
    [string]$DbUser         = 'root',
    [string]$DbPass         = '',
    [string]$DbHost         = 'localhost',
    [string]$DbPrefix       = 'perego_wp_',
    [string]$WpDir          = 'wp',
    [string]$FrameworkRoot  = 'C:\wamp64\www\corex',
    [string[]]$FoundationPlugins = @('corex-core', 'corex-blocks', 'corex-config', 'corex-forms'),
    [string[]]$AddonSlugs        = @('corex-ui', 'corex-kit-company', 'corex-media'),
    [string]$MysqlBin       = ''   # auto-detected from WAMP if empty
)

$ErrorActionPreference = 'Continue'

function Fail([string]$Message) { Write-Host "ERROR: $Message" -ForegroundColor Red; exit 1 }

$Root   = Split-Path -Parent $PSScriptRoot
Set-Location $Root
$WpPath = Join-Path $Root $WpDir

if (-not (Test-Path $FrameworkRoot)) { Fail "FrameworkRoot not found: $FrameworkRoot" }
if (-not (Test-Path (Join-Path $Root 'perego-site'))) { Fail "perego-site/ not found - run Task 1 (make:site) first." }
if (-not (Test-Path (Join-Path $Root 'perego-theme'))) { Fail "perego-theme/ not found - run Task 1 (make:site) first." }

# --- MySQL client on PATH ---
if (-not $MysqlBin) {
    $found = Get-ChildItem 'C:\wamp64\bin\mysql\*\bin', 'C:\wamp64\bin\mariadb\*\bin' -ErrorAction SilentlyContinue |
        Where-Object { Test-Path (Join-Path $_.FullName 'mysql.exe') } | Select-Object -First 1
    if ($found) { $MysqlBin = $found.FullName }
}
if ($MysqlBin -and (Test-Path $MysqlBin)) { $env:PATH = "$MysqlBin;$env:PATH" }

Write-Host "== Perego WordPress setup ==  repo: $Root" -ForegroundColor Cyan

# --- 1. WordPress core ---
if (-not (Test-Path (Join-Path $WpPath 'wp-load.php'))) {
    Write-Host "Downloading WordPress core into ./$WpDir ..."
    & wp core download --path="$WpPath" --skip-content --locale=en_US
    if ($LASTEXITCODE -ne 0) { Fail "wp core download failed." }
} else {
    Write-Host "WordPress core already present in ./$WpDir."
}

# --- 2. wp-config.php ---
if (-not (Test-Path (Join-Path $WpPath 'wp-config.php'))) {
    Write-Host "Creating wp-config.php ..."
    @"
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
"@ | & wp config create --path="$WpPath" --dbname="$DbName" --dbuser="$DbUser" `
        --dbpass="$DbPass" --dbhost="$DbHost" --dbprefix="$DbPrefix" --locale=en_US --extra-php
    if ($LASTEXITCODE -ne 0) { Fail "wp config create failed." }
} else {
    Write-Host "wp-config.php already present."
}

# --- 3. Database ---
& wp db create --path="$WpPath" 2>$null
if ($LASTEXITCODE -eq 0) { Write-Host "Database '$DbName' created." }
else { Write-Host "Database '$DbName' already exists (ok)." }

# --- 4. Install ---
& wp core is-installed --path="$WpPath" 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Installing WordPress ..."
    & wp core install --path="$WpPath" --url="$SiteUrl" --title="$Title" `
        --admin_user="$AdminUser" --admin_email="$AdminEmail" --admin_password="$AdminPassword" --skip-email
    if ($LASTEXITCODE -ne 0) { Fail "wp core install failed." }
} else {
    Write-Host "WordPress already installed; ensuring siteurl/home = $SiteUrl ."
    & wp option update siteurl "$SiteUrl" --path="$WpPath" | Out-Null
    & wp option update home    "$SiteUrl" --path="$WpPath" | Out-Null
}

# --- 5. Wire the framework + this repo's own plugin/theme into wp-content via junctions ---
function Set-Junction {
    param([string]$Link, [string]$Target)
    cmd /c "if exist `"$Link`" rmdir `"$Link`" 2>nul"
    cmd /c "mklink /J `"$Link`" `"$Target`"" | Out-Null
    Write-Host ("  junction  {0}  ->  {1}" -f ([System.IO.Path]::GetFileName($Link)), $Target)
}
$themesDir  = Join-Path $WpPath 'wp-content\themes'
$pluginsDir = Join-Path $WpPath 'wp-content\plugins'
New-Item -ItemType Directory -Force -Path $themesDir, $pluginsDir | Out-Null

Write-Host "Wiring CoreX framework ($FrameworkRoot) + this repo -> wp-content:"
foreach ($slug in $FoundationPlugins) {
    Set-Junction (Join-Path $pluginsDir $slug) (Join-Path $FrameworkRoot "plugins\$slug")
}
foreach ($slug in $AddonSlugs) {
    Set-Junction (Join-Path $pluginsDir $slug) (Join-Path $FrameworkRoot "addons\$slug")
}
Set-Junction (Join-Path $pluginsDir 'perego-site') (Join-Path $Root 'perego-site')
Set-Junction (Join-Path $themesDir 'perego-theme') (Join-Path $Root 'perego-theme')

# --- 6. Activate theme + plugins (WP resolves "Requires Plugins" order) ---
& wp theme activate perego-theme --path="$WpPath" | Out-Null
$allPlugins = $FoundationPlugins + $AddonSlugs + @('perego-site')
& wp plugin activate @allPlugins --path="$WpPath" | Out-Null

# --- 7. Verify ---
Write-Host "`n== Verification ==" -ForegroundColor Cyan
& wp theme list --path="$WpPath"
& wp plugin list --path="$WpPath"
Write-Host "`nSite : $SiteUrl"
Write-Host "Admin: $SiteUrl/wp-admin/  ($AdminUser / $AdminPassword - change this password)"
Write-Host "Done." -ForegroundColor Green
