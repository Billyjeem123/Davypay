<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformRevenueController extends Controller
{
    public function index()
    {
        $stats = [
            'total_profit' => PlatformRevenue::sum('profit'),
            'total_revenue' => PlatformRevenue::sum('amount'),
            'total_transactions' => PlatformRevenue::count(),
            'successful_transactions' => PlatformRevenue::where('status', 'delivered')->count(),
        ];

        // Monthly profit and revenue chart data (last 12 months)
        $monthlyData = PlatformRevenue::select(
            DB::raw("DATE_FORMAT(transaction_date, '%b %Y') as month"),
            DB::raw("SUM(profit) as total_profit"),
            DB::raw("SUM(amount) as total_revenue")
        )
            ->whereNotNull('transaction_date')
            ->groupBy('month')
            ->orderBy(DB::raw("MIN(transaction_date)"))
            ->take(12)
            ->get();

        $chartMonths = $monthlyData->pluck('month');
        $chartProfits = $monthlyData->pluck('total_profit');
        $chartRevenues = $monthlyData->pluck('total_revenue');

        // Recent transactions
        $transactions = PlatformRevenue::latest()->get();

        return view('dashboard.revenues.index', compact(
            'stats',
            'transactions',
            'chartMonths',
            'chartProfits',
            'chartRevenues'
        ));
    }

}
