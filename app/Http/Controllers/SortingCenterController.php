<?php

namespace App\Http\Controllers;

use App\Models\SortingCenter;
use App\Support\PhCoordinates;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin → Sorting Centers: one BoomBuy Sorting Center per province. Set where
 * a province's center is, close or reopen one, and say which center each
 * logistics account works at.
 */
class SortingCenterController extends Controller
{
    /** The staff select's value for "Head office (all centers)". */
    public const HEAD_OFFICE = 'head-office';

    public function index()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $search = trim((string) request('q', ''));

        // By region (north to south), then province.
        $regionOrder = array_flip(array_keys(PhLocations::REGIONS));
        $sortKey = fn ($c) => sprintf('%02d', $regionOrder[$c->region] ?? 99) . $c->province;

        // Every center is on the page; the search box filters the rows as
        // you type. ?q= only decides which rows start out shown.
        $centers = SortingCenter::centers()
            ->withCount('staff')
            ->get()
            ->sortBy($sortKey)
            ->each(function ($c) use ($search) {
                $c->search_text = mb_strtolower(implode(' ', [
                    $c->name, $c->town, $c->province, $c->address, PhLocations::regionLabel($c->region),
                ]));
                $c->matches_search = $search === '' || str_contains($c->search_text, mb_strtolower($search));
            })
            ->values();

        // Parcels each center is holding or expecting right now.
        $parcelCounts = DB::table('orders')
            ->whereIn('status', ['Picked Up', 'Dropped Off', 'Received at Branch', 'To Sorting Center', 'At Sorting Center', 'In Transit', 'Sorted', 'To Branch', 'At Branch'])
            ->selectRaw('COALESCE(current_center_id, CASE WHEN status = ? THEN destination_center_id ELSE origin_center_id END) as center_id, COUNT(*) as total', ['In Transit'])
            ->groupBy('center_id')
            ->pluck('total', 'center_id');

        $staff = DB::table('users')
            ->where('role', 'logistics')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'sorting_center_id', 'is_head_office']);

        $allCenters = SortingCenter::get(['id', 'parent_id', 'name', 'region', 'province'])->sortBy(fn ($c) => $sortKey($c) . ($c->parent_id ? '1' : '0') . $c->name)->values();

        // Delivery branches: the center trucks a town's parcels there, and
        // the branch's riders deliver them.
        $branches = SortingCenter::whereNotNull('parent_id')
            ->with('parent:id,name')
            ->withCount('staff')
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get();

        $stats = [
            'centers' => SortingCenter::centers()->where('is_active', true)->distinct()->count('province'),
            'provinces' => count(PhLocations::all()),
            'staff' => $staff->whereNotNull('sorting_center_id')->count(),
        ];

        // One pin per center on the map, at its province.
        $mapPins = SortingCenter::centers()->get(['id', 'name', 'province', 'city_municipality', 'is_active'])
            ->map(function ($center) use ($parcelCounts) {
                $point = PhCoordinates::forProvince($center->province);

                return $point ? [
                    'name' => $center->name,
                    'town' => $center->town,
                    'lat' => $point[0],
                    'lng' => $point[1],
                    'parcels' => (int) ($parcelCounts[$center->id] ?? 0),
                    'open' => (bool) $center->is_active,
                ] : null;
            })
            ->filter()
            ->values();

        $regions = PhLocations::REGIONS;
        $provinces = PhLocations::all();

        return view('pages.admin.sorting-centers', compact(
            'centers', 'parcelCounts', 'staff', 'allCenters', 'branches', 'stats', 'regions', 'provinces', 'search', 'mapPins'
        ));
    }

    /**
     * Set up a province's center, or move it: where the building is (a town
     * in that province) and its street address. (The region only narrows the
     * province list on the form.)
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

        $center = SortingCenter::centers()->where('province', $data['province'])->orderBy('id')->first();
        $isNew = !$center;
        $center ??= new SortingCenter(['province' => $data['province'], 'is_active' => true]);

        $center->fill([
            'name' => SortingCenter::nameFor($data['province']),
            'region' => $data['region'],
            'city_municipality' => $data['city_municipality'],
            'address' => trim((string) ($data['address'] ?? '')) ?: $data['city_municipality'] . ', ' . PhLocations::provinceLabel($data['province']),
        ])->save();

        return back()->with('success', $isNew
            ? $center->name . ' is open in ' . $center->town . '. It serves every town in ' . $center->area . '.'
            : $center->name . ' is now in ' . $center->town . '.');
    }

    /**
     * Open a delivery branch of a province's center in one of its towns.
     * Parcels for that town go there once it has staff to receive them.
     */
    public function storeBranch()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = request()->validate([
            'parent_id' => ['required', 'integer', 'exists:sorting_centers,id'],
            'city_municipality' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $center = SortingCenter::centers()->find($data['parent_id']);

        if (!$center) {
            return back()->withInput()->with('error', 'Pick a province\'s Sorting Center for the branch.');
        }

        if (!PhLocations::exists($center->province, $data['city_municipality'])) {
            return back()->withInput()->with('error', 'Pick a town in ' . $center->area . ' for the branch.');
        }

        $taken = SortingCenter::where('parent_id', $center->id)->get()
            ->first(fn ($b) => PhLocations::sameTown($b->city_municipality, $data['city_municipality']));

        if ($taken) {
            return back()->withInput()->with('error', $data['city_municipality'] . ' already has ' . $taken->name . '.');
        }

        $branch = SortingCenter::create([
            'parent_id' => $center->id,
            'name' => SortingCenter::branchNameFor($data['city_municipality']),
            'region' => $center->region,
            'province' => $center->province,
            'city_municipality' => $data['city_municipality'],
            'address' => trim((string) ($data['address'] ?? '')) ?: $data['city_municipality'] . ', ' . PhLocations::provinceLabel($center->province),
            'is_active' => true,
        ]);

        return back()->with('success', $branch->name . ' is open. Assign its staff below — parcels for ' . $branch->town . ' go there once it has staff.');
    }

    public function toggle($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $center = SortingCenter::findOrFail($id);
        $center->update(['is_active' => !$center->is_active]);

        if ($center->isBranch()) {
            return back()->with('success', $center->name . ($center->is_active
                ? ' is open again.'
                : ' is closed. New parcels for ' . $center->town . ' are delivered from ' . ($center->parent->name ?? 'the Sorting Center') . ' (parcels already there stay).'));
        }

        return back()->with('success', $center->name . ($center->is_active
            ? ' is open again.'
            : ' is closed. New orders in ' . $center->area . ' have no Sorting Center until it reopens — the seller\'s center delivers them, or head office handles them (parcels already there stay).'));
    }

    public function assignStaff($userId)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // A center's id, "head-office" (every center), or empty (not assigned: no access).
        $choice = (string) request('sorting_center_id', '');
        $headOffice = $choice === self::HEAD_OFFICE;

        if (!$headOffice && $choice !== '') {
            request()->validate(['sorting_center_id' => ['integer', 'exists:sorting_centers,id']]);
        }

        $user = DB::table('users')->where('id', $userId)->where('role', 'logistics')->first();

        if (!$user) {
            return back()->with('error', 'Logistics account not found.');
        }

        $centerId = !$headOffice && $choice !== '' ? (int) $choice : null;

        DB::table('users')->where('id', $userId)->update([
            'sorting_center_id' => $centerId,
            'is_head_office' => $headOffice,
            'updated_at' => now(),
        ]);

        if (!$centerId && !$headOffice) {
            return back()->with('success', $user->name . ' is no longer assigned to a Sorting Center and can\'t handle parcels until you assign one.');
        }

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
