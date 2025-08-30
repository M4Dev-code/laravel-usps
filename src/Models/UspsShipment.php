<?php

namespace UspsShipping\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class UspsShipment extends Model
{
    protected $table = 'usps_shipments';

    protected $fillable = [
        'tracking_number',
        'service_type',
        'from_address',
        'to_address',
        'weight',
        'dimensions',
        'cost',
        'label_url',
        'label_base64',
        'status',
        'tracking_events',
        'shipped_at',
        'delivered_at',
        'metadata'
    ];

    protected $casts = [
        'from_address' => 'json',
        'to_address' => 'json',
        'dimensions' => 'json',
        'tracking_events' => 'json',
        'metadata' => 'json',
        'cost' => 'decimal:2',
        'weight' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, ['in_transit', 'out_for_delivery']);
    }

    public function getEstimatedDeliveryAttribute(): ?string
    {
        if (!$this->tracking_events) {
            return null;
        }

        foreach ($this->tracking_events as $event) {
            if (isset($event['estimatedDeliveryDate'])) {
                return $event['estimatedDeliveryDate'];
            }
        }

        return null;
    }

    public function getFormattedWeightAttribute(): string
    {
        return $this->weight . ' lbs';
    }

    public function getServiceNameAttribute(): string
    {
        $services = config('usps.services');
        return $services[$this->service_type]['name'] ?? $this->service_type;
    }
}
