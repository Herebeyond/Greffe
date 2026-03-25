<?php

namespace App\Repository\Reference;

use App\Entity\Reference\MedicalHistoryType;
use Doctrine\Persistence\ManagerRegistry;

class MedicalHistoryTypeRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalHistoryType::class);
    }
}
