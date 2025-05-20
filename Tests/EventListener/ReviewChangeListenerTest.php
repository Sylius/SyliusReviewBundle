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

namespace Tests\Sylius\Bundle\ReviewBundle\EventListener;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ReviewBundle\EventListener\ReviewChangeListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sylius\Bundle\ReviewBundle\Updater\ReviewableRatingUpdaterInterface;
use Sylius\Component\Review\Model\ReviewableInterface;
use Sylius\Component\Review\Model\ReviewInterface;

final class ReviewChangeListenerTest extends TestCase
{
    /**
     * @var ReviewableRatingUpdaterInterface|MockObject
     */
    private MockObject $averageRatingUpdaterMock;
    private ReviewChangeListener $reviewChangeListener;
    protected function setUp(): void
    {
        $this->averageRatingUpdaterMock = $this->createMock(ReviewableRatingUpdaterInterface::class);
        $this->reviewChangeListener = new ReviewChangeListener($this->averageRatingUpdaterMock);
    }

    public function testRecalculatesSubjectRatingOnAcceptedReviewDeletion(): void
    {
        /** @var LifecycleEventArgs|MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        /** @var ReviewableInterface|MockObject $reviewSubjectMock */
        $reviewSubjectMock = $this->createMock(ReviewableInterface::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn($reviewMock);
        $reviewMock->expects($this->once())->method('getReviewSubject')->willReturn($reviewSubjectMock);
        $this->averageRatingUpdaterMock->expects($this->once())->method('update')->with($reviewSubjectMock);
        $this->reviewChangeListener->recalculateSubjectRating($eventMock);
    }

    public function testRemovesAReviewFromAReviewSubjectOnThePreRemoveEvent(): void
    {
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        /** @var ReviewableInterface|MockObject $reviewSubjectMock */
        $reviewSubjectMock = $this->createMock(ReviewableInterface::class);
        /** @var EntityManagerInterface|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $event = new PreRemoveEventArgs($reviewMock, $entityManagerMock);
        $reviewMock->expects($this->once())->method('getReviewSubject')->willReturn($reviewSubjectMock);
        $reviewSubjectMock->expects($this->once())->method('removeReview')->with($reviewMock);
        $this->averageRatingUpdaterMock->expects($this->once())->method('update')->with($reviewSubjectMock);
        $this->reviewChangeListener->recalculateSubjectRating($event);
    }

    public function testDoesNothingIfEventSubjectIsNotReviewObject(): void
    {
        /** @var LifecycleEventArgs|MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn('badObject');
        $this->averageRatingUpdaterMock->expects($this->never())->method('update')->with($this->isInstanceOf(ReviewableInterface::class));
        $this->reviewChangeListener->recalculateSubjectRating($eventMock);
    }
}
