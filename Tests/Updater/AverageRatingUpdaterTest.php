<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\Bundle\ReviewBundle\Updater;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ReviewBundle\Updater\AverageRatingUpdater;
use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\ReviewBundle\Updater\ReviewableRatingUpdaterInterface;
use Sylius\Component\Review\Calculator\ReviewableRatingCalculatorInterface;
use Sylius\Component\Review\Model\ReviewableInterface;
use Sylius\Component\Review\Model\ReviewInterface;

final class AverageRatingUpdaterTest extends TestCase
{
    /**
     * @var ReviewableRatingCalculatorInterface|MockObject
     */
    private MockObject $averageRatingCalculatorMock;
    /**
     * @var ObjectManager|MockObject
     */
    private MockObject $reviewSubjectManagerMock;
    private AverageRatingUpdater $averageRatingUpdater;
    protected function setUp(): void
    {
        $this->averageRatingCalculatorMock = $this->createMock(ReviewableRatingCalculatorInterface::class);
        $this->reviewSubjectManagerMock = $this->createMock(ObjectManager::class);
        $this->averageRatingUpdater = new AverageRatingUpdater($this->averageRatingCalculatorMock, $this->reviewSubjectManagerMock);
    }

    public function testImplementsProductAverageRatingUpdaterInterface(): void
    {
        $this->assertInstanceOf(ReviewableRatingUpdaterInterface::class, $this->averageRatingUpdater);
    }

    public function testUpdatesReviewSubjectAverageRating(): void
    {
        /** @var ReviewableInterface|MockObject $reviewSubjectMock */
        $reviewSubjectMock = $this->createMock(ReviewableInterface::class);
        $this->averageRatingCalculatorMock->expects($this->once())->method('calculate')->with($reviewSubjectMock)->willReturn(4.5);
        $reviewSubjectMock->expects($this->once())->method('setAverageRating')->with(4.5);
        $this->reviewSubjectManagerMock->expects($this->once())->method('flush');
        $this->averageRatingUpdater->update($reviewSubjectMock);
    }

    public function testUpdatesReviewSubjectAverageRatingFromReview(): void
    {
        /** @var ReviewableInterface|MockObject $reviewSubjectMock */
        $reviewSubjectMock = $this->createMock(ReviewableInterface::class);
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        $reviewMock->expects($this->once())->method('getReviewSubject')->willReturn($reviewSubjectMock);
        $this->averageRatingCalculatorMock->expects($this->once())->method('calculate')->with($reviewSubjectMock)->willReturn(4.5);
        $reviewSubjectMock->expects($this->once())->method('setAverageRating')->with(4.5);
        $this->reviewSubjectManagerMock->expects($this->once())->method('flush');
        $this->averageRatingUpdater->updateFromReview($reviewMock);
    }
}
