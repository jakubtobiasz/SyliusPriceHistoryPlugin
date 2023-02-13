<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\PriceHistoryPlugin\Infrastructure\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\PriceHistoryPlugin\Domain\Model\ChannelPricingLogEntryInterface;
use Webmozart\Assert\Assert;

class ChannelPricingLogEntryRepository extends EntityRepository implements ChannelPricingLogEntryRepositoryInterface
{
    public function createByChannelPricingIdListQueryBuilder(mixed $channelPricingId): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.channelPricing', 'channelPricing')
            ->andWhere('channelPricing = :channelPricingId')
            ->orderBy('o.id', 'DESC')
            ->setParameter('channelPricingId', $channelPricingId)
        ;
    }

    public function findOlderThan(\DateTimeInterface $date, ?int $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.loggedAt < :date')
            ->setParameter('date', $date)
        ;

        if (null !== $limit) {
            Assert::positiveInteger($limit);
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findLatestOneByChannelPricing(ChannelPricingInterface $channelPricing): ?ChannelPricingLogEntryInterface
    {
        /** @var ChannelPricingLogEntryInterface|null $channelPricingLogEntry */
        $channelPricingLogEntry = $this->createQueryBuilder('o')
            ->andWhere('o.channelPricing = :channelPricing')
            ->setParameter('channelPricing', $channelPricing)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $channelPricingLogEntry;
    }

    public function findLowestPriceInPeriod(
        int $channelPricingLogEntryId,
        ChannelPricingInterface $channelPricing,
        \DateTimeInterface $startDate,
    ): ?int {
        /** @var ChannelPricingLogEntryInterface|null $channelPricingLogEntry */
        $channelPricingLogEntry = $this->createQueryBuilder('o')
            ->andWhere('o.loggedAt >= :startDate')
            ->andWhere('o.id != :channelPricingLogEntryId')
            ->andWhere('o.channelPricing = :channelPricing')
            ->setParameter('startDate', $startDate)
            ->setParameter('channelPricing', $channelPricing)
            ->setParameter('channelPricingLogEntryId', $channelPricingLogEntryId)
            ->orderBy('o.price', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($channelPricingLogEntry === null) {
            return $this->findLatestPriceBeyondPeriod($channelPricing, $startDate);
        }

        return $channelPricingLogEntry->getPrice();
    }

    private function findLatestPriceBeyondPeriod(
        ChannelPricingInterface $channelPricing,
        \DateTimeInterface $startDate,
    ): ?int {
        /** @var ChannelPricingLogEntryInterface|null $channelPricingLogEntry */
        $channelPricingLogEntry = $this->createQueryBuilder('o')
            ->andWhere('o.loggedAt < :startDate')
            ->andWhere('o.channelPricing = :channelPricing')
            ->setParameter('startDate', $startDate)
            ->setParameter('channelPricing', $channelPricing)
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $channelPricingLogEntry?->getPrice();
    }
}
