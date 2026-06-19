<?php

declare(strict_types=1);

namespace Softspring\CmsAnalyticsPlugin\Tests\Unit\Analytics;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        if (!\array_key_exists($key, $this->values)) {
            $this->values[$key] = $callback(new class implements ItemInterface {
                public function getKey(): string
                {
                    return 'test';
                }

                public function get(): mixed
                {
                    return null;
                }

                public function isHit(): bool
                {
                    return false;
                }

                public function set(mixed $value): static
                {
                    return $this;
                }

                public function expiresAt(?\DateTimeInterface $expiration): static
                {
                    return $this;
                }

                public function expiresAfter(\DateInterval|int|null $time): static
                {
                    return $this;
                }

                public function tag(string|iterable $tags): static
                {
                    return $this;
                }

                public function getMetadata(): array
                {
                    return [];
                }
            }, false);
        }

        return $this->values[$key];
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }
}
