<?php

namespace App\Repository\Reference;

use App\Entity\Reference\PeritonealPosition;
use Doctrine\Persistence\ManagerRegistry;

class PeritonealPositionRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeritonealPosition::class);
    }
}
