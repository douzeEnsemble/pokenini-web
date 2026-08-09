<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\ReportDetail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ReportDetail::class)]
final class ReportDetailTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "catch_state": {
                    "name": "Yes",
                    "french_name": "Oui",
                    "slug": "yes",
                    "color": "#66bb6a"
                },
                "count": 20
            }
            JSON;

        $object = $serializer->deserialize($json, ReportDetail::class, 'json');

        $this->assertInstanceOf(ReportDetail::class, $object);
        $this->assertSame('yes', $object->getSlug());
        $this->assertSame('Yes', $object->getName());
        $this->assertSame('Oui', $object->getFrenchName());
        $this->assertSame('#66bb6a', $object->getColor());
        $this->assertSame(20, $object->getCount());
    }
}
