<?php

namespace App\Repository;

use App\Entity\CookRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CookRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CookRequest::class);
    }

    /**
     * @return CookRequest[]
     */
    public function findQueueForUser(User $owner): array
    {
        return $this->createQueryBuilder("c")
            ->andWhere("c.owner = :owner")
            ->andWhere("c.status = :status")
            ->setParameter("owner", $owner)
            ->setParameter("status", CookRequest::STATUS_QUEUED)
            ->orderBy("c.position", "ASC")
            ->addOrderBy("c.id", "ASC")
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CookRequest[]
     */
    public function findHistoryForUser(User $owner): array
    {
        return $this->createQueryBuilder("c")
            ->andWhere("c.owner = :owner")
            ->andWhere("c.status = :status")
            ->setParameter("owner", $owner)
            ->setParameter("status", CookRequest::STATUS_DONE)
            ->orderBy("c.completedAt", "DESC")
            ->addOrderBy("c.id", "DESC")
            ->getQuery()
            ->getResult();
    }

    public function nextPositionForUser(User $owner): int
    {
        $max = $this->createQueryBuilder("c")
            ->select("MAX(c.position)")
            ->andWhere("c.owner = :owner")
            ->setParameter("owner", $owner)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }

    public function findForUser(int $id, User $owner): ?CookRequest
    {
        return $this->findOneBy(["id" => $id, "owner" => $owner]);
    }
}
