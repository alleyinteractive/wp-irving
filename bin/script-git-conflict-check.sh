#!/bin/bash

set -e

# Bail early if we're in CI and aren't linting.
if [[ $WP_TRAVISCI != "phpcs" ]]; then
	exit 0
fi

echo "Checking for Git merge conflicts..."

# Exclude the current script, via https://stackoverflow.com/a/30084612.
! git grep -E '<<<<<<< HEAD|>>>>>>>' ":!$0"
