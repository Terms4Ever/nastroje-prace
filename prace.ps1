param([Parameter(ValueFromRemainingArguments = $true)][string[]]$Arguments)
$ErrorActionPreference = 'Stop'
$env:PYTHONUTF8 = '1'
$pythonPath = $env:NASTROJE_PRACE_PYTHON
if (-not $pythonPath) {
    $pythonPath = & git -C $PSScriptRoot config --local --get nastrojePrace.python 2>$null
}
if (-not $pythonPath) {
    $candidate = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe'
    if (Test-Path -LiteralPath $candidate) { $pythonPath = $candidate }
}
if (-not $pythonPath) { $pythonPath = 'python' }
Push-Location -LiteralPath $PSScriptRoot
try {
    & $pythonPath -m nastroje_prace @Arguments
    $resultCode = $LASTEXITCODE
} finally { Pop-Location }
exit $resultCode
