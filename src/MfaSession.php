<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\Raindrop\Client;
use Adrenth\LaravelHydroRaindrop\Exceptions\MessageNotFoundInSessionStorage;
use Exception;
use Illuminate\Session\Store;

/**
 * Class MfaSession
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
final class MfaSession
{
    private const KEY_VERIFIED = 'hydro_raindrop_verified';
    private const KEY_MESSAGE = 'hydro_raindrop_message';
    private const KEY_TIME = 'hydro_raindrop_time';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $verificationLifetime;

    /**
     * Construct the MFA Session.
     *
     * @param Client $client
     * @param int $lifetime
     * @param int $verificationLifetime
     */
    public function __construct(Client $client, int $lifetime, int $verificationLifetime)
    {
        $this->client = $client;
        $this->store = resolve(Store::class);
        $this->lifetime = $lifetime;
        $this->verificationLifetime = $verificationLifetime;
    }

    /**
     * @return MfaSession
     * @throws Exception If message cannot be generated.
     */
    public function start(): MfaSession
    {
        $this->destroy();

        $this->store->put(self::KEY_TIME, time() + $this->lifetime);
        $this->store->put(self::KEY_MESSAGE, $this->client->generateMessage());

        return $this;
    }

    /**
     * @return MfaSession
     */
    public function setVerified(): MfaSession
    {
        if ($this->verificationLifetime > 0) {
            $this->store->put(self::KEY_VERIFIED, time() + ($this->verificationLifetime * 60));
        } else {
            $this->store->put(self::KEY_VERIFIED, true);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        if ($this->store->has(self::KEY_VERIFIED)) {
            $time = $this->store->get(self::KEY_VERIFIED);
            return ($time === true || (is_int($time) && time() <= $time));
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->store->has(self::KEY_TIME);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->store->has(self::KEY_TIME)) {
            return false;
        }

        $time = $this->store->get(self::KEY_TIME);

        return time() <= $time;
    }

    /**
     * @return int
     * @throws MessageNotFoundInSessionStorage
     */
    public function getMessage(): int
    {
        if (!$this->store->has(self::KEY_MESSAGE)) {
            throw new MessageNotFoundInSessionStorage(
                'No message found in session storage. Generate a message first.'
            );
        }

        return $this->store->get(self::KEY_MESSAGE);
    }

    /**
     * @return MfaSession
     * @throws Exception
     */
    public function regenerateMessage(): MfaSession
    {
        $this->store->put(self::KEY_MESSAGE, $this->client->generateMessage());

        return $this;
    }

    /**
     * @return MfaSession
     */
    public function destroy(): MfaSession
    {
        $this->store->forget(self::KEY_VERIFIED);
        $this->store->forget(self::KEY_MESSAGE);
        $this->store->forget(self::KEY_TIME);

        return $this;
    }
}
