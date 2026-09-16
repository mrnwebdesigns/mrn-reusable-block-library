#!/usr/bin/env bash
set -euo pipefail

plugin_dir="$(cd "$(dirname "$0")/.." && pwd)"
parent_dir="$(dirname "$plugin_dir")"
slug="$(basename "$plugin_dir")"
version="$(sed -n 's/^ \* Version: //p' "$plugin_dir/mrn-reusable-block-library.php" | head -1)"
output="$plugin_dir/dist/mrn-reusable-block-library-$version.zip"

if [[ -z "$version" ]]; then
	echo "Could not read the plugin version." >&2
	exit 1
fi

mkdir -p "$plugin_dir/dist"
rm -f "$output"

staging_root="$(mktemp -d)"
trap 'rm -rf "$staging_root"' EXIT
staging_dir="$staging_root/mrn-reusable-block-library"
mkdir -p "$staging_dir"

rsync -a "$plugin_dir/" "$staging_dir/" \
	--exclude '.git' \
	--exclude '.gitignore' \
	--exclude '.mrn-qa.env' \
	--exclude 'AGENTS.md' \
	--exclude 'STACK_BASELINE.md' \
	--exclude 'bin' \
	--exclude 'dist' \
	--exclude 'phpcs.xml.dist' \
	--exclude 'stack.lock' \
	--exclude 'tests'

(
	cd "$staging_root"
	find mrn-reusable-block-library -exec touch -t 202001010000 {} +
	zip -X -q -r "$output" mrn-reusable-block-library
)

shasum -a 256 "$output"
