#!/usr/bin/env bash
set -Eeuo pipefail
MODULE_SLUG="flussonic_1f4a9"
XCVM_HOME="${XCVM_HOME:-/home/xc_vm}"
MODULES_DIR="${XCVM_MODULES_DIR:-${XCVM_HOME}/Modules}"
TARGET_DIR="${MODULES_DIR}/${MODULE_SLUG}"
PHP_BIN="${XCVM_PHP_BIN:-${XCVM_HOME}/bin/php/bin/php}"

[ "${EUID}" -eq 0 ] || { echo 'ERROR: run as root (sudo ./uninstall.sh --yes)' >&2; exit 1; }
[ "${1:-}" = "--yes" ] || { echo "Usage: sudo $0 --yes" >&2; exit 2; }
if [ ! -e "${TARGET_DIR}" ]; then
  printf 'Flussonic module is not installed at %s\n' "${TARGET_DIR}"
  exit 0
fi
[ -f "${TARGET_DIR}/module.json" ] || { echo "ERROR: refusing to remove a directory without module.json" >&2; exit 1; }
[ -x "${PHP_BIN}" ] || { echo "ERROR: XC_VM PHP was not found at ${PHP_BIN}" >&2; exit 1; }
name="$("${PHP_BIN}" -r '$m=json_decode(file_get_contents($argv[1]),true); echo $m["name"]??"";' "${TARGET_DIR}/module.json")"
[ "${name}" = "flussonic" ] || { echo "ERROR: target is not the Flussonic module" >&2; exit 1; }
archive="${TARGET_DIR}.removed.$(date -u +%Y%m%dT%H%M%SZ)"
mv -- "${TARGET_DIR}" "${archive}"
printf 'Flussonic module uninstalled. Files retained at %s\n' "${archive}"
printf 'Delete that backup manually after confirming XC_VM works correctly.\n'
