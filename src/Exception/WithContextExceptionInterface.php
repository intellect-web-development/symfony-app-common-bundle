<?php

namespace IWD\SymfonyAppCommon\Exception;

interface WithContextExceptionInterface
{
    public function getContext(): array;
}
