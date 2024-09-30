<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->json([
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Получаем JSON-данные из запроса
        $data = json_decode($request->getContent(), true);

        // Проверка на наличие данных
        if (!isset($data['username'], $data['email'], $data['password'])) {
            return new Response('Invalid data', Response::HTTP_BAD_REQUEST);
        }

        // Создаем нового пользователя
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->json([
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['PUT'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Получаем JSON-данные из запроса
        $data = json_decode($request->getContent(), true);

        // Обновляем данные пользователя
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        // Обновляем данные пользователя
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $entityManager->flush();

        return $this->json(['message' => 'User updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'User deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Получаем JSON-данные из запроса
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new Response('Invalid data', Response::HTTP_BAD_REQUEST);
        }

        // Ищем пользователя по email
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return new Response('User not found', Response::HTTP_UNAUTHORIZED);
        }

        // Проверяем пароль
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new Response('Invalid password', Response::HTTP_UNAUTHORIZED);
        }

        // Аутентификация прошла успешно
        return new Response('Successfully logged in', Response::HTTP_OK);
    }
}
