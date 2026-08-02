<?php

return [
    'url'      => env('ODOO_URL'),
    'db'       => env('ODOO_DB'),
    'username' => env('ODOO_USERNAME'),
    'api_key'  => env('ODOO_API_KEY'),
    // 'default_company_id' => env('ODOO_DEFAULT_COMPANY_ID', 2), 
    'default_company_id' => (int) env('ODOO_DEFAULT_COMPANY_ID', 2),
];