<?php

namespace Tests\Feature;

use App\Modules\System\Realtime\Events\EntryPublishedRealtimeEvent;
use App\Modules\System\Realtime\Events\EntryUpdatedRealtimeEvent;
use App\Modules\System\Realtime\Events\SystemAlertRealtimeEvent;
use Illuminate\Broadcasting\Channel;
use Tests\TestCase;

final class RealtimeContractsTest extends TestCase
{
    public function test_realtime_event_payload_shape(): void
    {
        $e = new EntryPublishedRealtimeEvent(spaceId: 1, collectionHandle: 'posts', entryId: 99);

        $this->assertEquals('entry.published', $e->broadcastAs());
        $payload = $e->broadcastWith();

        $this->assertEquals(1, $payload['space_id']);
        $this->assertEquals('posts', $payload['collection']);
        $this->assertEquals(99, $payload['entry_id']);
    }

    public function test_entry_updated_realtime_event_payload_shape(): void
    {
        $e = new EntryUpdatedRealtimeEvent(spaceId: 1, collectionHandle: 'posts', entryId: 99);

        $this->assertEquals('entry.updated', $e->broadcastAs());
        $payload = $e->broadcastWith();

        $this->assertEquals(1, $payload['space_id']);
        $this->assertEquals('posts', $payload['collection']);
        $this->assertEquals(99, $payload['entry_id']);
    }

    public function test_system_alert_realtime_event_payload_and_channel(): void
    {
        $e = new SystemAlertRealtimeEvent('failed_job', 'Job "ProcessPodcast" failed: Connection refused.', 'https://horizon.example.com/failed');

        $this->assertEquals('system.alert', $e->broadcastAs());

        $channels = $e->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertEquals('admin', $channels[0]->name);

        $payload = $e->broadcastWith();
        $this->assertSame('failed_job', $payload['type']);
        $this->assertSame('Job "ProcessPodcast" failed: Connection refused.', $payload['message']);
        $this->assertSame('https://horizon.example.com/failed', $payload['link']);
    }

    public function test_system_alert_realtime_event_without_link(): void
    {
        $e = new SystemAlertRealtimeEvent('warning', 'Disk space low');

        $payload = $e->broadcastWith();
        $this->assertSame('warning', $payload['type']);
        $this->assertSame('Disk space low', $payload['message']);
        $this->assertArrayNotHasKey('link', $payload);
    }
}
