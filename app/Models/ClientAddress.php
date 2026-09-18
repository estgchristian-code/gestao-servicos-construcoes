<?php

namespace App\Models;

use Database\Factories\ClientAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'label', 'street', 'number', 'complement', 'district', 'city', 'state', 'zip', 'reference', 'main'])]
class ClientAddress extends Model
{
    /** @use HasFactory<ClientAddressFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'main' => 'boolean',
        ];
    }

    /**
     * The client this address belongs to.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Guarantee a single main address per client: whenever an address is saved
     * as "main", every other address of the same client is unmarked.
     */
    protected static function booted(): void
    {
        static::saving(function (ClientAddress $address): void {
            if (! $address->main) {
                return;
            }

            static::query()
                ->where('client_id', $address->client_id)
                ->where('id', '!=', $address->id)
                ->update(['main' => false]);
        });
    }
}