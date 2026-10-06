<?php

namespace App\Http\Controllers;

use App\Models\SortingCenter;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin → Sorting Centers: one BoomBuy Sorting Center per region. Set where a
 * region's center is, close or reopen one, and say which center each
 * logistics account works at.
 */
class SortingCenterController extends Controller
{
    public function index()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $search = trim((string) request('q', ''));

        $centers = SortingCenter::query()
            ->withCount('staff')
            ->get()
            ->sortBy(fn ($c) => array_search($c->region, array_keys(PhLocations::REGIONS), true))
            ->when($search !== '', fn ($list) => $list->filter(fn ($c) => stripos($c->name, $search) !== false
                || stripos($c->town, $search) !== false
                || collect($c->provinces)->contains(fn ($p) => stripos($p, $search) !== false)))
            ->values();

        // Parcels each center is holding or expecting right now.
        $parcelCounts = DB::table('orders')
            ->whereIn('status', ['Dropped Off', 'At Sorting Center', 'In Transit'])
            ->selectRaw('COALESCE(current_center_id, CASE WHEN status = ? THEN destination_center_id ELSE origin_center_id END) as center_id, COUNT(*) as total', ['In Transit'])
            ->groupBy('center_id')
            ->pluck('total', 'center_id');

        $staff = DB::table('users')
            ->where('role', 'logistics')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'sorting_center_id']);

        $allCenters = SortingCenter::get(['id', 'name', 'region'])
            ->sortBy(fn ($c) => array_search($c->region, array_keys(PhLocations::REGIONS), true))
            ->values();

        $openRegions = SortingCenter::where('is_active', true)->whereNotNull('region')->distinct()->pluck('region');

        $stats = [
            'centers' => $openRegions->count(),
            'regions' => count(PhLocations::REGIONS),
            'staff' => $staff->whereNotNull('sorting_center_id')->count(),
        ];

        $regions = PhLocations::REGIONS;
        $provinces = PhLocations::all();

        return view('pages.admin.sorting-centers', compact(
            'centers', 'parcelCounts', 'staff', 'allCenters', 'stats', 'regions', 'provinces', 'search'
        ));
    }

    /**
     * Set up a region's center, or move it: where the building is (a town in
     * that region) and its street address.
     */
    public function store()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = request()->validate([
            'region' => ['required', 'string', Rule::in(array_keys(PhLocations::REGIONS))],
            'province' => ['required', 'string', Rule::in(array_keys(PhLocations::all()))],
            'city_municipality' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if (PhLocations::region($data['province']) !== $data['region']) {
            return back()->withInput()->with('error', $data['province'] . ' is not in ' . PhLocations::regionLabel($data['region']) . '.');
        }

        if (!PhLocations::exists($data['province'], $data['city_municipality'])) {
            return back()->withInput()->with('error', 'Pick a city/municipality from the list for that province.');
        }

        $center = SortingCenter::where('region', $data['region'])->orderBy('id')->first();
        $isNew = !$center;
        $center ??= new SortingCenter(['region' => $data['region'], 'is_active' => true]);

        $center->fill([
            'name' => SortingCenter::nameFor($data['region']),
            'province' => $data['province'],
            'city_municipality' => $data['city_municipality'],
            'address' => trim((string) ($data['address'] ?? '')) ?: $data['city_municipality'] . ', ' . str_replace(' (NCR)', '', $data['province']),
        ])->save();

        return back()->with('success', $isNew
            ? $center->name . ' is open in ' . $center->town . '. It serves ' . implode(', ', $center->provinces) . '.'
            : $center->name . ' is now in ' . $center->town . '.');
    }

    public function toggle($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $center = SortingCenter::findOrFail($id);
        $center->update(['is_active' => !$center->is_active]);

        return back()->with('success', $center->name . ($center->is_active
            ? ' is open again.'
            : ' is closed. New orders in ' . $center->area . ' have no regional center until it reopens — the seller\'s center delivers them, or head office handles them (parcels already there stay).'));
    }

    public function assignStaff($userId)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = request()->validate([
            'sorting_center_id' => ['nullable', 'integer', 'exists:sorting_centers,id'],
        ]);

        $user = DB::table('users')->where('id', $userId)->where('role', 'logistics')->first();

        if (!$user) {
            return back()->with('error', 'Logistics account not found.');
        }

        $centerId = $data['sorting_center_id'] ?? null;

        DB::table('users')->where('id', $userId)->update(['sorting_center_id' => $centerId, 'updated_at' => now()]);

        $where = $centerId ? SortingCenter::whereKey($centerId)->value('name') : 'head office (all centers)';

        createNotification(
            (int) $userId,
            'Sorting Center Assignment',
            'You now work at ' . $where . '. Your Parcels page shows that center\'s parcels.',
            'account_status'
        );

        return back()->with('success', $user->name . ' now works at ' . $where . '.');
    }
}
