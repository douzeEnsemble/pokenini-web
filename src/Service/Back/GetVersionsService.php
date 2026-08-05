<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Versions;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionsService extends AbstractBackService
{
    public function get(): Versions
    {
        try {
            $content = $this->requestContent('GET', '/istration/version');

            /** @var Versions */
            return $this->serializer->deserialize($content, Versions::class, 'json');
        } catch (ExceptionInterface|NotEncodableValueException|\TypeError) {
            return new Versions(null, null);
        }
    }
}
