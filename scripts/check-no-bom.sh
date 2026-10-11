#!/usr/bin/env bash
set -euo pipefail

# Fails if any tracked PHP file starts with a UTF-8 BOM (EF BB BF).
# Run from repository root.

if ! command -v git >/dev/null 2>&1; then
  echo "git is required" >&2
  exit 2
fi

bad_files=()
while IFS= read -r f; do
  [[ -f "$f" ]] || continue
  sig="$(head -c 3 "$f" | od -An -t x1 | tr -d ' \n')"
  if [[ "$sig" == "efbbbf" ]]; then
    bad_files+=("$f")
  fi
done < <(git ls-files '*.php')

if (( ${#bad_files[@]} > 0 )); then
  echo "UTF-8 BOM detected in PHP files:" >&2
  printf ' - %s\n' "${bad_files[@]}" >&2
  echo "\nFix quickly:" >&2
  echo "  sed -i '1s/^\\xEF\\xBB\\xBF//' <file.php>" >&2
  exit 1
fi

echo "OK: no UTF-8 BOM found in tracked PHP files."
