# Amelia → Netgsm SMS Webhook (Atomix)

[English](#english) · [Türkçe](#türkçe)

## English

A WordPress plugin that adds a token protected endpoint at `/wp-json/atomix/v1/amelia-sms`. Amelia posts its webhook JSON there, the plugin builds a short Turkish message for the event (created, rescheduled, canceled or a status change) and sends it to the customer through the Netgsm REST API. It is the second version of [amelia-netgsm-atomix](https://github.com/atakanozdal/amelia-netgsm-atomix): per-event messages, a few fallback field names, a selectable encoding, debug logging and a button to regenerate the token.

### Requirements

- WordPress 4.7 or later (uses the REST API, `register_rest_route`, `wp_remote_post`)
- PHP 7.0 or later (the code uses the `??` operator)
- Amelia with webhook support
- A Netgsm account with API access and an approved SMS header (msgheader), enabled for web service use in the Netgsm panel
- Pretty permalinks. The URL shown in the admin is built as `/wp-json/...`, which does not work with plain permalinks.

### Installation

1. Download this repository as a ZIP, or copy `amelia-netgsm-atomix-webhook.php` into `wp-content/plugins/amelia-netgsm-atomix-webhook/`.
2. Activate "Amelia → Netgsm SMS Webhook (Atomix)" in Plugins. A 24 character token is created on activation.
3. Open Settings → Atomix Webhook SMS, enter your Netgsm details and save.
4. Copy the Webhook URL at the top of the page.
5. In Amelia, add one webhook per event you want an SMS for, with method POST and a JSON body. Add `&evt=` to the URL so the plugin knows which message to send:

```
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=created
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=rescheduled
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=canceled
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=status
```

Opening the URL in a browser (GET) with the right token returns `{"ok": true, "hint": "POST JSON bekleniyor"}`, which is a quick way to check the token.

### Configuration

Settings are stored in the `atomix_netgsm_webhook` option.

| Field | Option key | Notes |
|---|---|---|
| Usercode | `usercode` | Netgsm user code |
| Password | `password` | Netgsm API password |
| Msgheader | `msgheader` | Approved sender name |
| Encoding | `encoding` | `TR` (default), `TURKCE` or `UTF-8`, passed to Netgsm as is |
| Debug log | `log` | On by default. Writes the Netgsm response to the PHP error log |
| Token'ı Yenile button | `token` | Creates a new token. The old URL stops working. |

Usercode, password and msgheader are all required.

### How it works

`/wp-json/atomix/v1/amelia-sms` accepts GET and POST. On a POST it:

1. Checks the `token` parameter. Returns 403 if it does not match.
2. Takes the phone from the `phone` query parameter if present, otherwise from `customer.phone`, otherwise from `appointment.customer.phone`. Returns 400 if none is found.
3. Reads the rest of the fields, using the first one that exists:
   - first name: `customer.firstName`, `customer.first_name`
   - last name: `customer.lastName`, `customer.last_name`
   - service: `service.name`, `appointment.service.name`
   - date: `appointment.date`, `appointmentDate`
   - time: `appointment.time`, `appointmentTime`
   - status: `appointment.status`, `status`

   If there is no name, "Müşterimiz" is used.
4. Picks the message by `evt`:

   | `evt` | Message |
   |---|---|
   | `created` | Merhaba {name}, {service} randevunuz {date} {time} için oluşturuldu. |
   | `rescheduled` | Merhaba {name}, {service} randevunuz {date} {time} tarihine güncellendi. |
   | `canceled` | Merhaba {name}, {service} randevunuz {date} {time} iptal edilmiştir. |
   | anything else, or missing | Merhaba {name}, {service} randevunuz {date} {time} için durum güncellendi ({status}). |

5. Normalizes the phone to `905XXXXXXXXX` and sends it to `https://api.netgsm.com.tr/sms/rest/v2/send`.
6. Returns `{"ok": ..., "http": <Netgsm HTTP code>, "resp": <Netgsm response body>}`, with status 200 on success and 500 otherwise.

### Example request

```bash
curl -X POST "https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=created" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "05XXXXXXXXX" },
    "appointment": {
      "service": { "name": "Cilt Bakımı" },
      "date": "2025-10-30",
      "time": "14:00",
      "status": "approved"
    }
  }'
```

Sending a test SMS without a body also works, since the phone can come from the URL:

```bash
curl -X POST "https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&phone=5XXXXXXXXX"
```

### Known limitations

- The event is not detected from the payload. Without `evt` in the URL every webhook gets the generic "durum güncellendi" message.
- Message texts are fixed in the code. There is no template setting.
- If your Amelia version puts the customer inside `appointment.bookings[]` or sends the start time as `bookingStart`, none of the paths above match and the phone, date or time will be missing. Check the JSON your Amelia version sends.
- The response includes Netgsm's raw reply, which the caller (Amelia) can see.
- With debug log on, full Netgsm responses are written to the log.
- The password field is a plain text input and the password is stored unencrypted in the options table.

### License

MIT with an attribution clause. See [LICENSE](LICENSE).

Atakan Özdal · atomiXbilişim · https://atakanozdal.com.tr

## Türkçe

`/wp-json/atomix/v1/amelia-sms` adresinde token korumalı bir uç nokta açan WordPress eklentisi. Amelia webhook JSON'unu buraya gönderir, eklenti olaya göre (oluşturma, erteleme, iptal ya da durum değişikliği) kısa bir Türkçe mesaj hazırlar ve Netgsm REST API üzerinden müşteriye yollar. [amelia-netgsm-atomix](https://github.com/atakanozdal/amelia-netgsm-atomix) eklentisinin ikinci sürümü sayılır: olaya göre ayrı mesajlar, birkaç yedek alan adı, seçilebilir karakter kodlaması, debug günlüğü ve token yenileme düğmesi eklendi.

### Gereksinimler

- WordPress 4.7 veya üstü (REST API, `register_rest_route`, `wp_remote_post` kullanılıyor)
- PHP 7.0 veya üstü (kodda `??` operatörü var)
- Webhook desteği olan Amelia
- API erişimi olan bir Netgsm hesabı ve Netgsm panelinde web servis kullanımına açılmış onaylı bir SMS başlığı (msgheader)
- Kalıcı bağlantı ayarının "Düz" dışında bir seçenekte olması. Yönetim sayfasındaki adres `/wp-json/...` biçiminde üretiliyor, düz bağlantılarda çalışmaz.

### Kurulum

1. Depoyu ZIP olarak indirin ya da `amelia-netgsm-atomix-webhook.php` dosyasını `wp-content/plugins/amelia-netgsm-atomix-webhook/` klasörüne kopyalayın.
2. Eklentiler sayfasından "Amelia → Netgsm SMS Webhook (Atomix)" eklentisini etkinleştirin. Etkinleştirme sırasında 24 karakterlik bir token oluşturulur.
3. Ayarlar → Atomix Webhook SMS sayfasında Netgsm bilgilerinizi girip kaydedin.
4. Sayfanın üstündeki Webhook URL'yi kopyalayın.
5. Amelia'da SMS gitmesini istediğiniz her olay için ayrı bir webhook ekleyin; yöntem POST, gövde JSON olsun. Eklentinin hangi mesajı göndereceğini bilmesi için adrese `&evt=` ekleyin:

```
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=created
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=rescheduled
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=canceled
https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=status
```

Adresi doğru token ile tarayıcıda açarsanız (GET) `{"ok": true, "hint": "POST JSON bekleniyor"}` döner. Token'ı denemenin kolay yolu bu.

### Ayarlar

Ayarlar `atomix_netgsm_webhook` seçeneğinde saklanır.

| Alan | Anahtar | Açıklama |
|---|---|---|
| Usercode | `usercode` | Netgsm kullanıcı kodu |
| Password | `password` | Netgsm API şifresi |
| Msgheader | `msgheader` | Onaylı gönderici adı |
| Encoding | `encoding` | `TR` (varsayılan), `TURKCE` veya `UTF-8`; Netgsm'e olduğu gibi gönderilir |
| Debug log | `log` | Varsayılan olarak açık. Netgsm yanıtını PHP hata günlüğüne yazar |
| Token'ı Yenile düğmesi | `token` | Yeni token üretir. Eski adres çalışmaz hale gelir. |

Usercode, password ve msgheader zorunlu.

### Nasıl çalışır

`/wp-json/atomix/v1/amelia-sms` GET ve POST kabul eder. POST geldiğinde:

1. `token` parametresine bakar. Tutmazsa 403 döner.
2. Telefonu önce `phone` sorgu parametresinden, yoksa `customer.phone` alanından, o da yoksa `appointment.customer.phone` alanından alır. Hiçbiri yoksa 400 döner.
3. Diğer alanlarda bulduğu ilk değeri kullanır:
   - ad: `customer.firstName`, `customer.first_name`
   - soyad: `customer.lastName`, `customer.last_name`
   - hizmet: `service.name`, `appointment.service.name`
   - tarih: `appointment.date`, `appointmentDate`
   - saat: `appointment.time`, `appointmentTime`
   - durum: `appointment.status`, `status`

   Ad yoksa "Müşterimiz" yazılır.
4. Mesajı `evt` değerine göre seçer:

   | `evt` | Mesaj |
   |---|---|
   | `created` | Merhaba {ad}, {hizmet} randevunuz {tarih} {saat} için oluşturuldu. |
   | `rescheduled` | Merhaba {ad}, {hizmet} randevunuz {tarih} {saat} tarihine güncellendi. |
   | `canceled` | Merhaba {ad}, {hizmet} randevunuz {tarih} {saat} iptal edilmiştir. |
   | başka bir değer ya da boş | Merhaba {ad}, {hizmet} randevunuz {tarih} {saat} için durum güncellendi ({durum}). |

5. Telefonu `905XXXXXXXXX` biçimine çevirip `https://api.netgsm.com.tr/sms/rest/v2/send` adresine gönderir.
6. `{"ok": ..., "http": <Netgsm HTTP kodu>, "resp": <Netgsm yanıt gövdesi>}` döner; başarılıysa 200, değilse 500.

### Örnek istek

```bash
curl -X POST "https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&evt=created" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": { "firstName": "Ayşe", "lastName": "Yılmaz", "phone": "05XXXXXXXXX" },
    "appointment": {
      "service": { "name": "Cilt Bakımı" },
      "date": "2025-10-30",
      "time": "14:00",
      "status": "approved"
    }
  }'
```

Telefon adresten de alınabildiği için gövdesiz bir deneme SMS'i de atılabilir:

```bash
curl -X POST "https://example.com/wp-json/atomix/v1/amelia-sms?token=YOUR_TOKEN&phone=5XXXXXXXXX"
```

### Bilinen kısıtlar

- Olay gövdeden anlaşılmıyor. Adreste `evt` yoksa her webhook'a genel "durum güncellendi" mesajı gider.
- Mesaj metinleri kodun içinde sabit, şablon ayarı yok.
- Amelia sürümünüz müşteriyi `appointment.bookings[]` içinde ya da başlangıç zamanını `bookingStart` olarak gönderiyorsa yukarıdaki yolların hiçbiri tutmaz; telefon, tarih ya da saat eksik kalır. Amelia'nın gönderdiği JSON'a bir bakın.
- Yanıtta Netgsm'in ham cevabı da var, bunu çağıran taraf (Amelia) görür.
- Debug log açıkken Netgsm yanıtlarının tamamı günlüğe yazılır.
- Şifre alanı düz metin kutusu, şifre seçenekler tablosunda şifrelenmeden duruyor.

### Lisans

Atıf şartı eklenmiş MIT. Ayrıntılar için [LICENSE](LICENSE).

Atakan Özdal · atomiXbilişim · https://atakanozdal.com.tr
