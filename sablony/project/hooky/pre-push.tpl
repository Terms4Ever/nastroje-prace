#!/bin/sh
php_path=$(git config --local --get nastrojePrace.php) || exit 1
exec "$php_path" .nastroje-prace/scripts/pre-push.php
