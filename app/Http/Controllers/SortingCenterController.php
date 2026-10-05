<?php

namespace App\Http\Controllers;

use App\Models\SortingCenter;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin → Sorting Centers: open a center in a town, switch one off, and say
 * which center each logistics account works at.
 */
class SortingCenterController extends Controller
{
    public function index()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $province = (string) request('province', '');
        $search = trim((string) request('q', ''));

        $centers = SortingCenter::query()
            ->when($province !== '', fn ($q) => $q->where('province', $province))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%' . $search . '%')
                ->orWhere('city_municipality', 'like', '%' . $search . '%')))
            ->withCount('staff')
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get();

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

        $allCenters = SortingCenter::orderBy('province')->orderBy('city_municipality')->get(['id', 'name', 'province']);

        $stats = [
            'centers' => SortingCenter::where('is_active', true)->count(),
            'provinces' => SortingCenter::where('is_active', true)->distinct()->count('province'),
            'staff' => $staff->whereNotNull('sorting_center_id')->count(),
        ];

        $provinces = array_keys(PhLocations::all());
        $usedProvinces = SortingCenter::distinct()->orderBy('province')->pluck('province');

        return view('pages.admin.sorting-centers', compact(
            'centers', 'parcelCounts', 'staff', 'allCenters', 'stats', 'provinces', 'usedProvinces', 'province', 'search'
        ));
    }

    public function store()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = request()->validate([
            'province' => ['required', 'string', Rule::in(array_keys(PhLocations::all()))],
            'city_municipality' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if (!PhLocations::exists($data['province'], $data['city_municipality'])) {
            return back()->withInput()->with('error', 'Pick a city/municipality from the list for that province.');
        }

        $exists = SortingCenter::where('province', $data['province'])->get()
            ->contains(fn ($c) => PhLocations::sameTown($c->city_municipality, $data['city_municipality']));

        if ($exists) {
            return back()->withInput()->with('error', $data['city_municipality'] . ' already has a Sorting Center.');
        }

        $center = SortingCenter::create([
            'name' => $data['city_municipality'] . ' Sorting Center',
            'province' => $data['province'],
            'city_municipality' => $data['city_municipality'],
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', $center->name . ' is open. Orders to and from ' . $center->town . ' now go through it.');
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
            : ' is closed. New orders in ' . $center->town . ' go to another center in ' . $center->province . ' (parcels already there stay).'));
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
