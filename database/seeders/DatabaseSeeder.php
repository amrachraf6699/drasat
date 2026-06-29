<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\BankTransfer;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'orders.view',
            'transfers.view',
            'transfers.approve',
            'transfers.reject',
            'transfers.review',
            'users.view',
            'admins.view',
            'admins.create',
            'admins.update',
            'admins.delete',
            'faqs.view',
            'faqs.create',
            'faqs.update',
            'faqs.delete',
            'faqs.manage',
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
            'settings.manage',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'admin',
        ]);

        $role->syncPermissions($permissions);

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@drasa.test'],
            [
                'name' => 'Main Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->assignRole($role);

        if (User::count() < 8) {
            User::factory()->count(8 - User::count())->create();
        }

        User::updateOrCreate(
            ['email' => 'user@drasa.test'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $this->seedProducts();
        $this->seedFaqs();
        $this->seedSettings();
        $this->seedOrdersAndTransfers();
    }

    private function seedProducts(): void
    {
        $products = [
            [
                'sku' => 'FS-FACTORY-001',
                'slug' => 'factory-feasibility-study',
                'price_cents' => 125000,
                'currency' => 'EGP',
                'status' => 'active',
                'en' => ['title' => 'Factory Feasibility Study', 'short' => 'Operational and financial study for a small factory.', 'description' => 'Includes market assumptions, startup costs, operating costs, and return projections.'],
                'ar' => ['title' => 'دراسة جدوى مصنع', 'short' => 'دراسة تشغيلية ومالية لمصنع صغير.', 'description' => 'تشمل افتراضات السوق وتكاليف التأسيس والتشغيل وتوقعات العائد.'],
            ],
            [
                'sku' => 'FS-ECOM-002',
                'slug' => 'ecommerce-feasibility-study',
                'price_cents' => 95000,
                'currency' => 'EGP',
                'status' => 'active',
                'en' => ['title' => 'E-commerce Feasibility Study', 'short' => 'Study for launching an online store.', 'description' => 'Covers audience, fulfillment, marketing budget, and cash-flow expectations.'],
                'ar' => ['title' => 'دراسة جدوى متجر إلكتروني', 'short' => 'دراسة لإطلاق متجر إلكتروني.', 'description' => 'تغطي الجمهور المستهدف والتشغيل وميزانية التسويق وتوقعات التدفقات النقدية.'],
            ],
            [
                'sku' => 'FS-SALON-003',
                'slug' => 'beauty-salon-feasibility-study',
                'price_cents' => 78000,
                'currency' => 'EGP',
                'status' => 'draft',
                'en' => ['title' => 'Beauty Salon Feasibility Study', 'short' => 'Startup study for a service business.', 'description' => 'Includes location assumptions, staffing, service pricing, and monthly break-even estimates.'],
                'ar' => ['title' => 'دراسة جدوى صالون تجميل', 'short' => 'دراسة تأسيس لنشاط خدمي.', 'description' => 'تشمل افتراضات الموقع والعمالة وتسعير الخدمات ونقطة التعادل الشهرية.'],
            ],
            [
                'sku' => 'FS-APP-004',
                'slug' => 'mobile-services-feasibility-study',
                'price_cents' => 110000,
                'currency' => 'EGP',
                'status' => 'inactive',
                'en' => ['title' => 'Mobile Services Feasibility Study', 'short' => 'Study for an app-based services project.', 'description' => 'Covers product scope, technical team, acquisition cost, and expected subscription revenue.'],
                'ar' => ['title' => 'دراسة جدوى تطبيق خدمات', 'short' => 'دراسة لمشروع خدمات عبر تطبيق.', 'description' => 'تغطي نطاق المنتج والفريق التقني وتكلفة الاستحواذ وإيرادات الاشتراكات المتوقعة.'],
            ],
        ];

        foreach ($products as $item) {
            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'sku' => $item['sku'],
                    'price_cents' => $item['price_cents'],
                    'currency' => $item['currency'],
                    'status' => $item['status'],
                    'published_at' => $item['status'] === 'active' ? now() : null,
                ],
            );

            foreach (['en', 'ar'] as $locale) {
                $product->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $item[$locale]['title'],
                        'short_description' => $item[$locale]['short'],
                        'description' => $item[$locale]['description'],
                    ],
                );
            }
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            [
                'sort_order' => 1,
                'en' => ['question' => 'When can users access purchased files?', 'answer' => 'Files become available immediately after PayPal payment or after admin approval for bank transfers.'],
                'ar' => ['question' => 'متى يمكن للمستخدم الوصول إلى الملفات؟', 'answer' => 'تظهر الملفات مباشرة بعد الدفع عبر PayPal أو بعد موافقة الإدارة على التحويل البنكي.'],
            ],
            [
                'sort_order' => 2,
                'en' => ['question' => 'Can a product contain multiple documents?', 'answer' => 'Yes. Each product has one cover image and many downloadable study documents.'],
                'ar' => ['question' => 'هل يمكن أن تحتوي الدراسة على أكثر من ملف؟', 'answer' => 'نعم. لكل دراسة صورة غلاف واحدة وعدة ملفات قابلة للتحميل.'],
            ],
        ];

        foreach ($faqs as $item) {
            $faq = Faq::updateOrCreate(
                ['sort_order' => $item['sort_order']],
                ['status' => 'active'],
            );

            foreach (['en', 'ar'] as $locale) {
                $faq->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'question' => $item[$locale]['question'],
                        'answer' => $item[$locale]['answer'],
                    ],
                );
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['group' => 'general', 'input_type' => 'text', 'key' => 'site_name', 'value' => 'Dirasat', 'is_translatable' => true, 'en' => 'Dirasat', 'ar' => 'دراسات'],
            ['group' => 'general', 'input_type' => 'email', 'key' => 'support_email', 'value' => 'support@drasa.test', 'is_translatable' => false],
            ['group' => 'payments', 'input_type' => 'text', 'key' => 'default_currency', 'value' => 'EGP', 'is_translatable' => false],
            ['group' => 'social', 'input_type' => 'url', 'key' => 'facebook_url', 'value' => 'https://facebook.com/drasa', 'is_translatable' => false],
        ];

        foreach ($settings as $item) {
            $setting = Setting::updateOrCreate(
                ['group' => $item['group'], 'key' => $item['key']],
                [
                    'input_type' => $item['input_type'],
                    'value' => $item['value'],
                    'is_translatable' => $item['is_translatable'],
                ],
            );

            if ($setting->is_translatable) {
                foreach (['en', 'ar'] as $locale) {
                    $setting->translations()->updateOrCreate(
                        ['locale' => $locale],
                        ['value' => $item[$locale] ?? $item['value']],
                    );
                }
            }
        }
    }

    private function seedOrdersAndTransfers(): void
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
