<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/** Expected behavior checks: failures preserve evidence of issues found during the audit. */
#[Group('audit')]
class FeatureAuditTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['tipe' => 'masuk', 'nominal' => 10000, 'tanggal' => date('Y-m-d'),
            'kategori_id' => Kategori::create(['nama' => 'Audit'])->id, 'keterangan' => 'Catatan audit'];
    }

    public static function registeredResourcePages(): array
    {
        return [['/kategori/create'], ['/kategori/1'], ['/kategori/1/edit'], ['/transaksi/1']];
    }

    #[DataProvider('registeredResourcePages')]
    public function test_registered_resource_pages_do_not_return_server_error(string $url): void
    {
        $this->actingAs(User::factory()->create());
        TransaksiKeuangan::create($this->payload());
        $this->assertLessThan(500, $this->get($url)->status());
    }

    public function test_unverified_account_is_directed_to_verification_from_dashboard(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/dashboard')->assertRedirect('/verify-email');
    }

    public function test_dashboard_login_time_stays_constant_when_reloaded(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->startOfMinute());
        $loginTime = $this->get('/dashboard')->viewData('waktuLogin');
        $this->travel(10)->minutes();
        $this->assertSame($loginTime, $this->get('/dashboard')->viewData('waktuLogin'));
    }

    public static function invalidFinancialPrecision(): array
    {
        return [['99999999999999.99'], ['100.123']];
    }

    #[DataProvider('invalidFinancialPrecision')]
    public function test_nominal_outside_decimal_15_2_is_rejected(string $nominal): void
    {
        $this->actingAs(User::factory()->create());
        $payload = array_replace($this->payload(), ['nominal' => $nominal]);
        $this->postJson('/transaksi', $payload)->assertUnprocessable()->assertJsonValidationErrors('nominal');
    }

    public static function transactionForms(): array
    {
        return [['create'], ['edit']];
    }

    #[DataProvider('transactionForms')]
    public function test_transaction_forms_display_server_validation_errors(string $form): void
    {
        $this->actingAs(User::factory()->create());
        $payload = $this->payload();
        $transaction = TransaksiKeuangan::create($payload);
        $url = $form === 'create' ? '/transaksi/create' : '/transaksi/'.$transaction->id.'/edit';
        $bad = array_replace($payload, ['nominal' => -1]);
        $response = $form === 'create' ? $this->from($url)->post('/transaksi', $bad)
            : $this->from($url)->put('/transaksi/'.$transaction->id, $bad);
        $response->assertSessionHasErrors('nominal');
        $message = session('errors')->first('nominal');
        $this->get($url)->assertSee($message);
    }

    public static function invalidReportFilters(): array
    {
        return [[['start_date' => 'invalid']], [['start_date' => '2026-12-31', 'end_date' => '2026-01-01']], [['tipe' => 'invalid']]];
    }

    #[DataProvider('invalidReportFilters')]
    public function test_report_rejects_invalid_filters(array $filters): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/transaksi?'.http_build_query($filters))->assertUnprocessable();
    }
}
