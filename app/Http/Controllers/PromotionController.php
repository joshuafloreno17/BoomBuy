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

    /**
     * A flash sale for one product (without options) in one of today's or
     * a later day's 2-hour slots: a flash price below the regular price and
     * a flash quantity no more than the stock.
     */
    public function storeFlash()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $product = Product::where('seller_id', $user['id'])->where('is_draft', false)->find((int) request('product_id'));

        if (!$product) {
            return back()->withInput()->withErrors(['product_id' => 'Choose one of your published products.']);
        }

        $errors = [];

        if ($product->variations()->exists()) {
            $errors['product_id'] = 'Flash sales are for products without options.';
        }

        $slot = \App\Models\FlashSale::SLOTS[(int) request('slot', -1)] ?? null;
        $day = $this->time(request('date'))?->startOfDay();

        if (!$slot || !$day) {
            $errors['slot'] = 'Choose a day and a time slot.';
        } else {
            $start = $day->copy()->setTime($slot[0], 0);
            $end = $slot[1] === 24 ? $day->copy()->addDay()->startOfDay() : $day->copy()->setTime($slot[1], 0);

            if ($end->lte(now())) {
                $errors['slot'] = 'That time slot is already over.';
            } elseif (\App\Models\FlashSale::where('product_id', $product->id)->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                $errors['slot'] = $product->name . ' already has a flash sale in that slot.';
            }
        }

        $price = ProductRules::cleanNumber(request('flash_price'));
        $regular = $product->regularUnitPrice();

        if ($error = ProductRules::priceError($price, 'Flash price')) {
            $errors['flash_price'] = $error;
        } elseif ((float) $price >= $regular) {
            $errors['flash_price'] = 'The flash price must be lower than the regular price of ₱' . number_format($regular, 2) . '.';
        }

        $quantity = ProductRules::cleanNumber(request('quantity'));

        if (!preg_match('/^\d+$/', $quantity) || (int) $quantity < 1 || (int) $quantity > (int) $product->stock) {
            $errors['quantity'] = 'The flash quantity must be from 1 to the stock you have (' . (int) $product->stock . ').';
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $flash = \App\Models\FlashSale::create([
            'seller_id' => $user['id'],
            'product_id' => $product->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'flash_price' => round((float) $price, 2),
            'quantity' => (int) $quantity,
        ]);

        return redirect()->route('seller.promotions')->with('success', 'Flash sale on ' . $product->name . ' saved: ' . $flash->status() . ', '
            . $start->format('M d, g:i A') . ' – ' . $end->format('g:i A') . ', ₱' . number_format($flash->flash_price, 2) . ' for ' . $flash->quantity . ' units.');
    }

    /** A bundle deal: any N items of some products for X% off, or buy X get Y free. */
    public function storeBundle()
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $sellerId = (int) $user['id'];
        $errors = [];
        $type = request('type') === 'bxgy' ? 'bxgy' : 'bundle';
        $own = fn ($id) => Product::where('seller_id', $sellerId)->where('is_draft', false)->find((int) $id);
        $name = trim((string) request('name'));

        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Give the deal a name (up to 80 characters).';
        }

        if ($type === 'bundle') {
            $products = Product::where('seller_id', $sellerId)->whereIn('id', array_map('intval', (array) request('product_ids', [])))->pluck('id');

            if ($products->isEmpty()) {
                $errors['product_ids'] = 'Choose the products that count toward the bundle.';
            }

            if (!ctype_digit((string) request('min_quantity')) || (int) request('min_quantity') < 2) {
                $errors['min_quantity'] = 'A bundle needs at least 2 items.';
            }

            if (!ctype_digit((string) request('percent')) || (int) request('percent') < 1 || (int) request('percent') > 99) {
                $errors['percent'] = 'The bundle discount must be a whole number from 1% to 99%.';
            }
        } else {
            if (!$own(request('buy_product_id'))) {
                $errors['buy_product_id'] = 'Choose the product to buy.';
            }

            if (!$own(request('get_product_id'))) {
                $errors['get_product_id'] = 'Choose the free product.';
            }

            foreach (['buy_quantity' => 'buy', 'get_quantity' => 'get free'] as $field => $label) {
                if (!ctype_digit((string) request($field)) || (int) request($field) < 1) {
                    $errors[$field] = 'Enter how many to ' . $label . ' (at least 1).';
                }
            }
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $bundle = \App\Models\Bundle::create($type === 'bundle'
            ? ['seller_id' => $sellerId, 'name' => $name, 'type' => 'bundle', 'min_quantity' => (int) request('min_quantity'), 'percent' => (int) request('percent')]
            : ['seller_id' => $sellerId, 'name' => $name, 'type' => 'bxgy', 'buy_product_id' => (int) request('buy_product_id'), 'buy_quantity' => (int) request('buy_quantity'),
                'get_product_id' => (int) request('get_product_id'), 'get_quantity' => (int) request('get_quantity')]);

        if ($type === 'bundle') {
            $bundle->products()->sync($products->all());
        }

        return redirect()->route('seller.promotions')->with('success', 'Deal saved: ' . $bundle->fresh()->summary() . '.');
    }

    public function toggleBundle($id)
    {
        $user = requireUserRole('seller');

        if (!is_array($user)) {
            return $user;
        }

        $bundle = \App\Models\Bundle::where('seller_id', $user['id'])->find((int) $id);

        if (!$bundle) {
            abort(404);
        }

        $bundle->update(['is_active' => !$bundle->is_active]);

        return back()->with('success', $bundle->is_active ? 'Deal turned on.' : 'Deal turned off.');
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
