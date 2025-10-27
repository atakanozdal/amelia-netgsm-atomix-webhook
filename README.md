# amelia-netgsm-atomix-webhook
TR : Token korumalı REST endpoint: Amelia/3. parti sistem POST atar, Netgsm ile SMS gönderilir. EN:  Token-protected REST endpoint; any system (e.g., Amelia, Automator, Postman) POSTs JSON, SMS goes via Netgsm.
🇹🇷 Amaç

amelia-netgsm-atomix-webhook, token korumalı bir REST endpoint sağlar: /wp-json/atomix/v1/amelia-sms?token=...
Amelia (veya Postman/Automator/Zapier/Make) bu adrese JSON POST gönderir; eklenti telefonu ayrıştırır ve Netgsm REST v2 ile SMS gönderir.

amelia-netgsm-atomix-webhook exposes a token-protected REST endpoint: /wp-json/atomix/v1/amelia-sms?token=....
Amelia (or Postman/Automator/Zapier/Make) POSTs JSON here; the plugin extracts phone and sends SMS via Netgsm REST v2.

✨ Özellikler / Features

Token-based security

Flexible JSON parsing for different Amelia payloads

Quick Test in admin, token regeneration

Detailed debug logging

Easy Postman testing

🚀 Kurulum / Installation

ZIP olarak kur: amelia-netgsm-atomix-webhook.zip → Etkinleştir

Ayarlar → Amelia → Netgsm SMS (Webhook) sayfasına gir

Netgsm bilgilerini gir, Token’ı kopyala / gerekiyorsa Yenile

Amelia veya otomasyon aracında Webhook hedefini şu yap:
https://alanadresi.com/wp-json/atomix/v1/amelia-sms?token=TOKENBURADA

📥 Beklenen Payload / Expected Payload

Telefon otomatik şu alanlardan çekilmeye çalışılır:

customer.phone, appointment.customer.phone, data.customer.phone, booking.customer.phone

Etkinlik evt parametresi yoksa otomatik algılanır (created/rescheduled/canceled/status)

Postman örneği:

POST /wp-json/atomix/v1/amelia-sms?token=TOKEN
Content-Type: application/json

{
  "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "0532XXXXXXX" },
  "appointment": {
    "service": { "name": "Cilt Bakımı" },
    "date": "2025-10-30",
    "time": "14:00",
    "status": "approved"
  }
}

🧪 Test / Testing

Admin sayfasındaki Hızlı Test ile doğrudan SMS dene

Postman’la yukarıdaki gövdeleri yollayarak doğrula

Yanıt {"ok":true,...} ise Netgsm kuyruğa almıştır

🔐 Güvenlik / Security

Token’ı gizli tut; gerektiğinde Token’ı Yenile

/wp-json/*’u cache’ten hariç tut

HTTPS kullan

🚧 Cache & Firewall

WP Fastest Cache / LiteSpeed / Cloudflare: /wp-json/* hariç tutulmalı

Sunucudan api.netgsm.com.tr çıkışı açık olmalı

❗ Sorun Giderme / Troubleshooting

403 forbidden → Token yanlış / eksik

missing-phone → JSON’da telefon alanı yok; URL’ye ?phone=9053… geçebilirsin

Netgsm başarısız → debug.log’ı incele; code/body dönüyor

🧾 Sürüm Notları / Changelog

v1.0.0: İlk sürüm — Token, webhook, test, debug

v1.1.0: Otomatik evt algılama, telefon alanları tarama kapsamı genişledi

📄 Lisans / License

MIT — ayrıntı için LICENSE dosyasına bakın.
