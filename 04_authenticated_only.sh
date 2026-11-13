#!/usr/bin/env bash
# PROTECTED ENDPOINTS: only logged-in users may write; reading is public.
source "$(dirname "$0")/lib.sh"

title "04 ONLY LOGGED-IN USERS"
USER_TOKEN="$(user_login | field access_token)"

call "No token: create item" 401 POST /items "" '{}'
call "Broken token: create item" 401 POST /items "${USER_TOKEN}x" '{}'
call "Garbage token: create item" 401 POST /items "not.a.token" '{}'
call "Valid token, empty body (authentication passed, validation failed)" 422 POST /items "$USER_TOKEN" '{}'
call "Public read without token: list items (first page)" 200 GET "/items?per_page=2"
