<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Form\JoinTeamForm;
use App\Form\TeamForm;
use App\Repository\TeamRepository;
use App\Service\TeamService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TeamController extends AbstractController
{

    public function __construct(
        private readonly TeamService $teamService,
        private readonly TeamRepository $teamRepository
    )
    {
    }

    #[Route('/brigades', name: 'app_team')]
    public function index(Request $request): Response
    {

        /** @var User $user */
        $user = $this->getUser();

        // Nouvelle brigade
        $newTeam = new Team();
        $newTeamForm = $this->createForm(TeamForm::class, $newTeam);
        $newTeamForm->handleRequest($request);
        if ($newTeamForm->isSubmitted() && $newTeamForm->isValid()) {
            try {
                $this->teamService->createNewTeam($newTeam, $user);
                $this->addFlash('success', 'Votre brigade a bien été créée, utilisez ce code pour inviter vos commis : ' . $newTeam->getCode());
            } catch (Exception) {
                $this->addFlash('danger', 'Une erreur est survenue');
            }

            //Todo: redirection vers la page de la Team
        }


        // Rejoindre une brigade
        $joinTeamForm = $this->createForm(JoinTeamForm::class);
        $joinTeamForm->handleRequest($request);
        if ($joinTeamForm->isSubmitted() && $joinTeamForm->isValid()) {
            $code = $joinTeamForm->get('code')->getData();
            $status = $this->teamService->joinTeamByCode($user, $code);

            match ($status) {
                'not_found'      => $this->addFlash('danger', 'La brigade n\'existe pas.'),
                'already_member' => $this->addFlash('danger', 'Tu y es déjà mon coco'),
                'joined'         => $this->addFlash('success', 'Bienvenue dans la brigade'),
            };

            return $this->redirectToRoute('app_team');
        }


        return $this->render('team/index.html.twig', [
            'hasMoreThanTenTeams' => $this->teamService->hasMoreThanTenTeams($user),
            'newTeamForm' => $newTeamForm->createView(),
            'joinTeamForm' => $joinTeamForm->createView(),
        ]);
    }

    #[Route('/brigades/delete/{id}', name: 'app_team_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {

        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_team_' . $id, $submittedToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $team = $this->teamRepository->find($id);

        if (!$team) {
            throw $this->createNotFoundException('La brigade n\'existe pas.');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$user->getTeams()->contains($team)) {
            throw $this->createAccessDeniedException('Action non autorisée.');
        }

        try {
            $this->teamService->removeUserFromTeam($user, $team);
            $this->addFlash('success', 'Vous avez été retiré de la brigade avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_team');
    }


    #[Route('/brigades/{id}', name: 'app_team_show')]
    public function show(int $id): Response
    {
        $team = $this->teamRepository->find($id);

        if (!$team) {
            throw $this->createNotFoundException('La brigade n\'existe pas.');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$user->getTeams()->contains($team)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette brigade.');
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

}
