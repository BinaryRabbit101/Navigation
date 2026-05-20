<?php

namespace Database\Seeders;

use App\Models\Website;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'title' => 'AI Campaign Manager',
                'description' => 'AI-powered campaign management with character avatar generation.',
                'url' => 'http://localhost:8001',
                'source_favicon' => 'C:\\Users\\binar\\Documents\\websites\\AiCampaignManager\\public\\favicon.svg',
                'slug' => 'ai-campaign-manager',
                'sort_order' => 1,
            ],
            [
                'title' => 'Budget',
                'description' => 'Budget tracking and financial visualization.',
                'url' => 'http://localhost:8002',
                'source_favicon' => 'C:\\Users\\binar\\Documents\\websites\\Budget\\public\\favicon.svg',
                'slug' => 'budget',
                'sort_order' => 2,
            ],
            [
                'title' => 'Little Pocket Museum',
                'description' => 'Personal family memory and museum curator.',
                'url' => 'http://localhost:8003',
                'source_favicon' => 'C:\\Users\\binar\\Documents\\websites\\LittlePocketMeseum\\public\\favicon.svg',
                'slug' => 'little-pocket-museum',
                'sort_order' => 3,
            ],
        ];

        foreach ($sites as $site) {
            $imagePath = null;

            if (is_file($site['source_favicon'])) {
                $targetPath = "websites/{$site['slug']}.svg";
                Storage::disk('public')->put($targetPath, file_get_contents($site['source_favicon']));
                $imagePath = $targetPath;
            }

            Website::updateOrCreate(
                ['title' => $site['title']],
                [
                    'description' => $site['description'],
                    'url' => $site['url'],
                    'image_path' => $imagePath,
                    'sort_order' => $site['sort_order'],
                ],
            );
        }
    }
}
