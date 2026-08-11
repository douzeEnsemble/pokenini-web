#!/usr/bin/env bash

set -euo pipefail

BASE_URI="http://web:8081/"
OUT_DIR="var/html"
VNU_CMD="docker run --rm -v $(pwd):/app ghcr.io/validator/validator:latest vnu --format text"
CURL_CMD="docker compose exec -T php curl"

# In local dev, php/web also join the shared external "pokenini-local" network so the
# pokenini-api/-back/-web stacks can talk to each other. Docker Compose aliases every
# container on every network it joins with its own service name ("web", "php"), so on
# that shared network "web" and "php" collide across the three sibling projects and
# Docker's embedded DNS round-robins between them — causing curl (php -> web) and
# nginx's fastcgi_pass (web -> php) to intermittently hit another project's container.
# CI never joins that network, so it never sees this. Pin both hops to this project's
# own containers before running anything.
project_network_ip() {
	local service="$1"
	docker inspect "$(docker compose ps -q "$service")" --format '{{json .NetworkSettings.Networks}}' \
		| jq -r 'to_entries[] | select(.key != "pokenini-local") | .value.IPAddress' \
		| head -n1
}

pin_cross_project_dns() {
	local web_ip php_ip
	web_ip="$(project_network_ip web)"
	php_ip="$(project_network_ip php)"

	echo "🔧 Pinning web=${web_ip} / php=${php_ip} to avoid DNS collisions with sibling projects on the shared network"
	# /etc/hosts is a bind-mounted file here, so `sed -i` (rename-based) fails with
	# "Resource busy" — rewrite it in place via `cat` instead.
	docker compose exec -T web sh -c "(grep -vE ' php\$' /etc/hosts; echo '${php_ip} php') >/tmp/hosts.new && cat /tmp/hosts.new >/etc/hosts && rm /tmp/hosts.new"
	docker compose exec -T web nginx -s reload
	echo

	CURL_CMD="docker compose exec -T php curl --resolve web:8081:${web_ip}"
}

URLS=(
	""
	"fr"
	"fr/connect"
	"fr/album/dex"
	"fr/album/demolite"
	"fr/election/dex"
	"fr/election/demo"
	"fr/outerroom"
	"fr/policy"
	"fr/legals"
	"fr/cookies"
	"en/policy"
	"en/legals"
	"en/cookies"
	"fr/trainer"
	"fr/trainer/links"
	"fr/trainer/personnal_data"
	"fr/istration"
)

authenticate() {
	local url="${BASE_URI}fr/connect/f/c?t=trainer"
	echo "🔐 Authenticating with $url ..."
	if ! $CURL_CMD -fsSL "$url" >/dev/null; then
		echo "❌ Authentication failed"
		exit 1
	fi
	echo "✅ Authenticated successfully."
	echo
}

clean_output_dir() {
	echo "🧹 Cleaning output directory: $OUT_DIR"
	rm -rf "$OUT_DIR"
	mkdir -p "$OUT_DIR"
	echo
}

fetch_and_clean() {
	local website="$1"
	local url="${BASE_URI}${website}"
	local filename="$(echo "$website" | sed 's|^https\?://||; s|[^A-Za-z0-9._-]|_|g')"
	[ -z "$filename" ] && filename="index"
	local outfile="${OUT_DIR}/${filename}.html"

	echo "🌐 Fetching $url ... to $outfile"
	if ! $CURL_CMD -fsSL "$url" | grep -v "Symfony Web Debug Toolbar" >"$outfile"; then
		echo "⚠️ Failed to fetch $url"
		return
	fi
}

run_validation() {
	echo "🔍 Running HTML validation..."
	if ! $VNU_CMD "/app/$OUT_DIR"; then
		echo "⚠️ Validation errors detected"
	else
		echo "✅ All HTML files are valid!"
	fi
}

main() {
	pin_cross_project_dns
	clean_output_dir
	authenticate

	for website in "${URLS[@]}"; do
		fetch_and_clean "$website"
	done

	run_validation
	echo "🏁 Validation completed."
}

main "$@"
