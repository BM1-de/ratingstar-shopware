#!/usr/bin/env bash
#
# Builds the installable plugin ZIP: dist/RatingStarSeal-<version>.zip
#
#   bin/build-zip.sh            build the ZIP
#   bin/build-zip.sh --force    build although the administration sources are
#                               newer than the built assets
#
# The ZIP holds the plugin as Shopware expects it (one top-level folder
# "RatingStarSeal") with the built administration assets, and without the
# repository, this script or other development files. It is put together
# from the working tree: every file git tracks or would add.
#
# Build the administration assets first, inside a Shopware 6.7 installation
# that has the plugin installed and active:
#
#   SHOPWARE_ADMIN_BUILD_ONLY_EXTENSIONS=1 bin/build-administration.sh
#
# Needs: bash, git, zip.

set -euo pipefail

NAME="RatingStarSeal"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ASSETS="src/Resources/public/administration"
ADMIN_SRC="src/Resources/app/administration/src"

cd "$ROOT"

for tool in git zip; do
    command -v "$tool" > /dev/null || { echo "Error: $tool is required." >&2; exit 1; }
done

VERSION="$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([^"]*\)".*/\1/p' composer.json | head -n 1)"
[[ -n "$VERSION" ]] || { echo "Error: no version found in composer.json." >&2; exit 1; }

for changelog in CHANGELOG.md CHANGELOG_de-DE.md; do
    grep -qx "# $VERSION" "$changelog" || { echo "Error: $changelog has no entry \"# $VERSION\"." >&2; exit 1; }
done

if [[ ! -f "$ASSETS/.vite/entrypoints.json" ]]; then
    echo "Error: the built administration assets are missing ($ASSETS)." >&2
    echo "Build them in a Shopware installation: SHOPWARE_ADMIN_BUILD_ONLY_EXTENSIONS=1 bin/build-administration.sh" >&2
    exit 1
fi

# Snippets are read from the JSON files at runtime and need no rebuild.
STALE="$(find "$ADMIN_SRC" -type f -not -path '*/snippet/*' -newer "$ASSETS/.vite/entrypoints.json" | head -n 1)"
if [[ -n "$STALE" && "${1:-}" != "--force" ]]; then
    echo "Error: administration sources are newer than the built assets (e.g. $STALE)." >&2
    echo "Rebuild the assets, or pass --force to package them as they are." >&2
    exit 1
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$STAGE/$NAME" dist

while IFS= read -r -d '' file; do
    # Listed by git but deleted in the working tree.
    [[ -e "$file" ]] || continue

    case "$file" in
        bin/* | dist/* | tests/* | .gitignore | .gitattributes | *.zip | .DS_Store | */.DS_Store)
            continue
            ;;
    esac

    mkdir -p "$STAGE/$NAME/$(dirname "$file")"
    cp -p "$file" "$STAGE/$NAME/$file"
done < <(git ls-files -z --cached --others --exclude-standard)

TARGET="$ROOT/dist/$NAME-$VERSION.zip"
rm -f "$TARGET"
(cd "$STAGE" && zip -q -r -X "$TARGET" "$NAME")

echo "Built dist/$NAME-$VERSION.zip ($(find "$STAGE/$NAME" -type f | wc -l | tr -d ' ') files)"
