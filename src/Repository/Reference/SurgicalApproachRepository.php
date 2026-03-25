<?php

namespace App\Repository\Reference;

use App\Entity\Reference\SurgicalApproach;
use Doctrine\Persistence\ManagerRegistry;

class SurgicalApproachRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurgicalApproach::class);
    }
}
