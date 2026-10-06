{{-- Compliance: Sellers | Riders | Buyers. --}}
<nav class="compliance-tabs" aria-label="Compliance">
    <a href="{{ route('admin.compliance') }}" @class(['active' => $active === 'sellers'])><i class="bi bi-shop"></i> Sellers</a>
    <a href="{{ route('admin.compliance.riders') }}" @class(['active' => $active === 'riders'])><i class="bi bi-bicycle"></i> Riders</a>
    <a href="{{ route('admin.compliance.buyers') }}" @class(['active' => $active === 'buyers'])><i class="bi bi-bag"></i> Buyers</a>
</nav>
