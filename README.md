# Game+ Party Hub - Ortak Oyun Bulucu

Türk Telekom Code Night etkinliği için geliştirilmiş Game+ Party Hub projesi. Bu uygulama, arkadaşlarınızla birlikte oyun oynayabilmenizi kolaylaştıran bir parti yönetim sistemidir.

## 🎮 Özellikler

### ✅ MVP Özellikleri
- **Parti Oluşturma**: Parti başlığı, tarih, saat ve oyun listesi ile parti oluşturma
- **Oyun Oylaması**: Game+ kataloğundan 3-5 oyun arasından oylama
- **Slot Planlama**: Zaman aralıkları için katılımcı oylaması
- **Skor Girişi**: Maç sonrası skor tablosu ve puanlama
- **Liderlik Tablosu**: Otomatik güncellenen sıralama sistemi
- **Özet Paylaşımı**: Parti sonucu özet kartı

### 🏆 Bonus Özellikler
- **Achievement Sistemi**: Rozet ve başarı sistemi
- **Kişisel İstatistikler**: Kullanıcı bazlı performans takibi
- **Responsive Tasarım**: Mobil uyumlu arayüz
- **Real-time Updates**: Anlık veri güncellemeleri

## 🛠️ Teknolojiler

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Veritabanı**: MySQL 5.7+
- **Styling**: Custom CSS (Gradient design)
- **Icons**: Font Awesome 6

## 📁 Proje Yapısı

```
game-party-hub/
├── index.html              # Ana sayfa
├── scores.html             # Skor girişi sayfası
├── css/
│   └── style.css           # Ana stil dosyası
├── js/
│   └── main.js             # Frontend JavaScript
├── api/                    # PHP API dosyaları
│   ├── users.php           # Kullanıcı yönetimi
│   ├── games.php           # Oyun listesi
│   ├── parties.php         # Parti yönetimi
│   ├── party_details.php   # Parti detayları
│   ├── vote_game.php       # Oyun oylama
│   ├── slots.php           # Zaman slotu yönetimi
│   ├── matches.php         # Maç yönetimi
│   └── leaderboard.php     # Liderlik tablosu
├── includes/
│   └── config.php          # Veritabanı bağlantısı
├── images/
│   ├── games/              # Oyun resimleri
│   └── avatars/            # Kullanıcı avatarları
├── database.sql            # Veritabanı yapısı
└── README.md
```

## 🚀 Kurulum

### 1. Gereksinimler
- XAMPP/WAMP/MAMP (Apache + MySQL + PHP)
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri

### 2. Kurulum Adımları

1. **Projeyi İndirin**
   ```bash
   # Proje dosyalarını XAMPP/htdocs klasörüne kopyalayın
   cp -r game-party-hub /xampp/htdocs/
   ```

2. **Veritabanını Oluşturun**
   - phpMyAdmin'e girin (http://localhost/phpmyadmin)
   - `database.sql` dosyasını import edin
   - Veritabanı otomatik olarak oluşturulacak

3. **Ayarları Yapın**
   - `includes/config.php` dosyasını açın
   - MySQL bağlantı bilgilerinizi düzenleyin:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'game_party_hub');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // XAMPP için genellikle boş
   ```

4. **Projeyi Başlatın**
   - Apache ve MySQL servislerini başlatın
   - Tarayıcıda http://localhost/game-party-hub açın

## 📖 Kullanım Kılavuzu

### 1. Kullanıcı Girişi
- Ana sayfada adınızı ve gamer tag'inizi girin
- "Kullanıcı Ayarla" butonuna tıklayın

### 2. Parti Oluşturma
- "Yeni Parti Oluştur" butonuna tıklayın
- Parti başlığı ve tarih seçin
- 3-5 adet oyun seçin
- "Parti Oluştur" ile tamamlayın

### 3. Partiye Katılma
- "Partiye Katıl" butonunu kullanın
- Davet kodunu girin veya parti listesinden seçin

### 4. Oyun Oylama
- Parti detaylarından oyunlara oy verin
- En çok oy alan oyun otomatik seçilir

### 5. Zaman Slotu Planlama
- Host olarak zaman slotları ekleyin
- Katılımcılar uygun saatleri işaretler

### 6. Skor Girişi
- "Skor Girişi" sayfasına gidin
- Maç oluşturun ve sonuçları girin
- Liderlik tablosu otomatik güncellenir

## 🏆 Puanlama Sistemi

- **Galibiyet**: 3 puan
- **Beraberlik**: 1 puan  
- **Yenilgi**: 0 puan

## 🎯 Achievement Sistemi

- **Hot Streak** 🔥: 3+ galibiyet
- **Dominator** 👑: %80+ galibiyet oranı
- **Dedicated** 🎯: 5+ maç
- **High Scorer** ⭐: 50+ ortalama skor
- **Party Animal** 🎉: 10+ parti (global)
- **Century Club** 💯: 100+ puan (global)

## 🔧 API Endpoints

### Kullanıcılar
- `POST /api/users.php` - Kullanıcı oluştur/getir
- `GET /api/users.php` - Kullanıcı listesi

### Partiler
- `GET /api/parties.php` - Parti listesi
- `POST /api/parties.php` - Parti oluştur/katıl/güncelle
- `GET /api/party_details.php` - Parti detayları

### Oyunlar
- `GET /api/games.php` - Oyun listesi
- `POST /api/vote_game.php` - Oyun oylama

### Slotlar
- `POST /api/slots.php` - Slot oluştur/oyla

### Maçlar
- `GET /api/matches.php` - Maç listesi
- `POST /api/matches.php` - Maç oluştur/skor gir

### Liderlik
- `GET /api/leaderboard.php` - Liderlik tablosu

## 🎨 Tasarım

Proje modern, gradient tabanlı bir tasarım kullanır:
- **Ana Renkler**: Mor-mavi gradient (#667eea → #764ba2)
- **Typography**: Arial, sans-serif
- **Responsive**: Mobil uyumlu grid sistemi
- **Animations**: Hover efektleri ve transitions

## 📊 Veritabanı Şeması

### Ana Tablolar
- `users` - Kullanıcı bilgileri
- `games` - Oyun kataloğu
- `parties` - Parti bilgileri
- `party_participants` - Parti katılımcıları
- `game_votes` - Oyun oyları
- `slots` - Zaman slotları
- `slot_votes` - Slot oyları
- `matches` - Maç bilgileri
- `scores` - Skor kayıtları

## 🐛 Troubleshooting

### Yaygın Sorunlar

1. **Veritabanı Bağlantı Hatası**
   - MySQL servisinin çalıştığından emin olun
   - `config.php` ayarlarını kontrol edin

2. **API 404 Hatası**
   - Apache mod_rewrite aktif olmalı
   - Dosya yollarını kontrol edin

3. **JavaScript Hatası**
   - Browser console'u kontrol edin
   - CORS ayarlarını kontrol edin

## 👥 Geliştirici Notları

### Code Night Süreci
- **Süre**: 6-8 saat
- **Metodoloji**: Agile/MVP odaklı
- **Öncelik**: Çalışabilirlik > Özellik zenginliği

### Geliştirme Sırası
1. Veritabanı tasarımı (30 dk)
2. Temel API'ler (2 saat)
3. Frontend ana sayfa (1.5 saat)
4. Parti yönetimi (1.5 saat)
5. Oylama sistemi (1 saat)
6. Skor ve liderlik (1.5 saat)
7. Test ve polish (1 saat)

## 📞 Destek

Sorularınız için:
- GitHub Issues
- Code Night Discord

## 📄 Lisans

Bu proje Turkcell Codenight etkinliği kapsamında geliştirilmiştir.

---

**🎮 İyi Oyunlar! Game+ Party Hub ile arkadaşlarınızla oyun keyfini doyasıya yaşayın!**
