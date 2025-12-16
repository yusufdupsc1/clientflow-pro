#!/usr/bin/env node

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const packageJsonPath = resolve(rootDir, 'package.json');

let enginesRange = '';

try {
    const packageJson = JSON.parse(readFileSync(packageJsonPath, 'utf8'));
    enginesRange = packageJson.engines?.node ?? '';
} catch (error) {
    console.error(`Unable to read package.json for Node.js version check: ${error.message}`);
    process.exit(1);
}

if (!enginesRange) {
    console.error('package.json does not specify engines.node; please set it to the LTS major version.');
    process.exit(1);
}

const requiredMajor = (() => {
    const match = enginesRange.match(/(\d+)/);
    return match ? Number.parseInt(match[1], 10) : Number.NaN;
})();

if (!Number.isInteger(requiredMajor)) {
    console.error(`Unable to determine required Node.js major from engines.node="${enginesRange}".`);
    process.exit(1);
}

const currentVersion = process.version;
const currentMajor = Number.parseInt(process.versions.node.split('.')[0], 10);

if (currentMajor !== requiredMajor) {
    console.error(`Node.js ${currentVersion} does not satisfy required LTS ${requiredMajor}.x (engines.node=${enginesRange}).`);
    process.exit(1);
}

if (!process.release.lts) {
    console.error(`Node.js ${currentVersion} is not an LTS build; please switch to the ${requiredMajor}.x LTS release.`);
    process.exit(1);
}

console.log(`Node.js ${currentVersion} matches required LTS ${requiredMajor}.x.`);
