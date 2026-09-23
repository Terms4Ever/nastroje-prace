name: Tvar issues
on:
  issues:
    types: [opened, edited, deleted, closed, reopened, assigned, unassigned, labeled, unlabeled]
  issue_comment:
    types: [created, edited, deleted]
  workflow_dispatch:
permissions:
  contents: read
  issues: read
jobs:
  issues:
    name: Kontrola issues a komentaru
    runs-on: ubuntu-24.04
    timeout-minutes: 5
    steps:
      - uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
        with:
          ref: main
          fetch-depth: 0
          persist-credentials: false
      - name: Prostredi
        run: |
          if ! php -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("mbstring") && extension_loaded("curl") && extension_loaded("zip") && extension_loaded("SimpleXML") ? 0 : 1);'; then
            sudo apt-get update
            sudo apt-get install -y php-cli php-mbstring php-curl php-zip php-xml
          fi
          php prace.php bootstrap
      - name: Zkontrolovat aktualni obsah
        env:
          GITHUB_TOKEN: ${{ github.token }}
        run: |
          php prace.php metadata-check
          php prace.php issues-check
