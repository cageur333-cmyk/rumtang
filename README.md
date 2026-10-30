# 📦 Dashboard Inventaris & Order Rumtang (Rumah Tangga & Chemical)

Aplikasi web dashboard sistem informasi inventaris, mutasi stok barang (masuk & keluar), serta sistem pengajuan order permohonan barang untuk divisi **Rumah Tangga & Chemical**.

---

## 🚀 Akses Cepat Aplikasi di Browser

Karena proyek ini sudah berada di folder `C:\laragon\www\rumtang\` dan server Laragon Anda (Apache & MySQL) sudah aktif, aplikasi bisa langsung dibuka melalui browser:

- 👉 **[http://localhost/rumtang/](http://localhost/rumtang/)**
- Atau via virtual host Laragon: **[http://rumtang.test/](http://rumtang.test/)**

---

## 🗄️ Konfigurasi Database (MySQL)

- **Host**: `localhost` / `127.0.0.1`
- **Port**: `3306`
- **User**: `root`
- **Password**: *(kosong)*
- **Nama Database**: `rumtang_db`

> **Note**: Database `rumtang_db` beserta tabel-tabel dan data awal (53 item & 227 riwayat transaksi yang Anda berikan) sudah **otomatis diinisialisasi & di-seed secara lengkap**.

---

## ✨ Fitur-Fitur Utama

### 1. 📊 Dashboard Overview & Analitik (`index.php`)
- **Kartu Ringkasan Real-Time**: Total Stok Fisik, Varian Barang, Total Masuk, Total Keluar, dan Peringatan Stok Menipis/Habis.
- **Grafik Tren Pergerakan Bulanan**: Visualisasi perbandingan volume barang masuk vs keluar (Juli – Oktober 2026).
- **Grafik Top 8 Barang Terpakai**: Menampilkan barang dengan frekuensi pemakaian tertinggi (Plastik Sampah, Stella, Floor Cleaner, dll).
- **Grafik Distribusi Lokasi**: Proporsi alokasi barang ke unit (DIKA 1, DIKA 2, TONDANO, TANGERANG, BEKASI, MATRAMAN, dll).
- **Peringatan Stok Kritis (Restock Alert)**: Menampilkan barang yang stoknya di bawah batas minimum dengan shortcut order cepat.
- **Tabel Transaksi Terkini**: Mutasi 8 transaksi terbaru.

### 2. 📦 Inventaris & Stok Barang (`inventaris.php`)
- Menampilkan seluruh 53 barang dalam 3 kategori:
  1. **CHEMICALS** (Dishwashing, Karbol, Handsoap, Floor cleaner, dll)
  2. **ALAT KEBERSIHAN** (Plastik sampah, Stella, Sapu, Kain pel, Spons, dll)
  3. **STOK LAINNYA** (Cat, Roll cat, Tong sampah, Lampu, dll)
- Filter kategori dengan 1 klik tab & status stok (Aman, Menipis, Habis).
- Live instant search nama barang.
- Form **Tambah Barang Baru** & **Edit Barang** (Nama, Kategori, Satuan, Stok Min, Stok Awal).
- Tombol **Quick Mutasi** pada setiap baris untuk input langsung barang masuk / keluar.
- Export data stok ke file **Excel / CSV** & mode Cetak.

### 3. 🔄 Log Mutasi Transaksi (`transaksi.php`)
- Pencatatan seluruh transaksi Masuk & Keluar (terdata lengkap 227 transaksi awal).
- Form **Catat Transaksi Baru**:
  - Pilihan barang dinamis lengkap dengan indikator sisa stok fisik saat ini.
  - Validasi otomatis: Mencegah pengeluaran barang jika jumlah yang diminta melebihi stok yang tersedia.
  - Auto-fill satuan sesuai data master.
  - Autocomplete nama pemohon dan lokasi tujuan.
- Filter transaksi berdasarkan: Jenis (MASUK/KELUAR), Lokasi Keperluan, Rentang Tanggal, dan Kata Kunci.
- Export riwayat mutasi transaksi ke **Excel / CSV**.

### 4. 📝 Sistem Order & Permohonan Barang Online (`order.php`)
- Form pengajuan kebutuhan operasional via web:
  - Nomor Order otomatis (`ORD-YYYYMM-XXX`).
  - Pemohon & Lokasi Tujuan.
  - Multi-item picker: Bisa order banyak barang sekaligus dalam 1 formulir.
  - Catatan khusus permohonan.
- Manajemen Status Permohonan:
  - Status: `PENDING` (Kuning), `DISETUJUI` (Biru), `DITOLAK` (Merah), `SELESAI` (Hijau).
  - Modal rincian barang per order.
  - **Fitur Otomatisasi Cerdas**: Saat order disetujui / diselesaikan, Anda dapat mencentang *"Otomatis Potong Stok (Catat Barang Keluar)"* sehingga stok di database langsung terpotong secara otomatis tanpa perlu input manual dua kali!

### 5. 📑 Laporan Rekapitulasi & Cetak PDF (`laporan.php`)
- Format dokumen resmi dengan kop Departemen Rumah Tangga & Logistik.
- Rekapitulasi stok awal, total masuk, total keluar, dan stok akhir per kategori & total keseluruhan.
- Rekapitulasi konsumsi barang per lokasi kerja dan per nama pemohon.
- Kolom tanda tangan resmi penanggung jawab gudang & koordinator.
- Desain cetak ramah printer (`@media print` siap simpan ke PDF atau dicetak ke kertas).

---

## 🛠️ Struktur Berkas Proyek

```
c:\laragon\www\rumtang\
├── config/
│   └── database.php           # Koneksi PDO & Auto Database/Schema/View Seeder
├── includes/
│   ├── header.php             # Head, Tailwind CSS, FontAwesome, ChartJS
│   ├── sidebar.php            # Menu navigasi & badge counter stok menipis
│   ├── navbar.php             # Header sticky & shortcut aksi cepat
│   └── footer.php             # Footer & modal scripts
├── api/
│   ├── save_transaction.php   # Simpan/update transaksi mutasi
│   ├── delete_transaction.php # Hapus transaksi
│   ├── save_item.php          # Tambah/edit barang master
│   ├── delete_item.php        # Hapus barang master
│   ├── save_order.php         # Simpan formulir order barang
│   ├── update_order_status.php# Update status order & auto-potong stok
│   ├── delete_order.php       # Hapus order
│   ├── get_order_details.php  # JSON fetch detail order
│   └── export_csv.php         # Export CSV stok & transaksi
├── seed/
│   └── seed_data.php          # Data master 53 barang & 227 transaksi awal
├── index.php                  # Dashboard & visual charts
├── inventaris.php             # Master data stok & inventaris barang
├── transaksi.php              # Log mutasi masuk & keluar
├── order.php                  # Pengajuan & kelola order barang
└── laporan.php                # Dokumen laporan resmi siap cetak / PDF
```

