<?php

namespace App\Repository\Reference;

use App\Entity\Reference\PatientProgress;
use Doctrine\Persistence\ManagerRegistry;

class PatientProgressRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientProgress::class);
    }
}
