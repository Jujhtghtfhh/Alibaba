#!/usr/bin/env bash
# Shared helpers. Every NN_*.sh script sources this file, so each script runs on its own.
# Server address can be overridden:  BASE=http://127.0.0.1:8000/api bash 02_login.sh

BASE="${BASE:-http://127.0.0.1:8000/api}"
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

# Pretty-print JSON from stdin (falls back to raw text, or a note when empty).
pretty() {
  php -r '
    $s = stream_get_contents(STDIN);
    if (trim($s) === "") { echo "(empty body)"; exit; }
    $j = json_decode($s);
    echo $j === null ? $s : json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  '
}

# Read one top-level field from JSON on stdin.
field() { php -r '$j = json_decode(stream_get_contents(STDIN), true); $v = $j[$argv[1]] ?? ""; echo is_array($v) ? json_encode($v) : $v;' "$1"; }

# Read a nested field, e.g. data.id
nested() { php -r '$j = json_decode(stream_get_contents(STDIN), true); foreach (explode(".", $argv[1]) as $k) { $j = $j[$k] ?? ""; } echo is_array($j) ? json_encode($j) : $j;' "$1"; }

# Decode the payload (claims) of a JWT given as $1.
jwt_claims() {
  php -r '
    $p = explode(".", $argv[1])[1] ?? "";
    $p = strtr($p, "-_", "+/");
    $p .= str_repeat("=", (4 - strlen($p) % 4) % 4);
    echo json_encode(json_decode(base64_decode($p)), JSON_PRETTY_PRINT);
  ' "$1"
}

title() { printf '\n==================== %s ====================\n' "$*"; }

# call LABEL EXPECTED_CODE METHOD PATH [TOKEN] [JSON_BODY]
# Prints the request, the HTTP code and the whole JSON response.
# Afterwards LAST_CODE and LAST_BODY hold the result.
call() {
  local label="$1" expected="$2" method="$3" path="$4" token="${5:-}" body="${6:-}"
  local args=(-s -X "$method" "$BASE$path" -H "Accept: application/json" -H "Content-Type: application/json" -o "$TMP" -w "%{http_code}")
  [ -n "$token" ] && args+=(-H "Authorization: Bearer $token")
  [ -n "$body" ] && args+=(-d "$body")

  LAST_CODE="$(curl "${args[@]}")"
  LAST_BODY="$(cat "$TMP")"

  printf '\n--- %s\n' "$label"
  printf '  Request : %s %s\n' "$method" "$BASE$path"
  [ -n "$token" ] && printf '  Header  : Authorization: Bearer %s...\n' "${token:0:24}"
  [ -n "$body" ] && printf '  Body    : %s\n' "$body"
  if [ "$LAST_CODE" = "$expected" ]; then
    printf '  HTTP    : %s  (expected %s) OK\n' "$LAST_CODE" "$expected"
  else
    printf '  HTTP    : %s  (expected %s) MISMATCH\n' "$LAST_CODE" "$expected"
  fi
  printf '  Response:\n'
  printf '%s' "$LAST_BODY" | pretty | sed 's/^/    /'
  printf '\n'
}

# Log in silently and print the whole login JSON (used to obtain tokens without noise).
login_json() {
  curl -s -X POST "$BASE/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "{\"email\":\"$1\",\"password\":\"${2:-password}\"}"
}
admin_login() { login_json admin@example.com; }
user_login()  { login_json test@example.com; }

# Stop early with a clear message when the server is not running.
if ! curl -s -o /dev/null --max-time 5 "$BASE/dashboard"; then
  echo "Cannot reach $BASE - start the server first: php artisan serve" >&2
  exit 1
fi
