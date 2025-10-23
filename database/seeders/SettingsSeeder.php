<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Company Information
            'company_name' => 'ClientLedger Solutions',
            'company_email' => 'contact@clientledger.com',
            'company_phone' => '+1 (555) 123-4567',
            'company_website' => 'https://www.clientledger.com',
            'company_vat_id' => 'DE123456789',
            'company_address_street' => 'Innovation Boulevard',
            'company_address_number' => '42',
            'company_address_zipcode' => '10115',
            'company_address_city' => 'Berlin',
            'company_bank_info' => "Bank: Deutsche Bank\nIBAN: DE89 3704 0044 0532 0130 00\nBIC/SWIFT: DEUTDEFF\nAccount Holder: ClientLedger Solutions GmbH",
            'company_logo' => '',

            // Financial & Invoice Settings
            'currency_symbol' => '€',
            'currency_code' => 'EUR',
            'tax_rate' => '19.00',
            'invoice_prefix' => 'INV-',
            'invoice_number_format' => 'YYYY-MM-number',
            'invoice_number_start' => '1001',
            'invoice_number_random' => 'false',
            'invoice_number_random_length' => '8',
            'invoice_default_status' => 'draft',
            'invoice_auto_send' => 'false',
            'invoice_default_message' => 'Thank you for your business! We appreciate the opportunity to work with you on this project.',
            'invoice_payment_terms' => "Payment Terms:\n- Payment is due within 30 days of invoice date\n- Late payments may incur a 5% monthly interest charge\n- Please reference the invoice number with your payment\n- Bank transfer preferred, credit cards accepted\n\nFor any questions regarding this invoice, please contact our billing department.",
            'invoice_footer_col1' => 'company_info',
            'invoice_footer_col2' => 'bank_info',
            'invoice_footer_col3' => 'page_info',

            // Date & Time Settings
            'date_format' => 'DD/MM/YYYY',
            'time_format' => '24h',

            // Email Settings (using safe defaults)
            'mail_host' => 'smtp.mailtrap.io',
            'mail_port' => '2525',
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@clientledger.com',
            'mail_from_name' => 'ClientLedger',

            // Legal Settings
            'privacy_notice' => '<div style="background-color: #9151f8ff; border: 2px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
<h2 style="color: #ffebb0ff; margin-top: 0;">⚠️ Test Environment Notice</h2>
<p><strong>Important:</strong> This is a publicly accessible test environment designed for demonstration purposes. All content is automatically reset every 60 minutes. Any user may create, modify, or delete content within the system.</p>
<p><strong>Legal Warning:</strong> The creation or distribution of illegal content is strictly prohibited. This includes, but is not limited to, content involving racism, hate speech, incitement to violence (Volksverhetzung), harassment, or any other unlawful material. Any violations will be taken seriously. Server logs and IP addresses will be preserved and forwarded to law enforcement authorities. German law applies to all activities on this platform.</p>
</div>

<h2>Privacy Policy</h2>

<h3>Last Updated: October 19, 2025</h3>

<h3>1. Data Controller</h3>
<p>
Julian Billinger<br>
c/o Block Services<br>
Stuttgarter Str. 106<br>
70736 Fellbach<br>
Germany<br>
Email: <a href="mailto:datenschutz@billinger.dev">datenschutz@billinger.dev</a>
</p>

<p>No data protection officer has been appointed as there is no legal obligation to do so.</p>

<h3>2. General Information on Data Processing</h3>
<p>When visiting this website, personal data is processed only to the extent necessary. Processing is based on applicable data protection laws, in particular the General Data Protection Regulation (GDPR) and the German Telecommunications and Telemedia Data Protection Act (TTDSG).</p>
<ul>
<li>Art. 6(1)(f) GDPR (legitimate interest)</li>
<li>Art. 6(1)(a) GDPR (consent)</li>
<li>Art. 6(1)(b) GDPR (contract performance)</li>
</ul>

<h3>3. Hosting and Server Log Files</h3>
<p>This website is hosted by Hetzner Online GmbH. A data processing agreement (DPA) exists with Hetzner.</p>
<p>When accessing the website, the following information is automatically stored in server log files:</p>
<ul>
<li>Full IP address of the visitor</li>
<li>Date and time of access</li>
<li>Browser type and version</li>
<li>Operating system</li>
<li>Referrer URL</li>
</ul>
<p>This storage is necessary to ensure operation, error analysis, defense against attacks, and compliance with legal obligations. Given the nature of this test environment and the explicit warning regarding illegal content, full IP addresses are retained to enable law enforcement cooperation if necessary.</p>
<p><strong>Legal basis:</strong> Art. 6(1)(f) GDPR (legitimate interest in security and legal compliance) and Art. 6(1)(c) GDPR (legal obligation to cooperate with law enforcement authorities when illegal content is reported).</p>
<p><strong>Retention period:</strong> Log files are retained for a sufficient period to investigate security incidents and potential legal violations. In case of reported illegal content, relevant logs may be retained longer and forwarded to law enforcement authorities.</p>

<h3>4. Content Delivery Network and Security Services (Cloudflare)</h3>
<p>To secure and optimize loading times, this website uses services from Cloudflare Inc., USA. Cloudflare acts as a Content Delivery Network (CDN) and provides additional security functions. A Data Processing Addendum (DPA) with Standard Contractual Clauses (SCCs) has been concluded with Cloudflare to ensure an adequate level of data protection.</p>
<p>Cloudflare sets technically necessary cookies that are required for the operation and security of the website.</p>
<p>Transfer of personal data to third countries (particularly the USA) cannot be ruled out.</p>
<p><strong>Risk Notice:</strong> The USA is considered an unsafe third country under the GDPR. There is a risk that US authorities may access your data without you having effective legal remedies available as a data subject.</p>

<h3>5. Cookies and Session Management</h3>
<p>This application uses cookies that are technically necessary for authentication and session management. These cookies are essential for the proper functioning of the application.</p>
<p><strong>Session Cookies:</strong></p>
<ul>
<li>Name: Laravel session cookie (typically named with application name + "_session")</li>
<li>Purpose: User authentication and session management</li>
<li>Storage: Database-backed sessions</li>
<li>Retention: 120 minutes of inactivity (configurable)</li>
<li>Security: HttpOnly flag enabled, encrypted, CSRF protection active</li>
</ul>
<p><strong>CSRF Token:</strong></p>
<ul>
<li>Purpose: Protection against Cross-Site Request Forgery attacks</li>
<li>Included in: Laravel Sanctum middleware</li>
</ul>
<p><strong>Legal basis:</strong> Art. 6(1)(b) GDPR (necessary for contract performance) and Art. 6(1)(f) GDPR (legitimate interest in securing the application).</p>

<h3>6. User Registration and Account Data</h3>
<p>When you create an account, we collect and process:</p>
<ul>
<li>Name</li>
<li>Email address</li>
<li>Password (encrypted)</li>
<li>User role (admin/freelancer)</li>
<li>Hourly rate (if applicable)</li>
</ul>
<p><strong>Legal basis:</strong> Art. 6(1)(b) GDPR (contract performance).</p>
<p><strong>Retention:</strong> Account data is retained as long as your account exists. In this test environment, all data is reset every 60 minutes.</p>

<h3>7. Application Data</h3>
<p>When using the application, the following data may be processed:</p>
<ul>
<li>Customer information (name, contact details)</li>
<li>Project data (names, descriptions, deadlines, hourly rates)</li>
<li>Work logs (dates, hours worked, descriptions)</li>
<li>Invoice data</li>
</ul>
<p><strong>Legal basis:</strong> Art. 6(1)(b) GDPR (contract performance).</p>
<p><strong>Important:</strong> This is a test environment. All data is automatically deleted every 60 minutes.</p>

<h3>8. No External Tracking or Analytics</h3>
<p>This website does not use Google Analytics, tracking pixels, or any other third-party analytics services. No external services are integrated that would transfer personal data to third parties for marketing or analytics purposes.</p>

<h3>9. Technical and Organizational Measures</h3>
<p>The website is exclusively accessible via an SSL-encrypted connection (HTTPS).</p>
<p>We implement regular updates and security measures to protect your data as best as possible, including:</p>
<ul>
<li>Encrypted password storage (bcrypt)</li>
<li>CSRF protection</li>
<li>HttpOnly cookies to prevent XSS attacks</li>
<li>Session encryption</li>
<li>Regular security updates</li>
</ul>

<h3>10. Rights of Data Subjects</h3>
<p>You have the right to:</p>
<ul>
<li>Request access to your personal data (Art. 15 GDPR)</li>
<li>Request correction of inaccurate data (Art. 16 GDPR)</li>
<li>Request deletion of your data (Art. 17 GDPR)</li>
<li>Request restriction of processing (Art. 18 GDPR)</li>
<li>Withdraw consent at any time (Art. 7(3) GDPR)</li>
<li>Object to processing (Art. 21 GDPR)</li>
<li>Lodge a complaint with a supervisory authority (Art. 77 GDPR)</li>
</ul>
<p>To exercise your rights, please send an informal message to the email address provided above.</p>
<p><strong>Note:</strong> Due to the test environment nature of this application with automatic data resets every 60 minutes, some of these rights may have limited practical applicability.</p>

<h3>11. Changes to This Privacy Policy</h3>
<p>This privacy policy is regularly updated to comply with legal requirements or to reflect changes to the services used. The current version can always be found on this page.</p>',

            'imprint' => '<div style="background-color: #9151f8ff; border: 2px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
<h2 style="color: #ffebb0ff; margin-top: 0;">⚠️ Test Environment Notice</h2>
<p><strong>Important:</strong> This is a publicly accessible test environment designed for demonstration purposes. All content is automatically reset every 60 minutes. Any user may create, modify, or delete content within the system.</p>
<p><strong>Legal Warning:</strong> The creation or distribution of illegal content is strictly prohibited. This includes, but is not limited to, content involving racism, hate speech, incitement to violence (Volksverhetzung), harassment, or any other unlawful material. Any violations will be taken seriously. Server logs and IP addresses will be preserved and forwarded to law enforcement authorities. German law applies to all activities on this platform.</p>
</div>

<h2>Legal Notice (Impressum)</h2>

<h3>Information pursuant to § 5 TMG (German Telemedia Act)</h3>
<p>
Julian Billinger<br>
c/o Block Services<br>
Stuttgarter Str. 106<br>
70736 Fellbach<br>
Germany
</p>

<p><strong>Represented by:</strong><br>
Julian Billinger</p>

<p><strong>Contact:</strong><br>
Email: <a href="mailto:kontakt@billinger.dev">kontakt@billinger.dev</a></p>

<p><strong>Responsible for content according to § 18(2) MStV (German State Media Treaty):</strong><br>
Julian Billinger, c/o Block Services, Stuttgarter Str. 106, 70736 Fellbach, Germany</p>

<h3>Disclaimer</h3>
<p>The contents of this website have been created with the greatest care. However, we cannot guarantee the accuracy, completeness, or timeliness of the content. As a service provider, we are responsible for our own content on these pages in accordance with § 7(1) DDG (German Digital Services Act, formerly TMG) and general laws.</p>

<h3>Copyright</h3>
<p>The content and works created by the site operators on these pages are subject to German copyright law. Third-party contributions are identified as such.</p>

<h3>Test Environment Disclaimer</h3>
<p>This application is provided as a demonstration and test environment. No warranty is given for continuous availability, data persistence, or fitness for any particular purpose. All data is subject to automatic deletion every 60 minutes. Use at your own risk.</p>'
        ];

        foreach ($settings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value
            ]);
        }
    }
}
