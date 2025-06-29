<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class TeamService
{

    const PREFIX_CODE = 'B-';

    public function __construct(
        private EntityManagerInterface $manager,
        private TeamRepository $teamRepository
    )
    {
    }

    public function createNewTeam(Team $team, User $user): void
    {
        $team->setCode($this->generateUniqueTeamCode())
            ->addContributor($user);
        $this->manager->persist($team);
        $this->manager->flush();
    }

    public function generateUniqueTeamCode(): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 100; // Limiter le nombre de tentatives
        $attempts = 0;

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }

            $attempts++;
            if ($this->isTeamCodeUnique($code)) {
                return self::PREFIX_CODE . $code;
            }
        } while ($attempts < $maxAttempts);

        throw new \RuntimeException('Unable to generate a unique team code after ' . $maxAttempts . ' attempts.');
    }

    private function isTeamCodeUnique(string $code): bool
    {
        return $this->teamRepository->findOneBy(['code' => $code]) === null;
    }

    public function hasMoreThanTenTeams(User $user): bool
    {
        $teamCount = $user->getTeams()->count();

        // Retourner true si l'utilisateur a plus de 10 équipes, sinon false
        return $teamCount >= 10;
    }

    public function removeUserFromTeam(User $user, Team $team)
    {
        $team->removeContributor($user);
        $this->manager->persist($team);
        $this->manager->flush();
    }

    public function joinTeamByCode(User $user, string $code): string
    {
        $team = $this->teamRepository->findOneBy(['code' => $code]);

        if (!$team) {
            return 'not_found';
        }

        if ($user->getTeams()->contains($team)) {
            return 'already_member';
        }

        $team->addContributor($user); // ou addUser selon ton entité
        $this->manager->persist($team);
        $this->manager->flush();

        return 'joined';
    }

}

