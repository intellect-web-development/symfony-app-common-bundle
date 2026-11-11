<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Cipher;

use InvalidArgumentException;
use phpseclib3\Crypt\AES;

readonly class AesCipher
{
    private AES $cipher;

    public function __construct(
        private CipherConfigInterface $systemCipherKey,
    ) {
        $this->cipher = new AES('ctr');
        $this->cipher->setIV($this->systemCipherKey->getVector());
        $this->cipher->setKey($this->systemCipherKey->getKey());
    }

    public function encrypt(string $payload): string
    {
        return bin2hex($this->encryptBytes($payload));
    }

    public function decrypt(string $payload): string
    {
        $binary = hex2bin($payload);
        if (false === $binary) {
            throw new InvalidArgumentException(sprintf('Invalid hex string "%s"', $payload));
        }

        return $this->decryptBytes($binary);
    }

    public function encryptBytes(string $payload): string
    {
        return $this->cipher->encrypt($payload);
    }

    public function decryptBytes(string $payload): string
    {
        return $this->cipher->decrypt($payload);
    }
}
