<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EntriesAdminListParamsTest extends TestCase
{
    use RefreshDatabase;

    private Space $space;
    private User $admin;
    private int $collectionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->space = Space::query()->create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'spaces/main',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
        $this->admin->givePermissionTo('posts.read');

        $coll = Collection::query()->create([
            'space_id' => $this->space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id' => 'f1', 'handle' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['id' => 'f2', 'handle' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => false],
            ],
            'settings' => [],
        ]);
        $this->collectionId = $coll->id;
    }

    public function test_list_accepts_status_filter_draft(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft Post', 'slug' => 'draft-post'],
            'title' => 'Draft Post',
            'slug' => 'draft-post',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'published',
            'published_at' => now(),
            'data' => ['title' => 'Published Post', 'slug' => 'published-post'],
            'title' => 'Published Post',
            'slug' => 'published-post',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?status=draft', ['X-Space-Id' => (string) $this->space->id]);

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertSame('draft', $data[0]['status']);
    }

    public function test_list_accepts_status_filter_published(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft Post', 'slug' => 'draft-post'],
            'title' => 'Draft Post',
            'slug' => 'draft-post',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'published',
            'published_at' => now(),
            'data' => ['title' => 'Published Post', 'slug' => 'published-post'],
            'title' => 'Published Post',
            'slug' => 'published-post',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?status=published', ['X-Space-Id' => (string) $this->space->id]);

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertSame('published', $data[0]['status']);
    }

    public function test_list_accepts_status_filter_scheduled(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'scheduled',
            'published_at' => now()->addDay(),
            'data' => ['title' => 'Scheduled Post', 'slug' => 'scheduled-post'],
            'title' => 'Scheduled Post',
            'slug' => 'scheduled-post',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft No Date', 'slug' => 'draft-no-date'],
            'title' => 'Draft No Date',
            'slug' => 'draft-no-date',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?status=scheduled', ['X-Space-Id' => (string) $this->space->id]);

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertSame('scheduled', $data[0]['status']);
        $this->assertNotNull($data[0]['published_at']);
    }

    public function test_list_accepts_status_filter_all(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft', 'slug' => 'draft'],
            'title' => 'Draft',
            'slug' => 'draft',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'published',
            'published_at' => now(),
            'data' => ['title' => 'Published', 'slug' => 'published'],
            'title' => 'Published',
            'slug' => 'published',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?status=all', ['X-Space-Id' => (string) $this->space->id]);

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(2, $data);
    }

    public function test_list_accepts_status_filter_archived(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'archived',
            'published_at' => null,
            'data' => ['title' => 'Archived Post', 'slug' => 'archived-post'],
            'title' => 'Archived Post',
            'slug' => 'archived-post',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Draft Post', 'slug' => 'draft-post'],
            'title' => 'Draft Post',
            'slug' => 'draft-post',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?status=archived', ['X-Space-Id' => (string) $this->space->id]);

        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertSame('archived', $data[0]['status']);
    }

    public function test_list_accepts_sort_param(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'First', 'slug' => 'first'],
            'title' => 'First',
            'slug' => 'first',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Second', 'slug' => 'second'],
            'title' => 'Second',
            'slug' => 'second',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?sort=id', ['X-Space-Id' => (string) $this->space->id]);
        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(2, $data);
        $this->assertLessThan($data[1]['id'], $data[0]['id']); // ascending id: first < second
    }

    public function test_list_accepts_search_param(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'UniqueFooTitle', 'slug' => 'unique-foo'],
            'title' => 'UniqueFooTitle',
            'slug' => 'unique-foo',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Other', 'slug' => 'other'],
            'title' => 'Other',
            'slug' => 'other',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?search=Foo', ['X-Space-Id' => (string) $this->space->id]);
        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Foo', $data[0]['title'] ?? $data[0]['data']['title'] ?? '');
    }

    public function test_list_accepts_filter_param_on_data(): void
    {
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Match', 'slug' => 'match', 'category' => 'news'],
            'title' => 'Match',
            'slug' => 'match',
        ]);
        Entry::query()->create([
            'space_id' => $this->space->id,
            'collection_id' => $this->collectionId,
            'status' => 'draft',
            'published_at' => null,
            'data' => ['title' => 'Other', 'slug' => 'other', 'category' => 'blog'],
            'title' => 'Other',
            'slug' => 'other',
        ]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?filter[category]=news', ['X-Space-Id' => (string) $this->space->id]);
        $res->assertOk()->assertJsonPath('success', true);
        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertSame('news', $data[0]['data']['category'] ?? null);
    }

    public function test_list_accepts_per_page_and_page(): void
    {
        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/v1/admin/posts?per_page=5&page=1', ['X-Space-Id' => (string) $this->space->id]);
        $res->assertOk()->assertJsonPath('success', true);
        $this->assertSame(5, $res->json('data.per_page'));
        $this->assertSame(1, $res->json('data.current_page'));
    }
}
