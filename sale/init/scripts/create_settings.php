<?php

use core\setting\Setting;

// Init script for symbiose-related settings (default language: en)

Setting::assert_value('sale', 'order', 'sequence_format', '%05d{sequence}');
Setting::assert_value('sale', 'order', 'option_validity', 10);
Setting::assert_value('sale', 'accounting', 'invoice.sequence_format', '%2d{year}-%05d{sequence}');
Setting::assert_value('sale', 'accounting', 'invoice.sequence', 1);
Setting::assert_value('sale', 'accounting', 'account_sales', '700');
Setting::assert_value('sale', 'accounting', 'account_sales-taxes', '451');
Setting::assert_value('sale', 'accounting', 'account_trade-debtors', '400');
Setting::assert_value('sale', 'accounting', 'downpayment_sku', 'DOWNPAYMENT');
Setting::assert_value('sale', 'accounting', 'account_downpayment', '460', ['organisation_id' => 1]);
Setting::assert_value('finance', 'accounting', 'fiscal_year', 2024);
Setting::assert_value('finance', 'accounting', 'accounting_entry.sequence_format', '%s{journal}/%02d{year}/%05d{sequence}', ['organisation_id' => 1]);
Setting::assert_value('finance', 'accounting', 'accounting_entry.sequence', 1, ['organisation_id' => 1]);