<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Analytics;

use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function hash;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error_msg;
use function sprintf;

final class GoogleAnalyticsAccessTokenProvider implements GoogleAnalyticsAccessTokenProviderInterface
{
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    private const APPLICATION_DEFAULT_CREDENTIALS_CLASS = 'Google\\Auth\\ApplicationDefaultCredentials';

    private const SERVICE_ACCOUNT_CREDENTIALS_CLASS = 'Google\\Auth\\Credentials\\ServiceAccountCredentials';

    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function getAccessToken(GoogleAnalyticsConfiguration $configuration): string
    {
        $credentials = $this->buildCredentials($configuration);
        $cacheKey = 'sfs_cms_analytics_ga4_access_token_' . hash('sha256', $this->credentialsCacheKey($credentials) ?: $configuration->propertyId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($credentials): string {
            $item->expiresAfter(3300);
            $token = $this->fetchAuthToken($credentials);

            if (!isset($token['access_token']) || !is_string($token['access_token']) || '' === $token['access_token']) {
                throw new RuntimeException('Google Analytics authentication did not return an access token.');
            }

            return $token['access_token'];
        });
    }

    private function buildCredentials(GoogleAnalyticsConfiguration $configuration): object
    {
        if (!class_exists(self::SERVICE_ACCOUNT_CREDENTIALS_CLASS) || !class_exists(self::APPLICATION_DEFAULT_CREDENTIALS_CLASS)) {
            throw new RuntimeException('Google Analytics 4 analytics requires the optional "google/auth" package. Install it to use the "google_analytics_4" analytics driver.');
        }

        if (null !== $configuration->credentialsJson) {
            $jsonKey = json_decode($configuration->credentialsJson, true);

            if (!is_array($jsonKey)) {
                throw new RuntimeException(sprintf('Invalid Google Analytics credentials JSON: %s.', json_last_error_msg()));
            }

            return new (self::SERVICE_ACCOUNT_CREDENTIALS_CLASS)(self::SCOPE, $jsonKey);
        }

        if (null !== $configuration->credentialsPath) {
            return new (self::SERVICE_ACCOUNT_CREDENTIALS_CLASS)(self::SCOPE, $configuration->credentialsPath);
        }

        $applicationDefaultCredentialsClass = self::APPLICATION_DEFAULT_CREDENTIALS_CLASS;

        return $applicationDefaultCredentialsClass::getCredentials(self::SCOPE);
    }

    private function credentialsCacheKey(object $credentials): string
    {
        if (!method_exists($credentials, 'getCacheKey')) {
            return '';
        }

        $cacheKey = $credentials->getCacheKey();

        return is_string($cacheKey) ? $cacheKey : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAuthToken(object $credentials): array
    {
        if (!method_exists($credentials, 'fetchAuthToken')) {
            throw new RuntimeException('Google Analytics credentials object cannot fetch an access token.');
        }

        $token = $credentials->fetchAuthToken();

        if (!is_array($token)) {
            throw new RuntimeException('Google Analytics authentication returned an invalid token response.');
        }

        return $token;
    }
}
