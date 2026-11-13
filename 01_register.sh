#!/usr/bin/env bash
# REGISTER: creates an ordinary user (role must be "user") and returns tokens.
source "$(dirname "$0")/lib.sh"

title "01 REGISTER"
EMAIL="demo$RANDOM@example.com"

call "Register a new account (role must be 'user')" 201 POST /auth/register "" \
  "{\"name\":\"Demo\",\"email\":\"$EMAIL\",\"password\":\"Passw0rd!x1\",\"password_confirmation\":\"Passw0rd!x1\"}"

call "Same email again -> validation error" 422 POST /auth/register "" \
  "{\"name\":\"Demo\",\"email\":\"$EMAIL\",\"password\":\"Passw0rd!x1\",\"password_confirmation\":\"Passw0rd!x1\"}"

call "Try to register as admin (extra 'role' field must be ignored)" 201 POST /auth/register "" \
  "{\"name\":\"Evil\",\"email\":\"evil$RANDOM@example.com\",\"role\":\"admin\",\"password\":\"Passw0rd!x1\",\"password_confirmation\":\"Passw0rd!x1\"}"
echo "  Note: the response above must say \"role\": \"user\" - mass assignment is blocked."
