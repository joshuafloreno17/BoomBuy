<style>
    .terms-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(23, 32, 51, 0.55);
        z-index: 9999;
        padding: 20px;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(3px);
    }

    .terms-modal.show {
        display: flex;
    }

    .terms-box {
        width: 100%;
        max-width: 560px;
        max-height: 85vh;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.20);
        overflow: hidden;
        animation: modalOpen 0.2s ease;
    }

    @keyframes modalOpen {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .terms-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #f4e5e0;
    }

    .terms-header h2 {
        color: #172033;
        font-size: 23px;
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    }

    .terms-header p {
        color: #977970;
        font-size: 11px;
        margin-top: 3px;
    }

    .close-terms {
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 50%;
        background: #fff3ef;
        color: #8d6c62;
        font-size: 20px;
        cursor: pointer;
        transition: 0.2s;
    }

    .close-terms:hover {
        background: #ffe1d7;
        color: #e8420f;
    }

    .terms-content {
        padding: 22px 24px;
        max-height: 55vh;
        overflow-y: auto;
        color: #66514a;
        font-size: 12px;
        line-height: 1.7;
    }

    .terms-content::-webkit-scrollbar {
        width: 7px;
    }

    .terms-content::-webkit-scrollbar-track {
        background: #fff7f4;
        border-radius: 10px;
    }

    .terms-content::-webkit-scrollbar-thumb {
        background: #f3c5b6;
        border-radius: 10px;
    }

    .terms-section {
        margin-bottom: 20px;
    }

    .terms-section:last-child {
        margin-bottom: 0;
    }

    .terms-section h3 {
        color: #e8420f;
        font-size: 14px;
        margin-bottom: 6px;
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    }

    .terms-section p {
        margin-bottom: 7px;
    }

    .terms-section ul {
        padding-left: 19px;
    }

    .terms-section li {
        margin-bottom: 5px;
    }

    .terms-footer {
        padding: 16px 24px 20px;
        border-top: 1px solid #f4e5e0;
        display: flex;
        justify-content: flex-end;
    }

    .terms-close-btn {
        border: none;
        background: #e8420f;
        color: #ffffff;
        padding: 11px 20px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }

    .terms-close-btn:hover {
        background: #c43408;
        transform: translateY(-1px);
    }

    @media (max-width: 500px) {
        .terms-box {
            max-height: 90vh;
        }

        .terms-content {
            max-height: 62vh;
            padding: 20px 18px;
        }

        .terms-header {
            padding: 18px;
        }

        .terms-header h2 {
            font-size: 20px;
        }

        .terms-footer {
            padding: 14px 18px 18px;
        }

        .terms-close-btn {
            width: 100%;
        }
    }
</style>

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
