<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoBannerRequest;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Support\SellerShop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Admin → Promo Banners: what the landing page and buyer home show at the top. */
class PromoBannerController extends Controller
{
    public function index()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $banners = PromoBanner::orderByRaw("CASE placement WHEN 'main' THEN 0 WHEN 'side_top' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Editing: the same page, with the form filled in.
        $editing = request('edit') ? PromoBanner::find((int) request('edit')) : null;

        $products = Product::onSale()->orderBy('name')->get(['id', 'name', 'seller_id']);
        $shops = SellerShop::many($products->pluck('seller_id'))->sortBy('name');

        return view('pages.admin.banners', compact('banners', 'editing', 'products', 'shops'));
    }

    public function store(PromoBannerRequest $request)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $banner = new PromoBanner($request->bannerData() + ['is_active' => true]);

        if ($request->hasFile('image')) {
            $banner->image = $this->storeImage($request->file('image'));
        }

        $banner->save();

        return redirect()->route('admin.banners')->with('success', 'Banner "' . $banner->title . '" saved. ' . $this->statusNote($banner));
    }

    public function update(PromoBannerRequest $request, int $id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $banner = PromoBanner::find($id);

        if (!$banner) {
            return redirect()->route('admin.banners')->with('error', 'Banner not found.');
        }

        $banner->fill($request->bannerData());

        if ($request->hasFile('image')) {
            $this->deleteImage($banner->image);
            $banner->image = $this->storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($banner->image);
            $banner->image = null;
        }

        $banner->save();

        return redirect()->route('admin.banners')->with('success', 'Banner "' . $banner->title . '" updated. ' . $this->statusNote($banner));
    }

    public function toggle(int $id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $banner = PromoBanner::find($id);

        if (!$banner) {
            return back()->with('error', 'Banner not found.');
        }

        $banner->update(['is_active' => !$banner->is_active]);

        return back()->with('success', 'Banner "' . $banner->title . '" is now ' . ($banner->is_active ? 'on' : 'off') . '.');
    }

    public function destroy(int $id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $banner = PromoBanner::find($id);

        if ($banner) {
            $this->deleteImage($banner->image);
            $banner->delete();
        }

        return redirect()->route('admin.banners')->with('success', 'Banner deleted.');
    }

    private function storeImage(UploadedFile $file): string
    {
        // Extension from the file's real content, never the client's name.
        return $file->storeAs('promo-banners', 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file->extension(), 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function statusNote(PromoBanner $banner): string
    {
        return match ($banner->status()) {
            'Live' => 'It is live now.',
            'Scheduled' => 'It goes live on ' . $banner->starts_at->format('M j, Y') . '.',
            'Ended' => 'Its end date has passed, so it is not shown.',
            default => 'It is switched off.',
        };
    }
}
