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
use Illuminate\Support\Facades\Hash;
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
        $this->assertTrue(Admin::where('email', 'admin@drasa.test')->first()->hasRole('super-admin'));

        foreach (['admins.view', 'admins.create', 'admins.update', 'admins.delete', 'transfers.view', 'transfers.approve', 'transfers.reject', 'faqs.view', 'settings.view', 'roles.view'] as $permission) {
            $this->assertTrue(Permission::where('name', $permission)->where('guard_name', 'admin')->exists());
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
            '/manage/settings' => ['Admin/Settings', 'settings'],
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

                    if ($paginatedProp) {
                        $page
                            ->has("{$paginatedProp}.data")
                            ->has("{$paginatedProp}.total")
                            ->has("{$paginatedProp}.per_page");
                    }
                });
        }
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

        $this->assertSame('Test Feasibility Study', $product->translations()->where('locale', 'en')->value('title'));
        $this->assertSame(19900, $product->price_cents);
        $this->assertSame('دراسة جدوى اختبارية', $product->translations()->where('locale', 'ar')->value('title'));
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
