<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BankTransfer;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seed_creates_super_admin_role_and_admin_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Admin::where('email', 'admin@drasa.test')->exists());
        $this->assertTrue(Role::where('name', 'super-admin')->where('guard_name', 'admin')->exists());
        $role = Role::where('name', 'super-admin')->where('guard_name', 'admin')->firstOrFail();
        $this->assertTrue(Admin::where('email', 'admin@drasa.test')->first()->hasRole('super-admin'));

        foreach (['admins.view', 'admins.create', 'admins.update', 'admins.delete', 'transfers.view', 'transfers.approve', 'transfers.reject', 'faqs.view', 'settings.view', 'roles.view'] as $permission) {
            $this->assertTrue(Permission::where('name', $permission)->where('guard_name', 'admin')->exists());
        }

        $this->assertTrue($role->hasPermissionTo('settings.view', 'admin'));
        $this->assertTrue($role->hasPermissionTo('settings.update', 'admin'));
        $this->assertFalse($role->permissions->pluck('name')->contains('settings.create'));
        $this->assertFalse($role->permissions->pluck('name')->contains('settings.delete'));

        foreach ([
            ['general', 'site_name'],
            ['general', 'site_logo'],
            ['general', 'support_email'],
            ['social', 'facebook'],
            ['social', 'x'],
            ['social', 'whatsapp'],
            ['social', 'linkedin'],
            ['social', 'twitter'],
            ['analytics', 'google_analytics_id'],
            ['analytics', 'google_tag_manager_id'],
            ['analytics', 'meta_pixel_id'],
        ] as [$group, $key]) {
            $this->assertTrue(Setting::where('group', $group)->where('key', $key)->exists());
        }
    }

    public function test_admin_login_page_renders_inertia_component(): void
    {
        $this->get('/manage/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Auth/Login')
                ->where('auth.admin', null)
            );
    }

    public function test_seeded_admin_can_login_and_view_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/manage/login', [
            'email' => 'admin@drasa.test',
            'password' => 'password',
        ])->assertRedirect('/manage');

        $this->get('/manage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('auth.admin')
                ->has('stats', 5)
                ->has('recentOrders')
                ->has('bankTransfers')
                ->has('productStatus')
                ->has('products')
            );
    }

    public function test_regular_users_do_not_satisfy_admin_guard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/manage')
            ->assertRedirect('/manage/login');
    }

    public function test_admin_without_permissions_cannot_manage_resources(): void
    {
        $this->seed(DatabaseSeeder::class);

        $limitedAdmin = Admin::create([
            'name' => 'Limited Admin',
            'email' => 'limited.admin@drasa.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($limitedAdmin, 'admin')
            ->get('/manage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.admin.permissions', [])
                ->where('stats', [])
                ->where('recentOrders', [])
                ->where('bankTransfers', [])
                ->where('productStatus', [])
                ->where('products', [])
            );

        foreach ([
            '/manage/products',
            '/manage/faqs',
            '/manage/settings',
            '/manage/orders',
            '/manage/bank-transfers',
            '/manage/users',
            '/manage/admins',
            '/manage/roles',
        ] as $url) {
            $this->actingAs($limitedAdmin, 'admin')
                ->get($url)
                ->assertForbidden();
        }

        $this->actingAs($limitedAdmin, 'admin')
            ->post('/manage/products', [])
            ->assertForbidden();
    }

    public function test_admin_management_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        foreach ([
            '/manage/products' => ['Admin/Products', 'products'],
            '/manage/faqs' => ['Admin/Faqs', 'faqs'],
            '/manage/settings' => ['Admin/Settings', null],
            '/manage/orders' => ['Admin/Orders', 'orders'],
            '/manage/bank-transfers' => ['Admin/BankTransfers', 'transfers'],
            '/manage/users' => ['Admin/Users', 'users'],
            '/manage/admins' => ['Admin/Admins', 'admins'],
            '/manage/roles' => ['Admin/Roles', 'roles'],
            '/manage/profile' => ['Admin/Profile', null],
        ] as $url => [$component, $paginatedProp]) {
            $this->actingAs($admin, 'admin')
                ->get($url)
                ->assertOk()
                ->assertInertia(function (Assert $page) use ($component, $paginatedProp) {
                    $page->component($component);

                    if ($component === 'Admin/Settings') {
                        $page
                            ->has('groups')
                            ->has('groups.0.settings');
                    } elseif ($paginatedProp) {
                        $page
                            ->has("{$paginatedProp}.data")
                            ->has("{$paginatedProp}.meta.total")
                            ->has("{$paginatedProp}.meta.per_page");
                    }
                });
        }
    }

    public function test_admin_inertia_resources_preserve_record_shapes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();
        $product = Product::query()->whereHas('purchases')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get('/manage/products')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products')
                ->has('products.data.0.id')
                ->has('products.data.0.title_en')
                ->has('products.data.0.title_ar')
                ->has('products.data.0.price')
                ->has('products.data.0.cover_url')
                ->has('products.links.next')
                ->has('products.meta.total')
            );

        $this->actingAs($admin, 'admin')
            ->get('/manage/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->where('groups.0.key', 'general')
                ->where('groups.0.settings.0.key', 'site_name')
                ->has('groups.0.settings.0.value_en')
                ->has('groups.0.settings.0.value_ar')
            );

        $this->actingAs($admin, 'admin')
            ->get("/manage/products/{$product->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Detail')
                ->has('sections.0.rows.0.customer')
                ->has('sections.0.rows.0.order_number')
            );

        $this->actingAs($admin, 'admin')
            ->get('/manage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.admin.email', 'admin@drasa.test')
                ->has('auth.admin.roles')
                ->has('auth.admin.permissions')
                ->has('adminNotifications.pendingTransferCount')
                ->has('adminNotifications.pendingTransfers.0.title')
            );
    }

    public function test_admin_show_pages_render_related_detail_view(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $urls = [
            '/manage/products/'.Product::firstOrFail()->id,
            '/manage/orders/'.Order::firstOrFail()->id,
            '/manage/bank-transfers/'.BankTransfer::firstOrFail()->id,
            '/manage/faqs/'.Faq::firstOrFail()->id,
            '/manage/settings/'.Setting::firstOrFail()->id,
            '/manage/users/'.User::firstOrFail()->id,
            '/manage/admins/'.$admin->id,
            '/manage/roles/'.Role::where('name', 'super-admin')->firstOrFail()->id,
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin, 'admin')
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Detail')
                    ->has('title')
                    ->has('fields')
                    ->has('sections')
                );
        }
    }

    public function test_admin_index_filters_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        foreach ([
            '/manage/products?q=study&status=active&sort=price_high',
            '/manage/orders?status=pending&sort=total_high',
            '/manage/bank-transfers?status=pending&sort=total_low',
            '/manage/faqs?q=what&status=active',
            '/manage/settings?input_type=text&translatable=yes',
            '/manage/users?q=admin&sort=name_az',
            '/manage/admins?q=admin&status=active&role=super-admin&sort=name_az',
            '/manage/roles?q=super&permission=admins.view&sort=name_az',
        ] as $url) {
            $this->actingAs($admin, 'admin')
                ->get($url)
                ->assertOk();
        }
    }

    public function test_admin_can_manage_admin_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/manage/admins', [
                'name' => 'Second Admin',
                'email' => 'second.admin@drasa.test',
                'password' => 'password123',
                'is_active' => true,
                'roles' => ['super-admin'],
            ])
            ->assertRedirect();

        $managedAdmin = Admin::where('email', 'second.admin@drasa.test')->firstOrFail();

        $this->assertTrue($managedAdmin->hasRole('super-admin'));

        $this->actingAs($admin, 'admin')
            ->put("/manage/admins/{$managedAdmin->id}", [
                'name' => 'Updated Second Admin',
                'email' => 'updated.second.admin@drasa.test',
                'password' => '',
                'is_active' => false,
                'roles' => [],
            ])
            ->assertRedirect();

        $managedAdmin->refresh();

        $this->assertSame('Updated Second Admin', $managedAdmin->name);
        $this->assertSame('updated.second.admin@drasa.test', $managedAdmin->email);
        $this->assertFalse($managedAdmin->is_active);
        $this->assertFalse($managedAdmin->hasRole('super-admin'));

        $this->actingAs($admin, 'admin')
            ->delete("/manage/admins/{$managedAdmin->id}")
            ->assertRedirect();

        $this->assertModelMissing($managedAdmin);
    }

    public function test_admin_can_manage_roles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/manage/roles', [
                'name' => 'content-manager',
                'permissions' => ['products.view', 'faqs.view'],
            ])
            ->assertRedirect();

        $role = Role::where('name', 'content-manager')->where('guard_name', 'admin')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('products.view', 'admin'));
        $this->assertTrue($role->hasPermissionTo('faqs.view', 'admin'));

        $this->actingAs($admin, 'admin')
            ->put("/manage/roles/{$role->id}", [
                'name' => 'catalog-manager',
                'permissions' => ['products.view', 'products.create'],
            ])
            ->assertRedirect();

        $role->refresh();

        $this->assertSame('catalog-manager', $role->name);
        $this->assertTrue($role->hasPermissionTo('products.create', 'admin'));
        $this->assertFalse($role->hasPermissionTo('faqs.view', 'admin'));

        $this->actingAs($admin, 'admin')
            ->delete("/manage/roles/{$role->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
            'guard_name' => 'admin',
        ]);
    }

    public function test_admin_can_update_seeded_settings_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();
        $supportEmail = Setting::where('group', 'general')->where('key', 'support_email')->firstOrFail();
        $siteName = Setting::where('group', 'general')->where('key', 'site_name')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put("/manage/settings/{$supportEmail->id}", [
                'value' => 'help@drasa.test',
            ])
            ->assertRedirect();

        $this->assertSame('help@drasa.test', $supportEmail->fresh()->value);

        $this->actingAs($admin, 'admin')
            ->put("/manage/settings/{$siteName->id}", [
                'value_en' => 'Dirasat Updated',
                'value_ar' => 'دراسات محدثة',
            ])
            ->assertRedirect();

        $siteName->refresh();

        $this->assertSame('Dirasat Updated', $siteName->getTranslation('value', 'en'));
        $this->assertSame('دراسات محدثة', $siteName->getTranslation('value', 'ar'));
        $this->assertSame('Dirasat Updated', $siteName->value);
    }

    public function test_admin_cannot_create_or_delete_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();
        $setting = Setting::firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/manage/settings', [
                'group' => 'general',
                'key' => 'new_setting',
                'input_type' => 'text',
            ])
            ->assertStatus(405);

        $this->actingAs($admin, 'admin')
            ->delete("/manage/settings/{$setting->id}")
            ->assertStatus(405);
    }

    public function test_admin_without_settings_update_permission_cannot_update_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $setting = Setting::where('group', 'general')->where('key', 'support_email')->firstOrFail();
        $limitedAdmin = Admin::create([
            'name' => 'Settings Viewer',
            'email' => 'settings.viewer@drasa.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $role = Role::create(['name' => 'settings-viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('settings.view');
        $limitedAdmin->assignRole($role);

        $this->actingAs($limitedAdmin, 'admin')
            ->get('/manage/settings')
            ->assertOk();

        $this->actingAs($limitedAdmin, 'admin')
            ->put("/manage/settings/{$setting->id}", [
                'value' => 'blocked@drasa.test',
            ])
            ->assertForbidden();

        $this->assertNotSame('blocked@drasa.test', $setting->fresh()->value);
    }

    public function test_admin_can_upload_site_logo_setting(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();
        $logo = Setting::where('group', 'general')->where('key', 'site_logo')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post("/manage/settings/{$logo->id}", [
                '_method' => 'put',
                'value' => UploadedFile::fake()->image('logo.png', 320, 160),
            ])
            ->assertRedirect();

        $logo->refresh();

        $this->assertStringStartsWith('settings/', $logo->value);
        Storage::disk('public')->assertExists($logo->value);
    }

    public function test_admin_can_create_bilingual_product(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/manage/products', [
                'sku' => 'FS-TEST-999',
                'title_en' => 'Test Feasibility Study',
                'title_ar' => 'دراسة جدوى اختبارية',
                'short_description_en' => 'Short English text',
                'short_description_ar' => 'نص عربي قصير',
                'description_en' => 'Long English description',
                'description_ar' => 'وصف عربي طويل',
                'price' => 199,
                'currency' => 'EGP',
                'status' => 'active',
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'FS-TEST-999')->firstOrFail();

        $this->assertSame('Test Feasibility Study', $product->getTranslation('title', 'en'));
        $this->assertSame(19900, $product->price_cents);
        $this->assertSame('دراسة جدوى اختبارية', $product->getTranslation('title', 'ar'));
    }

    public function test_admin_can_approve_bank_transfer_and_grant_purchase(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();
        $transfer = BankTransfer::where('status', 'pending')->firstOrFail();
        $transfer->load('order.items');

        $this->actingAs($admin, 'admin')
            ->patch("/manage/bank-transfers/{$transfer->id}/approve")
            ->assertRedirect();

        $transfer->refresh();

        $this->assertSame('approved', $transfer->status);
        $this->assertSame('paid', $transfer->order->fresh()->status);

        foreach ($transfer->order->items as $item) {
            $this->assertTrue(Purchase::where('user_id', $transfer->order->user_id)
                ->where('product_id', $item->product_id)
                ->exists());
        }
    }

    public function test_admin_can_switch_locale(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post('/manage/locale/ar')
            ->assertRedirect();

        $this->actingAs($admin, 'admin')
            ->get('/manage')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('locale', 'ar')
                ->where('direction', 'rtl')
                ->where('translations.layout.nav.products', 'المنتجات')
            );
    }

    public function test_admin_can_update_profile(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Admin::where('email', 'admin@drasa.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put('/manage/profile', [
                'name' => 'Updated Admin',
                'email' => 'updated.admin@drasa.test',
            ])
            ->assertRedirect();

        $admin->refresh();

        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('updated.admin@drasa.test', $admin->email);
    }
}
