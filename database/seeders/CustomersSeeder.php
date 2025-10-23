<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomersSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Acme Corp',
                'contact_person' => 'John Doe',
                'contact_email' => 'john@acme.com',
                'contact_phone' => '555-0123',
                'hourly_rate' => 150,
                'address_line_1' => '123 Business Avenue',
                'address_line_2' => 'Suite 100',
                'city' => 'New York',
                'state' => 'NY',
                'postcode' => '10001',
                'country' => 'USA',
                'vat_number' => 'US123456789'
            ],
            [
                'name' => 'TechStart Inc',
                'contact_person' => 'Jane Smith',
                'contact_email' => 'jane@techstart.com',
                'contact_phone' => '555-0124',
                'hourly_rate' => 180,
                'address_line_1' => '456 Innovation Drive',
                'address_line_2' => 'Floor 5',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postcode' => '94105',
                'country' => 'USA',
                'vat_number' => 'US987654321'
            ],
            [
                'name' => 'Global Solutions',
                'contact_person' => 'Mike Johnson',
                'contact_email' => 'mike@global.com',
                'contact_phone' => '555-0125',
                'hourly_rate' => 200,
                'address_line_1' => '789 Commerce Street',
                'address_line_2' => 'Tower A',
                'city' => 'London',
                'state' => 'Greater London',
                'postcode' => 'SW1A 1AA',
                'country' => 'UK',
                'vat_number' => 'GB123456789'
            ],
            [
                'name' => 'Digital Dynamics',
                'contact_person' => 'Sarah Wilson',
                'contact_email' => 'sarah@digital.com',
                'contact_phone' => '555-0126',
                'hourly_rate' => 220,
                'address_line_1' => '321 Tech Park Drive',
                'address_line_2' => 'Building C',
                'city' => 'Austin',
                'state' => 'TX',
                'postcode' => '78701',
                'country' => 'USA',
                'vat_number' => 'US456789012'
            ],
            [
                'name' => 'CloudVenture',
                'contact_person' => 'Tom Brown',
                'contact_email' => 'tom@cloudventure.com',
                'contact_phone' => '555-0127',
                'hourly_rate' => 190,
                'address_line_1' => '555 Cloud Way',
                'address_line_2' => 'Suite 200',
                'city' => 'Seattle',
                'state' => 'WA',
                'postcode' => '98101',
                'country' => 'USA',
                'vat_number' => 'US789012345'
            ],
            [
                'name' => 'Innovation Labs',
                'contact_person' => 'Emma Davis',
                'contact_email' => 'emma@innovationlabs.com',
                'contact_phone' => '555-0128',
                'hourly_rate' => 210,
                'address_line_1' => '888 Research Boulevard',
                'address_line_2' => 'Lab Complex',
                'city' => 'Boston',
                'state' => 'MA',
                'postcode' => '02108',
                'country' => 'USA',
                'vat_number' => 'US234567890'
            ],
            [
                'name' => 'Enterprise Solutions',
                'contact_person' => 'David Martinez',
                'contact_email' => 'david@enterprise.com',
                'contact_phone' => '555-0129',
                'hourly_rate' => 250,
                'address_line_1' => '999 Fortune Plaza',
                'address_line_2' => 'Executive Floor',
                'city' => 'Chicago',
                'state' => 'IL',
                'postcode' => '60601',
                'country' => 'USA',
                'vat_number' => 'US567890123'
            ],
            [
                'name' => 'StartUp Accelerator',
                'contact_person' => 'Lisa Anderson',
                'contact_email' => 'lisa@startup.com',
                'contact_phone' => '555-0130',
                'hourly_rate' => 170,
                'address_line_1' => '222 Growth Lane',
                'address_line_2' => 'Innovation Hub',
                'city' => 'Denver',
                'state' => 'CO',
                'postcode' => '80202',
                'country' => 'USA',
                'vat_number' => 'US890123456'
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
