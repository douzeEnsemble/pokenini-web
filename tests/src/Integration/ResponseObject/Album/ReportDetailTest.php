<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\ReportDetail;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ReportDetail::class)]
class ReportDetailTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "slug": "yes",
                "name": "Yes",
                "french_name": "Oui",
                "count": 20
            }
            JSON;

        $object = $serializer->deserialize($json, ReportDetail::class, 'json');

        $this->assertInstanceOf(ReportDetail::class, $object);
        $this->assertSame('yes', $object->getSlug());
        $this->assertSame('Yes', $object->getName());
        $this->assertSame('Oui', $object->getFrenchName());
        $this->assertSame(20, $object->getCount());
    }
}
