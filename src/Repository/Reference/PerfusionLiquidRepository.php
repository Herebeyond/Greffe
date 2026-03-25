<?php

namespace App\Repository\Reference;

use App\Entity\Reference\PerfusionLiquid;
use Doctrine\Persistence\ManagerRegistry;

class PerfusionLiquidRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerfusionLiquid::class);
    }
}
