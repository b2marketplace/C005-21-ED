<?php

namespace App\Services\AmazonSpApiCredentials\Contracts;

interface AmazonSpApiCredentialsServiceInterface
{
    /**
     * Obtiene las credenciales necesarias para autenticar peticiones a Amazon SP-API.
     *
     * @return array [
     *   'access_token' => string,
     *   'token_type' => string,
     *   'expires_in' => int,
     *   'generated_at' => string,
     *   'refresh_token' => string,
     *   'selling_partner_id' => string,
     *   'sts_credentials' => [
     *     'access_key' => string,
     *     'secret_key' => string,
     *     'session_token' => string,
     *   ],
     * ]
     */
    public function getCredentials(): array;
}
