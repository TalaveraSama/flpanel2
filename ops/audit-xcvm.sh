#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
XCVM_ROOT="${XCVM_ROOT:-/home/xc_vm}"
OUT="${1:-/home/xcvm_audit/work/xcvm-audit}"

[ "$(id -u)" -ne 0 ] || { echo 'Refusing to run as root.' >&2; exit 1; }
[ -d "$XCVM_ROOT" ] || { echo "$XCVM_ROOT does not exist." >&2; exit 1; }
mkdir -p "$OUT"
REPORT="$OUT/report.txt"
: > "$REPORT"
{
  echo "XC_VM read-only source audit"
  echo "Generated UTC: $(date -u +%FT%TZ)"
  echo "Host: $(hostname)"
  echo "User: $(id)"
  echo
  echo '== Runtime =='
  uname -a
  command -v php >/dev/null && php -v | head -4 || true
  [ -x "$XCVM_ROOT/bin/php/bin/php" ] && "$XCVM_ROOT/bin/php/bin/php" -v | head -4 || true
  echo
  echo '== Top-level layout =='
  find "$XCVM_ROOT" -mindepth 1 -maxdepth 1 -printf '%f\t%y\t%s bytes\n' | sort
  echo
  echo '== Module manifests =='
  find "$XCVM_ROOT/Modules" -mindepth 2 -maxdepth 2 -name module.json -print 2>/dev/null | sort | while read -r manifest; do
    echo "--- ${manifest#$XCVM_ROOT/}"
    if command -v jq >/dev/null; then
      jq '{name,hash_id,description,version,requires_core,environment,has_navbar,has_settings}' "$manifest" 2>/dev/null || echo '[invalid JSON]'
    else
      sed -n '1,80p' "$manifest"
    fi
  done
  echo
  echo '== Flussonic module files =='
  find "$XCVM_ROOT/Modules/flussonic_1f4a9" -type f -printf '%P\t%s bytes\n' 2>/dev/null | sort || true
  echo
  echo '== Public branding/media assets =='
  find "$XCVM_ROOT" -type f \( -iname '*logo*' -o -iname '*favicon*' -o -iname '*.mp4' -o -iname '*.webm' \) \
    -not -path '*/tmp/*' -not -path '*/vendor/*' -printf '%P\t%s bytes\n' 2>/dev/null | sort | head -500
  echo
  echo '== PHP syntax: Flussonic module =='
  if [ -x "$XCVM_ROOT/bin/php/bin/php" ] && [ -d "$XCVM_ROOT/Modules/flussonic_1f4a9" ]; then
    while IFS= read -r -d '' file; do "$XCVM_ROOT/bin/php/bin/php" -d opcache.enable_cli=0 -l "$file"; done \
      < <(find "$XCVM_ROOT/Modules/flussonic_1f4a9" -type f -name '*.php' -print0)
  fi
} >> "$REPORT" 2>&1

# Copy only the installed Flussonic source. Explicitly reject secret-like files.
MODULE_OUT="$OUT/flussonic_1f4a9"
rm -rf "$MODULE_OUT"
if [ -d "$XCVM_ROOT/Modules/flussonic_1f4a9" ]; then
  mkdir -p "$MODULE_OUT"
  find "$XCVM_ROOT/Modules/flussonic_1f4a9" -type f \
    ! -name '.env' ! -name '*.key' ! -name '*.pem' ! -name '*.lic' ! -name 'config.enc' \
    -exec cp --parents '{}' "$MODULE_OUT" \;
fi
chmod -R go-rwx "$OUT"
echo "$REPORT"
