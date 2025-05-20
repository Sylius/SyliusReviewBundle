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

namespace Tests\Sylius\Bundle\ReviewBundle\Doctrine\ORM\Subscriber;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ReviewBundle\Doctrine\ORM\Subscriber\LoadMetadataSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Prophecy\Argument;

final class LoadMetadataSubscriberTest extends TestCase
{
    private LoadMetadataSubscriber $loadMetadataSubscriber;
    protected function setUp(): void
    {
        $this->loadMetadataSubscriber = new LoadMetadataSubscriber([
            'reviewable' => [
                'subject' => 'AcmeBundle\Entity\ReviewableModel',
                'review' => [
                    'classes' => [
                        'model' => 'AcmeBundle\Entity\ReviewModel',
                    ],
                ],
                'reviewer' => [
                    'classes' => [
                        'model' => 'AcmeBundle\Entity\ReviewerModel',
                    ],
                ],
            ],
        ]);
    }

    public function testImplementsEventSubscriber(): void
    {
        $this->assertInstanceOf(EventSubscriber::class, $this->loadMetadataSubscriber);
    }

    public function testHasSubscribedEvents(): void
    {
        $this->assertSame(['loadClassMetadata'], $this->loadMetadataSubscriber->getSubscribedEvents());
    }

    public function testMapsProperRelationsForReviewModel(): void
    {
        /** @var ClassMetadataFactory|MockObject $metadataFactoryMock */
        $metadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $classMetadataInfoMock */
        $classMetadataInfoMock = $this->createMock(ClassMetadata::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgumentsMock */
        $eventArgumentsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        $eventArgumentsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgumentsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($metadataFactoryMock);
        $classMetadataInfoMock->fieldMappings = ['id' => ['columnName' => 'id']];
        $metadataFactoryMock->expects($this->exactly(2))->method('getMetadataFor')->willReturnMap([['AcmeBundle\Entity\ReviewableModel', $classMetadataInfoMock], ['AcmeBundle\Entity\ReviewerModel', $classMetadataInfoMock]]);
        $metadataMock->expects($this->once())->method('getName')->willReturn('AcmeBundle\Entity\ReviewModel');
        $metadataMock->expects($this->once())->method('hasAssociation')->with('reviewSubject')->willReturn(false);
        $metadataMock->expects($this->exactly(2))->method('hasAssociation')->willReturnMap([['reviewSubject', false], ['author', false]]);
        $metadataMock->expects($this->once())->method('mapManyToOne')->with([
            'fieldName' => 'author',
            'targetEntity' => 'AcmeBundle\Entity\ReviewerModel',
            'joinColumns' => [[
                'name' => 'author_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
                'onDelete' => 'CASCADE',
            ]],
            'cascade' => ['persist'],
        ]);
        $metadataMock->expects($this->exactly(2))->method('mapManyToOne')->willReturnMap([[[
            'fieldName' => 'reviewSubject',
            'targetEntity' => 'AcmeBundle\Entity\ReviewableModel',
            'inversedBy' => 'reviews',
            'joinColumns' => [[
                'name' => 'reviewable_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
                'onDelete' => 'CASCADE',
            ]],
        ]], [[
            'fieldName' => 'author',
            'targetEntity' => 'AcmeBundle\Entity\ReviewerModel',
            'joinColumns' => [[
                'name' => 'author_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
                'onDelete' => 'CASCADE',
            ]],
            'cascade' => ['persist'],
        ]]]);
    }

    public function testDoesNotMapRelationForReviewModelIfTheRelationAlreadyExists(): void
    {
        /** @var ClassMetadataFactory|MockObject $metadataFactoryMock */
        $metadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgumentsMock */
        $eventArgumentsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        $eventArgumentsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgumentsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($metadataFactoryMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('AcmeBundle\Entity\ReviewModel');
        $metadataMock->expects($this->exactly(2))->method('hasAssociation')->willReturnMap([['reviewSubject', true], ['author', true]]);
        $metadataMock->expects($this->never())->method('mapManyToOne');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgumentsMock);
    }

    public function testMapsProperRelationsForReviewableModel(): void
    {
        /** @var ClassMetadataFactory|MockObject $metadataFactoryMock */
        $metadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgumentsMock */
        $eventArgumentsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        $eventArgumentsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgumentsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($metadataFactoryMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('AcmeBundle\Entity\ReviewableModel');
        $metadataMock->expects($this->once())->method('hasAssociation')->with('reviews')->willReturn(false);
        $metadataMock->expects($this->once())->method('mapOneToMany')->with([
            'fieldName' => 'reviews',
            'targetEntity' => 'AcmeBundle\Entity\ReviewModel',
            'mappedBy' => 'reviewSubject',
            'cascade' => ['all'],
        ]);
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgumentsMock);
    }

    public function testDoesNotMapRelationsForReviewableModelIfTheRelationAlreadyExists(): void
    {
        /** @var ClassMetadataFactory|MockObject $metadataFactoryMock */
        $metadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgumentsMock */
        $eventArgumentsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        $eventArgumentsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgumentsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($metadataFactoryMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('AcmeBundle\Entity\ReviewableModel');
        $metadataMock->expects($this->once())->method('hasAssociation')->with('reviews')->willReturn(true);
        $metadataMock->expects($this->never())->method('mapOneToMany');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgumentsMock);
    }

    public function testSkipsMappingConfigurationIfMetadataNameIsDifferent(): void
    {
        /** @var ClassMetadataFactory|MockObject $metadataFactoryMock */
        $metadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgumentsMock */
        $eventArgumentsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        $this->loadMetadataSubscriber = new LoadMetadataSubscriber([
            'reviewable' => [
                'subject' => 'AcmeBundle\Entity\ReviewableModel',
                'review' => [
                    'classes' => [
                        'model' => 'AcmeBundle\Entity\BadReviewModel',
                    ],
                ],
                'reviewer' => [
                    'classes' => [
                        'model' => 'AcmeBundle\Entity\ReviewerModel',
                    ],
                ],
            ],
        ]);
        $eventArgumentsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgumentsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($metadataFactoryMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('AcmeBundle\Entity\ReviewModel');
        $metadataMock->expects($this->exactly(2))->method('mapManyToOne')->willReturnMap([[Argument::type('array')], [Argument::type('array')]]);
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgumentsMock);
    }
}
