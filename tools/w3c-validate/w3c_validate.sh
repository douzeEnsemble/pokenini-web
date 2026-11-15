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

# Pages à valider, au format "rôle|chemin[|code HTTP attendu]" :
#   - rôle : anon (page publique, sans session) ou trainer / collector / admin
#            (compte factice du firewall de dev, cf. FakeAuthenticator : /fr/connect/f/c?t=<rôle>).
#            collector = compte qui peut éditer (crayon d'édition sur l'album) ; admin = pages /istration.
#   - code attendu : 200 par défaut. Une page avec un autre code attendu est vérifiée mais PAS validée
#            par vnu (redirection, page d'erreur...) : elle ne doit jamais être validée à la place d'une vraie page.
URLS=(
	"anon|fr"
	"anon|fr/hub"
	"anon|fr/connect"
	"anon|fr/policy"
	"anon|fr/legals"
	"anon|fr/cookies"
	"anon|en/policy"
	"anon|en/legals"
	"anon|en/cookies"
	"trainer|fr/album/dex"
	"trainer|fr/album/demolite"
	"trainer|en/album/demolite"
	"collector|fr/album/alpha"
	"collector|en/album/alpha"
	"trainer|fr/election/dex"
	"trainer|fr/game"
	"trainer|fr/game/color"
	"trainer|fr/game/shiny"
	"trainer|fr/game/which-shiny"
	"trainer|fr/trainer"
	"trainer|fr/trainer/links"
	"trainer|fr/trainer/home_page"
	"trainer|fr/trainer/personnal_data"
	"admin|fr/istration/update_data"
	"admin|fr/istration/reports"
	# Exceptions documentées (code HTTP attendu autre que 200, rien à valider) :
	"anon||301"            # la racine redirige vers /fr (déjà couvert par "fr")
	"trainer|fr/outerroom|302" # redirige vers /fr
	"trainer|fr/istration|403" # réservé à l'admin : vérifie que trainer est bien refusé
	# fr/election/demo : 404 avec la fixture moco (pas d'élection "demo"), volontairement absent.
)

# Pages dont le contenu identique est explicitement attendu (liste de chemins séparés par un espace).
ALLOWED_DUPLICATES=""

# Rôles authentifiés nécessaires (déduits de URLS), un cookie jar par rôle (dans le conteneur php).
JAR_DIR="/tmp/w3c-cookies-$$"
declare -A SEEN_ROLES=()
declare -A ROLE_COOKIES=()
# Page réservée à chaque rôle, utilisée pour vérifier que la session est reconnue après la connexion.
declare -A PROBE_URLS=([trainer]="fr/trainer" [collector]="fr/trainer" [admin]="fr/istration/update_data")
FAILURES=0

fail() {
	echo "❌ $*" >&2
	FAILURES=$((FAILURES + 1))
}

cleanup() {
	docker compose exec -T php rm -rf "$JAR_DIR" >/dev/null 2>&1 || true
}
trap cleanup EXIT

authenticate() {
	local entry role
	for entry in "${URLS[@]}"; do
		role="${entry%%|*}"
		[ "$role" != "anon" ] && SEEN_ROLES["$role"]=1
	done

	$CURL_CMD --version >/dev/null # échoue tôt si le conteneur php est injoignable
	docker compose exec -T php mkdir -p "$JAR_DIR"

	for role in "${!SEEN_ROLES[@]}"; do
		local url="${BASE_URI}fr/connect/f/c?t=${role}"
		local jar="${JAR_DIR}/${role}.txt"
		echo "🔐 Authenticating as ${role} with $url ..."
		if ! $CURL_CMD -fsSL -c "$jar" -o /dev/null "$url"; then
			echo "❌ Authentication failed for role ${role}" >&2
			exit 1
		fi
		# Le cookie jar (lignes "#HttpOnly_" comprises) doit contenir un cookie de session, sinon les pages seraient récupérées déconnectées.
		# Le cookie est ensuite renvoyé tel quel dans un en-tête Cookie (pas de dépendance à la correspondance de domaine du jar).
		local cookie
		cookie="$(docker compose exec -T php sh -c "grep -E '^(#HttpOnly_)?[^#[:space:]]' '$jar' | awk '{ printf \"%s%s=%s\", sep, \$6, \$7; sep=\"; \" }'" | tr -d '\r')"
		if [ -z "$cookie" ]; then
			echo "❌ No session cookie obtained for role ${role}" >&2
			exit 1
		fi
		ROLE_COOKIES["$role"]="$cookie"
		# Preuve que la session est reconnue côté serveur : une page réservée au rôle doit répondre 200 (sinon 307 = session perdue).
		local probe code headers
		probe="${PROBE_URLS[$role]}"
		headers="$($CURL_CMD -sS -o /dev/null -D - -H "Cookie: ${cookie}" "${BASE_URI}${probe}" | tr -d '\r')"
		code="$(printf '%s\n' "$headers" | awk 'NR==1 { print $2 }')"
		if [ "$code" != "200" ]; then
			echo "❌ Session of role ${role} not recognized: ${probe} answered HTTP ${code} instead of 200" >&2
			echo "--- response headers" >&2
			printf '%s\n' "$headers" >&2
			echo "--- cookie jar" >&2
			docker compose exec -T php cat "$jar" >&2 || true
			exit 1
		fi
		echo "✅ Authenticated as ${role}."
	done
	echo
}

clean_output_dir() {
	echo "🧹 Cleaning output directory: $OUT_DIR"
	rm -rf "$OUT_DIR"
	mkdir -p "$OUT_DIR"
	echo
}

fetch_and_clean() {
	local entry="$1" role website expected
	IFS='|' read -r role website expected <<<"$entry"
	expected="${expected:-200}"
	local url="${BASE_URI}${website}"
	local filename
	filename="$(echo "$website" | sed 's|^https\?://||; s|[^A-Za-z0-9._-]|_|g')"
	[ -z "$filename" ] && filename="index"
	local outfile="${OUT_DIR}/${filename}.html"

	local cookie_args=()
	[ "$role" != "anon" ] && cookie_args=(-H "Cookie: ${ROLE_COOKIES[$role]}")

	echo "🌐 Fetching $url as ${role} ... to $outfile"
	# Pas de -L : on veut voir les redirections. Le code HTTP est ajouté après le corps.
	local raw code body
	if ! raw="$($CURL_CMD -sS "${cookie_args[@]}" -w $'\n%{http_code}' "$url")"; then
		fail "Failed to fetch $url (curl error)"
		return
	fi
	code="${raw##*$'\n'}"
	body="${raw%$'\n'*}"

	if [ "$code" != "$expected" ]; then
		if [[ "$code" == 30* && "$role" != "anon" ]]; then
			fail "$url as ${role}: HTTP $code instead of $expected (redirect: session missing or role refused?)"
		else
			fail "$url as ${role}: HTTP $code instead of $expected"
		fi
		return
	fi
	if [ "$expected" != "200" ]; then
		echo "   ↪ HTTP $code expected, not validated"
		return
	fi
	printf '%s\n' "$body" | { grep -v "Symfony Web Debug Toolbar" || true; } >"$outfile"
}

# Deux URLs distinctes ne doivent pas donner le même contenu (signe d'une session perdue / page d'erreur / login).
check_duplicates() {
	local line prev_md5="" prev_file group=()
	while read -r md5 file; do
		if [ "$md5" = "$prev_md5" ]; then
			group+=("$file")
		else
			report_group "${group[@]+"${group[@]}"}"
			group=("$file")
			prev_md5="$md5"
		fi
	done < <(cd "$OUT_DIR" && md5sum -- *.html | sort)
	report_group "${group[@]+"${group[@]}"}"
}

report_group() {
	[ "$#" -gt 1 ] || return 0
	case " $ALLOWED_DUPLICATES " in *" $1 "*) return 0 ;; esac
	fail "Identical content for distinct URLs: $* (wrong session, error page or login page?)"
}

run_validation() {
	echo "🔍 Running HTML validation..."
	if ! $VNU_CMD "/app/$OUT_DIR"; then
		fail "HTML validation errors detected"
	else
		echo "✅ All HTML files are valid!"
	fi
}

main() {
	pin_cross_project_dns
	clean_output_dir
	authenticate

	for entry in "${URLS[@]}"; do
		fetch_and_clean "$entry"
	done

	check_duplicates
	run_validation

	if [ "$FAILURES" -gt 0 ]; then
		echo "🏁 Validation failed: ${FAILURES} problem(s)." >&2
		exit 1
	fi
	echo "🏁 Validation completed."
}

main "$@"
