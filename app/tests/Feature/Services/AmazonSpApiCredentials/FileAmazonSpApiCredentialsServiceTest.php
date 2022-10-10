<?php

namespace Tests\Feature\Services\AmazonSpApiCredentials;

use App\Services\AmazonSpApiCredentials\Implementations\FileAmazonSpApiCredentialsService;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsFileNotFoundException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsInvalidFormatException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsMissingKeyException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsExpiredException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAmazonSpApiCredentialsServiceTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFile = storage_path('app/test_amazon_spapi_credentials.json.data');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        parent::tearDown();
    }

    public function testGetCredentialsReturnsValidData()
    {
        $data = [
            'access_token' => 'token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'generated_at' => date('Y-m-d H:i:s'),
            'refresh_token' => 'refresh',
            'selling_partner_id' => 'partner',
            'sts_credentials' => [
                'access_key' => 'key',
                'secret_key' => 'secret',
                'session_token' => 'session',
            ],
        ];
        $encrypted = Crypt::encryptString(json_encode($data));
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $result = $service->getCredentials();
        $this->assertEquals($data, $result);
    }

    public function testFileNotFoundThrowsException()
    {
        $service = new FileAmazonSpApiCredentialsService('/tmp/nonexistent.json');
        $this->expectException(AmazonSpApiCredentialsFileNotFoundException::class);
        $service->getCredentials();
    }

    public function testInvalidFormatThrowsException()
    {
        $encrypted = Crypt::encryptString('not-json');
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $this->expectException(AmazonSpApiCredentialsInvalidFormatException::class);
        $service->getCredentials();
    }

    public function testMissingKeyThrowsException()
    {
        $data = [
            'access_token' => 'token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'generated_at' => '2022-10-10 00:00:00',
            'refresh_token' => 'refresh',
            // 'selling_partner_id' => 'partner', // missing
            'sts_credentials' => [
                'access_key' => 'key',
                'secret_key' => 'secret',
                'session_token' => 'session',
            ],
        ];
        $encrypted = Crypt::encryptString(json_encode($data));
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $this->expectException(AmazonSpApiCredentialsMissingKeyException::class);
        $service->getCredentials();
    }

    public function testMissingStsKeyThrowsException()
    {
        $data = [
            'access_token' => 'token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'generated_at' => '2022-10-10 00:00:00',
            'refresh_token' => 'refresh',
            'selling_partner_id' => 'partner',
            'sts_credentials' => [
                // 'access_key' => 'key', // missing
                'secret_key' => 'secret',
                'session_token' => 'session',
            ],
        ];
        $encrypted = Crypt::encryptString(json_encode($data));
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $this->expectException(AmazonSpApiCredentialsMissingKeyException::class);
        $service->getCredentials();
    }

    public function testExpiredCredentialsThrowsException()
    {
        Event::fake();
        $data = [
            'access_token' => 'token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'generated_at' => '2022-10-09 00:00:00',
            'refresh_token' => 'refresh',
            'selling_partner_id' => 'partner',
            'sts_credentials' => [
                'access_key' => 'key',
                'secret_key' => 'secret',
                'session_token' => 'session',
            ],
        ];
        $encrypted = Crypt::encryptString(json_encode($data));
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $this->expectException(AmazonSpApiCredentialsExpiredException::class);
        $service->getCredentials();
        Event::assertDispatched(\App\Events\AmazonSpApiCredentialsExpired::class);
    }

    public function testMissingGeneratedAtThrowsException()
    {
        Event::fake();
        $data = [
            'access_token' => 'token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            // 'generated_at' => missing
            'refresh_token' => 'refresh',
            'selling_partner_id' => 'partner',
            'sts_credentials' => [
                'access_key' => 'key',
                'secret_key' => 'secret',
                'session_token' => 'session',
            ],
        ];
        $encrypted = Crypt::encryptString(json_encode($data));
        file_put_contents($this->testFile, $encrypted);
        $service = new FileAmazonSpApiCredentialsService($this->testFile);
        $this->expectException(AmazonSpApiCredentialsMissingKeyException::class);
        try {
            $service->getCredentials();
        } catch (\App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsMissingKeyException $e) {
            Event::assertNotDispatched(\App\Events\AmazonSpApiCredentialsExpired::class);
            throw $e;
        }
    }
}
