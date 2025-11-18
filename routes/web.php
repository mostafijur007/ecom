<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve OpenAPI specification
Route::get('/api-docs', function () {
    $yaml = file_get_contents(base_path('openapi.yaml'));
    return response($yaml, 200)->header('Content-Type', 'text/yaml');
});

// Swagger UI route
Route::get('/api/documentation', function () {
    return view('swagger-ui');
});
