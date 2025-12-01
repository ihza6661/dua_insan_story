# Order Email Notification System

## Overview
Automated email notification system that keeps customers informed throughout their order lifecycle.

## Implemented Features

### 1. **Order Confirmed Email**
- **Trigger:** Automatically sent when order is created via CheckoutService
- **Contains:** Order summary, wedding details, itemized list, payment info, next steps
- **Mailable:** `App\Mail\OrderConfirmed`
- **Template:** `resources/views/emails/order-confirmed.blade.php`
- **Subject:** "Order Confirmation - {order_number}"

### 2. **Payment Confirmed Email**
- **Trigger:** Sent when payment succeeds via Midtrans webhook
- **Contains:** Payment details, amount paid, remaining balance (if partial), order summary
- **Mailable:** `App\Mail\PaymentConfirmed`
- **Template:** `resources/views/emails/payment-confirmed.blade.php`
- **Subject:** "Payment Confirmed - {order_number}"

### 3. **Order Status Changed Email**
- **Trigger:** Sent when admin updates order status (except Shipped/Delivered which have dedicated emails)
- **Contains:** Old vs new status, status-specific information, next steps
- **Mailable:** `App\Mail\OrderStatusChanged`
- **Template:** `resources/views/emails/order-status-changed.blade.php`
- **Dynamic Subjects:**
  - Processing: "Your Order is Being Processed"
  - Design Approval: "Design Approval Required"
  - In Production: "Your Order is In Production"
  - Cancelled: "Your Order Has Been Cancelled"
  - Default: "Order Status Update"

### 4. **Order Shipped Email**
- **Trigger:** Sent when admin changes order status to "Shipped"
- **Contains:** Tracking number, courier name, estimated delivery, tracking instructions
- **Mailable:** `App\Mail\OrderShipped`
- **Template:** `resources/views/emails/order-shipped.blade.php`
- **Subject:** "Your Order Has Been Shipped - {order_number}"

### 5. **Order Delivered Email**
- **Trigger:** Sent when admin changes order status to "Delivered"
- **Contains:** Delivery confirmation, inspection checklist, support contact
- **Mailable:** `App\Mail\OrderDelivered`
- **Template:** `resources/views/emails/order-delivered.blade.php`
- **Subject:** "Your Order Has Been Delivered - {order_number}"

## Technical Implementation

### Database Changes
- **Migration:** `2025_12_01_173438_add_tracking_number_to_orders_table.php`
- **New Field:** `orders.tracking_number` (nullable string)

### Controller Changes

#### 1. CheckoutService.php
```php
// After order creation (line 77-79)
Mail::to($customer->email)->send(new OrderConfirmed($order));
```

#### 2. WebhookController.php
```php
// After successful payment (line 175-179)
Mail::to($customer->email)->send(new PaymentConfirmed($order, $payment));
```

#### 3. Admin/OrderController.php
```php
// In updateStatus() method
- Detects status changes
- Updates tracking_number if provided
- Sends appropriate email based on new status:
  - Shipped → OrderShipped email
  - Delivered → OrderDelivered email
  - Other → OrderStatusChanged email
```

### API Endpoint Update
**POST** `/api/v1/admin/orders/{order}/status`

**Request Body:**
```json
{
  "status": "Shipped",
  "tracking_number": "JNE12345678" // optional
}
```

**Validation Rules:**
- `status`: required, must be valid order status
- `tracking_number`: optional, string, max 255 characters

### Email Queue Configuration
All emails implement `ShouldQueue` interface for asynchronous sending via Laravel's queue system.

**To process queued emails:**
```bash
php artisan queue:work
```

## Email Design
- Consistent branding with primary color (#4F46E5) and success color (#10B981)
- Mobile-responsive HTML templates
- Clear call-to-action buttons
- Professional typography and spacing
- Inline CSS for email client compatibility

## Testing

### Test Coverage
- **Test File:** `tests/Feature/OrderEmailNotificationTest.php`
- **Tests:** 12 comprehensive tests
- **Coverage:**
  - Email sending on order creation
  - Email sending on payment confirmation
  - Email sending on status changes (Processing, Shipped, Delivered)
  - Email subject line verification for all types
  - Dynamic subject lines for different statuses
  - Queue implementation verification
  - No email sent when status unchanged

### Running Tests
```bash
# Run all email notification tests
php artisan test --filter=OrderEmailNotificationTest

# Run full test suite
php artisan test
```

**Current Status:** ✅ All 154 tests passing (12 new email tests + 142 existing)

## Development Testing

### Using Mailtrap (Recommended)
1. Configure `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@duainsan.story
MAIL_FROM_NAME="Dua Insan Story"
```

2. Test by:
   - Creating an order (triggers OrderConfirmed)
   - Making payment (triggers PaymentConfirmed)
   - Updating order status via admin dashboard (triggers status emails)

### Using Log Driver (Quick Testing)
```env
MAIL_MAILER=log
```
Emails will be written to `storage/logs/laravel.log`

## Production Deployment Checklist

- [ ] Configure production SMTP settings in `.env`
- [ ] Verify queue worker is running (`php artisan queue:work`)
- [ ] Set up queue supervisor for reliability
- [ ] Configure failed job handling
- [ ] Test email delivery to real addresses
- [ ] Monitor queue performance
- [ ] Set up email delivery monitoring (e.g., AWS SES, SendGrid)

## Email Content Localization
Currently emails are in English. To add Indonesian translations:
1. Update email templates in `resources/views/emails/`
2. Update subject lines in Mailable classes
3. Consider using Laravel's localization features for multi-language support

## Future Enhancements
- Email templates with customer's wedding design theme
- Order progress tracking timeline in emails
- SMS notifications for critical status changes
- WhatsApp notification integration
- Email preference management for customers
- Email analytics and open tracking
