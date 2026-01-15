1. FLOW ORDER PMO (Sales Order)
Proses Utama:

Cart Management → Submit Order → Insert ke data_part.tblso
Detail Flow:

Add to Cart (addCartController.php)

User menambahkan part ke temporary cart
Data disimpan di pmo.tb_temp_cart dan pmo.tb_temp_cart_detail
Mengecek stock dari data_part.tblstock_part_id
List Cart (listCartController.php)

Menampilkan isi cart
Menghitung total harga, diskon, sub_price
Update cart dengan harga terbaru
Submit Order (SubmitOrderController.php) - INI INTI NYA

Generate nomor SO dengan format: TAHUN/NOMOR/POD-PD (contoh: 2025/000123/POD-PD)
Insert ke data_part.tblso (tabel SO header) dengan field:
no_so, jenis_so (Oli Regular/Other), tgl_so, fk_salesman, fk_toko, grand_total
status_approve_reject = 'Waiting For Approval'
Insert ke data_part.tblso_detail (tabel SO detail) untuk setiap part:
fk_so, fk_part, harga, qty_so, total_harga, qty_sisa
Clear temporary cart
Update serial number counter di pmo.tbl_serial
Kirim notifikasi WhatsApp ke Sales & SPV
2. TABEL UTAMA PMO
Schema data_part:

tblso / tblso_id - Header Sales Order
tblso_detail / tblso_detail_id - Detail item SO
tblstock_part_id - Stock part per bulan/tahun
tbldo_id - Delivery Order (hasil dari SO yang diapprove)
tbldo_detail_id - Detail DO
Schema pmo:

tb_temp_cart - Temporary cart header
tb_temp_cart_detail - Temporary cart detail
SuggetedOrder - Suggested order berdasarkan sales history
tb_part_flag - Flag part (hotline, promo, dll)
tbl_serial - Counter untuk generate nomor SO
3. MODEL PENTING
M_so_part.php → Model untuk data_part.tblso
M_so_part_detail.php → Model untuk data_part.tblso_detail
M_temp_cart.php → Model temporary cart
M_temp_cart_detail.php → Model detail cart
M_stok_part_MD.php → Model stock part
4. JENIS ORDER
Oli Regular - Jika mayoritas part adalah OIL
Other - Jika mayoritas part bukan OIL
Sistem ini adalah flow lengkap dari pencarian part → add to cart → submit order → insert ke data_part.tblso yang merupakan inti dari PMO (Part Mobile Ordering).