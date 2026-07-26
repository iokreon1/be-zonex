<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'booking_code' => $this->booking_code,
            'venue_id' => $this->venue_id,
            'court_id' => $this->court_id,
            'user_id' => $this->user_id,
            'booking_date' => $this->booking_date,
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'midtrans_order_id' => $this->midtrans_order_id,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'court' => new CourtResource($this->whenLoaded('court')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
        ];
    }
}
