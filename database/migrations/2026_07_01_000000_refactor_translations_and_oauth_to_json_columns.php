<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addProductTranslationColumns();
        $this->addFaqTranslationColumns();
        $this->addUserOauthColumns();

        $this->migrateProductTranslations();
        $this->migrateFaqTranslations();
        $this->migrateSettingTranslations();
        $this->migrateOauthAccounts();
    }

    public function down(): void
    {
        $this->restoreProductTranslations();
        $this->restoreFaqTranslations();
        $this->restoreSettingTranslations();
        $this->restoreOauthAccounts();
    }

    private function addProductTranslationColumns(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        foreach (['title', 'short_description', 'description'] as $column) {
            if (! $this->hasColumn('products', $column)) {
                Schema::table('products', fn (Blueprint $table) => $table->json($column)->nullable());
            }
        }
    }

    private function addFaqTranslationColumns(): void
    {
        if (! Schema::hasTable('faqs')) {
            return;
        }

        foreach (['question', 'answer'] as $column) {
            if (! $this->hasColumn('faqs', $column)) {
                Schema::table('faqs', fn (Blueprint $table) => $table->json($column)->nullable());
            }
        }
    }

    private function addUserOauthColumns(): void
    {
        if (! Schema::hasTable('users') || $this->hasColumn('users', 'oauth_provider')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_provider_id')->nullable();
            $table->string('oauth_avatar')->nullable();
            $table->unique(['oauth_provider', 'oauth_provider_id']);
        });
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $table = str_replace('.', '__', DB::connection()->getTablePrefix().$table);
        $quotedTable = str_replace("'", "''", $table);

        foreach (DB::select("pragma table_info('{$quotedTable}')") as $definition) {
            if (strtolower((string) $definition->name) === strtolower($column)) {
                return true;
            }
        }

        return false;
    }

    private function migrateProductTranslations(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('product_translations')) {
            return;
        }

        DB::table('products')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function (Collection $products) {
                foreach ($products as $product) {
                    $translations = DB::table('product_translations')
                        ->where('product_id', $product->id)
                        ->get();

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'title' => $this->encodeTranslations($translations, 'title'),
                            'short_description' => $this->encodeTranslations($translations, 'short_description'),
                            'description' => $this->encodeTranslations($translations, 'description'),
                        ]);
                }
            });

        Schema::dropIfExists('product_translations');
    }

    private function migrateFaqTranslations(): void
    {
        if (! Schema::hasTable('faqs') || ! Schema::hasTable('faq_translations')) {
            return;
        }

        DB::table('faqs')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function (Collection $faqs) {
                foreach ($faqs as $faq) {
                    $translations = DB::table('faq_translations')
                        ->where('faq_id', $faq->id)
                        ->get();

                    DB::table('faqs')
                        ->where('id', $faq->id)
                        ->update([
                            'question' => $this->encodeTranslations($translations, 'question'),
                            'answer' => $this->encodeTranslations($translations, 'answer'),
                        ]);
                }
            });

        Schema::dropIfExists('faq_translations');
    }

    private function migrateSettingTranslations(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $locales = config('app.supported_locales', ['en', 'ar']);

        DB::table('settings')
            ->select(['id', 'value'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $settings) use ($locales) {
                foreach ($settings as $setting) {
                    $translations = Schema::hasTable('setting_translations')
                        ? DB::table('setting_translations')->where('setting_id', $setting->id)->get()
                        : collect();

                    DB::table('settings')
                        ->where('id', $setting->id)
                        ->update([
                            'value' => $translations->isNotEmpty()
                                ? $this->encodeTranslations($translations, 'value')
                                : $this->encodeSharedValue($setting->value, $locales),
                        ]);
                }
            });

        Schema::dropIfExists('setting_translations');
    }

    private function migrateOauthAccounts(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('oauth_accounts')) {
            return;
        }

        DB::table('oauth_accounts')
            ->select(['id', 'user_id', 'provider', 'provider_id', 'avatar'])
            ->orderBy('id')
            ->get()
            ->unique('user_id')
            ->each(function ($account) {
                DB::table('users')
                    ->where('id', $account->user_id)
                    ->update([
                        'oauth_provider' => $account->provider,
                        'oauth_provider_id' => $account->provider_id,
                        'oauth_avatar' => $account->avatar,
                    ]);
            });

        Schema::dropIfExists('oauth_accounts');
    }

    private function restoreProductTranslations(): void
    {
        if (! Schema::hasTable('products') || Schema::hasTable('product_translations')) {
            return;
        }

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'locale']);
        });

        DB::table('products')
            ->select(['id', 'title', 'short_description', 'description'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $products) {
                foreach ($products as $product) {
                    $titles = $this->decodeTranslations($product->title);
                    $shortDescriptions = $this->decodeTranslations($product->short_description);
                    $descriptions = $this->decodeTranslations($product->description);

                    foreach (array_keys($titles) as $locale) {
                        DB::table('product_translations')->insert([
                            'product_id' => $product->id,
                            'locale' => $locale,
                            'title' => $titles[$locale],
                            'short_description' => $shortDescriptions[$locale] ?? null,
                            'description' => $descriptions[$locale] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    private function restoreFaqTranslations(): void
    {
        if (! Schema::hasTable('faqs') || Schema::hasTable('faq_translations')) {
            return;
        }

        Schema::create('faq_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('question');
            $table->text('answer');
            $table->timestamps();
            $table->unique(['faq_id', 'locale']);
        });

        DB::table('faqs')
            ->select(['id', 'question', 'answer'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $faqs) {
                foreach ($faqs as $faq) {
                    $questions = $this->decodeTranslations($faq->question);
                    $answers = $this->decodeTranslations($faq->answer);

                    foreach (array_keys($questions) as $locale) {
                        DB::table('faq_translations')->insert([
                            'faq_id' => $faq->id,
                            'locale' => $locale,
                            'question' => $questions[$locale],
                            'answer' => $answers[$locale] ?? '',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    private function restoreSettingTranslations(): void
    {
        if (! Schema::hasTable('settings') || Schema::hasTable('setting_translations')) {
            return;
        }

        Schema::create('setting_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['setting_id', 'locale']);
        });

        DB::table('settings')
            ->select(['id', 'value'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $settings) {
                foreach ($settings as $setting) {
                    foreach ($this->decodeTranslations($setting->value) as $locale => $value) {
                        DB::table('setting_translations')->insert([
                            'setting_id' => $setting->id,
                            'locale' => $locale,
                            'value' => $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    private function restoreOauthAccounts(): void
    {
        if (! Schema::hasTable('users') || Schema::hasTable('oauth_accounts')) {
            return;
        }

        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->string('avatar')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
        });

        DB::table('users')
            ->select(['id', 'oauth_provider', 'oauth_provider_id', 'oauth_avatar'])
            ->whereNotNull('oauth_provider')
            ->whereNotNull('oauth_provider_id')
            ->orderBy('id')
            ->chunkById(100, function (Collection $users) {
                foreach ($users as $user) {
                    DB::table('oauth_accounts')->insert([
                        'user_id' => $user->id,
                        'provider' => $user->oauth_provider,
                        'provider_id' => $user->oauth_provider_id,
                        'avatar' => $user->oauth_avatar,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function encodeTranslations(Collection $translations, string $column): ?string
    {
        $values = [];

        foreach ($translations as $translation) {
            if ($translation->{$column} !== null) {
                $values[$translation->locale] = $translation->{$column};
            }
        }

        return $values === [] ? null : json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function encodeSharedValue(mixed $value, array $locales): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(array_fill_keys($locales, $value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decodeTranslations(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
