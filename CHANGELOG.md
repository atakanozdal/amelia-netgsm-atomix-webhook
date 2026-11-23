# Changelog

## 1.0.4 - 2026-11-23

- The token is compared with `hash_equals`. If no token is stored, every request is rejected. The settings page creates a token when none exists.
- The endpoint accepts POST only. The GET check that answered "POST JSON bekleniyor" is gone, because it told anyone whether a token was valid.
- The token can be sent in an `X-Atomix-Token` header. This is preferred over the `token` URL parameter, which still works.
- The response no longer contains Netgsm's raw reply or the reason for a failure. It is `{"ok":true}` or `{"ok":false,"error":"send_failed"}`.
- The log only shows the last 2 digits of the phone number, the HTTP code and the Netgsm status code, and only when `WP_DEBUG` is on.
- Form input is unslashed before saving, the encoding is checked against the three allowed values, and the two forms use their own nonce names.
- The password field is a password input. It is not shown again after saving, and leaving it empty keeps the stored password.
- The webhook URL in the admin is built with `rest_url()`, so it also works with plain permalinks.

## 1.0.3 - 2025-10-27

First version published in this repository.

- REST endpoint `/wp-json/atomix/v1/amelia-sms` (GET and POST), protected by a token created on activation.
- Message chosen by the `evt` URL parameter: `created`, `rescheduled`, `canceled`, or a generic status message.
- Phone can come from the `phone` URL parameter, `customer.phone` or `appointment.customer.phone`. Name, service, date, time and status have fallback field names.
- Settings page under Settings → Atomix Webhook SMS: usercode, password, msgheader, encoding, debug log, and a button to regenerate the token.
