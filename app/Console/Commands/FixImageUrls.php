<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Raffle;
use App\Models\CmsSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:fix-urls {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix image URLs in database by replacing incorrect URLs with correct ones based on current APP_URL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $appUrl = rtrim(config('app.url'), '/');
        $updated = 0;

        $this->info('=== Fixing Image URLs ===');
        $this->info("APP_URL: {$appUrl}");
        $this->info('');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No changes will be made');
            $this->info('');
        }

        // Fix BlogPost featured images
        $this->info('Checking BlogPost featured images...');
        $blogPosts = BlogPost::whereNotNull('featured_image')
            ->where('featured_image', '!=', '')
            ->where('featured_image', 'not like', $appUrl.'%')
            ->where('featured_image', 'not like', 'https://placehold.co%')
            ->get();

        foreach ($blogPosts as $post) {
            $newUrl = $this->reconstructImageUrl($post->featured_image, $appUrl);
            if ($newUrl !== $post->featured_image) {
                $this->line("  Blog Post #{$post->id}: {$post->title}");
                $this->line("    Old: {$post->featured_image}");
                $this->line("    New: {$newUrl}");

                if (!$isDryRun) {
                    $post->featured_image = $newUrl;
                    $post->save();
                }
                $updated++;
            }
        }

        // Fix Raffle images
        $this->info("\nChecking Raffle images...");
        $raffles = Raffle::whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'not like', $appUrl.'%')
            ->where('image', 'not like', 'https://placehold.co%')
            ->get();

        foreach ($raffles as $raffle) {
            $newUrl = $this->reconstructImageUrl($raffle->image, $appUrl);
            if ($newUrl !== $raffle->image) {
                $this->line("  Raffle #{$raffle->id}: {$raffle->title}");
                $this->line("    Old: {$raffle->image}");
                $this->line("    New: {$newUrl}");

                if (!$isDryRun) {
                    $raffle->image = $newUrl;
                    $raffle->save();
                }
                $updated++;
            }
        }

        // Fix CMS banners
        $this->info("\nChecking CMS banners...");
        $cmsSetting = CmsSetting::first();
        if ($cmsSetting && $cmsSetting->banners) {
            $banners = is_array($cmsSetting->banners) ? $cmsSetting->banners : json_decode($cmsSetting->banners, true);
            $updated_banners = false;

            foreach ($banners as $key => $banner) {
                if (isset($banner['url']) && strpos($banner['url'], $appUrl) === false && strpos($banner['url'], 'https://placehold.co') === false) {
                    $newUrl = $this->reconstructImageUrl($banner['url'], $appUrl);
                    if ($newUrl !== $banner['url']) {
                        $this->line("  Banner #{$key}");
                        $this->line("    Old: {$banner['url']}");
                        $this->line("    New: {$newUrl}");
                        $banners[$key]['url'] = $newUrl;
                        $updated_banners = true;
                        $updated++;
                    }
                }
            }

            if ($updated_banners && !$isDryRun) {
                $cmsSetting->banners = $banners;
                $cmsSetting->save();
            }
        }

        $this->info('');
        if ($isDryRun) {
            $this->info("Total changes that would be made: {$updated}");
            $this->comment('Run without --dry-run to apply changes: php artisan images:fix-urls');
        } else {
            $this->info("Total images updated: {$updated}");
            $this->comment('Image URLs have been fixed!');
        }

        return 0;
    }

    /**
     * Reconstruct image URL from potentially broken URL.
     * Extracts the storage path and reconstructs with correct APP_URL.
     */
    private function reconstructImageUrl(string $oldUrl, string $appUrl): string
    {
        // If it's already correct, return as is
        if (strpos($oldUrl, $appUrl) === 0) {
            return $oldUrl;
        }

        // Try to extract the storage path from the URL
        // Handle various patterns like:
        // - http://localhost/storage/blog-images/file.jpg
        // - http://wrong-domain.com/storage/blog-images/file.jpg
        // - /storage/blog-images/file.jpg
        
        $patterns = [
            '/\/storage\/(.+)$/',  // Matches /storage/... anywhere in URL
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $oldUrl, $matches)) {
                $storagePath = $matches[1];
                return $appUrl . '/storage/' . $storagePath;
            }
        }

        // If we can't extract storage path, return the old URL
        // (it might be a placeholder or external URL)
        return $oldUrl;
    }
}
