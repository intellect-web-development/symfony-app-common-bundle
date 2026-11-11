<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Cipher;

use SensitiveParameter;

class CipherConfig implements CipherConfigInterface
{
    public const int AES_KEY_LENGTH = 16;

    public function __construct(
        #[SensitiveParameter]
        public string $key,
        #[SensitiveParameter]
        public string $vector,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getVector(): string
    {
        return $this->vector;
    }
}
