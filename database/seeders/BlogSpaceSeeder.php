<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class BlogSpaceSeeder extends Seeder
{
    /**
     * Seed the blog space with collections and entries.
     */
    public function run(): void
    {
        // Get the first admin user for created_by/updated_by
        $admin = User::first();

        // Create or get the Blog Space
        $blogSpace = Space::firstOrCreate(
            ['handle' => 'blog'],
            [
                'name' => 'Blog Space',
                'settings' => [
                    'description' => 'Main blog space for articles and content',
                    'locale' => 'en',
                ],
                'storage_prefix' => 'spaces/blog',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        $spaceId = $blogSpace->id;

        // Create Categories Collection
        $categoriesCollection = Collection::firstOrCreate(
            ['space_id' => $spaceId, 'handle' => 'categories'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'name',
                        'label' => 'Category Name',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'description',
                        'label' => 'Description',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                ],
                'settings' => [
                    'drafts' => true,
                    'singleton' => false,
                ],
            ]
        );

        // Create Posts Collection
        $postsCollection = Collection::firstOrCreate(
            ['space_id' => $spaceId, 'handle' => 'posts'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'title',
                        'label' => 'Title',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'excerpt',
                        'label' => 'Excerpt',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'content',
                        'label' => 'Content',
                        'type' => 'richtext',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'featured_image',
                        'label' => 'Featured Image',
                        'type' => 'text',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'category_id',
                        'label' => 'Category',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'author',
                        'label' => 'Author',
                        'type' => 'text',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'tags',
                        'label' => 'Tags',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'settings' => [
                    'drafts' => true,
                    'singleton' => false,
                ],
            ]
        );

        // Create sample categories
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Articles about technology, programming, and software development.',
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Lifestyle tips, health, and wellness articles.',
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Business news, entrepreneurship, and finance.',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Travel guides, destinations, and adventure stories.',
            ],
        ];

        $categoryEntries = [];
        foreach ($categories as $categoryData) {
            $categoryEntry = Entry::create([
                'space_id' => $spaceId,
                'collection_id' => $categoriesCollection->id,
                'status' => 'published',
                'published_at' => now(),
                'data' => $categoryData,
            ]);
            $categoryEntries[$categoryData['slug']] = $categoryEntry;
        }

        // Create sample blog posts
        $posts = [
            [
                'title' => 'Getting Started with Laravel 11',
                'slug' => 'getting-started-with-laravel-11',
                'excerpt' => 'Learn the fundamentals of Laravel 11 and build your first application with this comprehensive guide.',
                'content' => '<p>Laravel 11 brings exciting new features and improvements to the PHP framework ecosystem. In this article, we will explore the key changes and how to get started with building modern web applications.</p><p>First, let\'s discuss the installation process and the new features that make development easier and more efficient.</p>',
                'featured_image' => '/images/laravel-11-featured.jpg',
                'category_id' => $categoryEntries['technology']->id,
                'author' => 'John Doe',
                'tags' => 'laravel,php,web-development,programming',
            ],
            [
                'title' => 'The Future of Web Development',
                'slug' => 'future-of-web-development',
                'excerpt' => 'Exploring emerging trends and technologies that are shaping the future of web development.',
                'content' => '<p>The web development landscape is constantly evolving. From serverless architectures to AI-powered tools, there are many exciting developments on the horizon.</p><p>In this article, we will discuss the key trends that developers should watch and prepare for in the coming years.</p>',
                'featured_image' => '/images/web-development-future.jpg',
                'category_id' => $categoryEntries['technology']->id,
                'author' => 'Jane Smith',
                'tags' => 'web-development,technology,trends',
            ],
            [
                'title' => 'Top 10 Travel Destinations for 2026',
                'slug' => 'top-10-travel-destinations-2026',
                'excerpt' => 'Discover the most amazing travel destinations to visit in 2026, from hidden gems to popular hotspots.',
                'content' => '<p>Planning your next adventure? We\'ve compiled a list of the top travel destinations for 2026 based on popularity, beauty, and unique experiences.</p><p>Each destination offers something special, whether you\'re looking for relaxation, adventure, or cultural immersion.</p>',
                'featured_image' => '/images/travel-destinations-2026.jpg',
                'category_id' => $categoryEntries['travel']->id,
                'author' => 'Sarah Johnson',
                'tags' => 'travel,destinations,adventure,2026',
            ],
            [
                'title' => 'Building a Successful Startup',
                'slug' => 'building-successful-startup',
                'excerpt' => 'Essential tips and strategies for entrepreneurs looking to build and grow a successful startup business.',
                'content' => '<p>Starting a business is challenging, but with the right approach and mindset, you can increase your chances of success. This article covers the fundamental steps every entrepreneur should know.</p><p>From validating your idea to securing funding and scaling your team, we\'ll guide you through the startup journey.</p>',
                'featured_image' => '/images/startup-success.jpg',
                'category_id' => $categoryEntries['business']->id,
                'author' => 'Mike Williams',
                'tags' => 'startup,business,entrepreneurship',
            ],
            [
                'title' => 'Healthy Living: A Beginner\'s Guide',
                'slug' => 'healthy-living-beginners-guide',
                'excerpt' => 'Simple and practical tips to improve your lifestyle and overall well-being.',
                'content' => '<p>Living a healthy lifestyle doesn\'t have to be complicated. Small, consistent changes can lead to significant improvements in your health and happiness.</p><p>In this guide, we\'ll cover nutrition, exercise, sleep, and mental health basics that anyone can incorporate into their daily routine.</p>',
                'featured_image' => '/images/healthy-living.jpg',
                'category_id' => $categoryEntries['lifestyle']->id,
                'author' => 'Emily Davis',
                'tags' => 'health,lifestyle,wellness',
            ],
            [
                'title' => 'Draft: Advanced PHP Patterns',
                'slug' => 'advanced-php-patterns-draft',
                'excerpt' => 'An in-depth look at design patterns and best practices in PHP development.',
                'content' => '<p>This article is still being written. Check back soon for the complete guide to advanced PHP patterns.</p>',
                'featured_image' => null,
                'category_id' => $categoryEntries['technology']->id,
                'author' => 'John Doe',
                'tags' => 'php,patterns,programming',
            ],
        ];

        foreach ($posts as $index => $postData) {
            Entry::create([
                'space_id' => $spaceId,
                'collection_id' => $postsCollection->id,
                'status' => $index === 5 ? 'draft' : 'published', // Last one is a draft
                'published_at' => $index === 5 ? null : now()->subDays(10 - $index),
                'data' => $postData,
            ]);
        }

        $this->command->info("Blog space seeded successfully!");
        $this->command->info("Space ID: {$spaceId}");
        $this->command->info("Created {$categoriesCollection->handle} collection with " . count($categories) . " entries");
        $this->command->info("Created {$postsCollection->handle} collection with " . count($posts) . " entries");
    }
}
