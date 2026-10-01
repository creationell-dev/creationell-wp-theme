#!/usr/bin/env bash
#
# Builds the public release of the theme: release check, reproducible ZIP with
# its checksum file, manifest and details page.
#
# Usage: bash build-public-release.sh --tag=<tag> --out=<dir> [--source=<dir>] [--previous=<file|https-url|none>]
#   --tag=<tag>        release tag vX.Y.Z
#   --out=<dir>        output folder; each run replaces <out>/assets (ZIP and
#                      .sha256) and <out>/pages (manifest and index.html)
#   --source=<dir>     theme tree to release (default: the current folder)
#   --previous=<src>   published manifest for the version and line rule: JSON
#                      file, https URL (404 means first publication) or "none"
# The public repository runs this script from .github/scripts/, the development
# repository from bin/release/. build-manifest.php and build-release-zip.sh lie
# next to it, zip-excludes.txt next to it or one folder up (.github/).
# SOURCE_DATE_EPOCH: commit time of HEAD when the source is in a Git work tree,
# else the environment value, else 315532800 (1980-01-01) with a notice.
# An output folder inside the source may only hold assets/, pages/ and .work/ and
# no versioned file (checked before anything is removed); the ZIP leaves it out.
# Exit codes: 0 ok, 1 finding (release check, symlink in the tree, checksum or
# manifest size), 2 usage, missing tool, read or write error.
set -euo pipefail

usage="Usage: bash build-public-release.sh --tag=<tag> --out=<dir> [--source=<dir>] [--previous=<file|https-url|none>]"

fail() {
	echo "::error::$1" >&2
	exit 2
}

for tool in php rsync zip sha256sum; do
	command -v "$tool" >/dev/null 2>&1 || fail "${tool} is not installed."
done

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
tag=""
out=""
source_dir="."
previous=""
previous_set=0
for arg in "$@"; do
	case "$arg" in
		--tag=*) tag="${arg#--tag=}" ;;
		--out=*) out="${arg#--out=}" ;;
		--source=*) source_dir="${arg#--source=}" ;;
		--previous=*)
			previous="${arg#--previous=}"
			previous_set=1
			;;
		*)
			echo "$usage" >&2
			fail "Unknown argument: ${arg}"
			;;
	esac
done
if [ -z "$tag" ] || [ -z "$out" ]; then
	echo "$usage" >&2
	fail "--tag and --out are required."
fi
[ -d "$source_dir" ] || fail "Source folder not found: ${source_dir}"
source_abs="$(cd "$source_dir" && pwd -P)"

manifest_script="$script_dir/build-manifest.php"
zip_script="$script_dir/build-release-zip.sh"
[ -r "$manifest_script" ] || fail "build-manifest.php not found next to the script: ${script_dir}"
[ -r "$zip_script" ] || fail "build-release-zip.sh not found next to the script: ${script_dir}"
if [ -r "$script_dir/zip-excludes.txt" ]; then
	excludes="$script_dir/zip-excludes.txt"
elif [ -r "$script_dir/../zip-excludes.txt" ]; then
	excludes="$(cd "$script_dir/.." && pwd -P)/zip-excludes.txt"
else
	fail "ZIP excludes not found next to the script or one folder up: ${script_dir}"
fi

mkdir -p -- "$out" || fail "Cannot create the output folder ${out}."
out_abs="$(cd "$out" && pwd -P)"
[ "$out_abs" != / ] || fail "The output folder must not be the source folder or contain it: ${out_abs}"
case "$source_abs/" in
	"$out_abs"/*) fail "The output folder must not be the source folder or contain it: ${out_abs}" ;;
esac
# An output folder inside the source is checked before anything in it is removed:
# only assets/, pages/ and .work/ of an earlier run, and no versioned file.
case "$out_abs/" in
	"$source_abs"/*)
		others="$(command find "$out_abs" -mindepth 1 -maxdepth 1 ! -name assets ! -name pages ! -name .work -printf '%f\n')"
		[ -z "$others" ] ||
			fail "The output folder lies inside the source and holds other entries than assets/, pages/ and .work/: ${out_abs}"
		if command -v git >/dev/null 2>&1 && git -C "$source_abs" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
			# No pipe into head: an early close would end git with SIGPIPE and open the check.
			versioned="$(git -C "$out_abs" ls-files -- .)" ||
				fail "Cannot list the versioned files in ${out_abs}."
			[ -z "$versioned" ] ||
				fail "The output folder lies inside the source and holds versioned files: ${out_abs}"
		fi
		;;
esac

epoch=""
if command -v git >/dev/null 2>&1 && git -C "$source_abs" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
	epoch="$(git -C "$source_abs" log -1 --format=%ct 2>/dev/null)" || epoch=""
fi
if [ -z "$epoch" ]; then
	epoch="${SOURCE_DATE_EPOCH:-}"
fi
if [ -z "$epoch" ]; then
	epoch=315532800
	echo "::notice::SOURCE_DATE_EPOCH: no Git commit and no environment value, using 315532800 (1980-01-01)."
fi
export SOURCE_DATE_EPOCH="$epoch"

check_args=(check "--theme=${source_abs}" "--tag=${tag}")
if [ "$previous_set" -eq 1 ]; then
	check_args+=("--previous=${previous}")
fi
php "$manifest_script" "${check_args[@]}"

rm -rf -- "$out_abs/assets" "$out_abs/pages" "$out_abs/.work" || fail "Cannot clear the output folder ${out_abs}."
mkdir -p -- "$out_abs/assets" "$out_abs/.work" || fail "Cannot create the folders in ${out_abs}."
trap 'rm -rf -- "$out_abs/.work"' EXIT

bash "$zip_script" "$source_abs" "$out_abs/assets" "--excludes=${excludes}" "--work=${out_abs}/.work"
php "$manifest_script" build "--theme=${source_abs}" "--tag=${tag}" "--zip=${out_abs}/assets/creationell-wp-theme.zip" "--out=${out_abs}/pages"
echo "Public release OK: ${tag} in ${out_abs}"
