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
            'total_rme_semester_1' => $this['total_rme_semester_1'] ?? 0,
            'total_rme_semester_2' => $this['total_rme_semester_2'] ?? 0,
            'target_jp_semester_1' => $this['target_jp_semester_1'] ?? 0,
            'target_jp_semester_2' => $this['target_jp_semester_2'] ?? 0,
            'kalender_bulanan' => $this['kalender_bulanan'] ?? [],
            'list_cp' => $this['list_cp'],
            'saved_prosem' => $this['saved_prosem'],
            'saved_atp' => $this['saved_atp'] ?? []
        ];
    }
}
