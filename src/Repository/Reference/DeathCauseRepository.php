<?php

namespace App\Repository\Reference;

use App\Entity\Reference\DeathCause;
use Doctrine\Persistence\ManagerRegistry;

class DeathCauseRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeathCause::class);
    }
}
