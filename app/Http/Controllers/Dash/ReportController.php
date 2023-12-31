<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\BarangMasuk;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected $label, $id_page;

    public function __construct()
    {
        $this->id_page = [11, 12, 13];
    }

    /**
     * Render view purchase report
     * 
     * @return View
     */
    protected function showPurchaseReport()
    {
        $barangmasuk = Barangmasuk::all();

        $data = [
            'title' => 'Data Pembelian Barang',
            'id_page' => $this->id_page[0],
            'purchase_reports' => Purchase::all(),
            'barangmasuk' => $barangmasuk,
        ];

        return view('dash.reports.purchase_report', $data);
    }

    /**
     * Logic to delete purchase report
     * 
     * @param int $purchase_id
     * @return RedirectResponse
     */
    protected function deleteReportPurchase($purchase_id)
    {
        DB::table('purchase')->where('purchase_id', $purchase_id)->delete();

        return back()->with('info', 'Purchase report has been deleted');
    }


    /**
     * Render view purchase report
     * 
     * @return View
     */
    protected function showSalesReport()
    {
        $data = [
            'title' => 'Laporan Penjualan',
            'id_page' => $this->id_page[1],
            'sales_report' => Order::all(),
            'income'    => Customer::sum('total_harga'),
        ];

        return view('dash.reports.sales_report', $data);
    }

    public function cetakSalesReport(Request $request)
    {
        $tanggalAwal = $request->input('tanggalAwal');
        $tanggalAkhir = $request->input('tanggalAkhir');

        $sales = Order::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])->get();

        $pdf = PDF::loadView('pdf.report_sales', compact('sales', 'tanggalAwal', 'tanggalAkhir'));
        return $pdf->stream('sales_report.pdf');
    }

    /**
     * Logic to delete purchase report
     * 
     * @param int $purchase_id
     * @return RedirectResponse
     */
    protected function deleteReportSales($customer_id)
    {
        DB::table('customer')->where('customer_id', $customer_id)->delete();

        return back()->with('info', 'Sales report has been deleted');
    }


    /**
     * Render view stock report
     * 
     * @return View
     */
    protected function showStockReport()
    {
        $data = [
            'title' => 'Laporan Persediaan',
            'id_page' => $this->id_page[2],
            'sub_page'  => 'stock1',
            'barang'    => Barang::all(),
        ];

        return view('dash.reports.stock_report', $data);
    }

    /**
     * Render view low stock report
     * 
     * @return View
     */
    protected function showLowStock()
    {
        $lowStock = Barang::with(['bentuk'])->whereColumn('isi', '<=', 'minimal_stok');

        $infoLowStock = $lowStock->count();

        $data = [
            'title' => 'Barang Hampir Habis',
            'id_page' => $this->id_page[2],
            'sub_page' => 'stock2',
            'barang' => $lowStock->get(),
            'info'  => $infoLowStock,
        ];

        return view('dash.reports.stock_report', $data);
    }

    /**
     * Render view almost expired stock report
     * 
     * @return View
     */
    protected function showAlmostExp()
    {
        date_default_timezone_set('Asia/Jakarta');
        $today = Carbon::now();
        $expiredDate = $today->copy()->addDays(30);
        $almostExp = DB::table('barang')
            ->whereDate('tanggal_kedaluwarsa', '>=', $today)
            ->whereDate('tanggal_kedaluwarsa', '<=', $expiredDate);

        $infoAlmostExp = $almostExp->count();

        $data = [
            'title' => 'Kedaluwarsa',
            'id_page' => $this->id_page[2],
            'sub_page'  => 'stock3',
            'barang'    => $almostExp->get(),
            'info'  => $infoAlmostExp,
        ];

        return view('dash.reports.stock_report', $data);
    }
}
