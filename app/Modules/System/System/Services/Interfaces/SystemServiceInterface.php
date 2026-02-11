<?php

namespace App\Modules\System\System\Services\Interfaces;

interface SystemServiceInterface
{
    public function health(): array;

    public function info(): array;

    public function stats(): array;
}
