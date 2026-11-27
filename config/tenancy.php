<?php

return [
    'enabled' => (bool) env('TENANCY_ENABLED', false),
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('TENANCY_CENTRAL_DOMAINS', 'localhost'))
    ))),
    'default_tenant' => env('TENANCY_DEFAULT_TENANT'),
    'tenant_connection' => 'tenant',
    'landlord_connection' => 'landlord',
];
