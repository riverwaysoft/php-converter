<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Dart;

use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Webmozart\Assert\Assert;
use Exception;

class DartEnumValidator
{
    public function assertIsValidEnumForDart(DtoType $dto): void
    {
        Assert::true($dto->getExpressionType()->isAnyEnum());

        if ($dto->isEmpty()) {
            return;
        }

        if ($dto->isStringEnum()) {
            foreach ($dto->getProperties() as $property) {
                if ($property->getValue() !== $property->getName()) {
                    throw new Exception(sprintf(
                        'String enum %s should have identical keys and values to be supported by Dart. Error key "%s" and value "%s". Rename one of those to make sure they are equal',
                        $dto->getName(),
                        $property->getName(),
                        $property->getValue(),
                    ));
                }
            }
        } else {
            $propertyValues = array_map(fn (DtoEnumProperty $property) => $property->getValue(), $dto->getProperties());
            sort($propertyValues);

            if ($propertyValues[0] !== 0) {
                throw new Exception(sprintf('Numeric enum %s must start with 0 to be supported by Dart', $dto->getName()));
            }

            $arrayHoles = $this->findArrayHoles($propertyValues);
            if (!empty($arrayHoles)) {
                throw new Exception(sprintf('Numeric enum %s should not have holes in the array to be supported by Dart. Missed values: %s', $dto->getName(), join(', ', $arrayHoles)));
            }
        }
    }

    /**
     * @param int[] $array
     * @return int[]
     */
    private function findArrayHoles(array $array): array
    {
        $holes = [];
        $previous = null;
        foreach ($array as $value) {
            if ($previous !== null && $previous + 1 !== $value) {
                $holes[] = $previous + 1;
            }
            $previous = $value;
        }
        return $holes;
    }
}
