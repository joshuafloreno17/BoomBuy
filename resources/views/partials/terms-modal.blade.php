<link rel="stylesheet" href="{{ vasset('css/partials/terms-modal.css') }}">

<div class="terms-modal" id="termsModal" onclick="closeTermsOutside(event)">
    <div class="terms-box">

        <div class="terms-header">
            <div>
                <h2>BoomBuy Terms &amp; Conditions</h2>
                <p>Please review the terms for using BoomBuy.</p>
            </div>

            <button type="button" class="close-terms" onclick="closeTerms()" aria-label="Close">×</button>
        </div>

        <div class="terms-content">
            @include('partials.policies.body', ['key' => 'terms_policy', 'default' => 'partials.policies.terms-default'])
        </div>

        <div class="terms-footer">
            <button type="button" class="terms-close-btn" onclick="closeTerms()">Close</button>
        </div>

    </div>
</div>

<div class="terms-modal" id="privacyModal" onclick="closePrivacyOutside(event)">
    <div class="terms-box">

        <div class="terms-header">
            <div>
                <h2>BoomBuy Privacy Policy</h2>
                <p>How BoomBuy handles user information.</p>
            </div>

            <button type="button" class="close-terms" onclick="closePrivacy()" aria-label="Close">×</button>
        </div>

        <div class="terms-content">
            @include('partials.policies.body', ['key' => 'privacy_policy', 'default' => 'partials.policies.privacy-default'])
        </div>

        <div class="terms-footer">
            <button type="button" class="terms-close-btn" onclick="closePrivacy()">Close</button>
        </div>

    </div>
</div>

<script>
    function openTerms(event) {
        if (event) event.preventDefault();
        document.getElementById('privacyModal').classList.remove('show');
        document.getElementById('termsModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeTerms() {
        document.getElementById('termsModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    function closeTermsOutside(event) {
        if (event.target === document.getElementById('termsModal')) {
            closeTerms();
        }
    }

    function openPrivacy(event) {
        if (event) event.preventDefault();
        document.getElementById('termsModal').classList.remove('show');
        document.getElementById('privacyModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closePrivacy() {
        document.getElementById('privacyModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    function closePrivacyOutside(event) {
        if (event.target === document.getElementById('privacyModal')) {
            closePrivacy();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeTerms();
            closePrivacy();
        }
    });
</script>
