document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'true') {
        document.getElementById('resultState')?.style && (document.getElementById('resultState').style.display = 'block');
        document.getElementById('lockBanner')?.style && (document.getElementById('lockBanner').style.display = 'none');
        document.getElementById('successBanner')?.style && (document.getElementById('successBanner').style.display = 'block');
        document.getElementById('panel3')?.classList.remove('active');
        document.getElementById('diet-chart')?.scrollIntoView({ behavior: 'smooth' });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Billing toggle logic
    const billingBtns = document.querySelectorAll('.billing-btn');
    billingBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            // Update active state
            billingBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = '#666';
                b.style.boxShadow = 'none';
            });
            this.classList.add('active');
            this.style.background = '#fff';
            this.style.color = 'var(--primary)';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';

            const cycle = this.dataset.cycle;

            // Update prices
            document.querySelectorAll('.plan-price').forEach(priceEl => {
                const newPrice = priceEl.dataset[cycle];
                if (newPrice !== undefined && newPrice !== "") {
                    // Format number with commas
                    const formatted = parseFloat(newPrice).toLocaleString('en-IN');
                    priceEl.innerText = '₹' + formatted;
                } else {
                    priceEl.innerText = 'N/A';
                }
            });
            
            // Update periods
            document.querySelectorAll('.plan-period').forEach(periodEl => {
                if (cycle === 'monthly') periodEl.innerText = 'per month';
                if (cycle === 'quarterly') periodEl.innerText = 'every 3 months';
                if (cycle === 'annually') periodEl.innerText = 'per year';
            });
        });
    });
});

window.startDietCheckout = async function (planSlug, btn, childId = null, selectedCycle = null) {
    const originalText = btn.innerText;
    btn.innerText = 'Processing...';
    btn.disabled = true;

    const gatewayEl = document.querySelector('input[name="diet_gateway"]:checked');
    const gateway = gatewayEl ? gatewayEl.value : (window.nutribuddyConfig.defaultPaymentGateway || 'razorpay');
    
    const activeCycleBtn = document.querySelector('.billing-btn.active');
    const cycle = selectedCycle || (activeCycleBtn ? activeCycleBtn.dataset.cycle : 'monthly');

    try {
        // 1. Create Order
        const createResponse = await fetch(window.nutribuddyConfig.paymentCreateRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.nutribuddyConfig.csrfToken
            },
            body: JSON.stringify({ plan_slug: planSlug, gateway: gateway, cycle: cycle, ...(childId ? { child_id: childId } : {}) })
        });

        const createData = await createResponse.json();

        if (!createResponse.ok || !createData.success) {
            if (typeof nbToast === 'function') {
                nbToast(createData.message || 'Failed to initialize payment.', 'error');
            } else {
                alert(createData.message || 'Failed to initialize payment.');
            }
            btn.innerText = originalText;
            btn.disabled = false;
            return;
        }

        if (createData.gateway === 'free') {
            if (typeof nbToast === 'function') {
                nbToast('Free plan activated successfully!', 'success');
            } else {
                alert('Free plan activated successfully!');
            }
            if (childId && window.nutribuddyConfig.mealPlanRoute) {
                window.location.href = `${window.nutribuddyConfig.mealPlanRoute}?child_id=${encodeURIComponent(childId)}`;
                return;
            }
            document.getElementById('lockBanner')?.style && (document.getElementById('lockBanner').style.display = 'none');
            document.getElementById('successBanner')?.style && (document.getElementById('successBanner').style.display = 'block');
            btn.innerText = originalText;
            btn.disabled = false;
            return;
        }

        if (gateway === 'cashfree') {
            // Cashfree Checkout
            btn.innerText = 'Opening Cashfree...';
            const cashfree = Cashfree({
                mode: createData.mode || window.nutribuddyConfig.cashfreeEnv || 'sandbox'
            });

            cashfree.checkout({
                paymentSessionId: createData.payment_session_id,
                redirectTarget: '_self'
            });

        } else {
            // Razorpay Checkout
            const options = {
                key: window.nutribuddyConfig.razorpayKey,
                amount: createData.amount,
                currency: createData.currency,
                name: 'NutriBuddy Diet Plan',
                description: 'Subscription for ' + planSlug,
                order_id: createData.razorpay_order_id,
                handler: async function (response) {
                    btn.innerText = 'Verifying...';

                    const verifyResponse = await fetch(window.nutribuddyConfig.paymentVerifyRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.nutribuddyConfig.csrfToken
                        },
                        body: JSON.stringify({
                            subscription_id: createData.subscription_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    });

                    const verifyData = await verifyResponse.json();

                    if (verifyResponse.ok && verifyData.success) {
                        if (typeof nbToast === 'function') {
                            nbToast('Payment successful! Your subscription is active.', 'success');
                        } else {
                            alert('Payment successful! Your subscription is active.');
                        }
                        if (childId && window.nutribuddyConfig.mealPlanRoute) {
                            window.location.href = `${window.nutribuddyConfig.mealPlanRoute}?child_id=${encodeURIComponent(childId)}`;
                            return;
                        }
                        document.getElementById('lockBanner')?.style && (document.getElementById('lockBanner').style.display = 'none');
                        document.getElementById('successBanner')?.style && (document.getElementById('successBanner').style.display = 'block');
                    } else {
                        if (typeof nbToast === 'function') {
                            nbToast('Payment verification failed: ' + (verifyData.message || 'Unknown error'), 'error');
                        } else {
                            alert('Payment verification failed: ' + (verifyData.message || 'Unknown error'));
                        }
                    }

                    btn.innerText = originalText;
                    btn.disabled = false;
                },
                modal: {
                    ondismiss: function () {
                        btn.innerText = originalText;
                        btn.disabled = false;
                    }
                },
                theme: {
                    color: '#654da8'
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                if (typeof nbToast === 'function') {
                    nbToast('Payment failed: ' + response.error.description, 'error');
                } else {
                    alert('Payment failed: ' + response.error.description);
                }
                btn.innerText = originalText;
                btn.disabled = false;
            });
            rzp.open();
        }

    } catch (err) {
        console.error(err);
        if (typeof nbToast === 'function') {
            nbToast('An error occurred while starting checkout.', 'error');
        } else {
            alert('An error occurred while starting checkout.');
        }
        btn.disabled = false;
    }
}

window.startQuestionnaire = function(planSlug) {
    const activeCycleBtn = document.querySelector('.billing-btn.active');
    const cycle = activeCycleBtn ? activeCycleBtn.dataset.cycle : 'monthly';
    const payload = btoa(`plan=${planSlug}&cycle=${cycle}`);
    window.location.href = `/nutrition/questionnaire?payload=${payload}`;
}
