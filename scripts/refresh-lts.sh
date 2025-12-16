#!/usr/bin/env sh
# POSIX shell script; use Git Bash or WSL on Windows.
# Refresh the workspace using the active Node.js LTS version.
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

ENGINE_RANGE="$(node -e "console.log(JSON.parse(require('fs').readFileSync('package.json','utf8')).engines?.node || '')")"
if [ -z "$ENGINE_RANGE" ]; then
    echo "package.json does not define engines.node; cannot determine required LTS." >&2
    exit 1
fi

ENGINE_MAJOR="$(printf '%s' "$ENGINE_RANGE" | sed -n 's/[^0-9]*\([0-9][0-9]*\).*/\1/p')"
if [ -z "$ENGINE_MAJOR" ]; then
    echo "Unable to parse engines.node value \"$ENGINE_RANGE\"." >&2
    exit 1
fi

NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    # shellcheck source=/dev/null
    . "$NVM_DIR/nvm.sh"
fi

if command -v nvm >/dev/null 2>&1; then
    printf "Switching to Node.js LTS via nvm (expected %s.x)...\n" "$ENGINE_MAJOR"
    nvm install "lts/*"
    nvm use "lts/*"
else
    printf "nvm not found; using existing Node.js %s.\n" "$(node -v)"
fi

echo "Verifying Node.js version..."
node scripts/check-node-lts.js

if [ -d "node_modules" ]; then
    echo "Removing existing node_modules..."
    rm -rf node_modules
fi

if [ -f "pnpm-lock.yaml" ]; then
    PM="pnpm"
    INSTALL_CMD="pnpm install --frozen-lockfile"
elif [ -f "yarn.lock" ]; then
    PM="yarn"
    INSTALL_CMD="yarn install --frozen-lockfile"
else
    PM="npm"
    if [ -f "package-lock.json" ]; then
        INSTALL_CMD="npm ci"
    else
        INSTALL_CMD="npm install"
    fi
fi

if ! command -v "$PM" >/dev/null 2>&1; then
    echo "Package manager \"$PM\" is not installed. Please install it to continue." >&2
    exit 1
fi

echo "Installing dependencies with $PM..."
sh -c "$INSTALL_CMD"

has_script() {
    node -e "const fs=require('fs');const pkg=JSON.parse(fs.readFileSync('package.json','utf8'));process.exit(pkg.scripts && pkg.scripts['$1'] ? 0 : 1);"
}

run_script() {
    script_name="$1"
    if has_script "$script_name"; then
        echo "Running \"$script_name\" script with $PM..."
        case "$PM" in
            npm) npm run "$script_name" ;;
            pnpm) pnpm run "$script_name" ;;
            yarn) yarn "$script_name" ;;
            *) "$PM" run "$script_name" ;;
        esac
    fi
}

run_script build
run_script test
run_script lint

echo "LTS refresh complete."
