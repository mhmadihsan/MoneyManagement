<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinanceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'tipe' => 'masuk', 'nominal' => '125000', 'tanggal' => date('Y').'-06-15',
            'kategori_id' => Kategori::firstOrCreate(['nama' => 'Gaji QA'])->id,
            'keterangan' => 'Transaksi pengujian',
        ], $overrides);
    }

    public static function protectedEndpoints(): array
    {
        return [
            ['GET', '/dashboard'], ['GET', '/kategori'], ['POST', '/kategori'],
            ['GET', '/kategori/create'], ['GET', '/kategori/1'], ['GET', '/kategori/1/edit'],
            ['PUT', '/kategori/1'], ['PATCH', '/kategori/1'], ['DELETE', '/kategori/1'],
            ['GET', '/transaksi'], ['GET', '/transaksi/create'], ['POST', '/transaksi'],
            ['GET', '/transaksi/1'], ['GET', '/transaksi/1/edit'], ['PUT', '/transaksi/1'],
            ['PATCH', '/transaksi/1'], ['DELETE', '/transaksi/1'], ['GET', '/profile'],
            ['PATCH', '/profile'], ['DELETE', '/profile'], ['PUT', '/password'],
            ['GET', '/confirm-password'], ['POST', '/confirm-password'],
            ['GET', '/verify-email'], ['GET', '/verify-email/1/hash'],
            ['POST', '/email/verification-notification'], ['POST', '/logout'],
        ];
    }

    #[DataProvider('protectedEndpoints')]
    public function test_guest_is_redirected_to_login(string $method, string $url): void
    {
        $this->call($method, $url)->assertRedirect('/login');
    }

    public static function pages(): array
    {
        return [['/dashboard'], ['/kategori'], ['/transaksi/create'], ['/transaksi'], ['/profile'], ['/confirm-password']];
    }

    #[DataProvider('pages')]
    public function test_authenticated_pages_render_with_empty_finance_data(string $url): void
    {
        $this->signIn();
        $this->get($url)->assertOk();
    }

    public function test_dashboard_totals_balance_current_year_count_and_chart_data(): void
    {
        $this->signIn();
        TransaksiKeuangan::create($this->payload(['nominal' => 1000000]));
        TransaksiKeuangan::create($this->payload(['tipe' => 'keluar', 'nominal' => 250000]));
        TransaksiKeuangan::create($this->payload(['nominal' => 50000, 'tanggal' => (date('Y') - 1).'-01-01']));
        $this->get('/dashboard')->assertOk()->assertViewHas('totalMasuk', 1050000)
            ->assertViewHas('totalKeluar', 250000)->assertViewHas('transaksiTahunIni', 2)
            ->assertSee('Rp 800.000')->assertSee('data: [1050000, 250000]', false);
        $this->get('/transaksi/create')->assertViewHas('saldoAkhir', 800000);
    }

    public function test_category_create_rename_delete_and_success_messages(): void
    {
        $this->signIn();
        $this->post('/kategori', ['nama' => 'Kategori QA'])->assertRedirect('/kategori')->assertSessionHas('success');
        $category = Kategori::where('nama', 'Kategori QA')->firstOrFail();
        $this->get('/kategori')->assertSee('Kategori QA')->assertSee('editKategoriModal'.$category->id);
        $this->put('/kategori/'.$category->id, ['nama' => 'Kategori Revisi'])->assertRedirect('/kategori');
        $this->assertDatabaseHas('kategoris', ['id' => $category->id, 'nama' => 'Kategori Revisi']);
        $this->delete('/kategori/'.$category->id)->assertRedirect('/kategori');
        $this->assertDatabaseMissing('kategoris', ['id' => $category->id]);
    }

    public static function invalidCategoryNames(): array
    {
        return [[''], [str_repeat('a', 256)], [['invalid']], ['   ']];
    }

    #[DataProvider('invalidCategoryNames')]
    public function test_category_name_is_validated_on_create_and_update(mixed $name): void
    {
        $this->signIn();
        $category = Kategori::create(['nama' => 'Asli']);
        $this->postJson('/kategori', ['nama' => $name])->assertUnprocessable()->assertJsonValidationErrors('nama');
        $this->putJson('/kategori/'.$category->id, ['nama' => $name])->assertUnprocessable()->assertJsonValidationErrors('nama');
        $this->assertSame('Asli', $category->fresh()->nama);
        $this->assertDatabaseCount('kategoris', 1);
    }

    public function test_category_maximum_length_is_accepted(): void
    {
        $this->signIn();
        $this->post('/kategori', ['nama' => str_repeat('a', 255)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('kategoris', 1);
    }

    public static function transactionTypes(): array
    {
        return [['masuk'], ['keluar']];
    }

    #[DataProvider('transactionTypes')]
    public function test_transaction_create_edit_update_and_delete(string $type): void
    {
        $this->signIn();
        $payload = $this->payload(['tipe' => $type]);
        $this->post('/transaksi', $payload)->assertRedirect('/transaksi')->assertSessionHas('success');
        $transaction = TransaksiKeuangan::firstOrFail();
        $this->assertDatabaseHas('transaksi_keuangans', $payload);
        $this->get('/transaksi/'.$transaction->id.'/edit')->assertOk()->assertSee('Transaksi pengujian');
        $updated = array_replace($payload, ['nominal' => '75000', 'keterangan' => 'Diubah QA']);
        $this->put('/transaksi/'.$transaction->id, $updated)->assertRedirect('/transaksi')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transaksi_keuangans', $updated);
        $this->get('/transaksi')->assertOk()->assertSee('Diubah QA')->assertSee('Rp 75.000');
        $this->delete('/transaksi/'.$transaction->id)->assertRedirect('/transaksi');
        $this->assertDatabaseCount('transaksi_keuangans', 0);
    }

    public static function invalidTransactions(): array
    {
        return [
            ['tipe', ''], ['tipe', 'transfer'], ['nominal', ''], ['nominal', -1], ['nominal', 'abc'],
            ['tanggal', ''], ['tanggal', 'bukan-tanggal'], ['kategori_id', ''], ['kategori_id', 99999],
            ['keterangan', ['invalid']],
        ];
    }

    #[DataProvider('invalidTransactions')]
    public function test_transaction_validation_preserves_data(string $field, mixed $value): void
    {
        $this->signIn();
        $payload = $this->payload();
        $transaction = TransaksiKeuangan::create($payload);
        $bad = array_replace($payload, [$field => $value]);
        $this->postJson('/transaksi', $bad)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->putJson('/transaksi/'.$transaction->id, $bad)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('transaksi_keuangans', 1);
        $this->assertSame('125000', (string) $transaction->fresh()->nominal);
    }

    public function test_optional_description_and_zero_nominal_are_accepted_by_current_rules(): void
    {
        $this->signIn();
        $payload = $this->payload(['nominal' => 0]);
        unset($payload['keterangan']);
        $this->post('/transaksi', $payload)->assertSessionHasNoErrors()->assertRedirect('/transaksi');
        $this->assertDatabaseHas('transaksi_keuangans', ['nominal' => 0, 'keterangan' => null]);
    }

    public static function filters(): array
    {
        return [
            [[], ['B', 'C', 'A']], [['tipe' => 'masuk'], ['C', 'A']], [['tipe' => 'keluar'], ['B']],
            [['start_date' => '2026-06-15'], ['B', 'C']], [['end_date' => '2026-06-15'], ['C', 'A']],
            [['start_date' => '2026-06-15', 'end_date' => '2026-06-15'], ['C']],
            [['tipe' => 'keluar', 'end_date' => '2026-06-15'], []],
            [['category' => 'first'], ['C', 'A']],
            [['category' => 'first', 'tipe' => 'masuk', 'start_date' => '2026-06-15', 'end_date' => '2026-06-15'], ['C']],
        ];
    }

    #[DataProvider('filters')]
    public function test_report_filters_order_and_filtered_totals(array $filter, array $expected): void
    {
        $this->signIn();
        $first = Kategori::create(['nama' => 'Alpha']);
        $second = Kategori::create(['nama' => 'Beta']);
        foreach ([['A', 'masuk', 100000, '2026-06-01', $first->id], ['B', 'keluar', 25000, '2026-06-30', $second->id], ['C', 'masuk', 50000, '2026-06-15', $first->id]] as [$note, $type, $amount, $date, $category]) {
            TransaksiKeuangan::create(['keterangan' => $note, 'tipe' => $type, 'nominal' => $amount, 'tanggal' => $date, 'kategori_id' => $category]);
        }
        if (isset($filter['category'])) {
            unset($filter['category']);
            $filter['kategori_id'] = $first->id;
        }
        $response = $this->get('/transaksi?'.http_build_query($filter))->assertOk();
        $this->assertSame($expected, $response->viewData('transaksis')->pluck('keterangan')->all());
        $income = (in_array('A', $expected) ? 100000 : 0) + (in_array('C', $expected) ? 50000 : 0);
        $expense = in_array('B', $expected) ? 25000 : 0;
        foreach ([$income, $expense, $income - $expense] as $amount) {
            $response->assertSee('Rp '.number_format($amount, 0, ',', '.'));
        }
    }

    public function test_output_escapes_category_and_description_html(): void
    {
        $this->signIn();
        $script = '<script>alert("QA")</script>';
        $category = Kategori::create(['nama' => $script]);
        TransaksiKeuangan::create($this->payload(['kategori_id' => $category->id, 'keterangan' => $script]));
        $this->get('/kategori')->assertSee($script)->assertDontSee($script, false);
        $this->get('/transaksi')->assertSee($script)->assertDontSee($script, false);
    }

    public static function missingRecords(): array
    {
        return [['PUT', '/kategori/99999'], ['DELETE', '/kategori/99999'], ['GET', '/transaksi/99999/edit'], ['PUT', '/transaksi/99999'], ['DELETE', '/transaksi/99999']];
    }

    #[DataProvider('missingRecords')]
    public function test_missing_resources_return_404(string $method, string $url): void
    {
        $this->signIn();
        $this->call($method, $url)->assertNotFound();
    }

    public function test_category_with_transactions_cannot_be_deleted_and_displays_error(): void
    {
        $this->signIn();
        $transaction = TransaksiKeuangan::create($this->payload());
        $this->delete('/kategori/'.$transaction->kategori_id)->assertRedirect('/kategori')
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh transaksi.')
            ->assertSessionMissing('success');
        $this->assertDatabaseHas('kategoris', ['id' => $transaction->kategori_id]);
        $this->assertDatabaseHas('transaksi_keuangans', ['id' => $transaction->id]);
        $this->get('/kategori')->assertSee('Kategori tidak dapat dihapus karena masih digunakan oleh transaksi.');
        $this->get('/dashboard')->assertViewHas('totalMasuk', 125000);
    }

    public function test_category_can_be_deleted_after_its_last_transaction_is_deleted(): void
    {
        $this->signIn();
        $transaction = TransaksiKeuangan::create($this->payload());
        $categoryId = $transaction->kategori_id;
        $this->delete('/transaksi/'.$transaction->id)->assertRedirect('/transaksi');
        $this->delete('/kategori/'.$categoryId)->assertRedirect('/kategori')->assertSessionHas('success');
        $this->assertDatabaseMissing('kategoris', ['id' => $categoryId]);
    }

    public function test_finance_data_is_currently_shared_between_accounts(): void
    {
        $this->signIn();
        $transaction = TransaksiKeuangan::create($this->payload());
        $this->signIn();
        $this->get('/transaksi')->assertSee('Transaksi pengujian');
        $this->delete('/transaksi/'.$transaction->id)->assertRedirect('/transaksi');
        $this->assertDatabaseCount('transaksi_keuangans', 0);
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();
        Event::fake([Lockout::class]);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        Event::assertDispatched(Lockout::class);
        $this->assertGuest();
    }

    public function test_remember_me_issues_cookie(): void
    {
        $user = User::factory()->create();
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 'on']);
        $this->assertAuthenticatedAs($user);
        $response->assertCookie(auth()->guard()->getRecallerName());
    }

    public function test_profile_rejects_duplicate_and_invalid_email(): void
    {
        $this->signIn();
        $other = User::factory()->create();
        foreach ([$other->email, 'invalid'] as $email) {
            $this->patchJson('/profile', ['name' => 'QA', 'email' => $email])->assertUnprocessable()->assertJsonValidationErrors('email');
        }
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $this->signIn();
        $this->put('/password', ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'different-password'])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_verification_notification_can_be_sent_and_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/email/verification-notification')->assertRedirect()->assertSessionHas('status', 'verification-link-sent');
        }
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->post('/email/verification-notification')->assertStatus(429);
    }
}
