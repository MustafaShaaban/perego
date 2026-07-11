# Perego Environment Bootstrap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up an independent, working WordPress install for Perego wired to the existing CoreX framework checkout, with the CoreX-generated `perego-site` plugin + `perego-theme` theme active — a booting site, ready for the M2+ foundation/feature work.

**Architecture:** Two directories, one relationship: `C:\wamp64\www\corex` (framework source + its own dev WordPress, untouched) and `C:\wamp64\www\perego` (the new `MustafaShaaban/perego` repo: `perego-site/` + `perego-theme/` tracked at the repo root, plus a git-ignored `wp/` runtime whose `wp-content` junctions in the framework's required plugins/add-ons from `corex` and the two generated folders from its own repo root). This is the same junction pattern CoreX's own `scripts/setup-wordpress.ps1` uses on itself, applied across two directories instead of one.

**Tech Stack:** WP-CLI, WAMP (Apache 2.4 / MySQL 8.0 / PHP 8.3), PowerShell 5.1, Windows directory junctions (`mklink /J`).

This is infrastructure bootstrap, not application code — there is no unit-testable behavior yet. Each "verify" step below is a concrete command with the exact expected output, in place of a unit test, mirroring how CoreX's own setup script verifies itself (`wp theme list` / `wp plugin list`, not Pest).

This is Plan 1 of 2 for the design's **M1 Foundation** milestone (see `docs/superpowers/specs/2026-07-11-perego-corex-design.md`). Plan 2 (tokens → theme.json, header/footer parts, Polylang, the preloader block) follows once this environment exists — it needs to inspect the actual generated stub contents first, so it isn't written until this plan lands.

---

### Task 1: Generate the Perego site scaffold

The scaffolder (`Corex\Cli\Site\SiteScaffolder`) is pure — it just writes files to whatever absolute `--path` you give it. It only needs a WordPress install with `corex-core` active to be *invoked* via `wp corex make:site`; it does not need to run against Perego's own (not-yet-existent) install. Run it against the already-working `corex.local` install, targeting the Perego repo root directly.

**Files:**
- Create (by the CLI, not by hand): `C:\wamp64\www\perego\perego-site\**`, `C:\wamp64\www\perego\perego-theme\**`, `C:\wamp64\www\perego\{AGENTS.md,CLAUDE.md,README.md,PROGRESS.md,DECISIONS.md,.gitignore}`, `C:\wamp64\www\perego\{specs,docs}\.gitkeep`

- [ ] **Step 1: Confirm the CoreX dev site is currently healthy**

Run:
```bash
cd "C:/wamp64/www/corex" && wp --path=wp plugin list --status=active
```
Expected: a table including `corex-core`, `corex-blocks`, `corex-config`, `corex-forms` all with status `active`. If any required plugin is inactive, stop and investigate before continuing — do not scaffold against a broken framework install.

- [ ] **Step 2: Run the scaffolder against the Perego repo root**

Run:
```bash
cd "C:/wamp64/www/corex" && wp --path=wp corex make:site Perego --path="C:/wamp64/www/perego" --starter
```
Expected: `Success: Client site scaffolded: C:/wamp64/www/perego`, followed by a `+ <path>` line per generated file (governance docs, `perego-site/**`, `perego-theme/**`, plus the `--starter` vertical slice: `Models/Example.php`, `Repositories/ExampleRepository.php`, `Services/ExampleService.php`, `Controllers/ExampleController.php`, `Blocks/example/{block.json,index.js,style.scss}`, `Options/ExampleOptions.php`, `tests/ExampleTest.php`, `REMOVE-EXAMPLE.md`, and the starter theme asset pipeline (`package.json`, `functions.php`, `assets/src/**`).

- [ ] **Step 3: Verify the identity is distinct from Corex and PHP is valid**

Run:
```bash
cd "C:/wamp64/www/perego" && grep -r "PeregoSite" perego-site/perego-site.php perego-site/src/PeregoSiteServiceProvider.php
find perego-site -name "*.php" -exec php -l {} \;
```
Expected: the grep shows the `PeregoSite\` namespace in both files; every `php -l` line ends `No syntax errors detected`.

- [ ] **Step 4: Confirm the governance files name the right paths**

Run:
```bash
grep -l "perego-site\|perego-theme" "C:/wamp64/www/perego/AGENTS.md" "C:/wamp64/www/perego/CLAUDE.md"
```
Expected: both files listed (they reference the plugin/theme paths this scaffold just generated).

---

### Task 2: Write the Perego environment bootstrap script

Adapted from `C:\wamp64\www\corex\scripts\setup-wordpress.ps1`, but wiring a *second*, independent WordPress install to the *first* directory's framework source instead of its own, and junctioning only the recommended add-on set (not every add-on in the framework, since Perego doesn't need e.g. `corex-bookings` or `corex-careers` unless a later milestone asks for them).

**Files:**
- Create: `C:\wamp64\www\perego\scripts\setup-wordpress.ps1`

- [ ] **Step 1: Write the script**

```powershell
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
if (-not (Test-Path (Join-Path $Root 'perego-site'))) { Fail "perego-site/ not found — run Task 1 (make:site) first." }
if (-not (Test-Path (Join-Path $Root 'perego-theme'))) { Fail "perego-theme/ not found — run Task 1 (make:site) first." }

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
Write-Host "Admin: $SiteUrl/wp-admin/  ($AdminUser / $AdminPassword — change this password)"
Write-Host "Done." -ForegroundColor Green
```

- [ ] **Step 2: Commit the script**

```bash
cd "C:/wamp64/www/perego" && git add scripts/setup-wordpress.ps1 && git commit -m "$(cat <<'EOF'
Add Perego WordPress bootstrap script

Adapted from corex/scripts/setup-wordpress.ps1: wires an independent
WordPress install to the CoreX framework checkout via junctions,
rather than junctioning a repo into its own wp/.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: `1 file changed` commit succeeds.

---

### Task 3: Run the bootstrap script and verify WordPress boots

**Files:** none (execution only)

- [ ] **Step 1: Run it**

Run:
```powershell
cd C:\wamp64\www\perego
powershell -ExecutionPolicy Bypass -File .\scripts\setup-wordpress.ps1
```
Expected: ends with `Done.` in green; the `wp plugin list` table shows `corex-core`, `corex-blocks`, `corex-config`, `corex-forms`, `corex-ui`, `corex-kit-company`, `corex-media`, `perego-site` all `active`; `wp theme list` shows `perego-theme` with status `active`.

- [ ] **Step 2: Verify no PHP fatals on boot (the Environment Gate)**

Run:
```bash
wp --path="C:/wamp64/www/perego/wp" eval "echo 'boot-ok';" 2>&1
```
Expected: output ends with `boot-ok` and no `PHP Fatal error` / `PHP Warning` lines above it.

- [ ] **Step 3: Run the CoreX readiness/doctor check**

Run:
```bash
wp --path="C:/wamp64/www/perego/wp" corex doctor 2>&1
```
Expected: a green/OK readiness report. If `doctor` is not a registered subcommand in this CoreX version, run `wp --path="C:/wamp64/www/perego/wp" corex --help` instead, confirm `corex-core` reports itself booted some other documented way (e.g. `wp corex readiness`), and note the actual command in `PROGRESS.md` (Task 5) instead of `doctor`.

---

### Task 4: Register the `perego.local` vhost + hosts entry

**Files:**
- Modify: `C:\Windows\System32\drivers\etc\hosts`
- Modify: `C:\wamp64\bin\apache\apache2.4.59\conf\extra\httpd-vhosts.conf`

- [ ] **Step 1: Add the hosts entry**

Run:
```bash
printf '127.0.0.1\tperego.local\n::1\tperego.local\n' >> "/c/Windows/System32/drivers/etc/hosts"
grep -i "perego.local" "/c/Windows/System32/drivers/etc/hosts"
```
Expected: the grep shows the two new lines. If this fails with a permission error, stop and ask the user to add these two lines to the hosts file themselves (needs an elevated editor), then continue from Step 2.

- [ ] **Step 2: Add the vhost block**

Append to `C:\wamp64\bin\apache\apache2.4.59\conf\extra\httpd-vhosts.conf`, matching the existing entries' exact format (see the `corex.local` block already in that file):

```apacheconf
<VirtualHost *:80>
	ServerName perego.local
	DocumentRoot "c:/wamp64/www/perego/wp"
	<Directory  "c:/wamp64/www/perego/wp/">
		Options +Indexes +Includes +FollowSymLinks +MultiViews
		AllowOverride All
		Require local
	</Directory>
</VirtualHost>
#
```

- [ ] **Step 3: Test the Apache config before restarting**

Run:
```powershell
& "C:\wamp64\bin\apache\apache2.4.59\bin\httpd.exe" -t
```
Expected: `Syntax OK`. If it fails, fix the appended block (check for a stray/missing `#` separator matching the file's existing style) before restarting anything.

- [ ] **Step 4: Restart Apache**

Run:
```powershell
Restart-Service wampapache64
```
Expected: no error. If this fails with an access-denied error (the service may need an elevated shell), ask the user to restart Apache themselves (WAMP tray icon → Restart All Services, or `Restart-Service wampapache64` from an elevated PowerShell).

---

### Task 5: Verify the site boots over HTTP and commit

**Files:**
- Modify: `C:\wamp64\www\perego\PROGRESS.md`

- [ ] **Step 1: Verify HTTP**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://perego.local/
```
Expected: `200` (a fresh, empty-content WordPress front page is fine at this stage — no theme templates have been built yet).

- [ ] **Step 2: Verify wp-admin is reachable**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://perego.local/wp-admin/
```
Expected: `200` or a `30x` redirect to the login screen — not a `500`.

- [ ] **Step 3: Update PROGRESS.md**

Append a `## Environment bootstrap (2026-07-11)` section to `C:\wamp64\www\perego\PROGRESS.md` recording: the vhost/DB/admin credentials (flag `changeme` as dev-only, to be changed), which plugins/add-ons are wired active, and the exact `corex doctor`-equivalent command confirmed in Task 3 Step 3.

- [ ] **Step 4: Commit**

```bash
cd "C:/wamp64/www/perego" && git add -A && git status --short
```
Review the output — `wp/` must **not** appear (it's git-ignored). Then:
```bash
git commit -m "$(cat <<'EOF'
Bootstrap the independent Perego WordPress environment

WP core + DB + vhost for perego.local, CoreX foundation plugins and
the recommended add-on set (corex-ui, corex-kit-company, corex-media)
junctioned in from the framework checkout, perego-site/perego-theme
active. Verified: no PHP fatals, HTTP 200 on / and /wp-admin/.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
Expected: commit succeeds; `git log --oneline` shows this on top of the Task 1/2 commits.

---

## Next

Once this lands: write **Plan 2 (Perego Foundation Build)** — tokens → `theme.json`, the `parts/header.html` / `parts/footer.html` rebuild (skip link, sticky nav, mobile menu, language toggle), Polylang install/config, and the `perego/preloader` block — inspecting the actual files this plan generated/activated rather than guessing their contents. Do not push `origin` yet; confirm with the user before the first push.
