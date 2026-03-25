<?php

namespace App\Repository\Reference;

use App\Entity\Reference\HlaLocus;
use Doctrine\Persistence\ManagerRegistry;

class HlaLocusRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HlaLocus::class);
    }
}
