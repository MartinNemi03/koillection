<?php

declare(strict_types=1);

namespace App\Tests\Api\Item;

use App\Entity\Collection;
use App\Entity\Datum;
use App\Entity\Item;
use App\Entity\Loan;
use App\Entity\Tag;
use App\Tests\ApiTestCase;
use App\Tests\Factory\CollectionFactory;
use App\Tests\Factory\DatumFactory;
use App\Tests\Factory\ItemFactory;
use App\Tests\Factory\LoanFactory;
use App\Tests\Factory\TagFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ItemApiTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_get_items(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        ItemFactory::createMany(3, ['collection' => $collection, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Item::class);
    }

    public function test_get_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Item::class);
        $this->assertJsonContains([
            'id' => $item->getId()
        ]);
    }

    public function test_get_item_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/collection');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Collection::class);
        $this->assertJsonContains([
            'id' => $collection->getId()
        ]);
    }

    public function test_get_item_data(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);
        DatumFactory::createMany(3, ['item' => $item, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/data');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Datum::class);
    }

    public function test_get_item_loans(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);
        LoanFactory::createMany(3, ['item' => $item, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/loans');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Loan::class);
    }

    public function test_get_item_related_items(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $relatedItems = ItemFactory::createMany(3, ['collection' => $collection, 'owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'relatedItems' => $relatedItems, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/related_items');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Item::class);
    }

    public function test_get_item_tags(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $tags = TagFactory::createMany(3, ['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'tags' => $tags, 'owner' => $user]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/tags');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Tag::class);
    }

    public function test_post_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/items', ['json' => [
            'collection' => '/api/collections/' . $collection->getId(),
            'name' => 'Frieren #1',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Item::class);
        $this->assertJsonContains([
            'collection' => '/api/collections/' . $collection->getId(),
            'name' => 'Frieren #1',
        ]);
    }

    public function test_put_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);
        $item = ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/items/' . $item->getId(), ['json' => [
            'name' => 'Frieren #2',
        ]]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Item::class);
        $this->assertJsonContains([
            'id' => $item->getId(),
            'name' => 'Frieren #2',
        ]);
    }

    public function test_patch_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user]);
        $item = ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('PATCH', '/api/items/' . $item->getId(), [
            'headers' => ['Content-Type: application/merge-patch+json'],
            'json' => [
                'name' => 'Frieren #2',
            ],
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $item->getId(),
            'name' => 'Frieren #2',
        ]);
    }

    public function test_delete_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);

        // Act
        $this->createClientWithCredentials($user)->request('DELETE', '/api/items/' . $item->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function test_post_item_image(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $user]);
        $uploadedFile = $this->createFile('png');

        // Act
        $crawler = $this->createClientWithCredentials($user)->request('POST', '/api/items/' . $item->getId() . '/image', [
            'headers' => ['Content-Type: multipart/form-data'],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Item::class);
        $this->assertNotNull(json_decode($crawler->getContent(), true)['image']);
        $this->assertFileExists(json_decode($crawler->getContent(), true)['image']);
    }
}
