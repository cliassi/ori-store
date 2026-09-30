#!/bin/zsh

# Default message or passed argument
MSG=${1:-"update"}

# macOS-compatible timestamp
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

git add .
git commit -m "$MSG: $TIMESTAMP"
git push