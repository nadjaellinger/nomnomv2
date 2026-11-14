<?php

namespace App\Controller;

use App\Entity\CookRequest;
use App\Entity\CookRequestEntry;
use App\Entity\User;
use App\Repository\CookRequestRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plan', name: 'app_plan_')]
class CookPlanController extends AbstractController
{
    public function __construct(
        private CookRequestRepository $cookRequests,
        private RecipeRepository $recipes,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->requireUser();

        return $this->render('plan/index.html.twig', [
            'queue' => $this->cookRequests->findQueueForUser($user),
            'history' => $this->cookRequests->findHistoryForUser($user),
            'recipes' => $this->recipes->findAllRecipes(),
        ]);
    }

    #[Route('/recipes', name: 'recipe_search', methods: ['GET'])]
    public function searchRecipes(Request $request): JsonResponse
    {
        $this->requireUser();

        $query = mb_substr(trim((string) $request->query->get('q', '')), 0, 100);
        $recipes = $this->recipes->searchForPlan($query);
        $hasMore = count($recipes) > 8;

        if ($hasMore) {
            array_pop($recipes);
        }

        return $this->json([
            'recipes' => array_map(static fn ($recipe): array => [
                'id' => $recipe->getId(),
                'name' => $recipe->getName(),
                'owner' => $recipe->getUser()?->getUsername(),
            ], $recipes),
            'hasMore' => $hasMore,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $user = $this->requireUser();
        $this->validateCsrf("plan_create", $request);

        $title = trim((string) $request->request->get("title", ""));
        $recipeIds = array_values(array_unique(array_map("intval", (array) $request->request->all("recipeIds"))));
        $recipeIds = array_values(array_filter($recipeIds, static fn (int $id): bool => $id > 0));

        if ($title === "" || $recipeIds === []) {
            $this->addFlash("error", "Bitte gib einen Titel an und wähle mindestens ein Rezept.");
            return $this->redirectToRoute("app_plan_index");
        }

        $recipesById = [];
        foreach ($this->recipes->findBy(["id" => $recipeIds]) as $recipe) {
            $recipesById[$recipe->getId()] = $recipe;
        }

        $selected = [];
        foreach ($recipeIds as $recipeId) {
            if (isset($recipesById[$recipeId])) {
                $selected[] = $recipesById[$recipeId];
            }
        }

        if ($selected === []) {
            $this->addFlash("error", "Die gewählten Rezepte wurden nicht gefunden.");
            return $this->redirectToRoute("app_plan_index");
        }

        $cookRequest = (new CookRequest())
            ->setTitle($title)
            ->setOwner($user)
            ->setPosition($this->cookRequests->nextPositionForUser($user));

        foreach ($selected as $recipe) {
            $cookRequest->addEntry((new CookRequestEntry())->setRecipe($recipe));
        }

        $this->entityManager->persist($cookRequest);
        $this->entityManager->flush();

        $this->addFlash("success", "Kochplan angelegt.");
        return $this->redirectToRoute("app_plan_index");
    }

    #[Route('/{id}/done', name: 'done', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function done(int $id, Request $request): Response
    {
        $user = $this->requireUser();
        $cookRequest = $this->findOwned($id, $user);
        $this->validateCsrf("plan_done_" . $id, $request);

        if (!$cookRequest->isDone()) {
            $cookRequest->markDone(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->addFlash("success", "Kochplan abgehakt.");
        }

        return $this->redirectToRoute("app_plan_index");
    }

    #[Route('/{id}/reopen', name: 'reopen', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reopen(int $id, Request $request): Response
    {
        $user = $this->requireUser();
        $cookRequest = $this->findOwned($id, $user);
        $this->validateCsrf("plan_reopen_" . $id, $request);

        if ($cookRequest->isDone()) {
            $cookRequest->reopen($this->cookRequests->nextPositionForUser($user));
            $this->entityManager->flush();
            $this->addFlash("success", "Kochplan wieder geöffnet.");
        }

        return $this->redirectToRoute("app_plan_index");
    }

    #[Route('/{id}/move/{direction}', name: 'move', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(int $id, string $direction, Request $request): Response
    {
        $user = $this->requireUser();
        $cookRequest = $this->findOwned($id, $user);
        $this->validateCsrf('plan_move_'.$id, $request);

        if ($cookRequest->isDone()) {
            return $this->redirectToRoute('app_plan_index');
        }

        $queue = $this->cookRequests->findQueueForUser($user);
        $index = null;

        foreach ($queue as $position => $queued) {
            if ($queued->getId() === $cookRequest->getId()) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return $this->redirectToRoute('app_plan_index');
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (!isset($queue[$targetIndex])) {
            return $this->redirectToRoute('app_plan_index');
        }

        foreach ($queue as $position => $queued) {
            $queued->setPosition($position + 1);
        }

        $neighbor = $queue[$targetIndex];
        $cookRequest->setPosition($targetIndex + 1);
        $neighbor->setPosition($index + 1);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reihenfolge angepasst.');

        return $this->redirectToRoute('app_plan_index');
    }

    private function requireUser(): User
    {
        $this->denyAccessUnlessGranted("ROLE_USER");
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function findOwned(int $id, User $user): CookRequest
    {
        $cookRequest = $this->cookRequests->findForUser($id, $user);

        if (!$cookRequest) {
            throw $this->createNotFoundException("Kochplan nicht gefunden.");
        }

        return $cookRequest;
    }

    private function validateCsrf(string $tokenId, Request $request): void
    {
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get("_token", ""))) {
            throw new BadRequestHttpException("Ungültiges CSRF-Token.");
        }
    }
}
