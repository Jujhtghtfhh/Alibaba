#!/usr/bin/env bash
# LOGOUT: deletes the refresh token; the access token stops working immediately (even before exp).
source "$(dirname "$0")/lib.sh"

title "08 LOGOUT"
LOGIN="$(user_login)"
ACCESS="$(printf '%s' "$LOGIN" | field access_token)"
REFRESH="$(printf '%s' "$LOGIN" | field refresh_token)"

call "Before logout: access token works" 422 POST /items "$ACCESS" '{}'
call "Logout" 204 POST /auth/logout "$ACCESS"
call "After logout: same access token (not expired yet) is rejected" 401 POST /items "$ACCESS" '{}'
call "After logout: refresh token is rejected" 401 POST /auth/refresh "" "{\"refresh_token\":\"$REFRESH\"}"
call "Logout without a token" 401 POST /auth/logout
