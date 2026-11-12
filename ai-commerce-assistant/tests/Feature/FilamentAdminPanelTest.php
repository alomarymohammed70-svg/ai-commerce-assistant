<?php

namespace Tests\Feature;

use App\Filament\Widgets\CommerceStatsOverview;
use App\Filament\Widgets\OpportunityScoreChart;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders_in_arabic_with_rtl(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('تسجيل الدخول');
        $response->assertSee('cairo');
    }

    public function test_authenticated_admin_can_access_dashboard_with_arabic_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('منصة الذكاء التجاري | AI Commerce');
        $response->assertSee('إدارة الكتالوج والمنتجات');
        $response->assertSee('أبحاث السوق والمنافسين');
        $response->assertSee('ذكاء الأعمال والتحليلات');
        $response->assertSee('إدارة النظام والمستخدمين');
        $response->assertSee('المنتجات المعيارية');
        $response->assertSee('عروض وقوائم المتاجر');
        $response->assertSee('تحليلات الذكاء الاصطناعي');
        $response->assertSee('مؤشرات أداء المنتجات');
    }

    public function test_dashboard_widgets_render_arabic_content(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CommerceStatsOverview::class)
            ->assertSee('المنتجات المعيارية الموحدة')
            ->assertSee('عروض المتاجر المسجلة')
            ->assertSee('جلسات التحليل بالذكاء الاصطناعي');

        Livewire::actingAs($admin)
            ->test(OpportunityScoreChart::class)
            ->assertSee('مؤشرات الفرص والربحية الاستثمارية للمنتجات المعيارية');
    }

    public function test_canonical_products_resource_table_renders_with_arabic_headers(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/canonical-products');

        $response->assertStatus(200);
        $response->assertSee('اسم المنتج المعياري');
        $response->assertSee('التصنيف');
        $response->assertSee('العلامة التجارية');
    }

    public function test_unauthenticated_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    public function test_regular_user_is_denied_access_to_admin_panel_and_resources(): void
    {
        $regularUser = User::factory()->create();

        $this->assertFalse($regularUser->isAdmin());
        $this->assertSame(User::ROLE_USER, $regularUser->role);

        $dashboardResponse = $this->actingAs($regularUser)->get('/admin');
        $dashboardResponse->assertForbidden();
        $dashboardResponse->assertDontSee('منصة الذكاء التجاري | AI Commerce');

        $this->actingAs($regularUser)->get('/admin/users')->assertForbidden();
        $this->actingAs($regularUser)->get('/admin/canonical-products')->assertForbidden();
        $this->actingAs($regularUser)->get('/admin/a-i-analyses')->assertForbidden();
    }

    public function test_regular_user_cannot_authenticate_via_filament_admin_login(): void
    {
        $regularUser = User::factory()->create([
            'password' => Hash::make('SecretPass123!'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $regularUser->email,
                'password' => 'SecretPass123!',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_authorized_admin_can_authenticate_via_filament_login_and_access_resources(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make('AdminSecret123!'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'AdminSecret123!',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin);

        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/a-i-analyses')->assertStatus(200);
    }

    public function test_api_registered_user_is_regular_can_use_mobile_api_and_is_denied_admin(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mobile Shopper',
            'email' => 'shopper@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'device_name' => 'flutter_app',
        ]);

        $registerResponse->assertStatus(201);
        $token = $registerResponse->json('data.token');

        $registeredUser = User::where('email', 'shopper@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_USER, $registeredUser->role);
        $this->assertFalse($registeredUser->isAdmin());

        // Regular user can still use the Mobile API normally
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        // Same registered user cannot access Filament Admin panel (explicitly use web guard)
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->actingAs($registeredUser, 'web')
            ->get('/admin')
            ->assertForbidden();
    }
}
