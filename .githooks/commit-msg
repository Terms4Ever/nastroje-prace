#!/bin/sh
php_path=$(git config --local --get nastrojePrace.php) || exit 1
exec "$php_path" prace.php commit-check "$1"
