$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$tracked = git ls-files '*.php'
$bad = @()

foreach ($f in $tracked) {
    if (-not (Test-Path $f)) { continue }
    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $f))
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191) {
        $bad += $f
    }
}

if ($bad.Count -gt 0) {
    Write-Error ("UTF-8 BOM detected in PHP files:`n - " + ($bad -join "`n - "))
    exit 1
}

Write-Output 'OK: no UTF-8 BOM found in tracked PHP files.'
