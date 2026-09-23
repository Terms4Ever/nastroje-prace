name: Kontroly
on:
  push:
    branches: [main]
  workflow_dispatch:
    inputs:
      base:
        description: 'Vychozi commit rozsahu, ROOT pouze pro prvni commit'
        required: true
        type: string
permissions:
  contents: read
  issues: read
jobs:
  linux:
    name: Linux
    runs-on: ubuntu-24.04
    timeout-minutes: 20
    env:
      GITHUB_TOKEN: ${{ github.token }}
    steps:
      - uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
        with:
          fetch-depth: 0
          ref: ${{ github.sha }}
          persist-credentials: false
      - name: Prostredi kontrol
        run: |
          if ! php -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("mbstring") && extension_loaded("curl") && extension_loaded("zip") && extension_loaded("SimpleXML") ? 0 : 1);'; then
            sudo apt-get update
            sudo apt-get install -y php-cli php-mbstring php-curl php-zip php-xml
          fi
          php prace.php bootstrap
      # Zde nastav skutečné prostředí aplikace a instalaci jejích závislostí.
      # PHP připravuje pouze kontroly. Testy aplikace určuje .prace.json.
      - name: Dokumentace, commity, issues a testy aplikace
        run: php .nastroje-prace/scripts/ci.php
  windows:
    name: Windows
    runs-on: windows-latest
    timeout-minutes: 20
    env:
      GITHUB_TOKEN: ${{ github.token }}
    steps:
      - uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
        with:
          fetch-depth: 0
          ref: ${{ github.sha }}
          persist-credentials: false
      - name: Prostredi kontrol
        run: |
          .\prace.ps1 bootstrap --php-windows
          .\prace.ps1 install-hooks
      # Připrav zde stejné aplikační závislosti jako v Linux úloze.
      - name: Dokumentace, commity, issues a testy aplikace
        run: .\.nastroje-prace\.cache\php\php.exe .nastroje-prace/scripts/ci.php
  povinne:
    name: Povinne kontroly
    runs-on: ubuntu-24.04
    needs: [linux, windows]
    if: always()
    steps:
      - name: Obe platformy musi projit
        env:
          LINUX_RESULT: ${{ needs.linux.result }}
          WINDOWS_RESULT: ${{ needs.windows.result }}
        run: test "$LINUX_RESULT" = success && test "$WINDOWS_RESULT" = success
