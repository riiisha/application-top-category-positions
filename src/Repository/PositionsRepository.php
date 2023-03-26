<?php

namespace App\Repository;

use App\Entity\Positions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Positions>
 *
 * @method Positions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Positions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Positions[]    findAll()
 * @method Positions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Positions::class);
    }

    public function save(Positions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Positions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPositionByDate($date)
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addScalarResult('category', 'category', 'integer');
        $rsm->addScalarResult('position', 'position', 'integer');

        $query = $this->_em->createNativeQuery(
            '
			SELECT 
			    category,
			    position
			FROM positions
			WHERE date = \'' . $date . '\'', $rsm);

        return $query->getResult();
    }
}
