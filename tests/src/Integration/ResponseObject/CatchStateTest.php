<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\CatchState;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(CatchState::class)]
class CatchStateTest extends WebTestCase
{
    public function testDeserialize(): void
    {
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<JSON
        {
            "name": "No",
            "frenchName": "Non",
            "slug": "no",
            "color": "#e57373"
        }
        JSON;

        /** @var CatchState $object */
        $object = $serializer->deserialize($json, CatchState::class, 'json');

        $this->assertInstanceOf(CatchState::class, $object);
        $this->assertSame('No', $object->getName());
        $this->assertSame('Non', $object->getFrenchName());
        $this->assertSame('no', $object->getSlug());
        $this->assertSame('#e57373', $object->getColor());
    }
}
