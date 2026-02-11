<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class RevisionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_revisions_list_and_restore(): void
    {
        $this->seed(\Database\Seeders\System\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@x.com',
            'password' => Hash::make('secret123'),
        ]);

        $space = \App\Models\Space::create([
            'handle' => 'main',
            'name' => 'Main',
            'settings' => [],
            'storage_prefix' => 'main',
        ]);

        $colPerms = ['posts.create','posts.read','posts.update','posts.delete','posts.publish'];
        foreach ($colPerms as $p) {
            Permission::findOrCreate($p, 'sanctum');
        }
        $user->givePermissionTo($colPerms);

        $this->actingAs($user, 'sanctum');

        $col = \App\Models\Collection::create([
            'space_id' => $space->id,
            'handle' => 'posts',
            'type' => 'collection',
            'fields' => [
                ['id'=>'title','name'=>'title','type'=>'text','required'=>true],
            ],
            'settings' => [],
        ]);

        $entry = \App\Models\Entry::create([
            'space_id' => $space->id,
            'collection_id' => $col->id,
            'status' => 'draft',
            'data' => ['title' => 'Old'],
        ]);

        $resUpdate = $this->putJson("/api/v1/admin/posts/{$entry->id}", [
            'data' => ['title' => 'New'],
        ], [
            'X-Space-Id' => (string) $space->id,
        ]);

        $resUpdate->assertOk();

        $resList = $this->getJson("/api/v1/admin/posts/{$entry->id}/revisions", [
            'X-Space-Id' => (string) $space->id,
        ]);

        $resList->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        ['id','entry_id','diff','created_by','created_at']
                    ]
                ]
            ]);

        $revisionId = (int) $resList->json('data.items.0.id');

        $resRestore = $this->postJson("/api/v1/admin/posts/{$entry->id}/restore", [
            'revision_id' => $revisionId,
        ], [
            'X-Space-Id' => (string) $space->id,
        ]);

        $resRestore->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $entry->refresh();
        $this->assertSame('Old', $entry->data['title']);
    }
}
