<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Cipher;

use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SystemCipherKey extends CipherConfig
{
    public function __construct(
        #[Autowire('%env(APP_SYSTEM_CIPHER_KEY)%')]
        #[SensitiveParameter]
        public string $key,
        #[Autowire('%env(APP_SYSTEM_CIPHER_VECTOR)%')]
        #[SensitiveParameter]
        public string $vector,
    ) {
        parent::__construct(key: $this->key, vector: $this->vector);
    }
}
