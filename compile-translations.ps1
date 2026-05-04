<#
.SYNOPSIS
    Compile WordPress plugin translation files (.po -> .mo) and optionally regenerate the .pot template.

.DESCRIPTION
    Generic script — works for any WordPress plugin. Drop it in the plugin root and run it.
    1. Refreshes PATH so msgfmt / wp CLI are always found (even in a fresh terminal).
    2. Optionally regenerates <text-domain>.pot via WP-CLI wp i18n make-pot.
    3. Compiles every .po file in translations/ to a matching .mo file using msgfmt.
    4. Reports successes and failures.

.PARAMETER MakePot
    When specified, regenerates the master .pot file from PHP source before compiling.

.PARAMETER SitePath
    WordPress root path used by WP-CLI when -MakePot is set.
    Defaults to three levels above this script (plugin -> wp-content -> public).

.EXAMPLE
    # Just compile all .po files to .mo
    .\compile-translations.ps1

    # Regenerate .pot first, then compile
    .\compile-translations.ps1 -MakePot

    # Regenerate .pot with explicit site path, then compile
    .\compile-translations.ps1 -MakePot -SitePath 'C:\Users\d\Local Sites\delta\app\public'
#>

param(
    [switch]$MakePot,
    [string]$SitePath = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..') -ErrorAction SilentlyContinue)
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ─── Refresh PATH so tools installed after this shell opened are found ──────
$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' +
            [System.Environment]::GetEnvironmentVariable('Path','User')

# ─── Paths ───────────────────────────────────────────────────────────────────
$pluginRoot      = $PSScriptRoot
$translationsDir = Join-Path $pluginRoot 'translations'

# Derive text domain from the plugin folder name (same convention WordPress uses).
$textDomain = Split-Path $pluginRoot -Leaf

# Use existing .pot if present; otherwise name it after the text domain.
$existingPot = Get-ChildItem -Path $translationsDir -Filter '*.pot' -ErrorAction SilentlyContinue | Select-Object -First 1
$potFile = if ($existingPot) { $existingPot.FullName } else { Join-Path $translationsDir "$textDomain.pot" }

Write-Host ""
Write-Host "=== WordPress Translation Compiler ===" -ForegroundColor Cyan
Write-Host "Plugin     : $textDomain"
Write-Host "Plugin root: $pluginRoot"
Write-Host "POT file   : $potFile"
Write-Host "Translations: $translationsDir"

# ─── Guard: msgfmt must be available ─────────────────────────────────────────
if (-not (Get-Command 'msgfmt' -ErrorAction SilentlyContinue)) {
    Write-Error "msgfmt not found on PATH.`nInstall GNU gettext:  winget install --id mlocati.GetText`nThen open a new terminal."
    exit 1
}

# ─── Step 1: Regenerate .pot (optional) ──────────────────────────────────────
if ($MakePot) {
    Write-Host ""
    Write-Host "-- Regenerating $potFile --" -ForegroundColor Yellow

    if (-not (Get-Command 'wp' -ErrorAction SilentlyContinue)) {
        Write-Warning "wp CLI not found. Skipping .pot regeneration."
    } else {
        $wpArgs = @('i18n', 'make-pot', $pluginRoot, $potFile)
        if ($SitePath) { $wpArgs += "--path=$SitePath" }

        Write-Host "Running: wp $($wpArgs -join ' ')"
        # wp i18n make-pot exits 1 for audit warnings even when the .pot is written
        # successfully. Capture stderr+stdout and check for the success message instead
        # of relying on exit code. Temporarily allow non-zero exits.
        $prevEap = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $potOutput = & wp @wpArgs 2>&1
        $ErrorActionPreference = $prevEap
        $succeeded = $potOutput | Where-Object { $_ -match 'POT file successfully generated' }
        $potOutput | Where-Object { $_ -match 'Warning:' } | ForEach-Object { Write-Warning ($_ -replace '^.*Warning:\s*','') }
        if ($succeeded) {
            Write-Host "  .pot regenerated OK" -ForegroundColor Green
        } else {
            Write-Warning "wp i18n make-pot did not report success. Full output:"
            $potOutput | ForEach-Object { Write-Host "    $_" }
        }
    }
}

# ─── Step 2: Compile each .po to .mo ─────────────────────────────────────────
Write-Host ""
Write-Host "-- Compiling .po -> .mo --" -ForegroundColor Yellow

$poFiles = Get-ChildItem -Path $translationsDir -Filter '*.po' -ErrorAction SilentlyContinue

# Fallback: some plugins keep .po files in the plugin root
if ($poFiles.Count -eq 0) {
    $poFiles = Get-ChildItem -Path $pluginRoot -MaxDepth 1 -Filter '*.po' -ErrorAction SilentlyContinue
    if ($poFiles.Count -gt 0) {
        Write-Warning "No .po files in translations/ - found $($poFiles.Count) in plugin root instead."
    }
}

if ($poFiles.Count -eq 0) {
    Write-Warning "No .po files found in $translationsDir"
    exit 0
}

$ok  = 0
$fail = 0

foreach ($po in $poFiles) {
    $mo = [System.IO.Path]::ChangeExtension($po.FullName, '.mo')
    Write-Host "  $($po.Name) -> $([System.IO.Path]::GetFileName($mo))" -NoNewline

    & msgfmt -o $mo $po.FullName 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK" -ForegroundColor Green
        $ok++
    } else {
        Write-Host "  FAILED" -ForegroundColor Red
        # Run again to surface the error message
        & msgfmt -o $mo $po.FullName
        $fail++
    }
}

# ─── Summary ─────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "=== Done: $ok compiled, $fail failed ===" -ForegroundColor Cyan

if ($fail -gt 0) { exit 1 }
