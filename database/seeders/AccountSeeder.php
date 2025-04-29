<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['code' => '10100', 'name' => 'Kas Dan Setara Kas', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10200', 'name' => 'Bank Rupiah', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10300', 'name' => 'Deposit', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10400', 'name' => 'Piutang', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10500', 'name' => 'Piutang Lainnya', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10600', 'name' => 'Persediaan', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10700', 'name' => 'Persediaan WIP', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10800', 'name' => 'Pembayaran Dimuka', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '10900', 'name' => 'Pajak Dibayar Dimuka', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11100', 'name' => 'Investasi Jangka Pendek', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11200', 'name' => 'Investasi Surat Berharga', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11300', 'name' => 'Investasi Penyertaan Modal', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11400', 'name' => 'Aset Program Imbalan Pasca Kerja', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11500', 'name' => 'Aset Tetap - Nilai Perolehan', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11600', 'name' => 'Aset Tetap - Akumulasi Penyusutan', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11700', 'name' => 'Aset Tidak Berwujud - Nilai Perolehan', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11800', 'name' => 'Amortisasi Aset Tidak Berwujud', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '11900', 'name' => 'Aset Lainnya', 'status' => 'D', 'type' => 'Assets'],
            ['code' => '20100', 'name' => 'Utang Usaha Bersih', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20200', 'name' => 'Utang Lancar Lainnya', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20300', 'name' => 'Utang Pajak', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20400', 'name' => 'Utang Bank Dan Non Bank Jangka Pendek', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20500', 'name' => 'Utang Gaji', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20600', 'name' => 'Utang Bank Dan Non Bank Jangka Panjang', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '20700', 'name' => 'Utang Imbalan Kerja Jangka Panjang (Psak 24)', 'status' => 'K', 'type' => 'Liabilities'],
            ['code' => '30100', 'name' => 'Ekuitas', 'status' => 'K', 'type' => 'Ekuitas'],
            ['code' => '40100', 'name' => 'Penjualan Barang Dan Jasa', 'status' => 'K', 'type' => 'Pendapatan'],
            ['code' => '40200', 'name' => 'Potongan Pembelian', 'status' => 'K', 'type' => 'Pendapatan'],
            ['code' => '40300', 'name' => 'Pendapatan Lainnya', 'status' => 'K', 'type' => 'Pendapatan'],
            ['code' => '40400', 'name' => 'Retur Penjualan & Penalti', 'status' => 'K', 'type' => 'Pendapatan'],
            ['code' => '50100', 'name' => 'Beban Pemakaian Bahan Baku', 'status' => 'D', 'type' => 'Harga Pokok Produksi'],
            ['code' => '50200', 'name' => 'Beban Penyedia Jasa Subkontrak', 'status' => 'D', 'type' => 'Harga Pokok Produksi'],
            ['code' => '60101', 'name' => 'Beban Gaji Langsung', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60102', 'name' => 'Beban Upah Tenaga Kontrak Langsung (Pulsa)', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60103', 'name' => 'Beban Konsultan, Tenaga Ahli Dan Agen Pulsa', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60104', 'name' => 'Beban Gaji Overhead Pulsa', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60105', 'name' => 'Beban Bahan Pendukung Usaha', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60106', 'name' => 'Beban Sewa Alat & Pengiriman', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60107', 'name' => 'Beban Perjalanan Dinas', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60108', 'name' => 'Beban Keselamatan Kerja', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60109', 'name' => 'Beban Pemeliharaan Dan Perbaikan Aset Tetap', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60110', 'name' => 'Beban Lain-Lain', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60111', 'name' => 'Pengurangan Penjualan', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60112', 'name' => 'Beban Board Of Director (Bod)', 'status' => 'D', 'type' => 'Biaya'],
            ['code' => '60113', 'name' => 'Beban Lain-Lain Corporate', 'status' => 'D', 'type' => 'Biaya']
        ];

        Account::insert($accounts);
    }
}
