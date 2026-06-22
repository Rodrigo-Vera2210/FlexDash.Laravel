<?php

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$kernel->bootstrap();

use App\Models\User;

// Find superadmin user
$user = User::where('email', 'superadmin@flexdash.com')->first();
if (!$user) {
    echo "Superadmin user not found!\n";
    exit(1);
}

// Generate JWT token manually
$header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
$secret = config('app.key') ?: env('APP_KEY', 'secret');
if (str_starts_with($secret, 'base64:')) {
    $secret = base64_decode(substr($secret, 7));
}

$payload = json_encode([
    'user_id' => $user->id,
    'role' => $user->role ?? 'user',
    'iat' => time(),
    'exp' => time() + 86400, // 24 hours
]);

$base64url = function ($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
};

$segments = [];
$segments[] = $base64url($header);
$segments[] = $base64url($payload);
$signingInput = implode('.', $segments);
$signature = hash_hmac('sha256', $signingInput, $secret, true);
$segments[] = $base64url($signature);

$jwt = implode('.', $segments);

echo "Generated Token: " . substr($jwt, 0, 15) . "...\n";

// Function to fetch page with Authorization header
$fetchPage = function($url) use ($kernel, $jwt) {
    $request = Illuminate\Http\Request::create($url, 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $jwt);
    
    $response = $kernel->handle($request);
    return $response->getContent();
};

// Request Superadmin Dashboard
$dashboardHtml = $fetchPage('/superadmin/dashboard');
file_put_contents(__DIR__ . '/dashboard_dump.html', $dashboardHtml);
echo "Dashboard HTML dumped to dashboard_dump.html\n";

// Request Company Detail (Company ID 1)
$companyHtml = $fetchPage('/superadmin/companies/1');
file_put_contents(__DIR__ . '/company_detail_dump.html', $companyHtml);
echo "Company Detail HTML dumped to company_detail_dump.html\n";

// Request Payments Page
$paymentsHtml = $fetchPage('/superadmin/payments');
file_put_contents(__DIR__ . '/payments_dump.html', $paymentsHtml);
echo "Payments HTML dumped to payments_dump.html\n";
