#!/usr/bin/env bash
# REFRESH: swap a refresh token for a new pair without logging in again. Tokens rotate.
source "$(dirname "$0")/lib.sh"

title "07 REFRESH TOKEN"
LOGIN="$(admin_login)"
REFRESH1="$(printf '%s' "$LOGIN" | field refresh_token)"

call "Refresh with a valid refresh token" 200 POST /auth/refresh "" "{\"refresh_token\":\"$REFRESH1\"}"
NEW_ACCESS="$(printf '%s' "$LAST_BODY" | field access_token)"

call "New access token works" 422 POST /items "$NEW_ACCESS" '{}'
call "Old refresh token used again (rotation: it is now invalid)" 401 POST /auth/refresh "" "{\"refresh_token\":\"$REFRESH1\"}"
call "Made-up refresh token" 401 POST /auth/refresh "" '{"refresh_token":"abc"}'
call "Missing refresh token" 422 POST /auth/refresh "" '{}'
