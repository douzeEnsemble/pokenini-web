<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\ImagePipelineStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStatus::class)]
final class ImagePipelineStatusTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "correlation_id": "corr-1",
                "workflow_a": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "icon_pr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflow_b": {"state": "idle", "url": null},
                "resources_pr": {"state": "idle", "url": null}
            }
            JSON;

        $object = $serializer->deserialize($json, ImagePipelineStatus::class, 'json');

        $this->assertInstanceOf(ImagePipelineStatus::class, $object);
        $this->assertSame('corr-1', $object->correlationId);
        $this->assertSame('done', $object->workflowA->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $object->workflowA->url);
        $this->assertSame('merged', $object->iconPr->state);
        $this->assertSame('https://github.com/x/y/pull/2', $object->iconPr->url);
        $this->assertSame('idle', $object->workflowB->state);
        $this->assertNull($object->workflowB->url);
        $this->assertSame('idle', $object->resourcesPr->state);
        $this->assertNull($object->resourcesPr->url);
    }
}
