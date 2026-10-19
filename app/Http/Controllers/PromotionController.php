<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSale;
use App\Support\ProductRules;
use Illuminate\Support\Carbon;

/**
 * Seller → Promotions: store sales on a product for a set time (S6).
 * Seller-funded: the buyer pays the sale price, the seller earns from it.
 */
class PromotionController extends Controller
{
    public function index()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];

        return view('pages.seller.promotions', [
            'user' => $user,
            'products' => Product::where('seller_id', $sellerId)->where('is_draft', false)->orderBy('name')->get(),
            'sales' => ProductSale::where('seller_id', $sellerId)->with('product.variations')->orderByDesc('starts_at')->get(),
            'flashSales' => class_exists(\App\Models\FlashSale::class) ? \App\Models\FlashSale::where('seller_id', $sellerId)->with('product')->orderByDesc('starts_at')->get() : collect(),
            'bundles' => class_exists(\App\Models\Bundle::class) ? \App\Models\Bundle::where('seller_id', $sellerId)->orderByDesc('id')->get() : collect(),
        ]);
    }

    public function storeSale()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('seller_id', $user['id'])->where('is_draft', false)->find((int) request('product_id'));

        if (!$product) {
            return back()->withInput()->withErrors(['product_id' => 'Choose one of your published products.']);
        }

        [$start, $end, $errors] = $this->dates();
        $errors += $this->discountErrors($product);

        if (!$errors && $start && $end && $this->overlaps($product->id, $start, $end)) {
            $errors['starts_at'] = $product->name . ' already has a sale in that period. A product can be in one store sale at a time.';
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $sale = ProductSale::create([
            'seller_id' => $user['id'],
            'product_id' => $product->id,
            'discount_type' => request('discount_type'),
            'discount_value' => (float) ProductRules::cleanNumber(request('discount_value')),
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        return redirect()->route('seller.promotions')->with('success', 'Sale on ' . $product->name . ' saved: ' . $sale->status() . ', '
            . $start->format('M d, g:i A') . ' to ' . $end->format('M d, g:i A') . '.');
    }

    /**
     * Change a sale's discount (a running one applies to new orders at
     * once; orders already placed keep their price) or, before it starts, its times.
     */
    public function updateSale($id)
    {
        [$user, $sale] = $this->ownSale($id);

        if (!$sale) {
            return $user;
        }

        if ($sale->status() === 'Ended') {
            return back()->with('error', 'This sale has ended and can no longer be changed.');
        }

        $product = $sale->product;
        $errors = $this->discountErrors($product, $sale->discount_type);
        $columns = ['discount_value' => (float) ProductRules::cleanNumber(request('discount_value'))];

        if ($sale->status() === 'Upcoming' && request()->filled('starts_at')) {
            [$start, $end, $dateErrors] = $this->dates();
            $errors += $dateErrors;

            if (!$dateErrors && $this->overlaps($product->id, $start, $end, $sale->id)) {
                $errors['starts_at'] = $product->name . ' already has a sale in that period.';
            }

            $columns += ['starts_at' => $start, 'ends_at' => $end];
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $sale->update($columns);

        return back()->with('success', 'Sale updated. New orders get the new price right away.');
    }

    public function endSale($id)
    {
        [$user, $sale] = $this->ownSale($id);

        if (!$sale) {
            return $user;
        }

        if ($sale->status() !== 'Ended') {
            $sale->update($sale->status() === 'Upcoming' ? ['ended_at' => now(), 'starts_at' => now()->subSecond()] : ['ended_at' => now()]);
        }

        return back()->with('success', 'Sale ended. Buyers see the regular price again.');
    }

    /** [start, end, errors] from the form. Start in the future, end after it. */
    private function dates(): array
    {
        $errors = [];
        $start = $this->time(request('starts_at'));
        $end = $this->time(request('ends_at'));

        if (!$start) {
            $errors['starts_at'] = 'Choose when the sale starts.';
        } elseif ($start->lte(now())) {
            $errors['starts_at'] = 'The start time must be in the future.';
        }

        if (!$end) {
            $errors['ends_at'] = 'Choose when the sale ends.';
        } elseif ($start && $end->lte($start)) {
            $errors['ends_at'] = 'The end time must be after the start time.';
        }

        return [$start, $end, $errors];
    }

    private function time(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse((string) $value)->seconds(0) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Percent: a whole number 1–99. Fixed: ₱1.00 up to below the regular price (products without options). */
    private function discountErrors(Product $product, ?string $type = null): array
    {
        $type ??= (string) request('discount_type');
        $value = ProductRules::cleanNumber(request('discount_value'));

        if ($type === 'percent') {
            if (!preg_match('/^\d+$/', $value) || (int) $value < 1 || (int) $value > 99) {
                return ['discount_value' => 'The discount must be a whole number from 1% to 99%.'];
            }

            return [];
        }

        if ($type === 'fixed') {
            if ($product->variations()->exists()) {
                return ['discount_type' => 'Products with options use a % discount, so every option gets the same cut.'];
            }

            $regular = $product->regularUnitPrice();

            if ($error = ProductRules::priceError($value, 'Sale price')) {
                return ['discount_value' => $error];
            }

            if ((float) $value >= $regular) {
                return ['discount_value' => 'The sale price must be lower than the regular price of ₱' . number_format($regular, 2) . '.'];
            }

            return [];
        }

        return ['discount_type' => 'Choose a % discount or a fixed sale price.'];
    }

    /** Another sale of this product (not ended) overlapping [start, end). */
    private function overlaps(int $productId, Carbon $start, Carbon $end, ?int $exceptId = null): bool
    {
        return ProductSale::where('product_id', $productId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get()
            ->contains(fn (ProductSale $sale) => $sale->starts_at->lt($end) && $sale->stopsAt()->gt($start));
    }

    private function ownSale($id): array
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return [$user, null];
        }

        $sale = ProductSale::where('seller_id', $user['id'])->find((int) $id);

        if (!$sale) {
            abort(404);
        }

        return [$user, $sale];
    }
}
