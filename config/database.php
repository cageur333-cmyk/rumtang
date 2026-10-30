<?php
// config/database.php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'rumtang_db');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $isLocal = in_array(DB_HOST, ['localhost', '127.0.0.1', '::1']);
            
            $pdoOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // Enable SSL for cloud MySQL (TiDB Cloud, Aiven, PlanetScale, etc.)
            if (!$isLocal && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            try {
                if ($isLocal) {
                    // First ensure database exists on local
                    $dsnServer = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $pdoServer = new PDO($dsnServer, DB_USER, DB_PASS, $pdoOptions);
                    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                }

                // Connect to database
                $dsnDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsnDb, DB_USER, DB_PASS, $pdoOptions);

                // Ensure schema & seeding
                self::ensureSchemaAndSeed(self::$instance);

            } catch (PDOException $e) {
                die("Koneksi Database Gagal: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function ensureSchemaAndSeed(PDO $pdo): void {
        // Create tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `code` VARCHAR(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT NOT NULL,
                `name` VARCHAR(150) NOT NULL UNIQUE,
                `stok_awal` INT NOT NULL DEFAULT 0,
                `satuan` VARCHAR(50) NOT NULL DEFAULT 'PCS',
                `stok_min` INT NOT NULL DEFAULT 2,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (`category_id`),
                CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `trans_no` VARCHAR(50) NULL,
                `tanggal` DATE NOT NULL,
                `jenis` ENUM('MASUK', 'KELUAR') NOT NULL,
                `item_id` INT NOT NULL,
                `jumlah` INT NOT NULL,
                `satuan` VARCHAR(50) NOT NULL,
                `penerima_pemohon` VARCHAR(100) NOT NULL,
                `keperluan_lokasi` VARCHAR(150) NOT NULL,
                `keterangan` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_item (`item_id`),
                INDEX idx_tanggal (`tanggal`),
                INDEX idx_jenis (`jenis`),
                CONSTRAINT `fk_transactions_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_no` VARCHAR(50) NOT NULL UNIQUE,
                `tanggal` DATE NOT NULL,
                `pemohon` VARCHAR(100) NOT NULL,
                `lokasi_tujuan` VARCHAR(150) NOT NULL,
                `status` ENUM('PENDING', 'DISETUJUI', 'DITOLAK', 'SELESAI') DEFAULT 'PENDING',
                `catatan` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `item_id` INT NOT NULL,
                `jumlah` INT NOT NULL,
                `satuan` VARCHAR(50) NOT NULL,
                `keterangan` VARCHAR(255) NULL,
                CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_order_items_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Create or replace view
        $pdo->exec("
            CREATE OR REPLACE VIEW `v_inventory_stock` AS
            SELECT 
                i.id,
                i.category_id,
                c.name AS category_name,
                c.code AS category_code,
                i.name,
                i.stok_awal,
                i.satuan,
                i.stok_min,
                COALESCE(SUM(CASE WHEN t.jenis = 'MASUK' THEN t.jumlah ELSE 0 END), 0) AS total_masuk,
                COALESCE(SUM(CASE WHEN t.jenis = 'KELUAR' THEN t.jumlah ELSE 0 END), 0) AS total_keluar,
                (i.stok_awal + COALESCE(SUM(CASE WHEN t.jenis = 'MASUK' THEN t.jumlah ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.jenis = 'KELUAR' THEN t.jumlah ELSE 0 END), 0)) AS stok_akhir,
                CASE 
                    WHEN (i.stok_awal + COALESCE(SUM(CASE WHEN t.jenis = 'MASUK' THEN t.jumlah ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.jenis = 'KELUAR' THEN t.jumlah ELSE 0 END), 0)) <= 0 THEN 'HABIS'
                    WHEN (i.stok_awal + COALESCE(SUM(CASE WHEN t.jenis = 'MASUK' THEN t.jumlah ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN t.jenis = 'KELUAR' THEN t.jumlah ELSE 0 END), 0)) <= i.stok_min THEN 'MENIPIS'
                    ELSE 'AMAN'
                END AS status_stok
            FROM items i
            JOIN categories c ON i.category_id = c.id
            LEFT JOIN transactions t ON i.id = t.item_id
            GROUP BY i.id, i.category_id, c.name, c.code, i.name, i.stok_awal, i.satuan, i.stok_min;
        ");

        // Check if items table has records, if not, auto seed
        $stmt = $pdo->query("SELECT COUNT(*) FROM items");
        if ($stmt->fetchColumn() == 0) {
            self::seedInitialData($pdo);
        }
    }

    public static function seedInitialData(PDO $pdo): void {
        $seedFile = __DIR__ . '/../seed/seed_data.php';
        if (!file_exists($seedFile)) {
            return;
        }

        $data = require $seedFile;

        $pdo->beginTransaction();
        try {
            // Seed Categories
            $stmtCat = $pdo->prepare("INSERT INTO categories (id, name, code) VALUES (:id, :name, :code) ON DUPLICATE KEY UPDATE name=VALUES(name)");
            foreach ($data['categories'] as $cat) {
                $stmtCat->execute($cat);
            }

            // Seed Items
            $stmtItem = $pdo->prepare("INSERT INTO items (id, category_id, name, stok_awal, satuan, stok_min) VALUES (:id, :category_id, :name, :stok_awal, :satuan, :stok_min)");
            $itemNameMap = [];
            foreach ($data['items'] as $item) {
                $stmtItem->execute($item);
                $itemNameMap[trim(strtolower($item['name']))] = $item['id'];
            }

            // Seed Transactions
            $stmtTrans = $pdo->prepare("INSERT INTO transactions (id, trans_no, tanggal, jenis, item_id, jumlah, satuan, penerima_pemohon, keperluan_lokasi, keterangan) VALUES (:id, :trans_no, :tanggal, :jenis, :item_id, :jumlah, :satuan, :penerima_pemohon, :keperluan_lokasi, :keterangan)");
            foreach ($data['transactions'] as $tx) {
                $rawNo = $tx[0];
                $transDate = $tx[1];
                $transType = strtoupper(trim($tx[2]));
                $rawItemName = trim($tx[3]);
                $qty = (int)$tx[4];
                $satuan = trim($tx[5]);
                $penerima = trim($tx[6]);
                $lokasi = trim($tx[7]);

                $key = strtolower($rawItemName);
                if (!isset($itemNameMap[$key])) {
                    // Try to match fuzzy
                    foreach ($itemNameMap as $nameKey => $idVal) {
                        if (str_contains($key, $nameKey) || str_contains($nameKey, $key)) {
                            $itemId = $idVal;
                            break;
                        }
                    }
                    if (!isset($itemId)) {
                        continue;
                    }
                } else {
                    $itemId = $itemNameMap[$key];
                }

                $transNo = sprintf("TRX-%04d", $rawNo);
                $stmtTrans->execute([
                    ':id' => $rawNo,
                    ':trans_no' => $transNo,
                    ':tanggal' => $transDate,
                    ':jenis' => $transType,
                    ':item_id' => $itemId,
                    ':jumlah' => $qty,
                    ':satuan' => $satuan,
                    ':penerima_pemohon' => $penerima,
                    ':keperluan_lokasi' => $lokasi,
                    ':keterangan' => 'Import data awal',
                ]);
            }

            // Also seed a sample pending order so user can see order management immediately!
            $stmtOrder = $pdo->prepare("INSERT INTO orders (order_no, tanggal, pemohon, lokasi_tujuan, status, catatan) VALUES (:order_no, :tanggal, :pemohon, :lokasi_tujuan, :status, :catatan)");
            $stmtOrder->execute([
                ':order_no' => 'ORD-202610-001',
                ':tanggal' => '2026-10-28',
                ':pemohon' => 'REZKI',
                ':lokasi_tujuan' => 'DIKA 1',
                ':status' => 'PENDING',
                ':catatan' => 'Permohonan mingguan untuk kebutuhan operasional cleaning'
            ]);
            $orderId = $pdo->lastInsertId();

            $stmtOrderItem = $pdo->prepare("INSERT INTO order_items (order_id, item_id, jumlah, satuan, keterangan) VALUES (:order_id, :item_id, :jumlah, :satuan, :keterangan)");
            // Order 2 items
            $stmtOrderItem->execute([
                ':order_id' => $orderId,
                ':item_id' => 1, // Dishwashing
                ':jumlah' => 3,
                ':satuan' => 'GALON',
                ':keterangan' => 'Stok pantry menipis'
            ]);
            $stmtOrderItem->execute([
                ':order_id' => $orderId,
                ':item_id' => 14, // Plastik sampah 60x100
                ':jumlah' => 5,
                ':satuan' => 'PCS',
                ':keterangan' => 'Kebutuhan koridor'
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

