#!/usr/bin/env bash
# Build the distributable packages for the WHMCS theme.
#
#   dist/hostdigi-theme.zip   -> extract inside  <whmcs>/templates/
#                                (contains a single hostdigi/ folder, the way
#                                 WHMCS themes are normally distributed)
#   dist/hostdigi_theme.php   -> upload to       <whmcs>/includes/hooks/
#
# Deliberately NOT one combined zip: extracting a zip that contains
# templates/ and includes/ into the wrong folder produces
# templates/templates/hostdigi, which WHMCS then lists as a bogus theme.
set -euo pipefail

cd "$(dirname "$0")/.."
rm -rf dist && mkdir -p dist/stage

cp -r whmcs-theme/templates/hostdigi dist/stage/
find dist/stage \( -name '.DS_Store' -o -name '._*' \) -delete

( cd dist/stage && zip -rq ../hostdigi-theme.zip hostdigi -x '__MACOSX/*' )
rm -rf dist/stage

cp whmcs-theme/includes/hooks/hostdigi_theme.php dist/

echo "built:"
ls -lh dist
