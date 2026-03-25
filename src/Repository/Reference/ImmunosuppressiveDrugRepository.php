<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ImmunosuppressiveDrug;
use Doctrine\Persistence\ManagerRegistry;

class ImmunosuppressiveDrugRepository extends AbstractReferenceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImmunosuppressiveDrug::class);
    }
}
