<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VenueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'city' => $this->city,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'featured_image' => $this->featured_image ? Storage::url($this->featured_image) : null,
            'bank_account' => $this->bank_account,
            'commission_rate' => (float) $this->commission_rate,
            'status' => $this->status,
            'operating_hours' => VenueOperatingHourResource::collection($this->whenLoaded('operatingHours')),
            'courts' => CourtResource::collection($this->whenLoaded('courts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
