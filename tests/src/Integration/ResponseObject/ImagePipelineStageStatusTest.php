<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\ImagePipelineStageStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStageStatus::class)]
final class ImagePipelineStageStatusTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "state": "done",
                "url": "https://github.com/x/y/actions/runs/1"
            }
            JSON;

        $object = $serializer->deserialize($json, ImagePipelineStageStatus::class, 'json');

        $this->assertInstanceOf(ImagePipelineStageStatus::class, $object);
        $this->assertSame('done', $object->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $object->url);
    }

    public function testDeserializeWithNullUrl(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "state": "idle",
                "url": null
            }
            JSON;

        $object = $serializer->deserialize($json, ImagePipelineStageStatus::class, 'json');

        $this->assertInstanceOf(ImagePipelineStageStatus::class, $object);
        $this->assertSame('idle', $object->state);
        $this->assertNull($object->url);
    }
}
