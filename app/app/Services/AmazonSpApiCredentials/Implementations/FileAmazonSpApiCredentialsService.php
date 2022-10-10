<?php

namespace App\Services\AmazonSpApiCredentials\Implementations;

use App\Services\AmazonSpApiCredentials\Contracts\AmazonSpApiCredentialsServiceInterface;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsException; // Base exception
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsFileNotFoundException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsInvalidFormatException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsMissingKeyException;
use App\Services\AmazonSpApiCredentials\Exceptions\AmazonSpApiCredentialsExpiredException;
use App\Events\AmazonSpApiCredentialsExpired;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Crypt;

// Service to retrieve Amazon SP-API credentials from a file
class FileAmazonSpApiCredentialsService implements AmazonSpApiCredentialsServiceInterface
{
    protected string $credentialsPath;

    /**
     * Constructor
     *
     * @param string|null $credentialsPath Path to the encrypted credentials file
     */
    public function __construct(string $credentialsPath = null)
    {
        $this->credentialsPath = $credentialsPath ?? storage_path('app/amazon_spapi_credentials.json.data');
    }

    /**
     * Get Amazon SP-API credentials from encrypted file
     *
     * @return array
     * @throws AmazonSpApiCredentialsFileNotFoundException
     * @throws AmazonSpApiCredentialsInvalidFormatException
     * @throws AmazonSpApiCredentialsMissingKeyException
     * @throws AmazonSpApiCredentialsExpiredException
     */
    public function getCredentials(): array
    {
        if (!file_exists($this->credentialsPath)) {
            throw new AmazonSpApiCredentialsFileNotFoundException('Amazon SP-API credentials file does not exist.');
        }

        $encrypted = file_get_contents($this->credentialsPath);
        try {
            $decrypted = Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            throw new AmazonSpApiCredentialsInvalidFormatException('Amazon SP-API credentials file could not be decrypted.');
        }
        $data = json_decode($decrypted, true);

        if (!is_array($data)) {
            throw new AmazonSpApiCredentialsInvalidFormatException('Amazon SP-API credentials file is not valid.');
        }

        $required = [
            'access_token',
            'token_type',
            'expires_in',
            'generated_at',
            'refresh_token',
            'selling_partner_id',
            'sts_credentials',
        ];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new AmazonSpApiCredentialsMissingKeyException("Missing key '$key' in Amazon SP-API credentials.");
            }
        }
        $stsRequired = ['access_key', 'secret_key', 'session_token'];
        foreach ($stsRequired as $key) {
            if (!isset($data['sts_credentials'][$key])) {
                throw new AmazonSpApiCredentialsMissingKeyException("Missing key '$key' in 'sts_credentials'.");
            }
        }

        // Check expiration
        if (empty($data['generated_at'])) {
            Event::dispatch(new AmazonSpApiCredentialsExpired());
            throw new AmazonSpApiCredentialsExpiredException('Amazon SP-API credentials do not have a generated_at date.');
        }
        $generatedAt = strtotime($data['generated_at']);
        $expiresIn = (int) $data['expires_in'];
        $now = time();
        if ($generatedAt + $expiresIn < $now) {
            // Dispatch event before throwing exception
            Event::dispatch(new AmazonSpApiCredentialsExpired());
            throw new AmazonSpApiCredentialsExpiredException('Amazon SP-API credentials have expired.');
        }

        return $data;
    }
}
