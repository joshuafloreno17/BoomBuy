<?php

namespace App\Http\Controllers;

use App\Models\SortingCenter;
use App\Support\PhLocations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Logistics → Branches: a province's Sorting Center opens the branches in
 * its towns and gives each its staff. The center trucks a town's parcels to
 * its branch, whose riders deliver them; pickup riders bring a seller's
 * parcels to the branch in the seller's town first.
 *
 * Head office manages every center's branches; a branch's own staff don't
 * see this page.
 */
class LogisticsBranchController extends Controller
{
    public function index()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        [$center, $denied] = $this->center($user);

        if ($denied) {
            return $denied;
        }

        $centerIds = $center ? [$center->id] : SortingCenter::centers()->pluck('id')->all();

        $branches = SortingCenter::whereIn('parent_id', $centerIds)
            ->with('parent:id,name')
            ->withCount('staff')
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->get();

        $staff = DB::table('users')
            ->where('role', 'logistics')
            ->whereIn('sorting_center_id', $branches->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'status', 'sorting_center_id'])
            ->groupBy('sorting_center_id');

        // Parcels each branch has now: on the truck to it, or there (pick-ups included).
        $parcelCounts = DB::table('orders')
            ->whereIn('status', ['Received at Branch', 'To Branch', 'At Branch', 'Ready to Collect'])
            ->selectRaw("CASE WHEN status = 'To Branch' THEN branch_id ELSE current_center_id END as branch, COUNT(*) as total")
            ->groupBy('branch')
            ->pluck('total', 'branch');

        $centers = $center ? collect([$center]) : SortingCenter::centers()->orderBy('province')->get();

        return view('pages.logistics.branches', compact('user', 'center', 'centers', 'branches', 'staff', 'parcelCounts'));
    }

    public function store()
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        [$myCenter, $denied] = $this->center($user);

        if ($denied) {
            return $denied;
        }

        $data = request()->validate([
            'parent_id' => [$myCenter ? 'nullable' : 'required', 'integer'],
            'city_municipality' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $center = $myCenter ?? SortingCenter::centers()->find($data['parent_id'] ?? 0);

        if (!$center) {
            return back()->withInput()->with('error', 'Pick the Sorting Center the branch belongs to.');
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

        return back()->with('success', $branch->name . ' is open. Add its staff — parcels for ' . $branch->town . ' go there once it has staff.');
    }

    /** Make a staff account for a branch. They log in with this email and password. */
    public function storeStaff($branchId)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $branch = $this->ownBranch($user, (int) $branchId);

        if (!$branch) {
            return back()->with('error', 'Branch not found.');
        }

        $data = request()->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'phone.regex' => 'Enter an 11-digit mobile number starting with 09.',
            'email.unique' => 'That email already has an account.',
        ]);

        $name = trim($data['first_name']) . ' ' . trim($data['last_name']);

        $staffId = DB::transaction(function () use ($data, $name, $branch) {
            $id = DB::table('users')->insertGetId([
                'name' => $name,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role' => 'logistics',
                'status' => 'Active',
                'is_verified' => true,
                'phone' => $data['phone'],
                'sorting_center_id' => $branch->id,
                'address' => $branch->address,
                'province' => $branch->province,
                'city_municipality' => $branch->city_municipality,
                'street_address' => $branch->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('logistics_applications')->insert([
                'user_id' => $id,
                'full_name' => $name,
                'business_name' => $branch->name,
                'phone' => $data['phone'],
                'address' => $branch->address,
                'status' => 'Approved',
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });

        createNotification($staffId, 'Welcome to ' . $branch->name, 'You now work at ' . $branch->name . '. Your Parcels page shows its parcels and riders.', 'account_status');

        return back()->with('success', $name . ' can now log in as ' . strtolower(trim($data['email'])) . ' and works at ' . $branch->name . '.');
    }

    public function toggle($branchId)
    {
        $user = requireUserRole('logistics');

        if (!is_array($user)) {
            return $user;
        }

        $branch = $this->ownBranch($user, (int) $branchId);

        if (!$branch) {
            return back()->with('error', 'Branch not found.');
        }

        $branch->update(['is_active' => !$branch->is_active]);

        return back()->with('success', $branch->name . ($branch->is_active
            ? ' is open again.'
            : ' is closed. New parcels for ' . $branch->town . ' go through ' . ($branch->parent->name ?? 'the Sorting Center') . ' (parcels already there stay).'));
    }

    /**
     * The staff member's province center (null = head office), or a
     * response when they may not manage branches.
     *
     * @return array{0: ?SortingCenter, 1: mixed}
     */
    private function center(array $user): array
    {
        $row = DB::table('users')->where('id', $user['id'])->first(['sorting_center_id', 'is_head_office']);
        $center = $row && $row->sorting_center_id ? SortingCenter::find($row->sorting_center_id) : null;

        if (!$center && !($row->is_head_office ?? false)) {
            return [null, redirect()->route('logistics.dashboard')->with('error', 'You are not assigned to a Sorting Center yet. Ask the BoomBuy admin to assign you.')];
        }

        if ($center && $center->isBranch()) {
            return [null, redirect()->route('logistics.dashboard')->with('error', 'Branches are managed by their Sorting Center.')];
        }

        return [$center, null];
    }

    private function ownBranch(array $user, int $branchId): ?SortingCenter
    {
        [$center, $denied] = $this->center($user);

        if ($denied) {
            return null;
        }

        return SortingCenter::whereNotNull('parent_id')
            ->when($center, fn ($q) => $q->where('parent_id', $center->id))
            ->find($branchId);
    }
}
