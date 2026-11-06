<?php

// Used only by all-features.cjs with a new database in the system temporary directory.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite' || ! str_starts_with($database, '/private/tmp/managementMoney-qa-')) {
    throw new RuntimeException('Browser QA requires its own temporary SQLite database.');
}
App\Models\User::factory()->create(['email' => 'browser-qa@example.test', 'password' => 'Browser-QA-2026!']);
$category = App\Models\Kategori::create(['nama' => 'Seed QA']);
for ($i = 1; $i <= 12; $i++) {
    App\Models\TransaksiKeuangan::create([
        'kategori_id' => $category->id, 'tipe' => 'masuk', 'nominal' => 1000,
        'tanggal' => date('Y').'-01-'.str_pad($i, 2, '0', STR_PAD_LEFT), 'keterangan' => 'Seed '.$i,
    ]);
}
echo "Browser fixtures ready.\n";
