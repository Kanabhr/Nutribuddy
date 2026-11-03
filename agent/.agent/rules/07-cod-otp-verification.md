# 07 – Cash on Delivery (COD) OTP Verification Rules

This document establishes the architecture, security policies, and implementation standards for Cash on Delivery (COD) OTP verification in the **Nutribuddy** codebase.

---

## 1. Objective & Threat Model
Cash on Delivery is vulnerable to:
- **Return to Origin (RTO) fraud**: Orders placed with incorrect or fake phone numbers.
- **Accidental/Prank orders**: Orders placed without customer consent.
- **Inventory blocking**: Fake COD orders tying up stock for valid customers.

**Rule:** Every order placed with `payment_method === 'cod'` must undergo **pre-placement OTP verification** sent to the delivery address phone number before the order is created in the database, unless explicitly disabled by an administrator.

---

## 2. Core Architectural Flow

1. **Address & Method Selection:**
   - Customer selects an address and chooses **Cash on Delivery (COD)**.
   - When the customer clicks **Place Order**, if payment method is `cod` and COD OTP is enabled, checkout triggers `POST /user/checkout/cod/send-otp`.

2. **OTP Generation & Dispatch:**
   - Server resolves the destination phone number from the selected `CustomerAddress` (or authenticated `User`).
   - Server generates a secure 6-digit numeric OTP.
   - The OTP hash is stored in Cache with a **10-minute TTL** keyed to `cod_otp:{user_id}`.
   - Dispatched via `AiSensyService` (or test OTP in `local`/`testing` environments).
   - Response returns `{ success: true, phone_masked: "******1234", resend_after: 30 }`.

3. **Frontend Modal:**
   - A branded Nutribuddy modal opens: *"Confirm Cash on Delivery Order"*.
   - Displays the masked destination phone number.
   - 6-digit OTP input with paste support, auto-advance, and a 30-second resend countdown.
   - User enters OTP and clicks *"Verify & Confirm Order"*.

4. **Order Placement (`POST /user/checkout/place-order`):**
   - Payload includes `cod_otp`.
   - `OrderPlacementService` verifies the OTP against the cached hash before the DB transaction.
   - If verified:
     - Order is created with `cod_verified_at = now()`.
     - Order status history logs: `"Order placed via COD with OTP verification."`.
     - Cached OTP is cleared immediately to prevent replay attacks.
   - If invalid:
     - Fails with 422 JSON response: `"Invalid or expired verification code."`.
     - Order is **not created** and inventory is **not deducted**.

---

## 3. Security, Rate Limiting & Integrity Standards

1. **Never Store Plaintext OTPs:**
   - Always hash the OTP using `Hash::make($otp)` before storing in Cache.
   - Verify using `Hash::check($inputOtp, $cachedHash)`.

2. **Rate Limiting:**
   - Limit OTP requests to **3 per 5 minutes per user/IP** using Laravel's `RateLimiter`.
   - Enforce minimum **30-second cooldown** between resends.
   - Maximum **5 failed verification attempts** per OTP before the code is permanently invalidated.

3. **No Phantom / Abandoned Orders:**
   - Pre-placement verification ensures no unverified COD orders pollute the `orders` table.

4. **Zero Impact on Online Payments:**
   - Online payments (`razorpay`, `cashfree`) handle 3D Secure / OTP via banking gateways and **must bypass** this COD OTP check completely.

5. **Admin Kill-Switch:**
   - Managed via `\App\Models\Setting::get('cod_otp_enabled', '1')`.
   - Configurable in **Admin Panel -> Settings -> Payment Gateways -> Cash on Delivery**.
   - If disabled (`0`), COD orders place directly without prompting for OTP.

6. **Testing & Local Environment:**
   - In `testing` or `local` environments when test OTP mode is active, accept `123456` and do not fail on external SMS gateway unavailability.

---

## 4. Database Schema
- `orders.cod_verified_at`: `timestamp nullable`.
  - Set to `now()` when COD OTP is verified.
  - Stays `null` for prepaid orders (or populated if prepaid ever requires phone verification).

---

## 5. View & API Contracts to Preserve
- `POST /user/checkout/place-order`:
  - Request fields: `address_id`, `coupon_code`, `coins_to_redeem`, `payment_method`, `checkout_token`, `cod_otp` (conditional).
  - Response on success: identical order JSON structure + status codes.
- Do not alter existing session tokens, cart clearing behavior, or pricing calculation pipeline.

