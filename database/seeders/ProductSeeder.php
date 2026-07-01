<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as $item) {
            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'sku' => $item['sku'],
                    'price_cents' => $item['price_cents'],
                    'currency' => $item['currency'],
                    'status' => $item['status'],
                    'published_at' => $item['status'] === 'active' ? now() : null,
                    'title' => $this->translations($item, 'title'),
                    'short_description' => $this->translations($item, 'short'),
                    'description' => $this->translations($item, 'description'),
                ],
            );
        }
    }

    private function translations(array $item, string $key): array
    {
        return collect(config('app.supported_locales', ['en', 'ar']))
            ->mapWithKeys(fn (string $locale) => [$locale => $item[$locale][$key] ?? null])
            ->all();
    }

    private function products(): array
    {
        return [
            [
                'sku' => 'FS-FACTORY-001',
                'slug' => 'factory-feasibility-study',
                'price_cents' => 125000,
                'currency' => 'EGP',
                'status' => 'active',
                'en' => [
                    'title' => 'Factory Feasibility Study',
                    'short' => 'Operational and financial study for a small factory.',
                    'description' => 'Includes market assumptions, startup costs, operating costs, and return projections.',
                ],
                'ar' => [
                    'title' => 'دراسة جدوى مصنع',
                    'short' => 'دراسة تشغيلية ومالية لمصنع صغير.',
                    'description' => 'تشمل افتراضات السوق وتكاليف التأسيس والتشغيل وتوقعات العائد.',
                ],
            ],
            [
                'sku' => 'FS-ECOM-002',
                'slug' => 'ecommerce-feasibility-study',
                'price_cents' => 95000,
                'currency' => 'EGP',
                'status' => 'active',
                'en' => [
                    'title' => 'E-commerce Feasibility Study',
                    'short' => 'Study for launching an online store.',
                    'description' => 'Covers audience, fulfillment, marketing budget, and cash-flow expectations.',
                ],
                'ar' => [
                    'title' => 'دراسة جدوى متجر إلكتروني',
                    'short' => 'دراسة لإطلاق متجر إلكتروني.',
                    'description' => 'تغطي الجمهور المستهدف والتشغيل وميزانية التسويق وتوقعات التدفقات النقدية.',
                ],
            ],
            [
                'sku' => 'FS-SALON-003',
                'slug' => 'beauty-salon-feasibility-study',
                'price_cents' => 78000,
                'currency' => 'EGP',
                'status' => 'draft',
                'en' => [
                    'title' => 'Beauty Salon Feasibility Study',
                    'short' => 'Startup study for a service business.',
                    'description' => 'Includes location assumptions, staffing, service pricing, and monthly break-even estimates.',
                ],
                'ar' => [
                    'title' => 'دراسة جدوى صالون تجميل',
                    'short' => 'دراسة تأسيس لنشاط خدمي.',
                    'description' => 'تشمل افتراضات الموقع والعمالة وتسعير الخدمات ونقطة التعادل الشهرية.',
                ],
            ],
            [
                'sku' => 'FS-APP-004',
                'slug' => 'mobile-services-feasibility-study',
                'price_cents' => 110000,
                'currency' => 'EGP',
                'status' => 'inactive',
                'en' => [
                    'title' => 'Mobile Services Feasibility Study',
                    'short' => 'Study for an app-based services project.',
                    'description' => 'Covers product scope, technical team, acquisition cost, and expected subscription revenue.',
                ],
                'ar' => [
                    'title' => 'دراسة جدوى تطبيق خدمات',
                    'short' => 'دراسة لمشروع خدمات عبر تطبيق.',
                    'description' => 'تغطي نطاق المنتج والفريق التقني وتكلفة الاستحواذ وإيرادات الاشتراكات المتوقعة.',
                ],
            ],
        ];
    }
}
