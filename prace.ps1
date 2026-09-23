param([Parameter(ValueFromRemainingArguments = $true)][string[]]$Arguments)
$ErrorActionPreference = 'Stop'
if ($Arguments -contains '--php-windows') {
    if ($Arguments -notcontains 'bootstrap') { throw '--php-windows patří k příkazu bootstrap.' }
    & (Join-Path $PSScriptRoot 'scripts\install-php.ps1')
}
$phpPath = $env:NASTROJE_PHP
if (-not $phpPath) {
    $localRuntime = Join-Path $PSScriptRoot '.cache\php\php.exe'
    if (Test-Path -LiteralPath $localRuntime) { $phpPath = $localRuntime }
}
if (-not $phpPath) {
    $phpPath = & git -C $PSScriptRoot config --local --get nastrojePrace.php 2>$null
}
if (-not $phpPath) { $phpPath = 'php' }
Push-Location -LiteralPath $PSScriptRoot
try {
    & $phpPath (Join-Path $PSScriptRoot 'prace.php') @Arguments
    $resultCode = $LASTEXITCODE
} finally { Pop-Location }
exit $resultCode
