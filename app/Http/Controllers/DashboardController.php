<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private const CATEGORIES = ['MEALS', 'SNACKS', 'DRINKS', 'EXTRAS'];

    public function __invoke(Request $request): View
    {
        $period = $request->validate([
            'period' => ['nullable', 'in:today,7days,month,all'],
        ])['period'] ?? 'today';
        [$from, $to] = $this->periodBounds($period);

        $orders = Order::query()->whereNotNull('paid_at')->where('status', '!=', 'voided');
        $this->applyPeriod($orders, $from, $to);
        $summary = (clone $orders)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total), 0) as sales_total, COALESCE(SUM(discount_amount), 0) as discount_total')
            ->first();
        $salesOrders = (clone $orders)->orderBy('paid_at')->get(['paid_at', 'total']);
        $salesChart = $this->salesChart($salesOrders, $period);

        $itemSales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')
            ->where('orders.status', '!=', 'voided')
            ->whereNotNull('order_items.category');
        if ($from && $to) {
            $itemSales->whereBetween('orders.paid_at', [$from, $to]);
        }

        $topSellers = $itemSales
            ->selectRaw('order_items.category, order_items.name, SUM(order_items.quantity) as units_sold, SUM(order_items.quantity * order_items.unit_price) as item_sales')
            ->groupBy('order_items.category', 'order_items.name')
            ->orderByDesc('units_sold')
            ->orderByDesc('item_sales')
            ->get()
            ->groupBy('category')
            ->map(fn ($products) => $products->first());

        return view('dashboard', [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period),
            'summary' => $summary,
            'salesChart' => $salesChart,
            'topSellers' => collect(self::CATEGORIES)->mapWithKeys(fn (string $category) => [
                $category => $topSellers->get($category),
            ]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $period = $request->validate([
            'period' => ['nullable', 'in:today,7days,month,all'],
        ])['period'] ?? 'today';
        [$from, $to] = $this->periodBounds($period);

        $orders = Order::query()->whereNotNull('paid_at')->where('status', '!=', 'voided');
        $this->applyPeriod($orders, $from, $to);
        return response()->streamDownload(function () use ($orders, $period): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ETIVACSILOG POS Sales Report']);
            fputcsv($output, ['Period', $this->periodLabel($period)]);
            fputcsv($output, ['Date', 'Time', 'Item', 'Quantity', 'Total Price']);
            $totalQuantity = 0;
            $totalPrice = 0.0;

            $orders->with([
                'items:id,order_id,name,quantity,unit_price',
            ])->orderBy('paid_at')->lazyById(200)->each(function (Order $order) use ($output, &$totalQuantity, &$totalPrice): void {
                foreach ($order->items as $item) {
                    $itemTotal = (float) $item->unit_price * $item->quantity;
                    $totalQuantity += $item->quantity;
                    $totalPrice += $itemTotal;
                    fputcsv($output, array_map($this->spreadsheetSafeCell(...), [
                        $order->paid_at?->format('Y-m-d'),
                        $order->paid_at?->format('H:i:s'),
                        $item->name,
                        $item->quantity,
                        number_format($itemTotal, 2, '.', ''),
                    ]));
                }
            });

            fputcsv($output, ['TOTAL', '', '', $totalQuantity, number_format($totalPrice, 2, '.', '')]);
            fclose($output);
        }, now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function spreadsheetSafeCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+@\-\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }

    private function periodBounds(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            default => [null, null],
        };
    }

    private function applyPeriod(Builder $query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from && $to) {
            $query->whereBetween('paid_at', [$from, $to]);
        }
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'today' => 'Today',
            '7days' => 'Last 7 days',
            'month' => 'This month',
            default => 'All time',
        };
    }

    private function salesChart($orders, string $period): array
    {
        $now = now();
        $buckets = collect();

        if ($period === 'today') {
            for ($hour = 0; $hour < 24; $hour++) {
                $time = $now->copy()->startOfDay()->addHours($hour);
                $buckets->put($time->format('Y-m-d-H'), ['label' => $time->format('g A'), 'sales' => 0.0]);
            }
        } elseif ($period === '7days') {
            for ($day = 6; $day >= 0; $day--) {
                $date = $now->copy()->subDays($day)->startOfDay();
                $buckets->put($date->format('Y-m-d'), ['label' => $date->format('D'), 'sales' => 0.0]);
            }
        } elseif ($period === 'month') {
            for ($day = 1; $day <= $now->daysInMonth; $day++) {
                $date = $now->copy()->startOfMonth()->addDays($day - 1);
                $buckets->put($date->format('Y-m-d'), ['label' => (string) $day, 'sales' => 0.0]);
            }
        } else {
            $firstPaidOrder = $orders->first();
            $start = $firstPaidOrder
                ? Carbon::parse($firstPaidOrder->paid_at)->startOfMonth()
                : $now->copy()->startOfMonth();
            $months = max(1, $start->diffInMonths($now) + 1);
            for ($offset = 0; $offset < $months; $offset++) {
                $month = $start->copy()->addMonths($offset);
                $buckets->put($month->format('Y-m'), ['label' => $month->format('M y'), 'sales' => 0.0]);
            }
        }

        foreach ($orders as $order) {
            $paidAt = Carbon::parse($order->paid_at);
            $key = match ($period) {
                'today' => $paidAt->format('Y-m-d-H'),
                'all' => $paidAt->format('Y-m'),
                default => $paidAt->format('Y-m-d'),
            };

            if ($buckets->has($key)) {
                $bucket = $buckets->get($key);
                $bucket['sales'] += (float) $order->total;
                $buckets->put($key, $bucket);
            }
        }

        $values = $buckets->values();
        $maxSales = max(1, (float) $values->max('sales'));
        $chartWidth = 1000;
        $chartHeight = 280;
        $paddingX = 38;
        $paddingY = 24;
        $plotWidth = $chartWidth - 2 * $paddingX;
        $plotHeight = $chartHeight - 2 * $paddingY;
        $lastIndex = max(1, $values->count() - 1);
        $labelInterval = max(1, (int) ceil($values->count() / 7));

        $points = $values->map(function (array $bucket, int $index) use ($lastIndex, $paddingX, $paddingY, $plotWidth, $plotHeight, $maxSales, $labelInterval, $values) {
            $x = $paddingX + ($index / $lastIndex) * $plotWidth;
            $y = $paddingY + $plotHeight - ($bucket['sales'] / $maxSales) * $plotHeight;

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $bucket['label'],
                'sales' => round($bucket['sales'], 2),
                'show_label' => $index % $labelInterval === 0 || $index === $values->count() - 1,
            ];
        });

        return [
            'points' => $points,
            'polyline' => $points->map(fn (array $point) => $point['x'].','.$point['y'])->implode(' '),
            'max' => $maxSales,
        ];
    }
}