<?php

namespace App\Repository\Reference;

use App\Entity\Reference\SerologyMarker;
use Doctrine\Persistence\ManagerRegistry;

class SerologyMarkerRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerologyMarker::class);
    }
}
