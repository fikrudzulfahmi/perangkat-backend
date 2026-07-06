<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProsemStructureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Mengembalikan struktur data yang sudah diracik di Service
        return [
            'meta_plotting' => $this['meta_plotting'],
            'total_rme' => $this['total_rme'],
            'jp_per_minggu' => $this['jp_per_minggu'],
            'total_jp_tahunan' => $this['total_jp_tahunan'],
            'list_cp' => $this['list_cp'],
            'saved_prosem' => $this['saved_prosem']
        ];
    }
}
