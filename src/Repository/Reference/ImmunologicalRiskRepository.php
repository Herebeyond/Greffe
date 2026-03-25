<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ImmunologicalRisk;
use Doctrine\Persistence\ManagerRegistry;

class ImmunologicalRiskRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImmunologicalRisk::class);
    }
}
