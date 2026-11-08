<?php

namespace Tests\Feature;

use App\Filament\Widgets\CommerceStatsOverview;
use App\Filament\Widgets\OpportunityScoreChart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

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
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommerceStatsOverview::class)
            ->assertSee('المنتجات المعيارية الموحدة')
            ->assertSee('عروض المتاجر المسجلة')
            ->assertSee('جلسات التحليل بالذكاء الاصطناعي');

        Livewire::actingAs($user)
            ->test(OpportunityScoreChart::class)
            ->assertSee('مؤشرات الفرص والربحية الاستثمارية للمنتجات المعيارية');
    }

    public function test_canonical_products_resource_table_renders_with_arabic_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/canonical-products');

        $response->assertStatus(200);
        $response->assertSee('اسم المنتج المعياري');
        $response->assertSee('التصنيف');
        $response->assertSee('العلامة التجارية');
    }
}
