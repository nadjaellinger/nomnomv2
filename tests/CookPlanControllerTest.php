<?php

namespace App\Tests;

use App\Entity\CookRequest;
use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CookPlanControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $user;
    private Recipe $recipe;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->clearDatabase();

        $this->user = (new User())
            ->setUsername('plan-user')
            ->setPassword('not-used')
            ->setIsApproved(true);

        $this->recipe = (new Recipe())
            ->setName('Testrezept')
            ->setDescription('')
            ->setImage('')
            ->setInstructions('')
            ->setUser($this->user);

        $this->entityManager->persist($this->user);
        $this->entityManager->persist($this->recipe);
        $this->entityManager->flush();

        $this->client->loginUser($this->user);
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();
        parent::tearDown();
    }

    public function testCreateDoneAndReopenPlan(): void
    {
        $crawler = $this->client->request('GET', '/plan');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('In die Warteschlange')->form();
        $form['title'] = 'Sonntag mit der Familie';
        $form['recipeIds[0]']->tick();
        $this->client->submit($form);

        self::assertResponseRedirects('/plan');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.plan-flash--ok', 'Kochplan angelegt.');
        self::assertSelectorTextContains('.plan-card-title', 'Sonntag mit der Familie');

        $form = $crawler->selectButton('Abhaken')->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/plan');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.plan-flash--ok', 'Kochplan abgehakt.');
        self::assertSelectorTextContains('.plan-history .plan-card-title', 'Sonntag mit der Familie');

        $form = $crawler->selectButton('Wieder öffnen')->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/plan');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.plan-flash--ok', 'Kochplan wieder geöffnet.');
        self::assertSelectorTextContains('.plan-columns .plan-card-title', 'Sonntag mit der Familie');
        self::assertSelectorTextContains('.plan-history', 'Noch nichts abgehakt.');
    }

    public function testCreateRejectsEmptySelection(): void
    {
        $crawler = $this->client->request('GET', '/plan');

        $form = $crawler->selectButton('In die Warteschlange')->form();
        $form['title'] = '';
        $this->client->submit($form);

        self::assertResponseRedirects('/plan');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.plan-flash--err', 'Bitte gib einen Titel an und wähle mindestens ein Rezept.');
    }

    public function testOtherUsersCannotCompletePlan(): void
    {
        $plan = (new CookRequest())
            ->setTitle('Fremder Plan')
            ->setOwner($this->user)
            ->setPosition(1);
        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $other = (new User())
            ->setUsername('other-user')
            ->setPassword('not-used')
            ->setIsApproved(true);
        $this->entityManager->persist($other);
        $this->entityManager->flush();

        $this->client->loginUser($other);
        $this->client->request('POST', '/plan/'.$plan->getId().'/done');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDoneRequiresCsrfToken(): void
    {
        $plan = (new CookRequest())
            ->setTitle('Ohne Token')
            ->setOwner($this->user)
            ->setPosition(1);
        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->client->request('POST', '/plan/'.$plan->getId().'/done');

        self::assertResponseStatusCodeSame(400);
    }

    private function clearDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\CookRequestEntry e')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CookRequest c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Ingredient i')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Recipe r')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $this->entityManager->clear();
    }
}
