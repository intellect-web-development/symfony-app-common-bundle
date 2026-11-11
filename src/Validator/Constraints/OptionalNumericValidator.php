<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class OptionalNumericValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof OptionalNumeric) {
            throw new UnexpectedTypeException($constraint, OptionalNumeric::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', 'numeric')
                ->setCode(OptionalNumeric::NOT_NUMERIC_ERROR)
                ->addViolation();

            return;
        }

        $stringValue = trim((string) $value);
        if ('' === $stringValue) {
            return;
        }

        if (!is_numeric($stringValue)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', 'numeric')
                ->setCode(OptionalNumeric::NOT_NUMERIC_ERROR)
                ->addViolation();
        }
    }
}
