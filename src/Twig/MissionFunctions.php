<?php

namespace App\Twig;

use App\Entity\Job;
use App\Entity\Mission;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\MissionParticipantRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MissionFunctions extends AbstractExtension
{
    public function __construct(
        private MissionParticipantRepository $missionParticipantRepository,
    ){}

    public function getFunctions()
    {
        return [
            new TwigFunction('user_get_mission_role', [$this, 'userGetMissionRole']),
            new TwigFunction('user_get_mission_job', [$this, 'userGetMissionJob']),
            new TwigFunction('hash', [$this, 'hash']),
        ];
    }

    public function userGetMissionRole(User $user, Mission $mission): ?Role
    {
        $participant = $this->missionParticipantRepository->findOneBy([
            'user' => $user,
            'mission' => $mission,
        ]);

        if (null === $participant) {
            return null;
        }

        return $participant->getRole();
    }

    public function userGetMissionJob(User $user, Mission $mission): ?Job
    {
        $participant = $this->missionParticipantRepository->findOneBy([
            'user' => $user,
            'mission' => $mission,
        ]);

        if (null === $participant) {
            return null;
        }

        return $participant->getJob();
    }

    public function hash(string $algo, string $data, bool $binary = false, array $options = [])
    {
        return hash($algo, $data, $binary, $options);
    }
}
