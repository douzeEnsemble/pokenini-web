<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Report;
use App\ResponseObject\Album\ReportDetail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Report::class)]
final class ReportTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "total": 37,
                "total_caught": 20,
                "total_uncaught": 17,
                "detail": [
                    {
                        "catch_state": {
                            "name": "No",
                            "french_name": "Non",
                            "slug": "no",
                            "color": "#e57373"
                        },
                        "count": 1
                    },
                    {
                        "catch_state": {
                            "name": "Yes",
                            "french_name": "Oui",
                            "slug": "yes",
                            "color": "#66bb6a"
                        },
                        "count": 20
                    }
                ]
            }
            JSON;

        $object = $serializer->deserialize($json, Report::class, 'json');

        $this->assertInstanceOf(Report::class, $object);
        $this->assertSame(37, $object->getTotal());
        $this->assertSame(20, $object->getTotalCaught());
        $this->assertSame(17, $object->getTotalUncaught());
        $this->assertCount(2, $object->getDetail());
        $this->assertContainsOnlyInstancesOf(ReportDetail::class, $object->getDetail());
    }

    #[Test]
    public function deserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
            }
            JSON;

        $object = $serializer->deserialize($json, Report::class, 'json');

        $this->assertInstanceOf(Report::class, $object);
        $this->assertNull($object->getTotal());
        $this->assertNull($object->getTotalCaught());
        $this->assertNull($object->getTotalUncaught());
        $this->assertEmpty($object->getDetail());
    }
}
