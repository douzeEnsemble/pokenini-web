<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\ActionLog;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ActionLog::class)]
final class ActionLogTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "created_at": "2023-03-21T09:14:36+00:00",
                "done_at": null,
                "execution_time": null,
                "details": [],
                "error_trace": null
            }
            JSON;

        $object = $serializer->deserialize($json, ActionLog::class, 'json');

        $this->assertInstanceOf(ActionLog::class, $object);
        $this->assertSame('2023-03-21T09:14:36+00:00', $object->createdAt->format('c'));
        $this->assertNull($object->doneAt);
        $this->assertNull($object->executionTime);
        $this->assertSame([], $object->details);
        $this->assertNull($object->errorTrace);
    }

    public function testDeserializeWithDoneAt(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "created_at": "2023-03-20T09:14:36+00:00",
                "done_at": "2023-03-20T10:05:08+00:00",
                "execution_time": 3032,
                "details": {"dex_availabilities": 22472},
                "error_trace": null
            }
            JSON;

        $object = $serializer->deserialize($json, ActionLog::class, 'json');

        $this->assertInstanceOf(ActionLog::class, $object);
        $this->assertSame('2023-03-20T09:14:36+00:00', $object->createdAt->format('c'));
        $this->assertNotNull($object->doneAt);
        $this->assertSame('2023-03-20T10:05:08+00:00', $object->doneAt->format('c'));
        $this->assertSame(3032, $object->executionTime);
        $this->assertSame(['dex_availabilities' => 22472], $object->details);
        $this->assertNull($object->errorTrace);
    }
}
