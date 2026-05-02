<?php

return [
    'bank_details' => [
        'bank_name' => env('QUOTATION_BANK_NAME', 'HDFC Bank'),
        'account_name' => env('QUOTATION_ACCOUNT_NAME', 'M.Balamurugan'),
        'account_number' => env('QUOTATION_ACCOUNT_NUMBER', '109944264559'),
        'ifsc' => env('QUOTATION_IFSC', 'ESFB0001113'),
        'branch' => env('QUOTATION_BANK_BRANCH', 'Main Branch'),
    ],
    'default_terms' => [
        '1. Payment Terms:
            80% of the total quotation in advance.
            20% of the total quotation after completion.',
        '2. Any changes to the project scope may affect the estimate and will be discussed and mutually agreed upon before implementation.',
        '3. All materials and labor costs mentioned in this estimate are accurate to the best of our knowledge at this time.',
        '4. Any unforeseen events or additional requirements may affect the estimate, and we will inform you promptly in such cases.',
        '5. Payment must be made as per the agreed terms to initiate the project.',
        '6. Duration may change due to weather, holidays, additional works, schedule change and cashflow.',
        '7. Material storage space, water and electricity will be arranged by the client.',

        'Please note: * this estimate is subject to change based on further analysis and detailed evaluation of the project requirements. We are not registered under GST. therefore, we
        are unable to issue a GST invoice.'
    ],
];
