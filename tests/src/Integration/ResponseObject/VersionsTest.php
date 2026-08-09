<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\Versions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Versions::class)]
final class VersionsTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "back": {"version": "1.2.12", "updated_at": "2026-08-05T09:12:00+00:00"},
                "api": {"version": "1.2.13", "updated_at": "2026-08-04T21:47:00+00:00"}
            }
            JSON;

        $object = $serializer->deserialize($json, Versions::class, 'json');

        $this->assertInstanceOf(Versions::class, $object);
        $this->assertSame('1.2.12', $object->back->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $object->back->updatedAt?->format(\DateTimeInterface::ATOM));
        $this->assertSame('1.2.13', $object->api->version);
        $this->assertSame('2026-08-04T21:47:00+00:00', $object->api->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function deserializeWithNullBricks(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "back": {"version": null, "updated_at": null},
                "api": {"version": null, "updated_at": null}
            }
            JSON;

        $object = $serializer->deserialize($json, Versions::class, 'json');

        $this->assertInstanceOf(Versions::class, $object);
        $this->assertNull($object->back->version);
        $this->assertNull($object->back->updatedAt);
        $this->assertNull($object->api->version);
        $this->assertNull($object->api->updatedAt);
    }
}
