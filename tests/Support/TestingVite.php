<?php

namespace Tests\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

class TestingVite extends Vite
{
    public function __invoke($entrypoints, $buildDirectory = null)
    {
        $this->withEntryPoints((array) $entrypoints);

        return new HtmlString('');
    }

    public function reactRefresh()
    {
        return new HtmlString('');
    }

    public function asset($asset, $buildDirectory = null)
    {
        $buildDirectory ??= $this->buildDirectory;

        return $this->assetPath($this->normalizeAssetPath($asset, $buildDirectory));
    }

    public function content($asset, $buildDirectory = null)
    {
        return '';
    }

    public function manifestHash($buildDirectory = null)
    {
        return null;
    }

    protected function normalizeAssetPath(string $asset, ?string $buildDirectory): string
    {
        $path = ltrim($asset, '/');

        if ($buildDirectory !== null && $buildDirectory !== '') {
            $path = trim($buildDirectory, '/').'/'.$path;
        }

        return $path;
    }
}
