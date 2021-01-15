#!/bin/bash

set -e

getTag(){
	git describe --tags `git rev-list --tags --max-count=1`
}

echo "Listing changes since tag: $(getTag)"

# List all merge commits since the last tag.
git log --reverse --merges --pretty="* %f – %b"  HEAD...$(getTag)
