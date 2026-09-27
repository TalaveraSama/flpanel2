#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
VERSION="$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["version"])' "$ROOT/src/Modules/flussonic_1f4a9/module.json")"
[ -n "$VERSION" ] || { echo 'Could not read module version.' >&2; exit 1; }
OUT="${1:-$ROOT/artifacts}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
DIR="$WORK/xcvm-flussonic-$VERSION"
mkdir -p "$DIR"
cp -a "$ROOT/src/Modules/flussonic_1f4a9" "$DIR/"
cp "$ROOT/packaging/flussonic/install.sh" "$ROOT/packaging/flussonic/uninstall.sh" "$ROOT/packaging/flussonic/README.md" "$DIR/"
mkdir -p "$OUT"
(cd "$WORK" && zip -qr "$OUT/xcvm-flussonic-$VERSION.zip" "xcvm-flussonic-$VERSION")
(cd "$OUT" && sha256sum "xcvm-flussonic-$VERSION.zip" > "xcvm-flussonic-$VERSION.zip.sha256")
printf '%s\n' "$OUT/xcvm-flussonic-$VERSION.zip"
