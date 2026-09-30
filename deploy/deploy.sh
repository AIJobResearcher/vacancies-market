#!/usr/bin/env bash

set -euo pipefail

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   Vacancies Market - Deploy Script${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

REPO_OWNER="AIJobResearcher"
REPO_NAME="docs"
BRANCH="${BRANCH:-main}"
BUILD_SCRIPT_URL="https://raw.githubusercontent.com/${REPO_OWNER}/${REPO_NAME}/${BRANCH}/deploy/vacancies-market/local/build.sh"

echo -e "${BLUE}📡 Downloading build script from docs repo...${NC}"
echo "  Source: $BUILD_SCRIPT_URL"
echo ""

echo -n "  Downloading build.sh... "
if curl -sSL --fail "$BUILD_SCRIPT_URL" -o .build.sh 2>/dev/null; then
    echo -e "${GREEN}done${NC}"
else
    echo -e "${RED}failed${NC}"
    echo "  ❌ Failed to download build script"
    echo "  URL: $BUILD_SCRIPT_URL"
    exit 1
fi

echo ""

chmod +x .build.sh
./.build.sh

rm -f .build.sh