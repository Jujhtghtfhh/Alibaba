#!/usr/bin/env bash
# Runs every demo script one after another. Each script can also be run on its own.
cd "$(dirname "$0")" || exit 1
for f in 0*.sh; do bash "$f"; done
