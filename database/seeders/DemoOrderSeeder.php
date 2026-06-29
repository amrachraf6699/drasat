<?php

namespace Database\Seeders;

use App\Models\BankTransfer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->take(4)->get();
        $products = Product::with('translations')->take(4)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($products->values() as $index => $product) {
            $user = $users[$index % $users->count()];
            $number = 'ORD-'.str_pad((string) (1523 + $index), 4, '0', STR_PAD_LEFT);
            $status = $index === 1 ? 'pending' : ($index === 3 ? 'cancelled' : 'paid');
            $paymentMethod = $index === 1 ? 'bank_transfer' : 'paypal';

            $order = Order::updateOrCreate(
                ['order_number' => $number],
                [
                    'user_id' => $user->id,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'subtotal_cents' => $product->price_cents,
                    'total_cents' => $product->price_cents,
                    'currency' => $product->currency,
                    'paid_at' => $status === 'paid' ? now()->subDays($index) : null,
                ],
            );

            $order->items()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'title' => $product->title('en'),
                    'quantity' => 1,
                    'unit_price_cents' => $product->price_cents,
                    'total_cents' => $product->price_cents,
                ],
            );

            if ($status === 'paid') {
                Purchase::updateOrCreate(
                    ['user_id' => $user->id, 'product_id' => $product->id],
                    ['order_id' => $order->id, 'purchased_at' => now()->subDays($index)],
                );
            }

            if ($paymentMethod === 'bank_transfer') {
                BankTransfer::updateOrCreate(
                    ['reference_number' => 'TRF-258'.(9 - $index)],
                    [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'status' => 'pending',
                        'amount_cents' => $order->total_cents,
                        'currency' => $order->currency,
                    ],
                );
            }
        }
    }
}
