<?php

declare(strict_types=1);

namespace App\Tests\Api\Template;

use App\Entity\Field;
use App\Entity\Template;
use App\Tests\ApiTestCase;
use App\Tests\Factory\FieldFactory;
use App\Tests\Factory\TemplateFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TemplateApiTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_get_templates(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        TemplateFactory::createMany(3, ['owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/templates');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Template::class);
    }

    public function test_get_template(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $template = TemplateFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/templates/' . $template->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Template::class);
        $this->assertJsonContains([
            'id' => $template->getId()
        ]);
    }

    public function test_get_template_fields(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $template = TemplateFactory::createOne(['owner' => $user]);
        FieldFactory::createMany(3, ['template' => $template, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/templates/' . $template->getId() . '/fields');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Field::class);
    }

    public function test_post_template(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/templates', ['json' => [
            'name' => 'Book',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Template::class);
        $this->assertJsonContains([
            'name' => 'Book',
        ]);
    }

    public function test_put_template(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $template = TemplateFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/templates/' . $template->getId(), ['json' => [
            'name' => 'Book',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Template::class);
        $this->assertJsonContains([
            'id' => $template->getId(),
            'name' => 'Book',
        ]);
    }

    public function test_patch_template(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $template = TemplateFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PATCH', '/api/templates/' . $template->getId(), [
            'headers' => ['Content-Type: application/merge-patch+json'],
            'json' => [
                'name' => 'Book',
            ],
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Template::class);
        $this->assertJsonContains([
            'id' => $template->getId(),
            'name' => 'Book',
        ]);
    }

    public function test_delete_template(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $template = TemplateFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('DELETE', '/api/templates/' . $template->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
