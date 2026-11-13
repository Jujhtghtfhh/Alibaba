#!/usr/bin/env bash
# TAMPERING: changing the claims breaks the signature, so the server rejects the token.
source "$(dirname "$0")/lib.sh"

title "09 FORGED TOKENS"
USER_TOKEN="$(user_login | field access_token)"

# 1) Payload changed to role=admin, old signature kept.
FORGED="$(php -r '
  $t = explode(".", $argv[1]);
  $p = strtr($t[1], "-_", "+/"); $p .= str_repeat("=", (4 - strlen($p) % 4) % 4);
  $c = json_decode(base64_decode($p), true); $c["role"] = "admin";
  $e = rtrim(strtr(base64_encode(json_encode($c)), "+/", "-_"), "=");
  echo "$t[0].$e.$t[2]";' "$USER_TOKEN")"

# 2) alg=none token with role=admin and no signature.
NONE="$(php -r '
  $b = fn($a) => rtrim(strtr(base64_encode(json_encode($a)), "+/", "-_"), "=");
  echo $b(["alg"=>"none","typ"=>"JWT"]).".".$b(["sub"=>1,"role"=>"admin","sid"=>1,"exp"=>time()+3600]).".";')"

# 3) Last character of the signature changed.
BADSIG="${USER_TOKEN%?}A"

printf '\nClaims inside the forged token:\n'; jwt_claims "$FORGED" | sed 's/^/    /'; printf '\n'

call "Forged: payload says role=admin, signature is the old one" 401 POST /categories "$FORGED" '{"name":"Hack"}'
call "Forged: alg=none, no signature" 401 POST /categories "$NONE" '{"name":"Hack"}'
call "Signature altered by one character" 401 POST /items "$BADSIG" '{}'
