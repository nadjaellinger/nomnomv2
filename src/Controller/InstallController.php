<?php
// src/Controller/InstallController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class InstallController extends AbstractController
{
    #[Route('/__install', name: 'app_install', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        #[Autowire('%env(APP_INSTALL_TOKEN)%')] string $installToken,
    ): Response {
        $flagFile = $projectDir.'/var/installed.flag';

        // Already installed? Disappear.
        if (is_file($flagFile)) {
            return new Response('Already installed. Flag file exists', 400);
        }

        // Users exist? Disappear (and optionally lock permanently).
        if ($users->count([]) > 0) {
            @file_put_contents($flagFile, 'users-exist '.date('c'));
            return new Response('Already installed. Users exist', 400);
        }

        // Token check (GET or POST)
        $token = $request->query->get('token') ?? $request->request->get('token');
        if (!$token) {
            return new Response('Missing install token', 400);
        }

        if ($token !== $installToken) {
            return new Response('Invalid install token', 403);
        }

        if ($request->isMethod('GET')) {
            $html = <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Install</title></head>
<body>
  <h1>Create initial admin</h1>
  <form method="post">
    <input type="hidden" name="token" value="{$this->escapeHtml($token)}">
    <label>Email <input name="email" type="email" required></label><br><br>
    <label>Password <input name="password" type="password" required></label><br><br>
    <button type="submit">Create admin</button>
  </form>
</body>
</html>
HTML;

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');

        if ($username === '' || $password === '') {
            return new Response('Missing username/password', 400);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        @file_put_contents($flagFile, 'ok '.date('c'));

        return new Response(
            "Initial admin created. Now REMOVE/disable the /__install route.\n",
            201,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    private function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
