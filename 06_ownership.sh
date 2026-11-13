#!/usr/bin/env bash
# OWNERSHIP: a user can change only their own items / reviews (ItemPolicy, ReviewPolicy). Admin can change any.
source "$(dirname "$0")/lib.sh"

title "06 OWNERSHIP: NOT SOMEONE ELSE'S DATA"
ADMIN_TOKEN="$(admin_login | field access_token)"
USER_TOKEN="$(user_login | field access_token)"
ITEM='{"category_id":1,"name":"Hacked","price":1.00,"stock":1}'

call "User edits an item owned by admin" 403 PUT /items/1 "$USER_TOKEN" "$ITEM"
call "User deletes an item owned by admin" 403 DELETE /items/1 "$USER_TOKEN"
call "User deletes a review owned by admin" 403 DELETE /reviews/1 "$USER_TOKEN"
call "User edits a review owned by admin" 403 PUT /reviews/1 "$USER_TOKEN" '{"item_id":1,"author_name":"Hacker","rating":1,"comment":"hacked"}'

call "User creates their OWN item" 201 POST /items "$USER_TOKEN" '{"category_id":1,"name":"My own item","price":9.99,"stock":5}'
ITEM_ID="$(printf '%s' "$LAST_BODY" | nested data.id)"
call "User edits their own item" 200 PUT "/items/$ITEM_ID" "$USER_TOKEN" '{"category_id":1,"name":"My own item (edited)","price":10.50,"stock":4}'

call "User posts a review on that item" 201 POST /reviews "$USER_TOKEN" "{\"item_id\":$ITEM_ID,\"author_name\":\"Test User\",\"rating\":5,\"comment\":\"My own review\"}"
REVIEW_ID="$(printf '%s' "$LAST_BODY" | nested data.id)"
call "User deletes their own review" 204 DELETE "/reviews/$REVIEW_ID" "$USER_TOKEN"

call "Admin deletes the user's item (admin may delete anything)" 204 DELETE "/items/$ITEM_ID" "$ADMIN_TOKEN"
