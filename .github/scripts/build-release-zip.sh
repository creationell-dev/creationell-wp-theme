#!/usr/bin/env bash
#
# Packs a staging tree into creationell-wp-theme.zip with one root folder.
#
# Usage: bash bin/release/build-release-zip.sh <staging> <output-dir> [--excludes=<file>] [--work=<dir>]
# Packs only the tree of a successful staging run: build-public-staging.sh
# writes the marker .staging-complete last, and a copy of the tree keeps it.
# The tree and the marker must be writable by the caller: a successful run
# leaves both writable, while a failed run that could not withdraw the marker
# (target and its parent folder not writable) leaves a tree that is not.
# <staging> may be a symlink to the tree; the checks run on the real folder.
#   --excludes=<file>  paths left out of the ZIP, one per line, each starting with "/"
#                      (relative to the staging root); default: zip-excludes.txt in
#                      bin/release/ of the repository that holds this script
#   --work=<dir>       folder for the temporary copy (default: build/ of that repository);
#                      the copy is removed after the run
# Writes <output-dir>/creationell-wp-theme.zip and creationell-wp-theme.zip.sha256.
# Output and work folders inside the staging tree stay out of the ZIP.
#
# The ZIP is reproducible: same files and SOURCE_DATE_EPOCH give the same checksum.
# Before packing, all modes become u=rwX,go=rX and all times SOURCE_DATE_EPOCH
# (default 315532800 = 1980-01-01 with a notice); files are added in byte order
# without folder entries and without extra fields, times in UTC.
# Exit codes: 0 ok, 1 symlink in the staging tree, 2 usage error (also an empty
# argument), missing folder, tree without the marker or not writable by the
# caller, invalid SOURCE_DATE_EPOCH, missing tool (rsync, zip, sha256sum) or any
# other failing command (an error of the environment, never a finding).
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
excludes="$root/bin/release/zip-excludes.txt"
work_parent="$root/build"
marker=".staging-complete"
positional=()

fail() {
	echo "::error::$1" >&2
	exit 2
}

# Any failing command without its own handling is an error of the environment:
# exit 2, so that exit 1 keeps meaning "symlink in the staging tree".
trap 'fail "Command failed with exit $? in line ${LINENO}: ${BASH_COMMAND}"' ERR

usage_error() {
	echo "::error::$1" >&2
	echo "Usage: bash bin/release/build-release-zip.sh <staging> <output-dir> [--excludes=<file>] [--work=<dir>]" >&2
	exit 2
}

for arg in "$@"; do
	case "$arg" in
		--excludes=*) excludes="${arg#--excludes=}" ;;
		--work=*) work_parent="${arg#--work=}" ;;
		-*) usage_error "Unknown option: ${arg}" ;;
		*) positional+=("$arg") ;;
	esac
done

if [ "${#positional[@]}" -ne 2 ] || [ -z "${positional[0]}" ] || [ -z "${positional[1]}" ]; then
	echo "Usage: bash bin/release/build-release-zip.sh <staging> <output-dir> [--excludes=<file>] [--work=<dir>]" >&2
	exit 2
fi
output="${positional[1]}"

# The real folder: find does not descend into a start path that is a symlink,
# while rsync would copy the tree behind it with every link inside.
[ -d "${positional[0]}" ] || fail "Staging folder not found: ${positional[0]}"
staging="$(realpath -e -- "${positional[0]}")" || fail "Cannot resolve the staging folder ${positional[0]}."
[ -f "$staging/$marker" ] ||
	fail "Not the tree of a successful staging run (${marker} missing): ${staging}. Run bin/release/build-public-staging.sh first."
{ [ -w "$staging" ] && [ -w "$staging/$marker" ]; } ||
	fail "Not the tree of a successful staging run (not writable by the caller): ${staging}. Run bin/release/build-public-staging.sh again."
[ -r "$excludes" ] || fail "ZIP excludes not readable: ${excludes}"

for tool in rsync zip sha256sum; do
	command -v "$tool" >/dev/null 2>&1 || fail "${tool} is not installed."
done

epoch="${SOURCE_DATE_EPOCH:-}"
if [ -z "$epoch" ]; then
	epoch=315532800
	echo "::notice::SOURCE_DATE_EPOCH not set, using 315532800 (1980-01-01)." >&2
fi
case "$epoch" in
	'' | *[!0-9]*) epoch_ok=0 ;;
	*) epoch_ok=1 ;;
esac
if [ "$epoch_ok" -ne 1 ] || [ "${#epoch}" -gt 12 ] || [ "$epoch" -lt 315532800 ]; then
	echo "::error::SOURCE_DATE_EPOCH must be a Unix time from 315532800 on (1980-01-01, the first ZIP date): ${epoch}" >&2
	exit 2
fi

links="$(command find "$staging" -type l -printf '%P\n' | LC_ALL=C sort)" || fail "Cannot list the files of ${staging}."
if [ -n "$links" ]; then
	echo "::error::Symlinks are not allowed in a release ZIP:" >&2
	printf '  %s\n' "$links" >&2
	exit 1
fi

mkdir -p -- "$output" "$work_parent" || fail "Cannot create ${output}."
output="$(cd "$output" && pwd -P)" || fail "Cannot open ${output}."
work_parent="$(cd "$work_parent" && pwd -P)" || fail "Cannot open ${work_parent}."
[ "$output" != "$staging" ] || usage_error "The output folder must not be the staging tree: ${staging}"
[ "$work_parent" != "$staging" ] || usage_error "The work folder must not be the staging tree: ${staging}"
work="$(mktemp -d "$work_parent/release-zip.XXXXXX")" || fail "Cannot create a work folder in ${work_parent}."
# rsync -a keeps read-only folders of the tree; they must not keep the work
# folder from being removed.
trap 'chmod -R u+w -- "$work" 2>/dev/null || true; rm -rf -- "$work" || echo "::warning::Cannot remove the work folder ${work}." >&2' EXIT

# Output and work folders inside the staging tree (the public checkout builds
# into itself) are not part of the package.
inner_excludes=()
for inner in "$output" "$work"; do
	case "$inner/" in
		"$staging"/*) inner_excludes+=("--exclude=/${inner#"$staging"/}") ;;
	esac
done

rsync -a --exclude-from="$excludes" --exclude="/${marker}" "${inner_excludes[@]}" "$staging/" "$work/creationell-wp-theme/" ||
	fail "Cannot copy ${staging} to the work folder."
chmod -R u=rwX,go=rX "$work/creationell-wp-theme"
command find "$work/creationell-wp-theme" -exec touch -h -d "@${epoch}" {} +

rm -f "$output/creationell-wp-theme.zip" "$output/creationell-wp-theme.zip.sha256" ||
	fail "Cannot remove the old ZIP in ${output}."
files="$(cd "$work" && command find creationell-wp-theme -type f -print | LC_ALL=C sort)"
[ -n "$files" ] || fail "No files to pack in ${staging}."
(cd "$work" && printf '%s\n' "$files" | TZ=UTC zip -X -D -q -@ "$output/creationell-wp-theme.zip") ||
	fail "Cannot write ${output}/creationell-wp-theme.zip."
(cd "$output" && sha256sum creationell-wp-theme.zip >creationell-wp-theme.zip.sha256) ||
	fail "Cannot write ${output}/creationell-wp-theme.zip.sha256."
echo "ZIP OK: ${output}/creationell-wp-theme.zip"
