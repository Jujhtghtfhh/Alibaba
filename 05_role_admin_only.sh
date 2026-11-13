#!/usr/bin/env bash
# ROLE CHECK: only an admin may create / update / delete categories.
source "$(dirname "$0")/lib.sh"

title "05 ROLE: ONLY ADMIN MANAGES CATEGORIES"
ADMIN_TOKEN="$(admin_login | field access_token)"
USER_TOKEN="$(user_login | field access_token)"

call "User creates a category" 403 POST /categories "$USER_TOKEN" '{"name":"Hacked","description":"x"}'
call "Admin creates a category" 201 POST /categories "$ADMIN_TOKEN" "{\"name\":\"Demo $RANDOM\",\"description\":\"created by admin\"}"
CAT_ID="$(printf '%s' "$LAST_BODY" | nested data.id)"

call "User updates that category" 403 PUT "/categories/$CAT_ID" "$USER_TOKEN" '{"name":"Hacked","description":"x"}'
call "User deletes that category" 403 DELETE "/categories/$CAT_ID" "$USER_TOKEN"
call "Admin updates the category" 200 PUT "/categories/$CAT_ID" "$ADMIN_TOKEN" '{"name":"Renamed by admin","description":"updated"}'
call "Admin deletes the category" 204 DELETE "/categories/$CAT_ID" "$ADMIN_TOKEN"
call "Deleted category is gone" 404 GET "/categories/$CAT_ID"
