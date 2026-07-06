<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KktpStructureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'list_cp' => $this['list_cp'],
            'saved_kktp' => $this['saved_kktp']
        ];
    }
}
