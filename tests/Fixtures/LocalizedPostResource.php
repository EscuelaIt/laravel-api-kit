<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

class LocalizedPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->title,
            'created_at_es' => $this->created_at->locale('es')->isoFormat('LL'),
            'created_at_en' => $this->created_at->locale('en')->isoFormat('LL'),
        ];
    }
}
