<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DismissedBrandSuggestion
 *
 * Przechowuje informacje o sugestiach brandow odrzuconych przez uzytkownika
 * w panelu Cross-Source Matrix. Pozwala na trwale ignorowanie niechcianych
 * sugestii bez ich usuwania z bazy.
 *
 * @property int    $id
 * @property int    $user_id
 * @property int    $shop_id
 * @property string $brand
 *
 * @package App\Models
 */
class DismissedBrandSuggestion extends Model
{
    protected $fillable = ['user_id', 'shop_id', 'brand'];

    /** @return BelongsTo<User, DismissedBrandSuggestion> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sprawdza czy dany brand jest juz odrzucony przez uzytkownika dla sklepu.
     */
    public static function isDismissed(int $userId, int $shopId, string $brand): bool
    {
        return static::where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->where('brand', $brand)
            ->exists();
    }

    /**
     * Odrzuca sugestie brandu (idempotentne - nie duplikuje rekordow).
     */
    public static function dismiss(int $userId, int $shopId, string $brand): void
    {
        static::firstOrCreate([
            'user_id' => $userId,
            'shop_id' => $shopId,
            'brand'   => $brand,
        ]);
    }

    /**
     * Przywraca sugestie brandu (usuwa odrzucenie).
     */
    public static function restore(int $userId, int $shopId, string $brand): void
    {
        static::where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->where('brand', $brand)
            ->delete();
    }

    /**
     * Zwraca liste odrzuconych brandow dla uzytkownika i sklepu.
     *
     * @return array<string>
     */
    public static function getDismissedBrands(int $userId, int $shopId): array
    {
        return static::where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->pluck('brand')
            ->toArray();
    }
}
