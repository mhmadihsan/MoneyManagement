<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OfflineAssetsTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [['/'], ['/login'], ['/dashboard'], ['/kategori'], ['/transaksi/create'],
            ['/transaksi'], ['/profile'], ['/confirm-password'], ['/verify-email']];
    }

    #[DataProvider('pages')]
    public function test_pages_only_reference_existing_local_styles_and_scripts(string $url): void
    {
        if (! in_array($url, ['/', '/login'])) {
            $this->actingAs(User::factory()->unverified()->create());
        }

        $response = $this->get($url)->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $assets = $xpath->query('//script[@src]/@src | //link[@rel="stylesheet"]/@href | //link[@rel="modulepreload"]/@href');
        $this->assertGreaterThan(0, $assets->length);
        foreach ($assets as $asset) {
            $parts = parse_url($asset->value);
            $this->assertSame('localhost', $parts['host'] ?? 'localhost', $asset->value);
            $this->assertFileExists(public_path(ltrim($parts['path'], '/')));
        }
    }

    public function test_templates_do_not_load_external_resources(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/(?:src|href)\s*=\s*["\']\s*(?:https?:)?\/\//i', $contents, $file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/["\'](?:https?:)?\/\/[^"\']+\.(?:json|css|js)(?:[?"\'])/i', $contents, $file->getPathname());
        }
    }

    public function test_vendor_styles_resolve_fonts_and_images_from_local_files(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path('vendor'))) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'css') {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression('/@import\b/i', $contents, $file->getPathname());
            preg_match_all('/url\(\s*["\']?([^\)"\']+)["\']?\s*\)/i', $contents, $matches);
            foreach ($matches[1] as $url) {
                if (str_starts_with($url, 'data:') || str_starts_with($url, '#')) {
                    continue;
                }
                $this->assertDoesNotMatchRegularExpression('/^(?:https?:)?\/\//i', $url);
                $this->assertFileExists($file->getPath().'/'.explode('?', $url)[0]);
            }
        }
    }

    public function test_vendored_assets_match_the_recorded_checksums(): void
    {
        $manifest = json_decode(file_get_contents(public_path('vendor/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['files'] as $asset) {
            $path = public_path('vendor/'.$asset['path']);
            $this->assertFileExists($path);
            $this->assertSame($asset['sha256'], hash_file('sha256', $path), $asset['path']);
        }
    }
}
