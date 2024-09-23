<?php

declare(strict_types=1);

namespace App\Tests\Api\Collection;

use App\Entity\Collection;
use App\Entity\Datum;
use App\Entity\Item;
use App\Tests\ApiTestCase;
use App\Tests\Factory\CollectionFactory;
use App\Tests\Factory\DatumFactory;
use App\Tests\Factory\ItemFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CollectionApiTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_get_collections(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        CollectionFactory::createMany(3, ['owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/collections');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Collection::class);
    }

    public function test_get_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/collections/' . $collection->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'id' => $collection->getId()
        ]);
    }

    public function test_get_collection_children(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        CollectionFactory::createMany(3, ['parent' => $collection, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/collections/' . $collection->getId() . '/children');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Collection::class);
    }

    public function test_get_collection_parent(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $parentCollection = CollectionFactory::createOne(['owner' => $user]);
        $collection = CollectionFactory::createOne(['parent' => $parentCollection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/collections/' . $collection->getId() . '/parent');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'id' => $parentCollection->getId()
        ]);
    }

    public function test_get_collection_items(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        ItemFactory::createMany(3, ['collection' => $collection, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/collections/' . $collection->getId() . '/items');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Item::class);
    }

    public function test_get_collection_data(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        DatumFactory::createMany(3, ['collection' => $collection, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/collections/' . $collection->getId() . '/data');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Datum::class);
    }

    public function test_post_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/collections', ['json' => [
            'title' => 'Frieren',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'title' => 'Frieren',
        ]);
    }

    public function test_put_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/collections/' . $collection->getId(), ['json' => [
            'title' => 'Berserk',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'id' => $collection->getId(),
            'title' => 'Berserk',
        ]);
    }

    public function test_cant_assign_collection_as_its_own_parent(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/collections/' . $collection->getId(), ['json' => [
            'parent' => '/api/collections/' . $collection->getId(),
        ]]);

        // Assert
        $this->assertResponseIsUnprocessable();
    }

    public function test_cant_assign_child_as_parent_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);
        $child = CollectionFactory::createOne(['parent' => $collection, 'title' => 'Ex-libris', 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/collections/' . $collection->getId(), ['json' => [
            'parent' => '/api/collections/' . $child->getId(),
        ]]);

        // Assert
        $this->assertResponseIsUnprocessable();
    }

    public function test_patch_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PATCH', '/api/collections/' . $collection->getId(), [
            'headers' => ['Content-Type: application/merge-patch+json'],
            'json' => [
                'title' => 'Berserk',
            ],
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'id' => $collection->getId(),
            'title' => 'Berserk',
        ]);
    }

    public function test_delete_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('DELETE', '/api/collections/' . $collection->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function test_post_collection_image(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $uploadedFile = $this->createFile('png');

        // Act
        $crawler = $this->createClientWithCredentials($user)->request('POST', '/api/collections/' . $collection->getId() . '/image', [
            'headers' => ['Content-Type: multipart/form-data'],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertNotNull(json_decode($crawler->getContent(), true)['image']);
        $this->assertFileExists(json_decode($crawler->getContent(), true)['image']);
    }

    public function test_delete_collection_image(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $uploadedFile = $this->createFile('png');
        $imagePath = $uploadedFile->getRealPath();
        $album = CollectionFactory::createOne(['owner' => $user, 'image' => $imagePath]);

        // Act
        $crawler = $this->createClientWithCredentials($user)->request('PUT', '/api/collections/' . $album->getId(), ['json' => [
            'deleteImage' => true,
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertNull(json_decode($crawler->getContent(), true)['image']);
        $this->assertFileDoesNotExist($imagePath);
    }
}
