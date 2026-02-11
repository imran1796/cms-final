<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\System\Realtime\Services\Interfaces\LockServiceInterface;

final class LockServiceTest extends TestCase
{
    public function test_lock_acquire_and_release(): void
    {
        $locks = app(LockServiceInterface::class);

        $key = 'test:lock:1';

        $this->assertTrue($locks->acquire($key, 2));
        $this->assertFalse($locks->acquire($key, 2)); // should be locked

        $locks->release($key);

        $this->assertTrue($locks->acquire($key, 2));
    }
}
