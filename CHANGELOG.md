# Changelog

## 1.0.3 - 2025-10-27

First version published in this repository.

- REST endpoint `/wp-json/atomix/v1/amelia-sms` (GET and POST), protected by a token created on activation.
- Message chosen by the `evt` URL parameter: `created`, `rescheduled`, `canceled`, or a generic status message.
- Phone can come from the `phone` URL parameter, `customer.phone` or `appointment.customer.phone`. Name, service, date, time and status have fallback field names.
- Settings page under Settings → Atomix Webhook SMS: usercode, password, msgheader, encoding, debug log, and a button to regenerate the token.
