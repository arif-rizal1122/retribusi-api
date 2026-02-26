<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'petugas@bapenda.go.id')->first();
echo "Petugas ID: " . $user->id . "\n";
echo "Petugas Name: " . $user->name . "\n";
echo "Assignments Count: " . $user->assignments()->count() . "\n";

$taxpayers = App\Models\Taxpayer::where('created_by', $user->id)->count();
echo "Taxpayers strictly created by this Petugas: " . $taxpayers . "\n";

$request = Illuminate\Http\Request::create('/api/taxpayers', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

echo "\n--- API Output Simulation ---\n";
// Simulating the controller logic independently since Request mocking is tricky
$query = App\Models\Taxpayer::with(['opd', 'retributionTypes', 'retributionClassifications', 'creator']);
$query->where('opd_id', $user->opd_id);
$query->where('created_by', $user->id); 

$assignments = $user->assignments;
if ($assignments->isNotEmpty()) {
    $query->whereHas('retributionTypes', function($q) use ($assignments) {
        $q->where(function($query) use ($assignments) {
            foreach ($assignments as $assignment) {
                $query->orWhere(function($sq) use ($assignment) {
                    $sq->where('retribution_types.id', $assignment->retribution_type_id);
                    if ($assignment->retribution_classification_id) {
                        $sq->where('taxpayer_retribution_type.retribution_classification_id', $assignment->retribution_classification_id);
                    }
                });
            }
        });
    });
} else {
    echo "NO ASSIGNMENTS FOUND! Filter blocked.\n";
    $query->whereRaw('1 = 0');
}

echo "Total matching after all filters: " . $query->count() . "\n";
