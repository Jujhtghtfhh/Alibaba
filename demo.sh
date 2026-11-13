#!/usr/bin/env bash
# Aligaga autorizacijos demonstracija. Naudojimas: bash demo.sh [http://127.0.0.1:8000]
BASE="${1:-http://127.0.0.1:8000}/api"
H=(-H "Content-Type: application/json" -H "Accept: application/json")

# pagalbinės funkcijos
field() { php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j[$argv[1]] ?? "";' "$1"; }
code()  { curl -s -o /dev/null -w "%{http_code}" "$@"; }
show()  { printf "   -> HTTP %s (tikėtasi %s)\n" "$1" "$2"; }
title() { printf "\n=== %s ===\n" "$1"; }

title "1. REGISTER: nauja paskyra (rolė turi būti user)"
EMAIL="demo$RANDOM@example.com"
REG=$(curl -s -X POST "$BASE/auth/register" "${H[@]}" \
  -d "{\"name\":\"Demo\",\"email\":\"$EMAIL\",\"password\":\"Passw0rd!x1\",\"password_confirmation\":\"Passw0rd!x1\"}")
echo "$REG" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo "   user: ".json_encode($j["user"] ?? $j)."\n";'

title "2. LOGIN: admin ir paprastas naudotojas"
ADMIN_JSON=$(curl -s -X POST "$BASE/auth/login" "${H[@]}" -d '{"email":"admin@example.com","password":"password"}')
USER_JSON=$(curl -s -X POST "$BASE/auth/login" "${H[@]}" -d '{"email":"test@example.com","password":"password"}')
ADMIN=$(echo "$ADMIN_JSON" | field access_token)
ADMIN_REFRESH=$(echo "$ADMIN_JSON" | field refresh_token)
USER=$(echo "$USER_JSON" | field access_token)
echo "   access token galioja (expires_in, s): $(echo "$ADMIN_JSON" | field expires_in)"
echo "   neteisingas slaptažodis:"; show "$(code -X POST "$BASE/auth/login" "${H[@]}" -d '{"email":"admin@example.com","password":"bad"}')" 401

title "3. ŽETONO VIDUS (claims): sub, role, sid, iat, exp"
echo "$ADMIN" | cut -d. -f2 | php -r '$p=stream_get_contents(STDIN); $p=strtr(trim($p),"-_","+/"); echo "   admin: ".base64_decode($p)."\n";'
echo "$USER"  | cut -d. -f2 | php -r '$p=stream_get_contents(STDIN); $p=strtr(trim($p),"-_","+/"); echo "   user:  ".base64_decode($p)."\n";'

title "4. ENDPOINT'AI TIK PRISIJUNGUSIEMS (be žetono, su sugadintu, su geru)"
echo "   POST /items be žetono:";        show "$(code -X POST "$BASE/items" "${H[@]}" -d '{}')" 401
echo "   POST /items sugadintas žetonas:"; show "$(code -X POST "$BASE/items" "${H[@]}" -H "Authorization: Bearer ${USER}x" -d '{}')" 401
echo "   POST /items su geru žetonu (tuščias body):"; show "$(code -X POST "$BASE/items" "${H[@]}" -H "Authorization: Bearer $USER" -d '{}')" "422 (praėjo autentifikaciją, nepraėjo validacijos)"
echo "   GET /items skaityti galima ir be žetono:"; show "$(code "$BASE/items")" 200

title "5. ROLĖ: tik admin kuria kategorijas"
echo "   user -> POST /categories:"; show "$(code -X POST "$BASE/categories" "${H[@]}" -H "Authorization: Bearer $USER" -d '{"name":"Hack"}')" 403
CAT=$(curl -s -X POST "$BASE/categories" "${H[@]}" -H "Authorization: Bearer $ADMIN" -d "{\"name\":\"Demo $RANDOM\",\"description\":\"demo\"}")
echo "   admin -> POST /categories:"; echo "   -> $(echo "$CAT" | head -c 160)"

title "6. SAVININKYSTĖ: user negali keisti / trinti svetimo įrašo"
echo "   user -> PUT /items/1 (priklauso admin):";    show "$(code -X PUT "$BASE/items/1" "${H[@]}" -H "Authorization: Bearer $USER" -d '{"category_id":1,"name":"Hacked","price":1.00,"stock":1}')" 403
echo "   user -> DELETE /items/1:";                   show "$(code -X DELETE "$BASE/items/1" -H "Accept: application/json" -H "Authorization: Bearer $USER")" 403
echo "   user -> DELETE /reviews/1 (svetimas komentaras):"; show "$(code -X DELETE "$BASE/reviews/1" -H "Accept: application/json" -H "Authorization: Bearer $USER")" 403

title "7. REFRESH: naujas access token be pakartotinio prisijungimo"
NEW=$(curl -s -X POST "$BASE/auth/refresh" "${H[@]}" -d "{\"refresh_token\":\"$ADMIN_REFRESH\"}")
NEW_ACCESS=$(echo "$NEW" | field access_token); NEW_REFRESH=$(echo "$NEW" | field refresh_token)
echo "   gautas naujas access token: $([ -n "$NEW_ACCESS" ] && echo taip || echo NE)"
echo "   senas refresh token panaudotas antrą kartą (rotacija):"; show "$(code -X POST "$BASE/auth/refresh" "${H[@]}" -d "{\"refresh_token\":\"$ADMIN_REFRESH\"}")" 401

title "8. LOGOUT: žetonas nustoja galioti, nors exp dar nepasibaigęs"
echo "   prieš logout:";  show "$(code -X POST "$BASE/items" "${H[@]}" -H "Authorization: Bearer $NEW_ACCESS" -d '{}')" 422
echo "   logout:";        show "$(code -X POST "$BASE/auth/logout" "${H[@]}" -H "Authorization: Bearer $NEW_ACCESS")" "200/204"
echo "   po logout tuo pačiu žetonu:"; show "$(code -X POST "$BASE/items" "${H[@]}" -H "Authorization: Bearer $NEW_ACCESS" -d '{}')" 401
echo "   po logout refresh token:";    show "$(code -X POST "$BASE/auth/refresh" "${H[@]}" -d "{\"refresh_token\":\"$NEW_REFRESH\"}")" 401

title "9. PAKLASTOTAS ŽETONAS (role pakeista į admin, parašas senas)"
FORGED=$(php -r '
  $t=explode(".", $argv[1]);
  $p=json_decode(base64_decode(strtr($t[1],"-_","+/")),true); $p["role"]="admin";
  $e=rtrim(strtr(base64_encode(json_encode($p)),"+/","-_"),"=");
  echo "$t[0].$e.$t[2]";' "$USER")
echo "   user žetonas su role=admin:"; show "$(code -X POST "$BASE/categories" "${H[@]}" -H "Authorization: Bearer $FORGED" -d '{"name":"Hack"}')" 401
