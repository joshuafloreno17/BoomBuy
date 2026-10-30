<?php

namespace App\Support;

use App\Models\PlatformSetting;
use App\Models\Product;

/**
 * Seller compliance: a product whose name or description mentions a
 * prohibited item is flagged (hidden from the Shop) the moment it is saved,
 * and waits for the admin on Compliance. The word list is edited in
 * Admin → Settings; a new word is also checked against products already listed.
 *
 * Words match whole words only, so "gun" would not hit "Gundam" — but it
 * would hit "glue gun", which is why the default list avoids such words.
 */
class ProhibitedItems
{
    public const SETTING = 'prohibited_words';

    public const DEFAULT = [
        'firearm', 'handgun', 'pistol', 'rifle', 'shotgun', 'revolver', 'ammunition', 'ammo', 'bullets',
        'explosive', 'grenade', 'dynamite', 'gunpowder',
        'shabu', 'marijuana', 'cannabis', 'cocaine', 'heroin', 'methamphetamine', 'ecstasy pills',
        'counterfeit', 'replica', 'class a', 'fake id', 'fake passport',
        'ivory', 'pangolin', 'human organ', 'stolen',
    ];

    /** @return string[] lower-case, one entry per word or phrase */
    public static function words(): array
    {
        $saved = PlatformSetting::get(self::SETTING, '');

        return $saved === '' ? self::DEFAULT : self::parse($saved);
    }

    /** One per line or comma-separated, as typed in Settings. */
    public static function parse(string $text): array
    {
        return collect(preg_split('/[\r\n,]+/', mb_strtolower($text)))
            ->map(fn ($w) => trim(preg_replace('/\s+/', ' ', $w)))
            ->filter(fn ($w) => $w !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** The first listed word found in the texts, or null. */
    public static function find(array $words, ?string ...$texts): ?string
    {
        $haystack = mb_strtolower(implode(' ', array_filter($texts)));

        foreach ($words as $word) {
            $pattern = '/(?<![\p{L}\p{N}])' . str_replace(' ', '\s+', preg_quote($word, '/')) . '(?![\p{L}\p{N}])/u';

            if (preg_match($pattern, $haystack)) {
                return $word;
            }
        }

        return null;
    }

    /**
     * Flags the product if it mentions a listed word. Drafts and products
     * already flagged are left alone. Returns the word that matched.
     */
    public static function screen(Product $product, ?array $words = null): ?string
    {
        if ($product->is_flagged || $product->is_draft) {
            return null;
        }

        $word = self::find($words ?? self::words(), $product->name, $product->description);

        if ($word === null) {
            return null;
        }

        $product->update([
            'is_flagged' => true,
            'flag_reason' => 'Auto-flagged: the listing mentions "' . $word . '", a prohibited item. An admin will review it.',
        ]);

        if ($product->seller_id) {
            createNotification(
                $product->seller_id,
                'Product Flagged: ' . $product->name,
                'Your product "' . $product->name . '" was automatically hidden from the Shop because it mentions "' . $word . '", which is not allowed on BoomBuy. Edit the listing or wait for the admin to review it.',
                'compliance_warning',
                $product->id
            );
        }

        if ($adminId = SupportAccount::id()) {
            createNotification(
                $adminId,
                'Product Auto-flagged',
                '"' . $product->name . '" was hidden automatically: it mentions "' . $word . '". Review it on Seller Compliance.',
                'compliance_warning',
                $product->id
            );
        }

        return $word;
    }

    /**
     * Checks listed products against words the admin just added. Only the
     * new words, so a product the admin already cleared isn't flagged again.
     *
     * @return int products flagged
     */
    public static function scanFor(array $newWords): int
    {
        if ($newWords === []) {
            return 0;
        }

        $flagged = 0;

        Product::where('is_flagged', false)->where('is_draft', false)
            ->select('id', 'seller_id', 'name', 'description', 'is_flagged', 'is_draft')
            ->chunkById(200, function ($products) use ($newWords, &$flagged) {
                foreach ($products as $product) {
                    if (self::screen($product, $newWords) !== null) {
                        $flagged++;
                    }
                }
            });

        return $flagged;
    }
}
