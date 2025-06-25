<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/nouveau-ami', name: 'app_add_friend', methods: ['POST'])]
    public function index(Request $request, UserService $userService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->request->has('friend_code')) {
            $friendCode = $request->request->get('friend_code');
            $result = $userService->addFriend($user, $friendCode);

            $this->addFlash($result['type'], $result['message']);
        }

        return $this->redirectToRoute('app_home');
    }
}
