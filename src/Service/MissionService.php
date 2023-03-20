<?php

namespace App\Service;

use App\Entity\Mission;
use App\Repository\MissionRepository;

class MissionService
{
    public function __construct(
        private MissionRepository $missionRepository,
    ){}

    public function generateReference(): int
    {
        $lastMission = $this->missionRepository->findOneBy([],['createdAt' => 'DESC']);

        if (null === $lastMission) {
            return 1;
        }

        return (int) $lastMission->getReference() + 1;
    }
}
