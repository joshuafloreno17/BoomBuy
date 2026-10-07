{{-- "% off" next to the product's Price field; shows what buyers will pay. --}}
@php $discountValue = old('discount_percent', $discount ?? ''); @endphp
<div class="form-group">
    <label for="discount_percent">Discount (% off) <small style="font-weight:500;color:var(--bb-muted,#6b6570)">· optional</small></label>
    <input
        type="number"
        id="discount_percent"
        name="discount_percent"
        value="{{ $discountValue }}"
        min="0"
        max="{{ \App\Models\Product::MAX_DISCOUNT }}"
        step="1"
        placeholder="0 = no discount"
    >
    <small id="discount-hint" style="display:block;margin-top:6px;color:var(--bb-muted,#6b6570);font-size:13px"></small>
</div>

<script>
    (function () {
        var price = document.getElementById('price');
        var off = document.getElementById('discount_percent');
        var hint = document.getElementById('discount-hint');
        if (!price || !off || !hint) return;

        function peso(n) {
            return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function update() {
            var p = parseFloat(price.value);
            var d = parseInt(off.value, 10);
            if (!(p > 0) || !(d > 0)) {
                hint.textContent = '';
                return;
            }
            d = Math.min(d, {{ \App\Models\Product::MAX_DISCOUNT }});
            hint.innerHTML = 'Buyers pay <strong style="color:var(--bb-accent,#e8420f)">' + peso(Math.round(p * (100 - d)) / 100) +
                '</strong> instead of <s>' + peso(p) + '</s> (' + d + '% off).';
        }

        price.addEventListener('input', update);
        off.addEventListener('input', update);
        update();
    })();
</script>
