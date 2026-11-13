#!/usr/bin/env bash
# TOKEN CONTENT: decodes the payload of the access token (sub = user ID, role, sid, iat, exp).
source "$(dirname "$0")/lib.sh"

title "03 TOKEN CLAIMS"
for who in admin user; do
  JSON="$([ "$who" = admin ] && admin_login || user_login)"
  TOKEN="$(printf '%s' "$JSON" | field access_token)"
  printf '\n--- Login response for %s (full JSON)\n' "$who"
  printf '%s' "$JSON" | pretty | sed 's/^/    /'
  printf '\n\n--- Header of the %s token\n' "$who"
  printf '%s' "$TOKEN" | cut -d. -f1 | php -r '$p=strtr(trim(stream_get_contents(STDIN)),"-_","+/"); $p.=str_repeat("=",(4-strlen($p)%4)%4); echo base64_decode($p);' | sed 's/^/    /'
  printf '\n--- Claims (payload) of the %s token\n' "$who"
  jwt_claims "$TOKEN" | sed 's/^/    /'
  printf '\n'
done
echo "sub = user ID, role = user role, sid = login session ID (refresh_tokens row), iat/exp = issued/expiry (Unix time)."
echo "The third part of the token is the HMAC-SHA256 signature."
