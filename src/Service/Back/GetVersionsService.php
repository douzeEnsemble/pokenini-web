<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Versions;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

class GetVersionsService extends AbstractBackService
{
    public function get(): Versions
    {
        try {
            $content = $this->requestContent('GET', '/istration/version');

            /** @var Versions */
            return $this->serializer->deserialize($content, Versions::class, 'json');
        } catch (HttpExceptionInterface|SerializerExceptionInterface|\TypeError) {
            return new Versions(null, null);
        }
    }
}
