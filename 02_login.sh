#!/usr/bin/env bash
# LOGIN: returns access token (15 min) and refresh token (7 days).
source "$(dirname "$0")/lib.sh"

title "02 LOGIN"
call "Login as admin" 200 POST /auth/login "" '{"email":"admin@example.com","password":"password"}'
call "Login as normal user" 200 POST /auth/login "" '{"email":"test@example.com","password":"password"}'
call "Wrong password" 401 POST /auth/login "" '{"email":"admin@example.com","password":"wrong"}'
call "Unknown email" 401 POST /auth/login "" '{"email":"nobody@example.com","password":"password"}'
call "Missing fields" 422 POST /auth/login "" '{}'
