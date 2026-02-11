<?php

namespace Database\Seeders\Content;

use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class BlogSpaceSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first admin user or create one
        $admin = User::first() ?? User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Blog Space
        $blogSpace = Space::firstOrCreate(
            ['handle' => 'blog'],
            [
                'name' => 'Blog Space',
                'storage_prefix' => 'spaces/blog',
                'settings' => [
                    'description' => 'Main blog space for articles and content',
                    'locale' => 'en',
                ],
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // Create Categories Collection
        $categoriesCollection = Collection::firstOrCreate(
            ['space_id' => $blogSpace->id, 'handle' => 'categories'],
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

        // Create Tags Collection
        $tagsCollection = Collection::firstOrCreate(
            ['space_id' => $blogSpace->id, 'handle' => 'tags'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'name',
                        'label' => 'Tag Name',
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
                ],
                'settings' => [],
            ]
        );

        // Create Posts Collection
        $postsCollection = Collection::firstOrCreate(
            ['space_id' => $blogSpace->id, 'handle' => 'posts'],
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
                        'required' => true,
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
                        'handle' => 'tag_ids',
                        'label' => 'Tags',
                        'type' => 'json',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'author',
                        'label' => 'Author',
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

        // Create Category Entries
        $techCategory = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $categoriesCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'Technology',
                    'slug' => 'technology',
                    'description' => 'Articles about technology, programming, and innovation',
                ],
            ]
        );

        $designCategory = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $categoriesCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'Design',
                    'slug' => 'design',
                    'description' => 'Articles about design principles, UI/UX, and creativity',
                ],
            ]
        );

        $businessCategory = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $categoriesCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'Business',
                    'slug' => 'business',
                    'description' => 'Articles about business, entrepreneurship, and strategy',
                ],
            ]
        );

        // Create Tag Entries
        $laravelTag = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $tagsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'Laravel',
                    'slug' => 'laravel',
                ],
            ]
        );

        $phpTag = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $tagsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'PHP',
                    'slug' => 'php',
                ],
            ]
        );

        $webDevTag = Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $tagsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now(),
                'data' => [
                    'name' => 'Web Development',
                    'slug' => 'web-development',
                ],
            ]
        );

        // Create Post Entries
        Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $postsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'data' => [
                    'title' => 'Getting Started with Laravel 11',
                    'slug' => 'getting-started-with-laravel-11',
                    'excerpt' => 'Learn the basics of Laravel 11 and how to build modern web applications with this powerful PHP framework.',
                    'content' => '<p>Laravel 11 is the latest version of the popular PHP framework, bringing new features and improvements to help developers build better applications faster.</p><p>In this article, we will explore the key features of Laravel 11 and how to get started with your first project.</p>',
                    'featured_image' => '/images/laravel-11.jpg',
                    'category_id' => $techCategory->id,
                    'tag_ids' => [$laravelTag->id, $phpTag->id, $webDevTag->id],
                    'author' => 'John Doe',
                ],
            ]
        );

        Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $postsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'data' => [
                    'title' => 'Modern Web Design Trends for 2026',
                    'slug' => 'modern-web-design-trends-2026',
                    'excerpt' => 'Discover the latest web design trends that will shape the digital landscape in 2026.',
                    'content' => '<p>Web design is constantly evolving, and staying up-to-date with the latest trends is crucial for creating engaging user experiences.</p><p>In this article, we explore the top design trends that will dominate 2026.</p>',
                    'featured_image' => '/images/design-trends.jpg',
                    'category_id' => $designCategory->id,
                    'tag_ids' => [$webDevTag->id],
                    'author' => 'Jane Smith',
                ],
            ]
        );

        Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $postsCollection->id,
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(1),
                'data' => [
                    'title' => 'Building a Scalable Business Strategy',
                    'slug' => 'building-scalable-business-strategy',
                    'excerpt' => 'Learn how to create a business strategy that can scale with your growth and adapt to changing market conditions.',
                    'content' => '<p>A scalable business strategy is essential for long-term success. It allows your business to grow without compromising quality or efficiency.</p><p>This article covers the key principles of building a scalable strategy.</p>',
                    'featured_image' => '/images/business-strategy.jpg',
                    'category_id' => $businessCategory->id,
                    'tag_ids' => [],
                    'author' => 'Bob Johnson',
                ],
            ]
        );

        // Create a draft post
        Entry::firstOrCreate(
            [
                'space_id' => $blogSpace->id,
                'collection_id' => $postsCollection->id,
            ],
            [
                'status' => 'draft',
                'published_at' => null,
                'data' => [
                    'title' => 'The Future of API Development',
                    'slug' => 'future-of-api-development',
                    'excerpt' => 'Exploring emerging trends in API development and architecture.',
                    'content' => '<p>API development continues to evolve with new standards and best practices emerging regularly.</p>',
                    'featured_image' => '/images/api-development.jpg',
                    'category_id' => $techCategory->id,
                    'tag_ids' => [$webDevTag->id],
                    'author' => 'John Doe',
                ],
            ]
        );

        $this->command->info('Blog Space seeded successfully!');
        $this->command->info("Space ID: {$blogSpace->id}");
        $this->command->info("Collections created: Categories, Tags, Posts");
        $this->command->info("Entries created: 3 categories, 3 tags, 3 published posts, 1 draft post");
    }
}
