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

            <div class="terms-section">
                <h3>1. Account Responsibility</h3>
                <p>Users are responsible for providing accurate account information and keeping their login credentials secure.</p>
            </div>

            <div class="terms-section">
                <h3>2. Account Types</h3>
                <p>BoomBuy provides different account types with different responsibilities.</p>
                <ul>
                    <li><strong>Buyer</strong> — may browse products, manage a cart, place orders, and manage purchases.</li>
                    <li><strong>Seller</strong> — may add and manage products and manage seller orders.</li>
                    <li><strong>Rider</strong> — may manage assigned deliveries and update delivery status.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>3. Proper Use</h3>
                <p>Users must use BoomBuy responsibly and must not attempt to access another user's account or misuse the system.</p>
            </div>

            <div class="terms-section">
                <h3>4. Orders and Transactions</h3>
                <p>Users should provide accurate information when placing orders or performing other transactions through BoomBuy.</p>
            </div>

            <div class="terms-section">
                <h3>5. Product Information</h3>
                <p>Sellers are responsible for providing accurate product names, descriptions, prices, and stock information.</p>
            </div>

            <div class="terms-section">
                <h3>6. Delivery</h3>
                <p>Buyers should provide accurate delivery details. Riders are responsible for properly managing assigned deliveries and updating delivery statuses.</p>
            </div>

            <div class="terms-section">
                <h3>7. Prohibited Activities</h3>
                <ul>
                    <li>Using false account information.</li>
                    <li>Accessing another user's account.</li>
                    <li>Misusing the ordering or delivery system.</li>
                    <li>Providing misleading product information.</li>
                    <li>Performing fraudulent or unauthorized activities.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h3>8. Account Access</h3>
                <p>Users must select the correct account type when logging in. Access to features depends on the user's assigned role. Seller and rider accounts also require identity verification before they can log in.</p>
            </div>

            <div class="terms-section">
                <h3>9. Agreement</h3>
                <p>By using BoomBuy, users acknowledge these Terms and Conditions and agree to use the platform responsibly.</p>
            </div>

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

            <div class="terms-section">
                <h3>1. Information We Collect</h3>
                <p>BoomBuy may store information needed to create and manage accounts and process marketplace activities, such as a user's name, email address, account role, orders, and delivery information. Sellers and riders also submit identity and business documents for verification.</p>
            </div>

            <div class="terms-section">
                <h3>2. How Information Is Used</h3>
                <p>Information may be used to provide account access, process orders, manage deliveries, verify seller and rider applications, display account information, and operate BoomBuy features.</p>
            </div>

            <div class="terms-section">
                <h3>3. Account Security</h3>
                <p>Users should keep their passwords confidential and should not share their account credentials with other people.</p>
            </div>

            <div class="terms-section">
                <h3>4. Transaction Information</h3>
                <p>Information related to orders and deliveries may be stored so that buyers, sellers, riders, and authorized administrators can perform their respective functions.</p>
            </div>

            <div class="terms-section">
                <h3>5. Information Protection</h3>
                <p>BoomBuy is designed to limit access to information according to user roles and system permissions. Verification documents are stored privately and are only accessible to administrators.</p>
            </div>

            <div class="terms-section">
                <h3>6. User Responsibility</h3>
                <p>Users should make sure that the information they provide is accurate and should avoid sharing personal account information unnecessarily.</p>
            </div>

            <div class="terms-section">
                <h3>7. Policy Updates</h3>
                <p>The Privacy Policy may be updated when BoomBuy features or requirements change.</p>
            </div>

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
