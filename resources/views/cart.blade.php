@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('pos') }}">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <span>
                <span class="brand-name">ETIVACSILOG</span>
                <span class="brand-subtitle">POS SYSTEM</span>
            </span>
        </a>
        <div class="user-area">
            <a class="header-link" href="{{ route('pos') }}">Menu</a>
            <div class="welcome-copy"><span>Cashier</span><strong>{{ auth()->user()->username }}</strong></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Log out</button>
            </form>
        </div>
    </header>

    <section class="page-content">
        <div class="page-heading">
            <div>
                <p class="eyebrow">CURRENT ORDER</p>
                <h1>Your cart</h1>
            </div>
            <a class="secondary-button link-button" href="{{ route('pos') }}">Continue shopping</a>
        </div>

        @if (session('status'))
            <p class="status-message" role="status">{{ session('status') }}</p>
        @endif

        @if ($items->isEmpty())
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Choose menu items to start an order.</p>
                <a class="primary-button link-button" href="{{ route('pos') }}">Browse menu</a>
            </div>
        @else
            <div class="cart-layout">
                <section class="cart-items" aria-label="Cart items">
                    @foreach ($items as $item)
                        <article class="cart-item" data-unit-price="{{ $item['price'] }}">
                            <div class="cart-item-info">
                                <h2>{{ $item['name'] }}</h2>
                                <p>₱{{ number_format((float) $item['price'], 2) }} each</p>
                            </div>
                            <form method="POST" action="{{ route('cart.update', $item['key']) }}" class="quantity-form" data-quantity-form>
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $item['key'] }}">Quantity</label>
                                <input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" max="99" value="{{ $item['quantity'] }}" required>
                                <span class="quantity-save-status" aria-live="polite">Saved</span>
                            </form>
                            <strong class="line-total" data-line-total>₱{{ number_format((float) $item['price'] * $item['quantity'], 2) }}</strong>
                            <form method="POST" action="{{ route('cart.remove', $item['key']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-button remove-button" type="submit">Remove</button>
                            </form>
                        </article>
                    @endforeach
                </section>

                <aside class="checkout-panel">
                    <p class="eyebrow">KITCHEN INSTRUCTIONS</p>
                    <form method="POST" action="{{ route('checkout') }}" class="checkout-form">
                        @csrf
                        <label for="kitchen-note">Note for the kitchen <span>(optional)</span></label>
                        <textarea id="kitchen-note" name="kitchen_note" maxlength="1000" rows="4" placeholder="Add preparation or serving instructions.">{{ old('kitchen_note') }}</textarea>
                        <input type="hidden" name="discount_type" value="none">
                        <input type="hidden" name="discount_label">
                        <input type="hidden" name="discount_rate" value="0">
                        <input type="hidden" name="payment_method">
                        <input type="hidden" name="payment_other">
                        <input type="hidden" name="cash_received">
                        <div class="cart-total">
                            <span>Order total</span>
                            <strong data-cart-total>₱{{ number_format($total, 2) }}</strong>
                        </div>
                        <p class="checkout-disclaimer">Any note you add will appear on the kitchen ticket.</p>
                        <button class="primary-button checkout-button" type="button" id="open-checkout">Checkout</button>
                    </form>
                </aside>
            </div>
        @endif
    </section>
</main>

@if (!$items->isEmpty())
    <script>
        document.querySelectorAll('[data-quantity-form]').forEach((form) => {
            const input = form.querySelector('input[name="quantity"]');
            const item = form.closest('.cart-item');
            const status = form.querySelector('.quantity-save-status');
            let lastSaved = input.value;
            input.addEventListener('change', async () => {
                if (!input.reportValidity() || input.value === lastSaved) return;

                status.textContent = 'Saving...';
                input.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ quantity: Number(input.value) }),
                    });

                    if (!response.ok) throw new Error('Unable to save quantity.');

                    const result = await response.json();
                    lastSaved = String(result.quantity);
                    item.querySelector('[data-line-total]').textContent = `₱${result.line_total}`;
                    document.querySelector('[data-cart-total]').textContent = `₱${result.total}`;
                    status.textContent = 'Saved';
                } catch (error) {
                    input.value = lastSaved;
                    status.textContent = 'Save failed. Try again.';
                } finally {
                    input.disabled = false;
                }
            });
        });
    </script>
@endif

@if (!$items->isEmpty())
    <dialog class="checkout-dialog" id="order-summary-dialog" aria-labelledby="order-summary-title">
        <section class="checkout-modal">
            <header class="modal-heading">
                <div><p class="eyebrow">REVIEW ORDER</p><h2 id="order-summary-title">Order summary</h2></div>
                <button class="icon-close" type="button" data-close-dialog="order-summary-dialog" aria-label="Close">×</button>
            </header>
            <div class="summary-items" id="summary-items"></div>
            <div class="discount-controls">
                <label for="discount-type">Discount type</label>
                <select id="discount-type">
                    <option value="none">No discount</option>
                    <option value="senior">Senior citizen (20%)</option>
                    <option value="pwd">PWD (20%)</option>
                    <option value="other">Others</option>
                </select>
                <div id="other-discount-fields" class="conditional-fields" hidden>
                    <label for="discount-label">Discount name</label>
                    <input id="discount-label" type="text" maxlength="80" placeholder="Enter discount type">
                    <label for="discount-rate">Discount rate (%)</label>
                    <input id="discount-rate" type="number" min="0" max="100" step="0.01" value="0" inputmode="decimal">
                </div>
            </div>
            <dl class="summary-totals">
                <div><dt>Subtotal</dt><dd id="summary-subtotal">₱0.00</dd></div>
                <div><dt id="summary-discount-label">Discount</dt><dd id="summary-discount">−₱0.00</dd></div>
                <div class="summary-grand-total"><dt>Total due</dt><dd id="summary-total">₱0.00</dd></div>
            </dl>
            <p class="payment-error" id="discount-error" role="alert"></p>
            <footer class="modal-actions">
                <button class="secondary-button" type="button" data-close-dialog="order-summary-dialog">Cancel</button>
                <button class="primary-button" type="button" id="open-payment">Pay</button>
            </footer>
        </section>
    </dialog>

    <dialog class="checkout-dialog" id="payment-dialog" aria-labelledby="payment-title">
        <section class="checkout-modal payment-modal">
            <header class="modal-heading">
                <div><p class="eyebrow">PAYMENT</p><h2 id="payment-title">Choose payment method</h2></div>
                <button class="icon-close" type="button" data-close-dialog="payment-dialog" aria-label="Close">×</button>
            </header>
            <label for="payment-method">Mode of payment</label>
            <select id="payment-method">
                <option value="">Select payment method</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="maribank">MariBank</option>
                <option value="others">Others</option>
            </select>
            <div id="other-payment-field" class="conditional-fields" hidden>
                <label for="payment-other">Payment method name</label>
                <input id="payment-other" type="text" maxlength="80" placeholder="Enter payment method">
            </div>
            <div id="cash-payment-fields" class="cash-payment-fields" hidden>
                <label for="cash-received-input">Pay amount</label>
                <div class="cash-amount-entry">
                    <input id="cash-received-input" type="number" min="0" step="0.01" inputmode="decimal" placeholder="Enter amount received">
                    <button class="secondary-button" type="button" id="exact-amount">Exact amount</button>
                </div>
                <p class="cash-change">Change <strong id="cash-change">₱0.00</strong></p>
            </div>
            <p class="payment-confirm-total">Amount due <strong id="payment-total">₱0.00</strong></p>
            <p class="payment-error" id="payment-error" role="alert"></p>
            <footer class="modal-actions">
                <button class="secondary-button" type="button" id="back-to-summary">Back</button>
                <button class="primary-button" type="button" id="confirm-payment">Confirm payment</button>
            </footer>
        </section>
    </dialog>

        <dialog class="checkout-dialog receipt-dialog" id="receipt-dialog" aria-labelledby="receipt-title" style="--receipt-width: {{ $receiptSettings->paper_width }}mm">
            <section class="checkout-modal receipt-modal">
            <header class="modal-heading">
                <div><p class="eyebrow">PAYMENT COMPLETE</p><h2 id="receipt-title">Receipt</h2></div>
                <button class="icon-close" type="button" data-close-dialog="receipt-dialog" aria-label="Close">×</button>
            </header>
            <div class="receipt-paper" id="receipt-paper" style="--receipt-width: {{ $receiptSettings->paper_width }}mm">
                <img src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
                <p class="receipt-brand" id="receipt-business-name">{{ $receiptSettings->business_name }}</p>
                <p class="receipt-subtitle">POS SYSTEM</p>
                <p class="receipt-contact" id="receipt-address">{{ $receiptSettings->address }}</p>
                <p class="receipt-contact" id="receipt-phone">{{ $receiptSettings->phone }}</p>
                <p class="receipt-meta" id="receipt-meta"></p>
                <div class="receipt-lines" id="receipt-lines"></div>
                <div class="receipt-calculations" id="receipt-calculations"></div>
                <p class="receipt-payment" id="receipt-payment"></p>
                <img class="receipt-qr-image" id="receipt-qr-image" alt="Survey QR code" hidden>
                <p class="receipt-survey" id="receipt-survey">{{ $receiptSettings->survey_url ? 'Survey: '.$receiptSettings->survey_url : '' }}</p>
                <p class="receipt-thanks" id="receipt-footer">{{ $receiptSettings->footer }}</p>
            </div>
            <p class="payment-error" id="receipt-error" role="alert"></p>
            <footer class="receipt-actions">
                <button class="secondary-button" type="button" id="copy-receipt">Copy</button>
                <button class="secondary-button" type="button" id="print-receipt">Print</button>
                <button class="primary-button" type="button" id="save-receipt">Save as PNG</button>
                <button class="primary-button" type="button" id="new-order">Make a new order</button>
            </footer>
        </section>
    </dialog>

    <script>
        const checkoutForm = document.querySelector('.checkout-form');
        const summaryDialog = document.querySelector('#order-summary-dialog');
        const paymentDialog = document.querySelector('#payment-dialog');
        const receiptDialog = document.querySelector('#receipt-dialog');
        const discountType = document.querySelector('#discount-type');
        const receiptSettings = @json($receiptSettings->toArray());
        const receiptQrUrl = @json($receiptSettings->survey_qr_path ? asset('uploads/receipts/'.$receiptSettings->survey_qr_path) : null);
        const posUrl = @json(route('pos'));
        let paidReceipt = null;

        const money = (amount) => `₱${Number(amount).toFixed(2)}`;
        const amountDue = () => Number(document.querySelector('#payment-total').textContent.replace(/[^\d.]/g, ''));
        const cashReceivedInput = document.querySelector('#cash-received-input');
        const cashChange = document.querySelector('#cash-change');
        const cashPaymentFields = document.querySelector('#cash-payment-fields');
        const paymentMethodInput = document.querySelector('#payment-method');
        const updateCashChange = () => {
            const received = Number(cashReceivedInput.value);
            cashChange.textContent = money(Number.isFinite(received) ? Math.max(0, received - amountDue()) : 0);
        };
        const currentItems = () => [...document.querySelectorAll('.cart-item')].map((row) => ({
            name: row.querySelector('.cart-item-info h2').textContent.trim(),
            price: Number(row.dataset.unitPrice),
            quantity: Number(row.querySelector('input[name="quantity"]').value),
        }));
        const discountValues = () => {
            if (discountType.value === 'senior' || discountType.value === 'pwd') return { label: discountType.value === 'senior' ? 'Senior Citizen' : 'PWD', rate: 20 };
            if (discountType.value === 'other') return { label: document.querySelector('#discount-label').value.trim(), rate: Number(document.querySelector('#discount-rate').value || 0) };
            return { label: '', rate: 0 };
        };

        function renderSummary() {
            const items = currentItems();
            const subtotal = items.reduce((total, item) => total + item.price * item.quantity, 0);
            const discount = discountValues();
            const discountAmount = Math.round(subtotal * discount.rate) / 100;
            const total = Math.max(0, subtotal - discountAmount);

            document.querySelector('#summary-items').innerHTML = items.map((item) => `<div><span>${item.quantity} × ${escapeHtml(item.name)}</span><strong>${money(item.price * item.quantity)}</strong></div>`).join('');
            document.querySelector('#summary-subtotal').textContent = money(subtotal);
            document.querySelector('#summary-discount-label').textContent = discount.label ? `Discount · ${discount.label} (${discount.rate}%)` : 'Discount';
            document.querySelector('#summary-discount').textContent = `−${money(discountAmount)}`;
            document.querySelector('#summary-total').textContent = money(total);
            document.querySelector('#payment-total').textContent = money(total);
            return { subtotal, discount, discountAmount, total };
        }

        function escapeHtml(value) {
            const element = document.createElement('span');
            element.textContent = value;
            return element.innerHTML;
        }

        document.querySelector('#open-checkout').addEventListener('click', () => {
            renderSummary();
            summaryDialog.showModal();
        });

        discountType.addEventListener('change', () => {
            document.querySelector('#other-discount-fields').hidden = discountType.value !== 'other';
            renderSummary();
        });
        document.querySelector('#discount-label').addEventListener('input', renderSummary);
        document.querySelector('#discount-rate').addEventListener('input', renderSummary);

        document.querySelector('#open-payment').addEventListener('click', () => {
            const discount = discountValues();
            const error = document.querySelector('#discount-error');
            error.textContent = '';
            if (discountType.value === 'other' && !discount.label) {
                error.textContent = 'Enter a name for this discount.';
                return;
            }
            if (!Number.isFinite(discount.rate) || discount.rate < 0 || discount.rate > 100) {
                error.textContent = 'Discount rate must be between 0 and 100%.';
                return;
            }

            checkoutForm.elements.discount_type.value = discountType.value;
            checkoutForm.elements.discount_label.value = discount.label;
            checkoutForm.elements.discount_rate.value = discount.rate;
            summaryDialog.close();
            document.querySelector('#payment-error').textContent = '';
            paymentDialog.showModal();
        });

        document.querySelector('#back-to-summary').addEventListener('click', () => {
            paymentDialog.close();
            summaryDialog.showModal();
        });

        paymentMethodInput.addEventListener('change', (event) => {
            document.querySelector('#other-payment-field').hidden = event.target.value !== 'others';
            cashPaymentFields.hidden = event.target.value !== 'cash';
            document.querySelector('#payment-error').textContent = '';
            updateCashChange();
        });

        cashReceivedInput.addEventListener('input', () => {
            document.querySelector('#payment-error').textContent = '';
            updateCashChange();
        });
        document.querySelector('#exact-amount').addEventListener('click', () => {
            cashReceivedInput.value = amountDue().toFixed(2);
            updateCashChange();
        });

        document.querySelector('#confirm-payment').addEventListener('click', async () => {
            const method = paymentMethodInput.value;
            const otherMethod = document.querySelector('#payment-other').value.trim();
            const error = document.querySelector('#payment-error');
            error.textContent = '';
            if (!method) {
                error.textContent = 'Choose a payment method.';
                return;
            }
            if (method === 'others' && !otherMethod) {
                error.textContent = 'Enter the payment method name.';
                return;
            }
            const cashReceived = Number(cashReceivedInput.value);
            if (method === 'cash' && (!cashReceivedInput.value || !Number.isFinite(cashReceived) || cashReceived < amountDue())) {
                error.textContent = 'Enter an amount that is at least the amount due.';
                return;
            }

            checkoutForm.elements.payment_method.value = method;
            checkoutForm.elements.payment_other.value = otherMethod;
            checkoutForm.elements.cash_received.value = method === 'cash' ? cashReceived.toFixed(2) : '';
            const button = document.querySelector('#confirm-payment');
            button.disabled = true;
            button.textContent = 'Processing...';

            try {
                const response = await fetch(checkoutForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: new FormData(checkoutForm),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || 'Unable to complete payment.');

                paidReceipt = result;
                renderReceipt(result);
                paymentDialog.close();
                receiptDialog.showModal();
            } catch (requestError) {
                error.textContent = requestError.message;
            } finally {
                button.disabled = false;
                button.textContent = 'Confirm payment';
            }
        });

        function renderReceipt(receipt) {
            document.querySelector('#receipt-business-name').textContent = receiptSettings.business_name;
            document.querySelector('#receipt-address').textContent = receiptSettings.address || '';
            document.querySelector('#receipt-phone').textContent = receiptSettings.phone || '';
            const receiptQrImage = document.querySelector('#receipt-qr-image');
            receiptQrImage.hidden = !receiptQrUrl;
            if (receiptQrUrl) receiptQrImage.src = receiptQrUrl;
            document.querySelector('#receipt-survey').textContent = receiptSettings.survey_url ? `Survey: ${receiptSettings.survey_url}` : '';
            document.querySelector('#receipt-footer').textContent = receiptSettings.footer || '';
            document.querySelector('#receipt-meta').textContent = `Order #${receipt.order_id}\n${receipt.date}\nCashier: ${receipt.cashier}`;
            document.querySelector('#receipt-lines').innerHTML = receipt.items.map((item) => `<div><span>${item.quantity} × ${escapeHtml(item.name)}<small>${money(item.unit_price)} each</small></span><strong>${money(item.line_total)}</strong></div>`).join('');
            document.querySelector('#receipt-calculations').innerHTML = `<div><span>Subtotal</span><strong>${money(receipt.subtotal)}</strong></div>${receipt.discount_label ? `<div><span>${escapeHtml(receipt.discount_label)} discount (${receipt.discount_rate}%)</span><strong>−${money(receipt.discount_amount)}</strong></div>` : ''}<div class="receipt-total"><span>Total due</span><strong>${money(receipt.total)}</strong></div>${receipt.cash_received !== null ? `<div><span>Cash received</span><strong>${money(receipt.cash_received)}</strong></div><div><span>Change</span><strong>${money(receipt.change_due)}</strong></div>` : ''}`;
            document.querySelector('#receipt-payment').textContent = `Paid via ${receipt.payment_method}`;
        }

        document.querySelectorAll('[data-close-dialog]').forEach((button) => {
            button.addEventListener('click', () => document.getElementById(button.dataset.closeDialog).close());
        });

        document.querySelector('#copy-receipt').addEventListener('click', async () => {
            if (!paidReceipt) return;
            const lines = [
                receiptSettings.business_name, ...(receiptSettings.address ? [receiptSettings.address] : []), ...(receiptSettings.phone ? [receiptSettings.phone] : []),
                `Order #${paidReceipt.order_id}`, paidReceipt.date, `Cashier: ${paidReceipt.cashier}`,
                ...paidReceipt.items.map((item) => `${item.quantity} x ${item.name} - ${money(item.line_total)}`),
                `Subtotal: ${money(paidReceipt.subtotal)}`,
                ...(paidReceipt.discount_label ? [`${paidReceipt.discount_label} discount (${paidReceipt.discount_rate}%): -${money(paidReceipt.discount_amount)}`] : []),
                `Total due: ${money(paidReceipt.total)}`,
                ...(paidReceipt.cash_received !== null ? [`Cash received: ${money(paidReceipt.cash_received)}`, `Change: ${money(paidReceipt.change_due)}`] : []),
                `Payment: ${paidReceipt.payment_method}`,
                ...(paidReceipt.kitchen_note ? [`Kitchen note: ${paidReceipt.kitchen_note}`] : []),
                ...(receiptSettings.survey_url ? [`Survey: ${receiptSettings.survey_url}`] : []),
                ...(receiptSettings.footer ? [receiptSettings.footer] : []),
            ];
            try {
                await navigator.clipboard.writeText(lines.join('\n'));
                document.querySelector('#receipt-error').textContent = 'Receipt copied.';
            } catch (error) {
                document.querySelector('#receipt-error').textContent = 'Clipboard access was denied by the browser.';
            }
        });

        document.querySelector('#print-receipt').addEventListener('click', () => window.print());
        document.querySelector('#save-receipt').addEventListener('click', () => saveReceiptPng(paidReceipt));
        document.querySelector('#new-order').addEventListener('click', () => window.location.assign(posUrl));

        async function saveReceiptPng(receipt) {
            if (!receipt) return;
            let qrImage = null;
            if (receiptQrUrl) {
                qrImage = await new Promise((resolve) => {
                    const image = new Image();
                    image.onload = () => resolve(image);
                    image.onerror = () => resolve(null);
                    image.src = receiptQrUrl;
                });
            }
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            const width = 640;
            const scale = Number(receiptSettings.paper_width) / 80;
            const padding = 40;
            const lineHeight = 32;
            const itemLines = receipt.items.length;
            const height = 570 + itemLines * 58 + (receipt.discount_label ? 40 : 0) + (receipt.cash_received !== null ? 72 : 0) + (receipt.kitchen_note ? 55 : 0) + (qrImage ? 155 : 0);
            canvas.width = Math.round(width * scale);
            canvas.height = Math.round(height * scale);
            context.fillStyle = '#ffffff';
            context.scale(scale, scale);
            context.fillRect(0, 0, width, height);
            context.fillStyle = '#24231f';
            context.textAlign = 'center';
            context.font = '700 28px Segoe UI';
            context.fillText(receiptSettings.business_name, width / 2, 45);
            context.font = '18px Segoe UI';
            let metaY = 73;
            if (receiptSettings.address) { context.fillText(receiptSettings.address, width / 2, metaY); metaY += 25; }
            if (receiptSettings.phone) { context.fillText(receiptSettings.phone, width / 2, metaY); metaY += 25; }
            context.fillText(`Order #${receipt.order_id}  ·  ${receipt.date}`, width / 2, metaY + 4);
            context.fillText(`Cashier: ${receipt.cashier}`, width / 2, metaY + 34);
            context.textAlign = 'left';
            let y = metaY + 86;
            context.beginPath(); context.moveTo(padding, y - 18); context.lineTo(width - padding, y - 18); context.strokeStyle = '#ded9ca'; context.stroke();
            context.font = '18px Segoe UI';
            receipt.items.forEach((item) => {
                context.fillText(`${item.quantity} × ${item.name}`, padding, y);
                context.textAlign = 'right'; context.fillText(`₱${item.line_total}`, width - padding, y); context.textAlign = 'left';
                context.font = '14px Segoe UI'; context.fillStyle = '#77746c';
                context.fillText(`₱${item.unit_price} each`, padding + 24, y + 24);
                context.font = '18px Segoe UI'; context.fillStyle = '#24231f'; y += lineHeight + 34;
            });
            y += 6;
            context.beginPath(); context.moveTo(padding, y - 16); context.lineTo(width - padding, y - 16); context.stroke();
            const row = (label, amount, bold = false) => {
                context.font = `${bold ? '700 ' : ''}18px Segoe UI`;
                context.textAlign = 'left'; context.fillText(label, padding, y);
                context.textAlign = 'right'; context.fillText(amount, width - padding, y);
                y += 36;
            };
            row('Subtotal', `₱${receipt.subtotal}`);
            if (receipt.discount_label) row(`${receipt.discount_label} discount (${receipt.discount_rate}%)`, `-₱${receipt.discount_amount}`);
            row('Total due', `₱${receipt.total}`, true);
            if (receipt.cash_received !== null) {
                row('Cash received', `₱${receipt.cash_received}`);
                row('Change', `₱${receipt.change_due}`, true);
            }
            row(`Payment: ${receipt.payment_method}`, '');
            if (qrImage) {
                const qrSize = 112;
                context.drawImage(qrImage, (width - qrSize) / 2, y, qrSize, qrSize);
                y += qrSize + 14;
            }
            if (receipt.kitchen_note) {
                context.textAlign = 'left'; context.font = '16px Segoe UI';
                context.fillText(`Kitchen note: ${receipt.kitchen_note}`, padding, y + 8);
                y += 35;
            }
            if (receiptSettings.survey_url) {
                context.textAlign = 'center'; context.font = '14px Segoe UI';
                context.fillText(`Survey: ${receiptSettings.survey_url}`, width / 2, y + 8);
                y += 28;
            }
            context.textAlign = 'center'; context.font = '700 20px Segoe UI';
            context.fillText(receiptSettings.footer || '', width / 2, Math.min(y + 20, height - 25));
            const link = document.createElement('a');
            link.download = `etivacsilog-receipt-${receipt.order_id}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    </script>
@endif
@endsection
