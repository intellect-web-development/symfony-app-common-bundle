<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Cipher;

interface CipherConfigInterface
{
    public function getKey(): string;

    public function getVector(): string;
}
