# Amelia → Netgsm SMS (Webhook Version)
**Developer:** Atakan Özdal  
**License:** MIT  

Amelia Webhook özelliğini kullanarak JSON POST alma → Netgsm SMS gönderimi sağlar.  
**Token korumalı REST endpoint** içerir.

📌 Endpoint formatı:

https://site.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN

---

## 🇹🇷 Özellikler
✅ Tüm Amelia Webhook event’leri desteklenir  
✅ Event otomatik algılama (created/canceled/rescheduled)  
✅ Token güvenliği  
✅ Debug log kaydı  
✅ XML fallback modu  
✅ Custom mesaj şablonu desteği: `?msg=...`

---

## ⚙️ Kurulum (TR)

1️⃣ ZIP yükleyin → Etkinleştirin  
2️⃣ Ayarlar → Amelia → Netgsm SMS (Webhook)  
3️⃣ Netgsm bilgilerini girin  
4️⃣ Token kopyalayın  
5️⃣ Amelia → Notifications → Webhooks
   - **Method:** POST
   - **Data Format:** JSON
   - **URL:** endpoint + token

Örnek:
https://baharbeklenbeauty.com/wp-json/atomix/v1/amelia-sms?token=XXXXXXXX


✅ Test randevusu → SMS gelmelidir

---

## JSON Gövde Örneği

```json
{
  "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "0532..." },
  "appointment": {
    "service": { "name": "Cilt Bakımı" },
    "date": "2025-10-30",
    "time": "14:00",
    "status": "approved"
  }
}
```


🚫 Cache / Firewall Ayarı

Cache hariç yolu:
/wp-json/* 


---
🇬🇧 English Quick Guide

Install ZIP → Activate

Enter Netgsm creds

Add Webhook URL with token

Create test appointment ✅

📌 Changelog
Version	Notes
1.0.0	First webhook build
1.1.0	Auto event detection
📄 License

MIT — © 2025 Atakan Özdal

---


