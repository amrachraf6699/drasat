<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $item) {
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

    private function faqs(): array
    {
        return [
            [
                'sort_order' => 1,
                'en' => [
                    'question' => 'When can users access purchased files?',
                    'answer' => 'Files become available immediately after PayPal payment or after admin approval for bank transfers.',
                ],
                'ar' => [
                    'question' => 'متى يمكن للمستخدم الوصول إلى الملفات؟',
                    'answer' => 'تظهر الملفات مباشرة بعد الدفع عبر PayPal أو بعد موافقة الإدارة على التحويل البنكي.',
                ],
            ],
            [
                'sort_order' => 2,
                'en' => [
                    'question' => 'Can a product contain multiple documents?',
                    'answer' => 'Yes. Each product has one cover image and many downloadable study documents.',
                ],
                'ar' => [
                    'question' => 'هل يمكن أن تحتوي الدراسة على أكثر من ملف؟',
                    'answer' => 'نعم. لكل دراسة صورة غلاف واحدة وعدة ملفات قابلة للتحميل.',
                ],
            ],
        ];
    }
}
