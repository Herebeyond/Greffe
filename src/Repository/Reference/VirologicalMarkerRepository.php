<?php

namespace App\Repository\Reference;

use App\Entity\Reference\VirologicalMarker;
use Doctrine\Persistence\ManagerRegistry;

class VirologicalMarkerRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VirologicalMarker::class);
    }
}
