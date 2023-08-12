<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\OutputGenerator\ApiEndpointGeneratorInterface;
use Webmozart\Assert\Assert;

class AxiosEndpointGenerator implements ApiEndpointGeneratorInterface
{
    public function __construct(
        private TypeScriptTypeResolver $typeResolver,
    ) {
    }

    public function generate(ApiEndpoint $apiEndpoint, DtoList $dtoList): string
    {
        $string = "\n%sexport const %s = (%s): %s => {\n%s\n}\n";

        $codeReference = '';
        if ($apiEndpoint->codeReference) {
            $codeReference = sprintf("/** @see %s */\n", $apiEndpoint->codeReference);
        }

        $fullRoute = $apiEndpoint->route . '/' . $apiEndpoint->method->getType();
        $name = $this->normalizeEndpointName($fullRoute);

        $params = array_map(
            fn (ApiEndpointParam $param): string => "{$param->name}: {$this->typeResolver->getTypeFromPhp($param->type, null, $dtoList)}",
            $apiEndpoint->routeParams,
        );

        if ($apiEndpoint->input) {
            $inputType = $this->typeResolver->getTypeFromPhp($apiEndpoint->input->type, null, $dtoList);
            $params = array_merge($params, ["{$apiEndpoint->input->name}: {$inputType}"]);
        }

        if (count($apiEndpoint->queryParams)) {
            $queryParamsAsTs = array_map(
                fn (ApiEndpointParam $param): string => sprintf(
                    "%s%s: %s",
                    $param->name,
                    $param->type instanceof PhpOptionalType ? '?' : '',
                    $this->typeResolver->getTypeFromPhp($param->type, null, $dtoList)
                ),
                $apiEndpoint->queryParams,
            );
            $params = array_merge($params, $queryParamsAsTs);
        }

        $params = implode(', ', array_filter($params));

        $formParams = [];
        if ($apiEndpoint->input) {
            $formParams[] = $apiEndpoint->input->name;
        }
        if ($apiEndpoint->queryParams) {
            $message = sprintf("Multiple query params are not supported. Context: %s", json_encode($apiEndpoint->queryParams));
            Assert::count($apiEndpoint->queryParams, 1, $message);
            $formParams[] = sprintf('{ params: %s }', $apiEndpoint->queryParams[0]->name);
        }
        $formParamsAsString = $formParams ? sprintf(", %s", implode(', ', $formParams)) : '';

        $outputType = $apiEndpoint->output ? $this->typeResolver->getTypeFromPhp($apiEndpoint->output, null, $dtoList) : 'null';

        $returnType = sprintf('Promise<%s>', $outputType);

        $route = $this->injectJavaScriptInterpolatedVariables($apiEndpoint->route);
        $body = sprintf('  return axios
    .%s<%s>(`%s`%s)
    .then((response) => response.data);', $apiEndpoint->method->getType(), $outputType, $route, $formParamsAsString);

        return sprintf($string, $codeReference, $name, $params, $returnType, $body);
    }

    private function normalizeEndpointName(string $str): string
    {
        // Remove slashes and braces
        $str = preg_replace('/[^a-zA-Z0-9]/', ' ', $str);
        // Convert to camel case
        $str = ucwords($str);
        // Remove spaces and convert the first character to lowercase
        return lcfirst(str_replace(' ', '', $str));
    }

    private function injectJavaScriptInterpolatedVariables(string $route): string
    {
        // Regular expression pattern to match parameter placeholders
        $pattern = '/\{([^\/}]+)\}/';
        return preg_replace($pattern, '${$1}', $route);
    }
}
