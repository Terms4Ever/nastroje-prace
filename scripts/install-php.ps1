$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$runtimeFolder = Join-Path $projectRoot '.cache\php'
$runtimeExe = Join-Path $runtimeFolder 'php.exe'
$lock = Get-Content -LiteralPath (Join-Path $projectRoot 'upstream.lock.json') -Raw | ConvertFrom-Json
if (-not (Test-Path -LiteralPath $runtimeExe)) {
    $cacheFolder = Join-Path $projectRoot '.cache'
    New-Item -ItemType Directory -Force -Path $cacheFolder | Out-Null
    $archivePath = Join-Path $cacheFolder 'php-download.zip'
    Invoke-WebRequest -Uri $lock.php_windows_url -OutFile $archivePath -UseBasicParsing
    if ((Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant() -ne $lock.php_windows_sha256) {
        throw 'Stažené PHP nemá očekávaný SHA-256.'
    }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [IO.Compression.ZipFile]::OpenRead($archivePath)
    try {
        $prefix = [IO.Path]::GetFullPath($runtimeFolder) + [IO.Path]::DirectorySeparatorChar
        foreach ($entry in $archive.Entries) {
            $destination = [IO.Path]::GetFullPath((Join-Path $runtimeFolder $entry.FullName))
            if (-not $destination.StartsWith($prefix, [StringComparison]::OrdinalIgnoreCase)) {
                throw 'Archiv PHP obsahuje cestu mimo cílovou složku.'
            }
        }
    } finally { $archive.Dispose() }
    Expand-Archive -LiteralPath $archivePath -DestinationPath $runtimeFolder
    Remove-Item -LiteralPath $archivePath
}
# Mění se pouze konfigurace vyhrazeného runtime tohoto repozitáře.
$extensionFolder = (Join-Path $runtimeFolder 'ext').Replace('\', '/')
$ini = "extension_dir=`"$extensionFolder`"`nextension=mbstring`nextension=curl`nextension=zip`n"
[IO.File]::WriteAllText((Join-Path $runtimeFolder 'php.ini'), $ini, [Text.UTF8Encoding]::new($false))
