<?php

namespace App\Tests;

use App\Entity\CookRequest;
use App\Entity\CookRequestEntry;
use App\Entity\Recipe;
use PHPUnit\Framework\TestCase;

class CookRequestTest extends TestCase
{
    public function testNewRequestStartsQueued(): void
    {
        $request = new CookRequest();

        self::assertSame(CookRequest::STATUS_QUEUED, $request->getStatus());
        self::assertFalse($request->isDone());
        self::assertNull($request->getCompletedAt());
        self::assertSame(0, $request->getPosition());
        self::assertCount(0, $request->getEntries());
    }

    public function testMarkDoneAndReopen(): void
    {
        $request = new CookRequest();
        $request->setTitle('Sonntag mit der Familie');
        $completedAt = new \DateTimeImmutable('2026-11-14 18:00:00');

        $request->markDone($completedAt);

        self::assertTrue($request->isDone());
        self::assertSame($completedAt, $request->getCompletedAt());
        self::assertSame('Sonntag mit der Familie', $request->getTitle());

        $request->reopen(4);

        self::assertFalse($request->isDone());
        self::assertSame(CookRequest::STATUS_QUEUED, $request->getStatus());
        self::assertNull($request->getCompletedAt());
        self::assertSame(4, $request->getPosition());
    }

    public function testEntriesKeepOrderAndBackReference(): void
    {
        $request = new CookRequest();
        $first = (new CookRequestEntry())->setRecipe(new Recipe());
        $second = (new CookRequestEntry())->setRecipe(new Recipe());

        $request->addEntry($first);
        $request->addEntry($second);
        $request->addEntry($first);

        self::assertCount(2, $request->getEntries());
        self::assertSame(1, $first->getPosition());
        self::assertSame(2, $second->getPosition());
        self::assertSame($request, $first->getCookRequest());

        $request->removeEntry($first);

        self::assertCount(1, $request->getEntries());
        self::assertSame($request, $second->getCookRequest());
    }
}
