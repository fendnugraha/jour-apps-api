<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acc_coa = [
            ['acc_code' => '10100-001', 'acc_name' => 'Kas Kecil', 'account_id' => 1, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => true],
            ['acc_code' => '10100-002', 'acc_name' => 'Kas Besar Admin (Dana Belum Disetor)', 'account_id' => 1, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => false],
            ['acc_code' => '10200-001', 'acc_name' => 'Bank BCA (Headquarter)', 'account_id' => 2, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => false],
            ['acc_code' => '10200-002', 'acc_name' => 'Bank MANDIRI (Headquarter)', 'account_id' => 2, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => false],
            ['acc_code' => '10200-003', 'acc_name' => 'Bank BRI (Headquarter)', 'account_id' => 2, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => false],
            ['acc_code' => '10200-004', 'acc_name' => 'Bank BNI (Headquarter)', 'account_id' => 2, 'st_balance' => 0, 'warehouse_id' => 1, 'is_locked' => false],
            ['acc_code' => '10400-001', 'acc_name' => 'Piutang Usaha', 'account_id' => 4, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '10400-002', 'acc_name' => 'Piutang Karyawan', 'account_id' => 4, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '10400-003', 'acc_name' => 'Piutang Service Belum Diambil', 'account_id' => 4, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '10600-001', 'acc_name' => 'Persediaan Barang', 'account_id' => 6, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '20100-001', 'acc_name' => 'Hutang Usaha', 'account_id' => 19, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '20100-002', 'acc_name' => 'Deposit Customer', 'account_id' => 19, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '20100-003', 'acc_name' => 'Fee Customer (Markup)', 'account_id' => 19, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '20200-001', 'acc_name' => 'Hutang Gaji, THR Dan Upah', 'account_id' => 20, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '30100-001', 'acc_name' => 'Modal (Ekuitas)', 'account_id' => 26, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '30100-002', 'acc_name' => 'Laba Rugi', 'account_id' => 26, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '30100-003', 'acc_name' => 'Modal Diambil (Prive)', 'account_id' => 26, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '40100-001', 'acc_name' => 'Penjualan Barang & Jasa', 'account_id' => 27, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '40100-002', 'acc_name' => 'Pendapatan Jasa Service Teknisi', 'account_id' => 27, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '40200-001', 'acc_name' => 'Potongan Pembelian', 'account_id' => 28, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '40300-001', 'acc_name' => 'Pendapatan Lainnya', 'account_id' => 29, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '40300-002', 'acc_name' => 'Bunga Bank', 'account_id' => 29, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '50100-001', 'acc_name' => 'Harga Pokok Penjualan (HPP)', 'account_id' => 31, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60105-001', 'acc_name' => 'Beban Listrik', 'account_id' => 37, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60105-002', 'acc_name' => 'Beban Jasa Internet dan Telepon', 'account_id' => 37, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60105-003', 'acc_name' => 'Beban Pulsa dan Kuota', 'account_id' => 37, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '60109-001', 'acc_name' => 'Beban Pemeliharaan Listrik, Air, Internet, Telepon, CCTV', 'account_id' => 41, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60109-002', 'acc_name' => 'Beban Pemeliharaan Peralatan', 'account_id' => 41, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60109-003', 'acc_name' => 'Beban Pemeliharaan Spanduk dan Banner', 'account_id' => 41, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60109-004', 'acc_name' => 'Beban Pemeliharaan Kendaraan', 'account_id' => 41, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60101-001', 'acc_name' => 'Beban Gaji Karyawan Langsung', 'account_id' => 33, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60101-002', 'acc_name' => 'Beban Upah Harian', 'account_id' => 33, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60101-003', 'acc_name' => 'Beban Upah Lembur Karyawan', 'account_id' => 33, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60101-004', 'acc_name' => 'Beban Uang Makan Karyawan', 'account_id' => 33, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60101-005', 'acc_name' => 'Beban THR Karyawan', 'account_id' => 33, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-001', 'acc_name' => 'Beban Rumah Tangga Kantor', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-002', 'acc_name' => 'Beban Keamanan', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-003', 'acc_name' => 'Beban Kebersihan', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-004', 'acc_name' => 'Beban Alat Tulis Kantor', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-005', 'acc_name' => 'Beban Percetakan', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-006', 'acc_name' => 'Beban BBM, Transportasi, Gas LPJ', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-007', 'acc_name' => 'Beban Ongkir Paket, Materai, dll', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60110-008', 'acc_name' => 'Beban Jamuan Tamu', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '60110-009', 'acc_name' => 'Beban Bonus Penjualan', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => false],
            ['acc_code' => '60110-010', 'acc_name' => 'Beban Penyesuaian Stok dan Kas', 'account_id' => 42, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60111-001', 'acc_name' => 'Potongan Penjualan', 'account_id' => 43, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60113-001', 'acc_name' => 'Beban Sponsorship dan Sumbangan', 'account_id' => 45, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60113-002', 'acc_name' => 'Beban Pajak', 'account_id' => 45, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60113-003', 'acc_name' => 'Beban Biaya Administrasi Bank', 'account_id' => 45, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
            ['acc_code' => '60113-004', 'acc_name' => 'Beban Asuransi BPJS Kesehatan - (4%) Langsung', 'account_id' => 45, 'st_balance' => 0, 'warehouse_id' => null, 'is_locked' => true],
        ];

        ChartOfAccount::insert($acc_coa);
    }
}
