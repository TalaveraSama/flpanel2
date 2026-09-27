#!/usr/bin/env bash
set -Eeuo pipefail

MODULE_SLUG="flussonic_1f4a9"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="${SCRIPT_DIR}/${MODULE_SLUG}"
XCVM_HOME="${XCVM_HOME:-/home/xc_vm}"
MODULES_DIR="${XCVM_MODULES_DIR:-${XCVM_HOME}/Modules}"
TARGET_DIR="${MODULES_DIR}/${MODULE_SLUG}"
PHP_BIN="${XCVM_PHP_BIN:-${XCVM_HOME}/bin/php/bin/php}"
OWNER="${XCVM_OWNER:-xc_vm:xc_vm}"

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
[ "${EUID}" -eq 0 ] || fail "run this installer as root (sudo ./install.sh)"
[ -d "${SOURCE_DIR}" ] || fail "${MODULE_SLUG} is missing next to install.sh"
[ -f "${SOURCE_DIR}/module.json" ] || fail "module.json is missing"
[ -x "${PHP_BIN}" ] || fail "XC_VM PHP was not found at ${PHP_BIN}; set XCVM_PHP_BIN"

"${PHP_BIN}" -r '
$p=json_decode(file_get_contents($argv[1]), true);
if (!is_array($p) || ($p["name"] ?? "") !== "flussonic" || empty($p["version"])) { fwrite(STDERR, "Invalid Flussonic module manifest.\n"); exit(1); }
printf("Installing Flussonic module v%s...\n", $p["version"]);
' "${SOURCE_DIR}/module.json"

while IFS= read -r -d '' file; do
  "${PHP_BIN}" -d opcache.enable_cli=0 -l "${file}" >/dev/null
done < <(find "${SOURCE_DIR}" -type f -name '*.php' -print0)

install -d -m 0755 "${MODULES_DIR}"
staging="$(mktemp -d "${MODULES_DIR}/.${MODULE_SLUG}.install.XXXXXX")"
backup=""
cleanup() { [ -d "${staging:-}" ] && rm -rf -- "${staging}"; }
trap cleanup EXIT
cp -a "${SOURCE_DIR}/." "${staging}/"
find "${staging}" -type d -exec chmod 0755 {} +
find "${staging}" -type f -exec chmod 0644 {} +
find "${staging}/bin" -type f -exec chmod 0755 {} + 2>/dev/null || true
chown -R "${OWNER}" "${staging}"

if [ -e "${TARGET_DIR}" ]; then
  backup="${TARGET_DIR}.backup.$(date -u +%Y%m%dT%H%M%SZ)"
  mv -- "${TARGET_DIR}" "${backup}"
  printf 'Previous installation backed up to %s\n' "${backup}"
fi
if ! mv -- "${staging}" "${TARGET_DIR}"; then
  [ -n "${backup}" ] && mv -- "${backup}" "${TARGET_DIR}"
  fail "could not activate the module"
fi
staging=""

printf 'Installed at %s\n' "${TARGET_DIR}"
printf 'API test: sudo -u xc_vm %q/bin/flussonic-api.php info\n' "${TARGET_DIR}"
printf 'Set FLUSSONIC_URL, FLUSSONIC_USER and FLUSSONIC_PASSWORD before the API test.\n'
